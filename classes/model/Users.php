<?php

/**
 * Copyright (c) Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Model;

use Plib\Document;
use Plib\DocumentStore;

class Users implements Document
{
    /** @var array<string,User> */
    private $users;

    public static function retrieve(DocumentStore $store): self
    {
        $that = $store->retrieve("users.csv", self::class);
        assert($that instanceof self);
        return $that;
    }

    public static function update(DocumentStore $store): self
    {
        $that = $store->update("users.csv", self::class);
        assert($that instanceof self);
        return $that;
    }

    public static function fromString(string $contents, string $key): self
    {
        $that = new self([]);
        $lines = preg_split('/\r?\n/', $contents);
        if ($lines === false) {
            return $that;
        }
        foreach ($lines as $line) {
            if (strncmp($line, "//", 2) === 0) {
                continue;
            }
            $fields = explode(':', $line);
            $fields = array_pad($fields, 6, "");
            // if (count($fields) < 7) {
            //     $fields[] = base64_encode($this->random->bytes(15));
            // }
            $user = User::fromArray($fields);
            if ($user === null) {
                continue;
            }
            $that->users[$user->getUsername()] = $user;
        }
        return $that;
    }

    /** @param array<string,User> $users */
    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * @param array{username:string,name:string,group:string,email:string,status:string} $filters
     * @return list<User>
     */
    public function users(array $filters): array
    {
        $users = array_filter($this->users, function (User $user) use ($filters) {
            return (!$filters["username"] || strpos($user->getUsername(), $filters["username"]) !== false)
                && (!$filters["name"] || strpos($user->getName(), $filters["name"]) !== false)
                && (!$filters["email"] || strpos($user->getEmail(), $filters["email"]) !== false)
                && (!$filters["group"] || in_array($filters["group"], $user->getAccessgroups(), true))
                && (!$filters["status"] || strpos($user->getStatus(), $filters["status"]) !== false);
        });
        usort($users, function (User $a, User $b) {
            return strnatcasecmp($a->getUsername(), $b->getUsername());
        });
        return array_values($users);
    }

    public function user(string $username): ?User
    {
        if (!array_key_exists($username, $this->users)) {
            return null;
        }
        return $this->users[$username];
    }

    public function userByEmail(string $email): ?User
    {
        foreach ($this->users as $user) {
            if ($user->getEmail() === $email) {
                return $user;
            }
        }
        return null;
    }

    /** @param list<string> $accessgroups */
    public function createUser(
        string $username,
        string $password,
        array $accessgroups,
        string $name,
        string $email,
        string $status,
        string $secret
    ): User {
        assert(!array_key_exists($username, $this->users));
        $user = new User($username, $password, $accessgroups, $name, $email, $status, $secret);
        $this->users[$username] = $user;
        return $user;
    }

    public function deleteUser(string $username): void
    {
        assert(array_key_exists($username, $this->users));
        unset($this->users[$username]);
    }

    public function toString(): string
    {
        $res = "// Register Plugin user Definitions\n// Line Format:\n"
            . "// login:password:accessgroup1,accessgroup2,...:fullname:email:status:secret\n";
        foreach ($this->users as $user) {
            $username = $user->getUsername();
            $password = $user->getPassword();
            $accessgroups = implode(',', $user->getAccessgroups());
            $fullname = $user->getName();
            $email = $user->getEmail();
            $status = $user->getStatus();
            $secret = $user->secret();
            $res .= "$username:$password:$accessgroups:$fullname:$email:$status:$secret\n";
        }
        return $res;
    }
}
