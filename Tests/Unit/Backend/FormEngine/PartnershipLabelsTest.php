<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Backend\FormEngine;

use FGTCLB\AcademicPartners\Backend\FormEngine\PartnershipLabels;
use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use FGTCLB\AcademicPartners\Domain\Repository\PartnershipRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The `label_userFunc` of the partnership table. The backend calls it for every record
 * title it renders, also for a record that is deleted or missing, for example from the
 * open documents or the history, and hands it no row or a row without a uid then. A
 * new record arrives with its `NEW…` placeholder as uid, which PostgreSQL rejects in
 * the integer comparison of a lookup.
 */
final class PartnershipLabelsTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @param array<string, mixed> $parameters
     */
    #[Test]
    #[DataProvider('parametersWithoutARecordUidProvider')]
    public function parametersWithoutARecordUidLeaveTheTitleUntouched(array $parameters): void
    {
        $repository = $this->createMock(PartnershipRepository::class);
        $repository->expects($this->never())->method('findByUid');
        GeneralUtility::setSingletonInstance(PartnershipRepository::class, $repository);

        $expected = $parameters;
        (new PartnershipLabels())->getTitle($parameters);

        $this->assertSame($expected, $parameters);
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function parametersWithoutARecordUidProvider(): array
    {
        return [
            'no row key' => [['table' => 'tx_academicpartners_domain_model_partnership', 'title' => '']],
            'row is null' => [['table' => 'tx_academicpartners_domain_model_partnership', 'row' => null, 'title' => '']],
            'row is empty' => [['table' => 'tx_academicpartners_domain_model_partnership', 'row' => [], 'title' => '']],
            'row without uid' => [['table' => 'tx_academicpartners_domain_model_partnership', 'row' => ['pid' => 1], 'title' => '']],
            'row of a new record' => [['table' => 'tx_academicpartners_domain_model_partnership', 'row' => ['uid' => 'NEW64f1a2b3c4d5e', 'pid' => 1], 'title' => '']],
            'row with uid 0' => [['table' => 'tx_academicpartners_domain_model_partnership', 'row' => ['uid' => 0, 'pid' => 1], 'title' => '']],
        ];
    }

    #[Test]
    public function aRecordUidIsLabelledByItsModel(): void
    {
        $record = $this->createMock(Partnership::class);
        $record->method('getLabel')->willReturn('Label of record 5');
        $repository = $this->createMock(PartnershipRepository::class);
        $repository->expects($this->once())->method('findByUid')->with(5)->willReturn($record);
        GeneralUtility::setSingletonInstance(PartnershipRepository::class, $repository);

        $parameters = ['table' => 'tx_academicpartners_domain_model_partnership', 'row' => ['uid' => 5, 'pid' => 1], 'title' => ''];
        (new PartnershipLabels())->getTitle($parameters);

        $this->assertSame('Label of record 5', $parameters['title']);
    }
}
