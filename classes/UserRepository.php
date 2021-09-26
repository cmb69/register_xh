<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

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
     * @return User|null
     */
    public function findByUsername(string $username)
    {
        $this->dbService->lock(LOCK_SH);
        $users = $this->dbService->readUsers();
        $this->dbService->lock(LOCK_UN);
        $user = registerSearchUserArray($users, 'username', $username);
        return $user ? $user : null;
    }

    /**
     * @return User|null
     */
    public function findByEmail(string $email)
    {
        $this->dbService->lock(LOCK_SH);
        $users = $this->dbService->readUsers();
        $this->dbService->lock(LOCK_UN);
        $user = registerSearchUserArray($users, 'email', $email);
        return $user ? $user : null;
    }

    public function add(User $user): bool
    {
        $this->dbService->lock(LOCK_EX);
        $users = $this->dbService->readUsers();
        $users[] = $user;
        $result = $this->dbService->writeUsers($users);
        $this->dbService->lock(LOCK_UN);
        return $result;
    }

    public function update(User $user): bool
    {
        $this->dbService->lock(LOCK_EX);
        $users = $this->dbService->readUsers();
        $userArray = $this->replaceUser($users, $user);
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->lock(LOCK_UN);
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
        $this->dbService->lock(LOCK_EX);
        $users = $this->dbService->readUsers();
        $userArray = $this->deleteUser($users, $user->getUsername());
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->lock(LOCK_UN);
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
