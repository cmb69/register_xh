<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Value;

class Passwords
{
    /** @var string */
    private $password;

    /** @var string */
    private $confirmation;

    public function __construct(string $password, string $confirmation)
    {
        $this->password = $password;
        $this->confirmation = $confirmation;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function confirmation(): string
    {
        return $this->confirmation;
    }
}
