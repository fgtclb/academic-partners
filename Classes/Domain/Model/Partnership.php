<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Partnership extends AbstractEntity
{
    protected int $page = 0;
    protected ?Partner $partner = null;
    protected ?Role $role = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getPartner(): ?Partner
    {
        return $this->partner;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function getLabel(): string
    {
        $labelParts = [];
        if ($this->role) {
            $labelParts[] = $this->role->getName();
        }
        if ($this->partner) {
            $labelParts[] = $this->partner->getTitle();
        }
        return implode(' - ', $labelParts);
    }
}
