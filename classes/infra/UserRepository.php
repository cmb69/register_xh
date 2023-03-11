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

    public function add(User $user): bool
    {
        return $this->modify($user, function (array $users, User $user) {
            $users[] = $user;
            return $users;
        });
    }

    public function update(User $user): bool
    {
        return $this->modify($user, function (array $users, User $newuser) {
            return array_map(function (User $user) use ($newuser) {
                return $user->getUsername() === $newuser->getUsername() ? $newuser : $user;
            }, $users);
        });
    }

    public function delete(User $user): bool
    {
        return $this->modify($user, function (array $users, User $olduser) {
            return array_values(array_filter($users, function (User $user) use ($olduser) {
                return $user->getUsername() !== $olduser->getUsername();
            }));
        });
    }

    /** @param callable(list<User>,User):list<User> $modify */
    private function modify(User $user, $modify): bool
    {
        $lock = $this->dbService->lock(true);
        $users = $this->dbService->readUsers();
        $userArray = $modify($users, $user);
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->unlock($lock);
        return $result;
    }
}
