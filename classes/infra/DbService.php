<?php

/**
 * Copyright (c) 2007 Carsten Heinelt (http://cmsimple.heinelt.eu)
 * Copyright (c) 2010-2012 Gert Ebersbach (http://www.ge-webdesign.de/cmsimpleplugins/)
 * Copyright (c) 2012-2021 Christoph M. Becker
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

    public function hasUsersFile(): bool
    {
        return is_file($this->dataFolder() . 'users.csv');
    }

    public function hasGroupsFile(): bool
    {
        return is_file($this->dataFolder() . 'groups.csv');
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

    /** @return UserGroup[] */
    public function readGroups(): array
    {
        $filename = $this->dataFolder() . "groups.csv";
        $groupArray = array();
        if (is_file($filename)) {
            $fp = fopen($filename, "r");
            if ($fp) {
                while (!feof($fp)) {
                    $line = rtrim((string) fgets($fp, 4096));
                    if ($entry = $this->readGroupLine($line)) {
                        $groupArray[] = $entry;
                    }
                }
                fclose($fp);
            }
        }
        return $groupArray;
    }

    /** @return ?UserGroup */
    private function readGroupLine(string $line)
    {
        if (!empty($line) && strpos($line, '//') !== 0) {
            $parts = explode('|', $line, 2);
            $groupname = $parts[0];
            $loginpage = $parts[1] ?? '';
            // line must not start with '//' and all fields must be set
            if (strpos($groupname, "//") === false && $groupname != "") {
                return new UserGroup($groupname, $loginpage);
            }
        }
        return null;
    }

    /** @param UserGroup[] $array */
    public function writeGroups(array $array): bool
    {
        $filename = $this->dataFolder() . "groups.csv";
        // remove old backup
        if (is_file($filename . ".bak")) {
            unlink($filename . ".bak");
        }
        // create new backup
        $permissions = false;
        if (is_file($filename)) {
            $permissions = fileperms($filename) & 0777;
            rename($filename, $filename . ".bak");
        }

        $fp = fopen($filename, "w");
        if ($fp === false) {
            return false;
        }

        // write comment line to file
        $line = '// Register Plugin Group Definitions'."\n" . '// Line Format:'."\n" . '// groupname|loginpage'."\n";
        if (!fwrite($fp, $line)) {
            fclose($fp);
            return false;
        }

        foreach ($array as $entry) {
            $groupname = $entry->getGroupname();
            $loginpage = $entry->getLoginpage();
            $line = "$groupname|$loginpage\n";
            if (!fwrite($fp, $line)) {
                fclose($fp);
                return false;
            }
        }
        fclose($fp);

        // change permissions of new file to same as backup file
        if ($permissions !== false) {
            chmod($filename, $permissions);
        }
        return true;
    }

    /** @return User[] */
    public function readUsers()
    {
        $filename = $this->dataFolder() . "users.csv";
        $userArray = array();

        if (is_file($filename)) {
            $fp = fopen($filename, "r");
            if ($fp) {
                while (!feof($fp)) {
                    $line = fgets($fp, 4096);
                    if ($line && strpos($line, '//') === false) {
                        if ($entry = $this->readUserLine($line)) {
                            $userArray[] = $entry;
                        }
                    }
                }
                fclose($fp);
            }
        }
        return $userArray;
    }

    /** @return ?User */
    private function readUserLine(string $line)
    {
        $fields = explode(':', rtrim($line));
        [$username,$password,$accessgroups,$name,$email,$status] = $fields;
        if ($username != "" && $password != "" && $accessgroups != ""
                && $name != "" && $email != ""/* && $status != ""*/) {
            if (count($fields) === 7) {
                $secret = $fields[6];
            } else {
                $secret = base64_encode($this->random->bytes(15));
            }
            return new User(
                $username,
                $password,
                explode(',', $accessgroups),
                $name,
                $email,
                $status,
                $secret
            );
        }
        return null;
    }

    /** @param User[] $array */
    public function writeUsers(array $array): bool
    {
        $filename = $this->dataFolder() . "users.csv";
        // remove old backup
        if (is_file($filename . ".bak")) {
            unlink($filename . ".bak");
        }

        // create new backup
        $permissions = false;
        if (is_file($filename)) {
            $permissions = fileperms($filename) & 0777;
            rename($filename, $filename . ".bak");
        }

        $fp = fopen($filename, "w");
        if ($fp === false) {
            return false;
        }

        // write comment line to file
        $line = "// Register Plugin user Definitions\n"
            . "// Line Format:\n"
            . "// login:password:accessgroup1,accessgroup2,...:fullname:email:status:secret\n";
        if (!fwrite($fp, $line)) {
            fclose($fp);
            return false;
        }

        foreach ($array as $entry) {
            $username = $entry->getUsername();
            $password = $entry->getPassword();
            $accessgroups = implode(',', $entry->getAccessgroups());
            $fullname = $entry->getName();
            $email = $entry->getEmail();
            $status = $entry->getStatus();
            $secret = $entry->secret();
            $line = "$username:$password:$accessgroups:$fullname:$email:$status:$secret\n";
            if (!fwrite($fp, $line)) {
                fclose($fp);
                return false;
            }
        }
        fclose($fp);

        // change permissions of new file to same as backup file
        if ($permissions !== false) {
            chmod($filename, $permissions);
        }
        return true;
    }
}
