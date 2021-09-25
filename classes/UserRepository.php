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
        $this->dbService->lock(LOCK_EX);
        return $result;
    }

    public function update(User $user): bool
    {
        $this->dbService->lock(LOCK_EX);
        $users = $this->dbService->readUsers();
        $userArray = registerReplaceUserEntry($users, $user);
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->lock(LOCK_EX);
        return $result;
    }

    public function delete(User $user): bool
    {
        $this->dbService->lock(LOCK_EX);
        $users = $this->dbService->readUsers();
        $userArray = registerDeleteUserEntry($users, $user->getUsername());
        $result = $this->dbService->writeUsers($userArray);
        $this->dbService->lock(LOCK_EX);
        return $result;
    }
}
