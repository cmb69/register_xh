<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;

class UserGroupRepositoryTest extends TestCase
{
    public function testFindsExistingGroupByName(): void
    {
        $dbService = $this->makeDbService();
        $group = new UserGroup("guest", "");
        $dbService->writeGroups([$group]);
        $sut = new UserGroupRepository($dbService);
        $this->assertEquals($group, $sut->findByGroupname("guest"));
    }

    public function testDoesNotFindNonExistingGroupByName(): void
    {
        $dbService = $this->makeDbService();
        $group = new UserGroup("guest", "");
        $dbService->writeGroups([$group]);
        $sut = new UserGroupRepository($dbService);
        $this->assertNull($sut->findByGroupname("mary"));
    }

    private function makeDbService(): DbService
    {
        return new class extends DbService {
            /** array<UserGroup> */
            private $groups = [];
            public function __construct()
            {
            }
            public function lock($mode)
            {
                return;
            }
            /** @return array<UserGroup> */
            public function readGroups(): array
            {
                return $this->groups;
            }
            /** @param array<UserGroup> $array */
            public function writeGroups(array $array): bool
            {
                $this->groups = $array;
                return true;
            }
        };
    }
}
