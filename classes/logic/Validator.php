<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

class Validator
{
    /**
     * @return list<array{string}>
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
     * @return list<array{string}>
     */
    private function validateName(string $name): array
    {
        $errors = [];
        if ($name === "") {
            $errors[] = ['err_name'];
        } elseif (strpos($name, ":") !== false) {
            $errors[] = ['err_colon'];
        }
        return $errors;
    }

    /**
     * @return list<array{string}>
     */
    private function validateUsername(string $username): array
    {
        $errors = [];
        if ($username === "") {
            $errors[] = ['err_username'];
        } elseif (!preg_match("/^[A-Za-z0-9_]+$/", $username)) {
            $errors[] = ['err_username_illegal'];
        }
        return $errors;
    }

    /**
     * @return list<array{string}>
     */
    private function validatePassword(string $password1, string $password2): array
    {
        $errors = [];
        if ($password1 === "") {
            $errors[] = ['err_password'];
        } elseif ($password1 !== $password2) {
            $errors[] = ['err_password2'];
        }
        return $errors;
    }

    /**
     * @return list<array{string}>
     */
    private function validateEmail(string $email): array
    {
        $errors = [];
        if ($email === "") {
            $errors[] = ['err_email'];
        } elseif (!preg_match("/^[^\s()<>@,;:\"\/\[\]?=]+@\w[\w-]*(\.\w[\w-]*)*\.[a-z]{2,}$/i", $email)) {
            $errors[] = ['err_email_invalid'];
        }
        return $errors;
    }
}