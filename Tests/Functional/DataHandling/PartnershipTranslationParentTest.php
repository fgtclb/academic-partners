<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\DataHandling;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A partnership and its translation written in one DataHandler run, the way an
 * import or a seed writes them: the translation names its original by the "NEW"
 * placeholder of the same data map.
 *
 * The table declared no "l10n_parent" and no "l10n_source" column. TYPO3 v12
 * then adds both as "passthrough", and DataHandler::processRemapStack() writes a
 * passthrough placeholder with the value the remap entry before it computed.
 * Here that is the uid of the role whose translation was written earlier in the
 * same run (ACE-854). TYPO3 v13 creates the parent pointer as a select itself,
 * which is what the TCA declares now on both versions.
 *
 * "l10n_source" stays a passthrough column. It comes out right because the data
 * map lists it directly after "l10n_parent" with the same placeholder, so it
 * takes over the value the select entry of "l10n_parent" just resolved.
 */
final class PartnershipTranslationParentTest extends AbstractAcademicPartnersTestCase
{
    private const TABLE_ROLE = 'tx_academicpartners_domain_model_role';
    private const TABLE_PARTNERSHIP = 'tx_academicpartners_domain_model_partnership';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/TranslationParent/partnerPagesAndRoles.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        parent::tearDown();
    }

    #[Test]
    public function aTranslationWrittenWithItsOriginalPointsAtItsOriginal(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                // The placeholders carry no underscore: the remap stack reads an
                // underscore as "<table>_<placeholder>".
                //
                // A record and its translation, written first. The translation
                // pointer of the role is resolved by a remap entry with a function,
                // and the uid of the role is what a passthrough pointer written
                // after it received.
                self::TABLE_ROLE => [
                    'NEWrole' => ['pid' => 100, 'name' => 'Partner'],
                    'NEWroleDe' => [
                        'pid' => 100,
                        'name' => 'Partner (de)',
                        'sys_language_uid' => 1,
                        'l10n_parent' => 'NEWrole',
                        'l10n_source' => 'NEWrole',
                    ],
                ],
                self::TABLE_PARTNERSHIP => [
                    'NEWpartnership' => ['pid' => 10, 'page' => 10, 'partner' => 11, 'role' => 2],
                    'NEWpartnershipDe' => [
                        'pid' => 10,
                        'page' => 10,
                        'partner' => 11,
                        'role' => 2,
                        'sys_language_uid' => 1,
                        'l10n_parent' => 'NEWpartnership',
                        'l10n_source' => 'NEWpartnership',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $original = (int)$dataHandler->substNEWwithIDs['NEWpartnership'];
        $translation = (int)$dataHandler->substNEWwithIDs['NEWpartnershipDe'];
        $this->assertNotSame(
            (int)$dataHandler->substNEWwithIDs['NEWrole'],
            $original,
            'The fixture has to give the partnership another uid than the role, or a wrong pointer would pass.'
        );

        $row = $this->getConnectionPool()->getConnectionForTable(self::TABLE_PARTNERSHIP)
            ->select(['l10n_parent', 'l10n_source', 'role', 'deleted'], self::TABLE_PARTNERSHIP, ['uid' => $translation])
            ->fetchAssociative();
        $this->assertSame(
            ['l10n_parent' => $original, 'l10n_source' => $original, 'role' => 2, 'deleted' => 0],
            array_map('intval', (array)$row)
        );
    }
}
