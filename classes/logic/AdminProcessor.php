<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Logic;

use Register\Value\User;
use Register\Value\UserGroup;

class AdminProcessor
{
    /**
     * @param list<UserGroup> $groups
     * @param list<string> $usernames
     * @param list<string> $passwords
     * @param list<string> $oldPasswords
     * @param list<string> $names
     * @param list<string> $emails
     * @param list<string> $groupStrings
     * @param list<string> $statuses
     * @param list<string> $secrets
     * @return array{list<User>,list<array{string}|list<array{string}>>}
     */
    public function processUsers(
        array $groups,
        array $usernames,
        array $passwords,
        array $oldPasswords,
        array $names,
        array $emails,
        array $groupStrings,
        array $statuses,
        array $secrets
    ): array {
        $groupNames = [];
        foreach ($groups as $entry) {
            $groupNames[] = $entry->getGroupname();
        }
        $errors = [];
        $users = [];
        foreach (array_keys($usernames) as $i) {
            [$user, $userErrors] = $this->processUser(
                explode(",", $groupStrings[$i]),
                $usernames[$i],
                $passwords[$i],
                $oldPasswords[$i],
                $names[$i],
                $emails[$i],
                $statuses[$i],
                $secrets[$i],
                $users,
                $groupNames
            );
            if (!empty($userErrors)) {
                $errors[] = array_merge([["error_in_user", $usernames[$i]]], $userErrors);
            }
            $users[] = $user;
        }
        return [$users, $errors];
    }

    /**
     * @param list<string> $groups
     * @param list<User> $users
     * @param list<string> $groupNames
     * @return array{User,list<array{string}>}
     */
    private function processUser(
        array $groups,
        string $username,
        string $password,
        string $oldPassword,
        string $name,
        string $email,
        string $status,
        string $secret,
        array $users,
        array $groupNames
    ) {
        $validator = new Validator();
        if ($password === $oldPassword) {
            $userErrors = $validator->validateUser($name, $username, "dummy", "dummy", $email);
        } else {
            $userErrors = $validator->validateUser($name, $username, $password, $password, $email);
        }
        foreach ($users as $user) {
            if ($user->getUsername() === $username) {
                $userErrors[] = ['err_username_exists'];
            }
            if ($user->getEmail() === $email) {
                $userErrors[] = ['err_email_exists'];
            }
        }
        foreach ($groups as $groupName) {
            if (!in_array($groupName, $groupNames, true)) {
                $userErrors[] = ['err_group_does_not_exist', $groupName];
            }
        }
        if ($password === "") {
            $password = base64_encode(random_bytes(16));
        }
        if ($password !== $oldPassword) {
            // TODO: handle password_hash() failure
            $password = (string) password_hash($password, PASSWORD_DEFAULT);
        }
        return [new User($username, $password, $groups, $name, $email, $status, $secret), $userErrors];
    }

    /**
     * @param list<string> $delete
     * @param list<string> $names
     * @param list<string> $loginPages
     * @return array{list<UserGroup>,bool,list<array{string}>}
     */
    public function processGroups(string $add, array $delete, array $names, array $loginPages): array
    {
        $groups = [];
        $save = true;
        $errors = [];
        foreach (array_keys($names) as $i) {
            if (!preg_match("/^[A-Za-z0-9_-]+$/", $names[$i])) {
                $errors[] = ['err_group_illegal'];
            }
            if (!isset($delete[$i]) || $delete[$i] == '') {
                $groups[] = new UserGroup($names[$i], $loginPages[$i]);
            } else {
                $save = false;
            }
        }
        if ($add != '') {
            $groups[] = new UserGroup("NewGroup", '');
            $save = false;
        }
        return [$groups, $save, $errors];
    }
}
