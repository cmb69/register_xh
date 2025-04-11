<?php

/**
 * Copyright (c) hristoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Model;

use Plib\Document;
use Plib\DocumentStore;

final class ActiveUsers implements Document
{
    private const FILENAME = "active_users.dat";

    /** @var array<string,int> */
    private $users;

    /** @return static */
    public static function fromString(string $contents, string $key)
    {
        $users = unserialize($contents);
        assert(is_array($users));
        return new static($users);
    }

    public static function retrieve(DocumentStore $store): self
    {
        $that = $store->retrieve(self::FILENAME, ActiveUsers::class);
        assert($that instanceof ActiveUsers);
        return $that;
    }

    public static function update(DocumentStore $store): self
    {
        $that = $store->update(self::FILENAME, ActiveUsers::class);
        assert($that instanceof ActiveUsers);
        return $that;
    }

    /** @param array<string,int> $users */
    public function __construct($users)
    {
        $this->users = $users;
    }

    public function toString(): string
    {
        return serialize($this->users);
    }

    /** @return list<string> */
    public function fetch(int $since): array
    {
        $users = array_filter($this->users, function (int $time) use ($since) {
            return $time >= $since;
        });
        $users = array_map(null, array_keys($users));
        natcasesort($users);
        return array_values($users);
    }

    public function updateUser(string $username, int $time): void
    {
        $this->users[$username] = $time;
    }

    public function removeUser(string $username): void
    {
        unset($this->users[$username]);
    }
}
