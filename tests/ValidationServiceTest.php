<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;

class ValidationServiceTest extends TestCase
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
        $lang = [
            "err_name" => "err_name",
            "err_username" => "err_username",
            "err_password" => "err_password",
            "err_email" => "err_email",
            "err_colon" => "err_colon",
            "err_username_illegal" => "err_username_illegal",
            "err_password2" => "err_password2",
            "err_email_invalid" => "err_email_invalid",
            "name" => "name",
        ];
        $validationService = new ValidationService($lang);
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
                ["err_name", "err_username", "err_password", "err_email"]
            ],
            [
                ":",
                ":",
                "a",
                "b",
                "c",
                ["name err_colon", "err_username_illegal", "err_password2", "err_email_invalid"]
            ],
        ];
    }
}
