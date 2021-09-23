<?php

/**
* Copyright (c) 2021 Christoph M. Becker
*
* This file is part of Register_XH.
*/

namespace Register;

class User
{
    /**
     * @var string
     * @readonly
     */
    public $username;

    /**
     * @var string
     * @readonly
     */
    public $password;
    
    /**
     * @var string[]
     * @readonly
     */
    public $accessgroups;
    
    /**
     * @var string
     * @readonly
     */
    public $name;

    /**
     * @var string
     * @readonly
     */
    public $email;

    /**
     * @var string
     * @readonly
     */
    public $status;

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

    /**
     * @return void
     */
    public function changePassword(string $password)
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
}
