<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use PHPUnit\Framework\TestCase;
use Register\Value\User;

class UtilTest extends TestCase
{
    /** @dataProvider isAuthorizedData */
    public function testIsAuthorized(?User $user, string $groups, bool $expected): void
    {
        $result = Util::isAuthorized($user, $groups);
        $this->assertEquals($expected, $result);
    }

    public function isAuthorizedData(): array
    {
        return [
            [null, "", true],
            [null, ",", true],
            [null, "guest", false],
            [$this->user(), "guest", true],
            [$this->user(), "admin", false],
            [$this->user(), "admin,", false],
        ];
    }

    private function user(): User
    {
        return new User("", "", ["guest"], "", "", "", "");
    }
}
