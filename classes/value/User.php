<?php

/**
* Copyright (c) 2021-2023 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register\Value;

class User
{
    public const STATUSES = ["activated", "locked", "deactivated"];

    /** @param list<string> $fields */
    public static function fromArray(array $fields): ?self
    {
        if (
            $fields[0] === "" || $fields[1] === "" || $fields[2] === "" || $fields[3] === ""
            || $fields[4] === "" || $fields[6] === ""
        ) {
            return null;
        }
        $fields[2] = explode(",", $fields[2]);
        assert(is_string($fields[5]));
        return new self($fields[0], $fields[1], $fields[2], $fields[3], $fields[4], $fields[5], $fields[6]);
    }

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    /** @var list<string> */
    private $accessgroups;

    /** @var string */
    private $name;

    /** @var string */
    private $email;

    /** @var string */
    private $status;

    /** @var string */
    private $secret;

    /**
     * @param string[] $accessgroups
     */
    public function __construct(
        string $username,
        string $password,
        array $accessgroups,
        string $name,
        string $email,
        string $status,
        string $secret
    ) {
        $this->username = $username;
        $this->password = $password;
        $this->accessgroups = $accessgroups;
        $this->name = $name;
        $this->email = $email;
        $this->status = $status;
        $this->secret = $secret;
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

    public function secret(): string
    {
        return $this->secret;
    }

    /** @param list<string> $accessgroups */
    public function with(string $name, string $email, array $accessgroups, string $status): self
    {
        $that = clone $this;
        $that->name = $name;
        $that->email = $email;
        $that->accessgroups = $accessgroups;
        $that->status = $status;
        return $that;
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

    public function withSecret(string $secret): self
    {
        $that = clone $this;
        $that->secret = $secret;
        return $that;
    }
}
