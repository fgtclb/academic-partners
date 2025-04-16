<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Role extends AbstractEntity
{
    protected string $name = '';

    protected string $description = '';

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
