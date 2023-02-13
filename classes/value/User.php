<?php

/**
* Copyright (c) 2021 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register\Value;

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

    public function withPassword(string $password): self
    {
        $that = clone $this;
        $that->password = $password;
        return $that;
    }

    /** @param array<int,string> $accessgroups */
    public function withAccessgroups(array $accessgroups): self
    {
        $that = clone $this;
        $that->accessgroups = $accessgroups;
        return $that;
    }

    public function withName(string $name): self
    {
        $that = clone $this;
        $that->name = $name;
        return $that;
    }

    public function withEmail(string $email): self
    {
        $that = clone $this;
        $that->email = $email;
        return $that;
    }

    public function activate(): self
    {
        $that = clone $this;
        $that->status = "activated";
        return $that;
    }

    public function withNewPassword(string $password): self
    {
        $that = clone $this;
        $that->password = (string) password_hash($password, PASSWORD_DEFAULT);
        return $that;
    }
}
