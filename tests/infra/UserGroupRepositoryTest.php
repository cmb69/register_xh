<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Value\UserGroup;

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
        vfsStream::setup("root");
        return new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
    }
}
