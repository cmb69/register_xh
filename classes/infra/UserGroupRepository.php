<?php

/**
 * Copyright (c) 2021-2023-2023-2023-2023-2023-2023 Christoph M. Becker
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

    public function findByGroupname(string $groupname): ?UserGroup
    {
        $lock = $this->dbService->lock(false);
        $groups = $this->dbService->readGroups();
        $this->dbService->unlock($lock);
        foreach ($groups as $group) {
            if ($group->getGroupname() == $groupname) {
                return $group;
            }
        }
        return null;
    }
}
