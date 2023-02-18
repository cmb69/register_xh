<?php

/**
 * Copyright (c) 2021-2023-2023-2023 Christoph M. Becker
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
        return $this->searchUserArray($users, 'username', $username);
    }

    public function findByEmail(string $email): ?User
    {
        $lock = $this->dbService->lock(false);
        $users = $this->dbService->readUsers();
        $this->dbService->unlock($lock);
        return $this->searchUserArray($users, 'email', $email);
    }

    /**
     * @param User[] $users
     * @param mixed $value
     */
    private function searchUserArray(array $users, string $key, $value): ?User
    {
        foreach ($users as $user) {
            if ($user->{"get$key"}() == $value) {
                return $user;
            }
        }
        return null;
    }

    public function add(User $user): bool
    {
        $lock = $this->dbService->lock(true);
        $users = $this->dbService->readUsers();
        $users[] = $user;
        $result = $this->dbService->writeUsers($users);
        $this->dbService->unlock($lock);
        return $result;
    }

    public function update(User $user): bool
    {
        $lock = $this->dbService->lock(true);
        $users = $this->dbService->readUsers();
        $userArray = $this->replaceUser($users, $user);
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->unlock($lock);
        return $result;
    }

    /**
     * @param array<int,User> $users
     * @return array<int,User>
     */
    private function replaceUser(array $users, User $newuser): array
    {
        $newusers = array();
        $username = $newuser->getUsername();
        foreach ($users as $user) {
            if ($user->getUsername() == $username) {
                $newusers[] = $newuser;
            } else {
                $newusers[] = $user;
            }
        }
        return $newusers;
    }

    public function delete(User $user): bool
    {
        $lock = $this->dbService->lock(true);
        $users = $this->dbService->readUsers();
        $userArray = $this->deleteUser($users, $user->getUsername());
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->unlock($lock);
        return $result;
    }

    /**
     * @param array<int,User> $users
     * @return array<int,User>
     */
    private function deleteUser(array $users, string $username): array
    {
        $newarray = array();
        foreach ($users as $user) {
            if ($user->getUsername() != $username) {
                $newarray[] = $user;
            }
        }
        return $newarray;
    }
}
