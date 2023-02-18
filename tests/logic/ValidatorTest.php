<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    /**
     * @dataProvider validateUserData
     * @return void
     */
    public function testValidateUser(
        string $name,
        string $username,
        string $password1,
        string $password2,
        string $email,
        array $expected
    ) {
        $validationService = new Validator();
        $actual = $validationService->validateUser($name, $username, $password1, $password2, $email);
        $this->assertEquals($expected, $actual);
    }

    public function validateUserData(): array
    {
        return [
            [
                "John Doe",
                "jd",
                "test",
                "test",
                "john@example.com",
                []
            ],
            [
                "",
                "",
                "",
                "",
                "",
                [["err_name"], ["err_username"], ["err_password"], ["err_email"]]
            ],
            [
                ":",
                ":",
                "a",
                "b",
                "c",
                [["err_colon"], ["err_username_illegal"], ["err_password2"], ["err_email_invalid"]]
            ],
        ];
    }
}
