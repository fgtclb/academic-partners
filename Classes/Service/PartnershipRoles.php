<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Service;

use FGTCLB\AcademicPartners\Domain\Model\Partnership;
use FGTCLB\AcademicPartners\Domain\Model\Role;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;

/**
 * Collects the roles the partnership list and teaser group their partnerships by, and
 * the partnerships that have no role and therefore belong to none of the groups.
 *
 * In a translated language a partnership that renders as its translation carries its
 * role in that language, and one that renders as its default language record carries
 * the role of the default language (ACE-430). Extbase holds the two as different
 * objects with the same uid. The roles are therefore keyed by uid, the templates
 * compare the uid, and the role object in a language other than the default one wins,
 * so the heading of a group names the role in the visitor's language whenever one
 * partnership of the group carries it.
 */
final readonly class PartnershipRoles
{
    /**
     * @param iterable<Partnership> $partnerships
     * @return array<int, Role> in the order the partnerships first name them
     */
    public function collect(iterable $partnerships): array
    {
        $roles = [];
        foreach ($partnerships as $partnership) {
            $role = $partnership->getRole();
            if ($role === null) {
                continue;
            }
            $uid = (int)$role->getUid();
            if (!isset($roles[$uid]) || $this->languageOf($roles[$uid]) === 0) {
                $roles[$uid] = $role;
            }
        }
        return $roles;
    }

    /**
     * The partnerships without a role. The templates render them after the role groups,
     * which only render the partnerships of a role (ACE-917). A hidden or deleted role
     * does not resolve, so its partnerships are among them.
     *
     * @param iterable<Partnership> $partnerships
     * @return list<Partnership> in the order of the given partnerships
     */
    public function withoutRole(iterable $partnerships): array
    {
        $withoutRole = [];
        foreach ($partnerships as $partnership) {
            if ($partnership->getRole() === null) {
                $withoutRole[] = $partnership;
            }
        }
        return $withoutRole;
    }

    private function languageOf(Role $role): int
    {
        return (int)$role->_getProperty(AbstractDomainObject::PROPERTY_LANGUAGE_UID);
    }
}
