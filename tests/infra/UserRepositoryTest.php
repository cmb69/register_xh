<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Value\User;

class UserRepositoryTest extends TestCase
{
    public function testFindsExistingUserByName(): void
    {
        $dbService = $this->makeDbService();
        $dbService->writeUsers([$this->john(), $this->jane()]);
        $sut = new UserRepository($dbService);
        $this->assertEquals($this->john(), $sut->findByUsername("john"));
    }

    public function testDoesNotFindNonExistingUserByName(): void
    {
        $dbService = $this->makeDbService();
        $dbService->writeUsers([$this->john(), $this->jane()]);
        $sut = new UserRepository($dbService);
        $this->assertNull($sut->findByUsername("mary"));
    }

    public function testFindsExistingUserByEmail(): void
    {
        $dbService = $this->makeDbService();
        $dbService->writeUsers([$this->john(), $this->jane()]);
        $sut = new UserRepository($dbService);
        $this->assertEquals($this->john(), $sut->findByEmail("john@example.com"));
    }

    public function testCanAddUser(): void
    {
        $dbService = $this->makeDbService();
        $dbService->writeUsers([$this->john()]);
        $sut = new UserRepository($dbService);
        $this->assertTrue($sut->save($this->jane()));
        $this->assertEquals([$this->john(), $this->jane()], $dbService->readUsers());
    }

    public function testCanUpdateUser(): void
    {
        $dbService = $this->makeDbService();
        $john = $this->john();
        $dbService->writeUsers([$john, $this->jane()]);
        $john = $john->withName("Henry Doe");
        $sut = new UserRepository($dbService);
        $this->assertTrue($sut->save($john));
        $this->assertEquals([$john, $this->jane()], $dbService->readUsers());
    }

    public function testCanDeleteUser(): void
    {
        $dbService = $this->makeDbService();
        $dbService->writeUsers([$this->john(), $this->jane()]);
        $sut = new UserRepository($dbService);
        $this->assertTrue($sut->delete($this->john()));
        $this->assertEquals([$this->jane()], $dbService->readUsers());
    }

    private function makeDbService(): DbService
    {
        vfsStream::setup("root");
        return new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
    }

    private function john(): User
    {
        return new User(
            "john",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["guest"],
            "John Doe",
            "john@example.com",
            "activated",
            "secret"
        );
    }

    private function jane(): User
    {
        return new User(
            "jane",
            '$2y$10$gOae/VL5wrESo5Uf6ZcWhuNlAEycCGW5Ov5opny5PWxa.gbl4SHQW',
            ["admin"],
            "Jane Doe",
            "jane@example.com",
            "activated",
            "secret"
        );
    }
}
