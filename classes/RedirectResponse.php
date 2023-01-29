<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class RedirectResponse
{
    /** @var string */
    private $location;

    public function __construct(string $location)
    {
        $this->location = $location;
    }

    public function location(): string
    {
        return $this->location;
    }

    /** @return never */
    public function trigger()
    {
        header('Location: ' . $this->location);
        exit;
    }
}
