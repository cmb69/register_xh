<?php

/**
 * Copyright (c) Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Model;

use Plib\Document;
use Plib\DocumentStore;

class Groups implements Document
{
    /** @var array<string,UserGroup> */
    private $groups;

    public static function retrieve(DocumentStore $store): self
    {
        $that = $store->retrieve("groups.csv", self::class);
        assert($that instanceof self);
        return $that;
    }

    public static function update(DocumentStore $store): self
    {
        $that = $store->update("groups.csv", self::class);
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
            $fields = explode('|', $line, 2);
            $fields = array_pad($fields, 2, "");
            $group = UserGroup::fromArray($fields);
            if ($group !== null) {
                $that->groups[$group->getGroupname()] = $group;
            }
        }
        return $that;
    }

    /** @param array<string,UserGroup> $groups */
    public function __construct(array $groups)
    {
        $this->groups = $groups;
    }

    /** @return list<UserGroup> */
    public function groups(): array
    {
        usort($this->groups, function (UserGroup $a, UserGroup $b) {
            return strnatcasecmp($a->getGroupname(), $b->getGroupname());
        });
        return array_values($this->groups);
    }

    public function group(string $groupname): ?UserGroup
    {
        if (!array_key_exists($groupname, $this->groups)) {
            return null;
        }
        return $this->groups[$groupname];
    }

    public function createGroup(string $groupname, string $loginpage): UserGroup
    {
        assert(!array_key_exists($groupname, $this->groups));
        $group = new UserGroup($groupname, $loginpage);
        $this->groups[$groupname] = $group;
        return $group;
    }

    public function deleteGroup(string $groupname): void
    {
        assert(array_key_exists($groupname, $this->groups));
        unset($this->groups[$groupname]);
    }

    public function toString(): string
    {
        $res = "// Register Plugin Group Definitions\n// Line Format:\n// groupname|loginpage\n";
        foreach ($this->groups as $group) {
            $groupname = $group->getGroupname();
            $loginpage = $group->getLoginpage();
            $res .= "$groupname|$loginpage\n";
        }
        return $res;
    }
}
