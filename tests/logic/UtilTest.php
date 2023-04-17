<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use PHPUnit\Framework\TestCase;
use Register\Value\Mail;
use Register\Value\User;

class UtilTest extends TestCase
{
    /** @dataProvider accessAuthorizationLegacyData */
    public function testAccessAuthorizationLegacy(?User $user, array $contents, array $expected): void
    {
        $actual = Util::accessAuthorizationLegacy($user, $contents);
        $this->assertEquals($expected, $actual);
    }

    public function accessAuthorizationLegacyData(): array
    {
        $user = new User("", "", ["guest"], "", "", "", "");
        return [
            "no call" => [$user, [""], [true]],
            "no groups" => [$user, ["{{{access('')}}}"], [true]],
            "admin only" => [$user, ["{{{access('admin')}}}"], [false]],
        ];
    }

    /**
     * @param list<array{string}> $expected
     * @dataProvider validateUserData
     */
    public function testValidateUser(User $user, string $password, array $expected): void
    {
        $actual = Util::validateUser($user, $password);
        $this->assertEquals($expected, $actual);
    }

    public function validateUserData(): array
    {
        return [
            "valid user" => [
                new User("john", "12345", ["guest"], "John Doe", "john@example.com", "activated", "secret"),
                "12345",
                [],
            ],
            "illegal username" => [
                new User("john doe", "12345", ["guest"], "John Doe", "john@example.com", "activated", "secret"),
                "12345",
                [["err_username_illegal"]],
            ],
            "name with colon" => [
                new User("john", "12345", ["guest"], "John:Doe", "john@example.com", "activated", "secret"),
                "12345",
                [["err_colon"]],
            ],
            "no group" => [
                new User("john", "12345", [], "John Doe", "john@example.com", "activated", "secret"),
                "12345",
                [["err_group_missing"]],
            ],
            "illegal status" => [
                new User("john", "12345", ["guest"], "John Doe", "john@example.com", "illegal", "secret"),
                "12345",
                [["err_status"]],
            ],
        ];
    }

    /**
     * @param list<array{string}> $expected
     * @dataProvider validateMailData
     */
    public function testValidateMail(Mail $mail, array $expected)
    {
        $actual = Util::validateMail($mail);
        $this->assertEquals($expected, $actual);
    }

    public function validateMailData(): array
    {
        return [
            "empty message" => [new Mail("subject", ""), [["err_message"]]],
        ];
    }
}
