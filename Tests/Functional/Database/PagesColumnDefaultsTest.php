<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Database;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The extension adds columns to the shared `pages` table. Every writer that does
 * not name them - the backend page creation being the obvious one - relies on
 * them being optional at database level.
 */
final class PagesColumnDefaultsTest extends AbstractAcademicPartnersTestCase
{
    #[Test]
    public function pagesRowCanBeInsertedWithoutTheAddedColumns(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $connection->insert(
            'pages',
            [
                'pid' => 0,
                'title' => 'Inserted without the columns of this extension',
                'doktype' => 1,
            ]
        );

        $this->assertSame(1, $this->countPages());
    }

    #[Test]
    public function pageCanBeCreatedThroughDataHandler(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesColumnDefaults/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PagesColumnDefaults/pages.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                'pages' => [
                    'NEW1' => [
                        'pid' => 1,
                        'title' => 'Created by the data handler',
                        'doktype' => 1,
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(2, $this->countPages());
    }

    private function countPages(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->count('uid')
            ->from('pages')
            ->executeQuery()
            ->fetchOne();
    }
}
