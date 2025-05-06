<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Role extends AbstractEntity
{
    protected string $name = '';
    protected string $description = '';

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
