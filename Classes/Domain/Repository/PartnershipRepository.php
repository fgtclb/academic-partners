<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Repository;

use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<Partnership>
 */
class PartnershipRepository extends Repository
{
    private const PARTNERSHIP_TABLE = 'tx_academicpartners_domain_model_partnership';

    /**
     * Finds the partnerships attached to the given page through their `page` column,
     * each partnership exactly once per language (ACE-430).
     *
     * The callers pass the uid of the default language page, in every language. The
     * translations a translated partner page holds point at the page translation and
     * never match, while a translation attached to the default page does: localizing a
     * role created such copies until ACE-874, and localizing a partnership on its own in
     * the List module still does. Matching the `page` column alone therefore returned
     * a translation next to its default record.
     *
     * The page contacts had the same query and are read the way this method now reads,
     * see `ContactRepository::findByPid()` of `academic_contacts4pages` (ACE-484). A
     * pre-query resolves one uid per partnership and language, then the Extbase query
     * fetches exactly those rows with `respectSysLanguage` lifted and the overlay pinned
     * to `OVERLAYS_MIXED`. That overlay maps a translation onto the identity of its
     * default record and passes an untranslated default record through, the same on
     * TYPO3 v13 and v14. The pinned overlay replaces the overlay type of the site
     * language, so which partnerships render does not depend on its fallback type, while
     * its fallback chain still decides which language a default record is overlaid with.
     * With `respectSysLanguage` lifted, the data mapper resolves the role in the language
     * of the partnership, so a partnership that renders as its translation names its role
     * in that language.
     *
     * @return QueryResult<Partnership>
     */
    public function findByPid(int $pid): QueryResult
    {
        $query = $this->createQuery();

        $currentLanguageAspect = $query->getQuerySettings()->getLanguageAspect();
        $partnershipUids = $this->resolvePartnershipUidsForLanguage($pid, $currentLanguageAspect->getContentId());

        $changedLanguageAspect = new LanguageAspect(
            $currentLanguageAspect->getId(),
            $currentLanguageAspect->getContentId(),
            LanguageAspect::OVERLAYS_MIXED,
            $currentLanguageAspect->getFallbackChain()
        );
        $query->getQuerySettings()->setLanguageAspect($changedLanguageAspect);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->getQuerySettings()->setRespectStoragePage(false);

        if ($partnershipUids === []) {
            // Extbase `in()` rejects an empty value list (exception 1484828466), so an
            // impossible uid keeps the query valid and the result empty.
            $query->matching($query->equals('uid', 0));
        } else {
            $query->matching($query->in('uid', $partnershipUids));
        }

        // The table is manually sortable (TCA ctrl `sortby`), so the order the editor
        // arranged in the backend is the one the frontend has to reproduce. Extbase does
        // not read `sortby`, and without an ORDER BY the row order belongs to the DBMS -
        // PostgreSQL returned this very query in two different orders for two renders of
        // the same data (ACE-491). `uid` settles ties deterministically. `sorting` is no
        // property of the model; the data mapper falls back to the column of that name.
        // Both are the columns of the row the query fetches, which in a translated
        // language is the translation where one matched the page.
        $query->setOrderings([
            'sorting' => QueryInterface::ORDER_ASCENDING,
            'uid' => QueryInterface::ORDER_ASCENDING,
        ]);

        return $query->execute();
    }

    /**
     * Resolves which rows stand for the partnerships of the page in the given language,
     * exactly one row per partnership, with the rule of the page contacts:
     *
     * - records in language "all" (-1) always match,
     * - in the default language only default language records match,
     * - in a translated language the records of that language match, translations and
     *   records that exist only in that language alike, and a default language record
     *   matches when no translation of it in that language is attached to the page.
     *
     * A default language record whose translation is attached to the page is selected
     * through that translation, so a copy left by a role localization before ACE-874
     * renders once and in the position its own `sorting` gives it. Should a record have
     * several such translations in one language, the lowest uid wins. Which translation
     * the object is then mapped from is up to the overlay of the core, which looks it
     * up by the default record without an order.
     *
     * The restrictions are what the Extbase frontend query applies to this table:
     * `deleted`, the workspace of the current context and `hidden`, its only enable
     * column, unless the context includes hidden content, as a frontend preview of
     * hidden records does.
     *
     * @return list<int>
     */
    private function resolvePartnershipUidsForLanguage(int $pid, int $languageId): array
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $workspaceId = (int)$context->getPropertyFromAspect('workspace', 'id', 0);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable(self::PARTNERSHIP_TABLE);
        $restrictions = $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $workspaceId));
        if (!$context->getPropertyFromAspect('visibility', 'includeHiddenContent', false)) {
            $restrictions->add(GeneralUtility::makeInstance(HiddenRestriction::class));
        }
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent')
            ->from(self::PARTNERSHIP_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'page',
                    $queryBuilder->createNamedParameter($pid, Connection::PARAM_INT)
                ),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $translatedDefaultUids = [];
        if ($languageId > 0) {
            foreach ($rows as $row) {
                if ((int)$row['sys_language_uid'] === $languageId && (int)$row['l10n_parent'] > 0) {
                    // The first translation per default record wins, the rows are ordered by uid.
                    $translatedDefaultUids[(int)$row['l10n_parent']] ??= (int)$row['uid'];
                }
            }
        }

        $uids = [];
        foreach ($rows as $row) {
            $uid = (int)$row['uid'];
            $rowLanguageId = (int)$row['sys_language_uid'];
            $rowTranslationParentUid = (int)$row['l10n_parent'];
            $keep = $rowLanguageId === -1
                || ($rowLanguageId === $languageId
                    && ($rowTranslationParentUid === 0 || ($translatedDefaultUids[$rowTranslationParentUid] ?? 0) === $uid))
                || ($rowLanguageId === 0 && $languageId > 0 && !isset($translatedDefaultUids[$uid]));
            if ($keep) {
                $uids[] = $uid;
            }
        }
        return $uids;
    }
}
