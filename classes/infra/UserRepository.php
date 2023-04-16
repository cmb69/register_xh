<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\User;

class UserRepository
{
    /**
     * @var DbService
     */
    private $dbService;

    public function __construct(DbService $dbService)
    {
        $this->dbService = $dbService;
    }

    /**
     * @param array{username:string,name:string,group:string,email:string,status:string} $filters
     * @return list<User>
     */
    public function select(array $filters): array
    {
        $lock = $this->dbService->lock(false);
        $users = $this->dbService->readUsers();
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
        $this->dbService->unlock($lock);
        return $users;
    }

    public function findByUsername(string $username): ?User
    {
        $lock = $this->dbService->lock(false);
        $users = $this->dbService->readUsers();
        $this->dbService->unlock($lock);
        return array_reduce($users, function (?User $carry, User $user) use ($username) {
            return $user->getUsername() === $username ? $user : $carry;
        });
    }

    public function findByEmail(string $email): ?User
    {
        $lock = $this->dbService->lock(false);
        $users = $this->dbService->readUsers();
        $this->dbService->unlock($lock);
        return array_reduce($users, function (?User $carry, User $user) use ($email) {
            return $user->getEmail() === $email ? $user : $carry;
        });
    }

    public function hasDuplicateEmail(User $user): bool
    {
        $lock = $this->dbService->lock(false);
        $users = $this->dbService->readUsers();
        $this->dbService->unlock($lock);
        return array_reduce($users, function (bool $carry, User $aUser) use ($user) {
            return $carry
                || ($aUser->getEmail() === $user->getEmail() && $aUser->getUsername() !== $user->getUsername());
        }, false);
    }

    public function save(User $user): bool
    {
        $lock = $this->dbService->lock(true);
        $users = $this->dbService->readUsers();
        $users = array_combine(array_map(function (User $user) {
            return $user->getUsername();
        }, $users), $users);
        assert($users !== false);
        $users[$user->getUsername()] = $user;
        $users = array_values($users);
        $res = $this->dbService->writeUsers($users);
        $this->dbService->unlock($lock);
        return $res;
    }

    public function delete(User $user): bool
    {
        $lock = $this->dbService->lock(true);
        $users = $this->dbService->readUsers();
        $users = array_values(array_filter($users, function (User $aUser) use ($user) {
            return $aUser->getUsername() !== $user->getUsername();
        }));
        $res = $this->dbService->writeUsers($users);
        $this->dbService->unlock($lock);
        return $res;
    }
}
