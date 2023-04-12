<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use Register\Value\User;
use Register\Value\UserGroup;

class DbService
{
    /** @var string */
    private $dirname;

    /** @var string */
    private $defaultGroupName;

    /** @var Random */
    private $random;

    /** @var bool */
    private $initialized = false;

    public function __construct(string $dirname, string $defaultGroupName, Random $random)
    {
        $this->dirname = $dirname;
        $this->defaultGroupName = $defaultGroupName;
        $this->random = $random;
    }

    public function dataFolder(): string
    {
        if ($this->initialized) {
            return $this->dirname;
        }
        $this->initialized = true;
        if (!is_dir($this->dirname)) {
            mkdir($this->dirname, 0777, true);
            chmod($this->dirname, 0777);
        }
        if (!is_file("{$this->dirname}users.csv")) {
            $this->writeUsers([]);
        }
        if (!is_file("{$this->dirname}groups.csv")) {
            $this->writeGroups([new UserGroup($this->defaultGroupName, '')]);
        }
        return $this->dirname;
    }

    /** @return resource|null */
    public function lock(bool $exclusive)
    {
        $fn = $this->dataFolder() . '/.lock';
        touch($fn);
        if ($fp = @fopen($fn, 'r')) {
            flock($fp, $exclusive ? LOCK_EX : LOCK_SH);
            return $fp;
        }
        return null;
    }

    /**
     * @param resource|null $stream
     * @return void
     */
    public function unlock($stream)
    {
        if ($stream !== null) {
            flock($stream, LOCK_UN);
            fclose($stream);
        }
    }

    /** @return list<UserGroup> */
    public function readGroups(): array
    {
        return $this->read($this->dataFolder() . "groups.csv", function (string $line) {
            $fields = explode('|', rtrim($line), 2);
            $fields = array_pad($fields, 2, "");
            return UserGroup::fromArray($fields);
        });
    }

    /** @return list<User> */
    public function readUsers()
    {
        return $this->read($this->dataFolder() . "users.csv", function (string $line) {
            $fields = explode(':', rtrim($line));
            $fields = array_pad($fields, 6, "");
            if (count($fields) < 7) {
                $fields[] = base64_encode($this->random->bytes(15));
            }
            return User::fromArray($fields);
        });
    }

    /**
     * @template T
     * @param callable(string):(T|null) $readLine
     * @return list<T>
     */
    private function read(string $filename, $readLine): array
    {
        $result = array();
        if (is_file($filename) && ($stream = fopen($filename, "r"))) {
            while (($line = fgets($stream)) !== false) {
                if ($line === "" || !strncmp($line, "//", strlen("//"))) {
                    continue;
                }
                if (($entry = $readLine($line)) !== null) {
                    $result[] = $entry;
                }
            }
            fclose($stream);
        }
        return $result;
    }

    /** @param list<UserGroup> $groups */
    public function writeGroups(array $groups): bool
    {
        $filename = $this->dataFolder() . "groups.csv";
        $header = "// Register Plugin Group Definitions\n// Line Format:\n// groupname|loginpage\n";
        return $this->write($groups, $filename, $header, function ($stream, UserGroup $group) {
            $groupname = $group->getGroupname();
            $loginpage = $group->getLoginpage();
            $line = "$groupname|$loginpage\n";
            return (bool) fwrite($stream, $line);
        });
    }

    /** @param list<User> $users */
    public function writeUsers(array $users): bool
    {
        $filename = $this->dataFolder() . "users.csv";
        $header = "// Register Plugin user Definitions\n// Line Format:\n"
            . "// login:password:accessgroup1,accessgroup2,...:fullname:email:status:secret\n";
        return $this->write($users, $filename, $header, function ($stream, User $user) {
            $username = $user->getUsername();
            $password = $user->getPassword();
            $accessgroups = implode(',', $user->getAccessgroups());
            $fullname = $user->getName();
            $email = $user->getEmail();
            $status = $user->getStatus();
            $secret = $user->secret();
            $line = "$username:$password:$accessgroups:$fullname:$email:$status:$secret\n";
            return (bool) fwrite($stream, $line);
        });
    }

    /**
     * @template T
     * @param list<T> $entries
     * @param string $header
     * @param callable(resource,T): bool $writeLine
     */
    public function write(array $entries, string $filename, $header, $writeLine): bool
    {
        if (!($stream = fopen($filename, "w"))) {
            return false;
        }
        if (!fwrite($stream, $header)) {
            fclose($stream);
            return false;
        }
        foreach ($entries as $entry) {
            if (!$writeLine($stream, $entry)) {
                fclose($stream);
                return false;
            }
        }
        fclose($stream);
        return true;
    }
}
