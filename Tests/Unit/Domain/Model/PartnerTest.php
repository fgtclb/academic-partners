<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Unit\Domain\Model;

use FGTCLB\AcademicPartners\Domain\Model\Partner;
use FGTCLB\CategoryTypes\Collection\CategoryCollection;
use FGTCLB\CategoryTypes\Collection\GetCategoryCollectionInterface;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Country\CountryProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Three parts of `Partner` do more than hold a value: the country lookup, the lazily
 * fetched category collection and the object storage set up in `initializeObject()`.
 * The plain accessors are deliberately not covered - they would assert that PHP works.
 *
 * Both non-accessor methods reach for a collaborator through `GeneralUtility::makeInstance()`,
 * which is why they are pinned here: the instance stack of the testing framework fails the
 * test when a lookup happens that should not have happened, and PHP fails it with an
 * `ArgumentCountError` when one happens that the test did not prepare for.
 */
final class PartnerTest extends UnitTestCase
{
    /**
     * The label is a `LLL:` reference, not a translated string - the Fluid template runs it
     * through `f:translate`. Asserting the reference is what keeps the two ends in sync.
     */
    #[Test]
    public function anIsoCodeIsResolvedToTheLocalizedCountryLabel(): void
    {
        $subject = new Partner();
        $subject->_setProperty('addressCountry', 'DE');
        GeneralUtility::addInstance(CountryProvider::class, $this->countryProvider());

        $this->assertSame(
            'LLL:EXT:core/Resources/Private/Language/Iso/countries.xlf:DE.name',
            $subject->getAddressCountryLocalizedNameLabel(),
        );
    }

    /**
     * `CountryProvider::getByIsoCode()` accepts the alpha-3 code as well, and the TCA field
     * is a free text field on older records - so both spellings have to arrive at the same
     * label rather than one of them falling through to the empty string.
     */
    #[Test]
    public function theAlphaThreeCodeResolvesToTheSameLabel(): void
    {
        $subject = new Partner();
        $subject->_setProperty('addressCountry', 'DEU');
        GeneralUtility::addInstance(CountryProvider::class, $this->countryProvider());

        $this->assertSame(
            'LLL:EXT:core/Resources/Private/Language/Iso/countries.xlf:DE.name',
            $subject->getAddressCountryLocalizedNameLabel(),
        );
    }

    /**
     * A code that no longer exists must not take the detail view down with a call on
     * `null`. It renders without a country instead.
     */
    #[Test]
    #[DataProvider('unresolvableCountryCodes')]
    public function anUnresolvableCodeYieldsAnEmptyLabel(string $code): void
    {
        $subject = new Partner();
        $subject->_setProperty('addressCountry', $code);
        GeneralUtility::addInstance(CountryProvider::class, $this->countryProvider());

        $this->assertSame('', $subject->getAddressCountryLocalizedNameLabel());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unresolvableCountryCodes(): \Generator
    {
        yield 'a withdrawn iso code' => ['XX'];
        yield 'a country name instead of a code' => ['Germany'];
        yield 'a numeric code' => ['276'];
    }

    /**
     * The empty default of the TCA field is the common case - every partner record without
     * an address goes through here - so it must not cost a `CountryProvider` instantiation.
     *
     * The prepared instance is still on the stack afterwards, which is what proves the
     * lookup was skipped; it is consumed explicitly so the tearDown integrity check of the
     * testing framework stays satisfied.
     */
    #[Test]
    public function noCountryIsNotLookedUpAtAll(): void
    {
        $subject = new Partner();
        $countryProvider = $this->countryProvider();
        GeneralUtility::addInstance(CountryProvider::class, $countryProvider);

        $this->assertSame('', $subject->getAddressCountryLocalizedNameLabel());
        $this->assertSame($countryProvider, GeneralUtility::makeInstance(CountryProvider::class));
    }

    /**
     * The categories of a partner are fetched by page uid, from the `partners` category
     * group. They are rendered repeatedly in a list view, once per partner, so the query
     * must happen once per object and not once per template access.
     */
    #[Test]
    public function theCategoriesAreFetchedOnceAndThenReused(): void
    {
        $subject = new Partner();
        $subject->_setProperty('uid', 42);
        $categoryCollection = new CategoryCollection();

        $categoryRepository = $this->createMock(CategoryRepository::class);
        $categoryRepository->expects($this->once())
            ->method('findByGroupAndPageId')
            ->with('partners', 42)
            ->willReturn($categoryCollection);
        GeneralUtility::addInstance(CategoryRepository::class, $categoryRepository);

        $this->assertSame($categoryCollection, $subject->getAttributes());
        $this->assertSame($categoryCollection, $subject->getAttributes());
    }

    /**
     * An already resolved collection is handed out untouched. Only one repository instance
     * is prepared above, so a second fetch would fail loudly rather than quietly costing a
     * query - and here none is prepared at all.
     */
    #[Test]
    public function anAlreadyResolvedCollectionIsNotFetchedAgain(): void
    {
        $subject = new Partner();
        $categoryCollection = new CategoryCollection();
        $subject->_setProperty('attributes', $categoryCollection);

        $this->assertSame($categoryCollection, $subject->getAttributes());
    }

    /**
     * `getCategoryCollection()` is the `GetCategoryCollectionInterface` end of the same
     * data - `EXT:category_types` calls it, the Fluid templates call `getAttributes()`.
     * The two must not drift apart into two separate lookups.
     */
    #[Test]
    public function theInterfaceMethodReturnsTheSameCollection(): void
    {
        $subject = new Partner();
        $categoryCollection = new CategoryCollection();
        $subject->_setProperty('attributes', $categoryCollection);

        $this->assertInstanceOf(GetCategoryCollectionInterface::class, $subject);
        $this->assertSame($categoryCollection, $subject->getCategoryCollection());
    }

    /**
     * `$media` is a typed property without a default, so reading it before
     * `initializeObject()` has run is an `Error`, not a `null`. Extbase calls the method
     * itself when it reconstitutes an object; the constructor is what covers every other
     * caller, including `new Partner()` in a test or a command.
     */
    #[Test]
    public function theMediaStorageExistsWithoutExtbaseHavingInitializedIt(): void
    {
        $subject = new Partner();

        $this->assertInstanceOf(ObjectStorage::class, $subject->getMedia());
        $this->assertCount(0, $subject->getMedia());
    }

    private function countryProvider(): CountryProvider
    {
        return new CountryProvider(new NoopEventDispatcher());
    }
}
