<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CropVariantsAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The crop variants an editor gets in the image cropper for the media of a partner page.
 *
 * The media of a partner page is the logo of the partner, which the partner list and the
 * partnerships render in its own ratio. The program and project pages configure named
 * variants with fixed ratios; the partner page configures none and keeps the free crop
 * TYPO3 offers without configuration.
 */
final class PageMediaCropVariantsTest extends AbstractAcademicPartnersTestCase
{
    use CropVariantsAssertionTrait;

    private const FIXTURES = __DIR__ . '/Fixtures/PageMediaCropVariants/';

    protected function setUp(): void
    {
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'landscape.jpg', $folder . '/landscape.jpg');
        $this->importCSVDataSet(self::FIXTURES . 'records.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function theMediaOfAPartnerPageOffersOnlyTheFreeDefaultCrop(): void
    {
        $variants = $this->offeredCropVariants('pages', 10, 'media');

        $this->assertSame(['default'], array_keys($variants));
        $this->assertSame('NaN', $variants['default']['selectedRatio']);
        $this->assertSame($this->cropVariantsWithoutConfiguration('pages', 10, 'media'), $variants);
    }
}
