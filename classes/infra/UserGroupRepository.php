<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\UserGroup;

class UserGroupRepository
{
    /**
     * @var DbService
     */
    private $dbService;

    public function __construct(DbService $dbService)
    {
        $this->dbService = $dbService;
    }

    /** @return list<UserGroup> */
    public function all(): array
    {
        $lock = $this->dbService->lock(false);
        $groups = $this->dbService->readGroups();
        $this->dbService->unlock($lock);
        usort($groups, function (UserGroup $a, UserGroup $b) {
            return strnatcasecmp($a->getGroupname(), $b->getGroupname());
        });
        return $groups;
    }

    public function findByGroupname(string $groupname): ?UserGroup
    {
        $lock = $this->dbService->lock(false);
        $groups = $this->dbService->readGroups();
        $this->dbService->unlock($lock);
        return array_reduce($groups, function (?UserGroup $carry, UserGroup $group) use ($groupname) {
            return $group->getGroupname() === $groupname ? $group : $carry;
        });
    }

    public function save(UserGroup $group): bool
    {
        $lock = $this->dbService->lock(true);
        $groups = $this->dbService->readGroups();
        $groups = array_combine(array_map(function (UserGroup $group) {
            return $group->getGroupname();
        }, $groups), $groups);
        assert($groups !== false);
        $groups[$group->getGroupname()] = $group;
        $groups = array_values($groups);
        $res = $this->dbService->writeGroups($groups);
        $this->dbService->unlock($lock);
        return $res;
    }

    public function delete(UserGroup $group): bool
    {
        $lock = $this->dbService->lock(true);
        $groups = $this->dbService->readGroups();
        $groups = array_values(array_filter($groups, function (UserGroup $aGroup) use ($group) {
            return $aGroup->getGroupname() !== $group->getGroupname();
        }));
        $res = $this->dbService->writeGroups($groups);
        $this->dbService->unlock($lock);
        return $res;
    }
}
