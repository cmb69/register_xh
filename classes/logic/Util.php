<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use Register\Value\Mail;
use Register\Value\User;
use Register\Value\UserGroup;

class Util
{
    private const EMAIL_PATTERN = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?'
        . '(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

    public static function base64url(string $string): string
    {
        return rtrim(strtr(base64_encode($string), "+/", "-_"), "=");
    }

    public static function hmac(string $data, string $key): string
    {
        return self::base64url(hash_hmac("sha1", $data, $key, true));
    }

    /**
     * @param array{oldpassword:string,name:string,password1:string,password2:string,email:string} $record
     * @return array{oldpassword:string,name:string,password1:string,password2:string,email:string}
      */
    public static function changePrefsRecordWithDefaults(array $record, User $user): array
    {
        if ($record["password1"] === "" && $record["password2"] === "") {
            $record["password1"] = $record["oldpassword"];
            $record["password2"] = $record["oldpassword"];
        }
        if ($record["email"] === "") {
            $record["email"] = $user->getEmail();
        }
        if ($record["name"] == "") {
            $record["name"] = $user->getName();
        }
        return $record;
    }

    public static function isAuthorized(?User $user, string $groups): bool
    {
        $groups = (string) preg_replace("/[ \t\r\n]*/", "", $groups);
        $groups = array_filter(explode(",", $groups));
        if ($groups === []) {
            return true;
        }
        return count(array_intersect($groups, $user !== null ? $user->getAccessgroups() : [])) > 0;
    }

    /** @return list<array{string}> */
    public static function validateUser(User $user, string $password2): array
    {
        $errors = [];
        if ($user->getUsername() === "") {
            $errors[] = ["err_username"];
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/u', $user->getUsername())) {
            $errors[] = ["err_username_illegal"];
        }
        if ($user->getName() === "") {
            $errors[] = ["err_name"];
        } elseif (strpos($user->getName(), ":") !== false) {
            $errors[] = ["err_colon"];
        }
        if ($user->getAccessgroups() === []) {
            $errors[] = ["err_group_missing"];
        }
        if (!in_array($user->getStatus(), User::STATUSES, true)) {
            $errors[] = ["err_status"];
        }
        if ($user->getPassword() === "") {
            $errors[] = ["err_password"];
        } elseif ($user->getPassword() !== $password2) {
            $errors[] = ["err_password2"];
        }
        if ($user->getEmail() === "") {
            $errors[] = ["err_email"];
        } elseif (!preg_match(self::EMAIL_PATTERN, $user->getEmail())) {
            $errors[] = ["err_email_invalid"];
        }
        return $errors;
    }

    /** @return list<array{string}> */
    public static function validateGroup(UserGroup $group): array
    {
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_-]+$/u', $group->getGroupname())) {
            $errors[] = ["err_group_illegal"];
        }
        return $errors;
    }

    /** @return list<array{string}> */
    public static function validateMail(Mail $mail): array
    {
        $errors = [];
        if (!preg_match('/.+/u', $mail->subject())) {
            $errors[] = ["err_subject"];
        }
        if (!preg_match('/.+/u', $mail->message())) {
            $errors[] = ["err_message"];
        }
        return $errors;
    }
}
