<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Partnership extends AbstractEntity
{
    protected int $page = 0;

    protected ?Partner $partner = null;

    protected ?Role $role = null;

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
