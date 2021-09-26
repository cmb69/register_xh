<?php

/**
* Copyright (c) 2021 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register;

class User
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;
    
    /**
     * @var string[]
     */
    private $accessgroups;
    
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $status;

    /**
     * @param string[] $accessgroups
     */
    public function __construct(
        string $username,
        string $password,
        array $accessgroups,
        string $name,
        string $email,
        string $status
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->accessgroups = $accessgroups;
        $this->name = $name;
        $this->email = $email;
        $this->status = $status;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return array<int,string>
     */
    public function getAccessgroups(): array
    {
        return $this->accessgroups;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function isActivated(): bool
    {
        return $this->status === "activated";
    }

    public function isLocked(): bool
    {
        return $this->status === "locked";
    }

    /**
     * @return void
     */
    public function setPassword(string $password)
    {
        $this->password = $password;
    }

    /**
     * @param array<int,string> $accessgroups
     * @return void
     */
    public function setAccessgroups(array $accessgroups)
    {
        $this->accessgroups = $accessgroups;
    }

    /**
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return void
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return void
     */
    public function activate()
    {
        $this->status = "activated";
    }

    /**
     * @return void
     */
    public function changePassword(string $password)
    {
        $this->password = (string) password_hash($password, PASSWORD_DEFAULT);
    }
}
