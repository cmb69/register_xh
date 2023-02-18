<?php

/**
* Copyright (c) 2021-2023 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register\Value;

class UserGroup
{
    /** @var string */
    private $groupname;

    /** @var string */
    private $loginpage;

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
}
