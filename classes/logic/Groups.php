<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use Register\Value\UserGroup;

class Groups
{
    /**
     * @param list<UserGroup> $groups
     * @return list<UserGroup>
     */
    public static function sortByName(array $groups): array
    {
        usort($groups, function (UserGroup $a, UserGroup $b) {
            return strnatcasecmp($a->getGroupname(), $b->getGroupname());
        });
        return $groups;
    }

    /** @param list<UserGroup> $groups */
    public static function findByGroupname(string $groupname, array $groups): ?UserGroup
    {
        return array_reduce($groups, function (?UserGroup $carry, UserGroup $group) use ($groupname) {
            return $group->getGroupname() === $groupname ? $group : $carry;
        });
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<array{string}>
     */
    public static function validate(UserGroup $group, array $groups, bool $new): array
    {
        assert($new || self::findByGroupname($group->getGroupname(), $groups));
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_-]+$/u', $group->getGroupname())) {
            $errors[] = ["err_group_illegal"];
        }
        if ($new && self::findByGroupname($group->getGroupname(), $groups)) {
            $errors[] = ["err_groupname_exists"];
        }
        return $errors;
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<UserGroup>
     */
    public static function add(UserGroup $group, array $groups): array
    {
        $groups[] = $group;
        return $groups;
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<UserGroup>
     */
    public static function update(UserGroup $group, array $groups): array
    {
        return array_map(function (UserGroup $aGroup) use ($group) {
            return $aGroup->getGroupname() === $group->getGroupname() ? $group : $aGroup;
        }, $groups);
    }

    /**
     * @param list<UserGroup> $groups
     * @return list<UserGroup>
     */
    public static function delete(UserGroup $group, array $groups): array
    {
        return array_filter($groups, function (UserGroup $aGroup) use ($group) {
            return $aGroup->getGroupname() !== $group->getGroupname();
        });
    }
}
