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
    private const ACCESS_PATTERN = '/(?:#CMSimple\s+|{{{.*?)access\((.*?)\)\s*;?\s*(?:#|}}})/isu';

    public static function base64url(string $string): string
    {
        return rtrim(strtr(base64_encode($string), "+/", "-_"), "=");
    }

    public static function hmac(string $data, string $key): string
    {
        return self::base64url(hash_hmac("sha1", $data, $key, true));
    }

    /**
     * @param list<array{int,string}> $data
     * @return array<int,bool>
     */
    public static function accessAuthorization(?User $user, array $data): array
    {
        $authorizations = [];
        $parents = [[0, true]];
        foreach ($data as [$level, $access]) {
            $parent = end($parents);
            assert(is_array($parent));
            [$parentLevel, $parentAuth] = $parent;
            while ($level <= $parentLevel) {
                array_pop($parents);
                $parent = end($parents);
                if (!is_array($parent)) {
                    break;
                }
                [$parentLevel, $parentAuth] = $parent;
            }
            $authorizations[] = $auth = $parentAuth && self::isAuthorized($user, $access);
            array_push($parents, [$level, $auth]);
        }
        return $authorizations;
    }

    /**
     * @param list<string> $contents
     * @return list<bool>
     */
    public static function accessAuthorizationLegacy(?User $user, array $contents)
    {
        return array_map(function (string $content) use ($user) {
            return !preg_match(self::ACCESS_PATTERN, $content, $matches)
                || self::isAuthorized($user, trim($matches[1], "\"'"));
        }, $contents);
    }

    private static function isAuthorized(?User $user, string $groups): bool
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
            $errors[] = ["error_username"];
        } elseif (!preg_match('/^[A-Za-z0-9_]+$/u', $user->getUsername())) {
            $errors[] = ["error_username_illegal"];
        }
        if ($user->getName() === "") {
            $errors[] = ["error_name"];
        } elseif (strpos($user->getName(), ":") !== false) {
            $errors[] = ["error_colon"];
        }
        if ($user->getAccessgroups() === []) {
            $errors[] = ["error_group_missing"];
        }
        if (!in_array($user->getStatus(), User::STATUSES, true)) {
            $errors[] = ["error_status"];
        }
        $errors = array_merge($errors, self::validatePasswords($user->getPassword(), $password2));
        $errors = array_merge($errors, self::validateEmail($user->getEmail()));
        return $errors;
    }

    /**
     * @return list<array{string}>
     */
    public static function validatePasswords(string $password1, string $password2): array
    {
        $errors = [];
        if ($password1 === "") {
            $errors[] = ['error_password'];
        } elseif ($password1 !== $password2) {
            $errors[] = ['error_password2'];
        }
        return $errors;
    }

    /**
     * @return list<array{string}>
     */
    public static function validateEmail(string $email): array
    {
        $local = "[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+";
        $label = "[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?";
        $pattern = "/^$local@$label(?:\.$label)*$/u";
        $errors = [];
        if ($email === "") {
            $errors[] = ['error_email'];
        } elseif (!preg_match($pattern, $email)) {
            $errors[] = ['error_email_invalid'];
        }
        return $errors;
    }

    /** @return list<array{string}> */
    public static function validateGroup(UserGroup $group): array
    {
        $errors = [];
        if (!preg_match('/^[A-Za-z0-9_-]+$/u', $group->getGroupname())) {
            $errors[] = ["error_group_illegal"];
        }
        return $errors;
    }

    /** @return list<array{string}> */
    public static function validateMail(Mail $mail): array
    {
        $errors = [];
        if (!preg_match('/.+/u', $mail->subject())) {
            $errors[] = ["error_subject"];
        }
        if (!preg_match('/.+/u', $mail->message())) {
            $errors[] = ["error_message"];
        }
        return $errors;
    }
}
