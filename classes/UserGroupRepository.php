<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

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

    /**
     * @return UserGroup|null
     */
    public function findByGroupname(string $groupname)
    {
        $this->dbService->lock(LOCK_SH);
        $groups = $this->dbService->readGroups();
        $this->dbService->lock(LOCK_UN);
        foreach ($groups as $group) {
            if ($group->getGroupname() == $groupname) {
                return $group;
            }
        }
        return null;
    }
}
