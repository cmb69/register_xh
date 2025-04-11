<?php

/**
* Copyright (c) 2021-2023 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register\Model;

class UserGroup
{
    /** @var string */
    private $groupname;

    /** @var string */
    private $loginpage;

    /** @param list<string> $fields */
    public static function fromArray(array $fields): ?self
    {
        if ($fields[0] === "") {
            return null;
        }
        return new self(...$fields);
    }

    public function __construct(string $groupname, string $loginpage)
    {
        $this->groupname = $groupname;
        $this->loginpage = $loginpage;
    }

    public function getGroupname(): string
    {
        return $this->groupname;
    }

    public function getLoginpage(): string
    {
        return $this->loginpage;
    }

    public function setLoginpage(string $loginpage): void
    {
        $this->loginpage = $loginpage;
    }
}
