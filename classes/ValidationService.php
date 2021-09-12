<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class ValidationService
{
    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(array $lang)
    {
        $this->lang = $lang;
    }

    /**
     * @return array<int,string>
     */
    public function validateUser(
        string $name,
        string $username,
        string $password1,
        string $password2,
        string $email
    ): array {
        return array_merge(
            [],
            $this->validateName($name),
            $this->validateUsername($username),
            $this->validatePassword($password1, $password2),
            $this->validateEmail($email)
        );
    }

    /**
     * @return array<int,string>
     */
    private function validateName(string $name): array
    {
        $errors = [];
        if ($name === "") {
            $errors[] = $this->lang['err_name'];
        } elseif (strpos($name, ":") !== false) {
            $errors[] = $this->lang["name"] . ' ' . $this->lang['err_colon'];
        }
        return $errors;
    }

    /**
     * @return array<int,string>
     */
    private function validateUsername(string $username): array
    {
        $errors = [];
        if ($username === "") {
            $errors[] = $this->lang['err_username'];
        } elseif (!preg_match("/^[A-Za-z0-9_]+$/", $username)) {
            $errors[] = $this->lang['err_username_illegal'];
        }
        return $errors;
    }

    /**
     * @return array<int,string>
     */
    private function validatePassword(string $password1, string $password2): array
    {
        $errors = [];
        if ($password1 === "") {
            $errors[] = $this->lang['err_password'];
        } elseif ($password1 !== $password2) {
            $errors[] = $this->lang['err_password2'];
        }
        return $errors;
    }

    /**
     * @return array<int,string>
     */
    private function validateEmail(string $email): array
    {
        $errors = [];
        if ($email === "") {
            $errors[] = $this->lang['err_email'];
        } elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            $errors[] = $this->lang['err_email_invalid'];
        }
        return $errors;
    }
}
