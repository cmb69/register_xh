<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use PHPUnit\Framework\TestCase;

use Register\Value\User;
use Register\Value\UserGroup;

class ProcessUsersTest extends TestCase
{
    public function testProcessesUsersSuccessfully(): void
    {
        [$users, $errors] = (new AdminProcessor())->processUsers(
            $this->groups(),
            ["jane", "john"],
            ["54321", "12345"],
            ["54321", "12345"],
            ["Jane Doe", "John Doe"],
            ["jane@example.com", "john@example.com"],
            ["admin", "guest"],
            ["activated", "activated"],
            ["secret", "secret"],
            "guest"
        );
        $this->assertEquals([$this->jane(), $this->john()], $users);
        $this->assertEmpty($errors);
    }

    public function testReportsErrorOnEmptyUserName(): void
    {
        [, $errors] = (new AdminProcessor())->processUsers(
            $this->groups(),
            [""],
            ["12345"],
            ["54321"],
            ["John Doe"],
            ["john@example.com"],
            ["guest"],
            ["activated"],
            ["secret"],
            "guest"
        );
        $this->assertEquals([[["error_in_user", ""], ["err_username"]]], $errors);
    }

    public function testReportsErrorOnDuplicateUsername(): void
    {
        [, $errors] = (new AdminProcessor())->processUsers(
            $this->groups(),
            ["j", "j"],
            ["54321", "12345"],
            ["54321", "12345"],
            ["Jane Doe", "John Doe"],
            ["jane@example.com", "john@example.com"],
            ["admin", "guest"],
            ["activated", "activated"],
            ["secret", "secret"],
            "guest"
        );
        $this->assertEquals([[["error_in_user", "j"], ["err_username_exists"]]], $errors);
    }

    public function testReportsErrorOnDuplicateEmail(): void
    {
        [, $errors] = (new AdminProcessor())->processUsers(
            $this->groups(),
            ["jane", "john"],
            ["54321", "12345"],
            ["54321", "12345"],
            ["Jane Doe", "John Doe"],
            ["j@example.com", "j@example.com"],
            ["admin", "guest"],
            ["activated", "activated"],
            ["secret", "secret"],
            "guest"
        );
        $this->assertEquals([[["error_in_user", "john"], ["err_email_exists"]]], $errors);
    }

    public function testReportsErrorOnNotExistingGroupName(): void
    {
        [, $errors] = (new AdminProcessor())->processUsers(
            $this->groups(),
            ["john"],
            ["12345"],
            ["54321"],
            ["John Doe"],
            ["john@example.com"],
            ["nogroup"],
            ["activated"],
            ["secret"],
            "guest"
        );
        $this->assertEquals([[["error_in_user", "john"], ["err_group_does_not_exist", "nogroup"]]], $errors);
    }

    /** @return list<UserGroup> */
    private function groups(): array
    {
        return [
            new UserGroup("guest", ""),
            new UserGroup("admin", ""),
        ];
    }

    private function defaultUser(): User
    {
        return new User("NewUser", "", ["guest"], "Name Lastname", "user@domain.com", "activated", "secret");
    }

    private function john(): User
    {
        return new User("john", "12345", ["guest"], "John Doe", "john@example.com", "activated", "secret");
    }

    private function jane(): User
    {
        return new User("jane", "54321", ["admin"], "Jane Doe", "jane@example.com", "activated", "secret");
    }
}
