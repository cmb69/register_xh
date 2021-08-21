<?php

/**
* Copyright (c) 2021 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register;

class UserGroup
{
    /**
     * @var string
     * @readonly
     */
    public $groupname;

    /**
     * @var string
     * @readonly
     */
    public $loginpage;

    public function __construct(string $groupname, string $loginpage)
    {
        $this->groupname = $groupname;
        $this->loginpage = $loginpage;
    }
}
