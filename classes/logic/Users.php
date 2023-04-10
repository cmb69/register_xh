<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use Register\Value\User;

class Users
{
    private const EMAIL_PATTERN = '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?'
        . '(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/';

    /** @param list<User> $users */
    public static function findByUsername(string $username, array $users): ?User
    {
        return array_reduce($users, function (?User $carry, User $user) use ($username) {
            return $user->getUsername() === $username ? $user : $carry;
        });
    }

    /** @param list<User> $users */
    private static function findByEmail(string $email, array $users): ?User
    {
        return array_reduce($users, function (?User $carry, User $user) use ($email) {
            return $user->getEmail() === $email ? $user : $carry;
        });
    }

    /**
     * @param array{username:string,name:string,group:string,email:string,status:string} $filters
     * @param list<User> $users
     * @return list<User>
     */
    public static function select(array $filters, array $users): array
    {
        $users = array_filter($users, function (User $user) use ($filters) {
            return (!$filters["username"] || strpos($user->getUsername(), $filters["username"]) !== false)
                && (!$filters["name"] || strpos($user->getName(), $filters["name"]) !== false)
                && (!$filters["email"] || strpos($user->getEmail(), $filters["email"]) !== false)
                && (!$filters["group"] || in_array($filters["group"], $user->getAccessgroups(), true))
                && (!$filters["status"] || strpos($user->getStatus(), $filters["status"]) !== false);
        });
        usort($users, function (User $a, User $b) {
            return strnatcasecmp($a->getUsername(), $b->getUsername());
        });
        return $users;
    }

    /**
     * @param list<User> $users
     * @return list<array{string}>
     */
    public static function validate(User $user, string $password2, array $users, bool $new): array
    {
        assert($new || self::findByUsername($user->getUsername(), $users));
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
        if ($new && self::findByUsername($user->getUsername(), $users)) {
            $errors[] = ["err_username_exists"];
        }
        $found = self::findByEmail($user->getEmail(), $users);
        if ($found && $found->getUsername() !== $user->getUsername()) {
            $errors = [["err_email_exists"]];
        }
        return $errors;
    }

    /**
     * @param list<User> $users
     * @return list<User>
     */
    public static function add(User $user, array $users): array
    {
        $users[] = $user;
        return $users;
    }

    /**
     * @param list<User> $users
     * @return list<User>
     */
    public static function update(User $user, array $users): array
    {
        return array_map(function (User $aUser) use ($user) {
            return $aUser->getUsername() === $user->getUsername() ? $user : $aUser;
        }, $users);
    }

    /**
     * @param list<User> $users
     * @return list<User>
     */
    public static function delete(User $user, array $users): array
    {
        return array_values(array_filter($users, function (User $aUser) use ($user) {
            return $aUser->getUsername() !== $user->getUsername();
        }));
    }
}
