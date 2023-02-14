<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Url
{
    /** @var string */
    private $base;

    /** @var string */
    private $page;

    public function __construct(string $base, string $page)
    {
        $this->base = $base;
        $this->page = $page;
    }

    public function relative(): string
    {
        return $this->base . "?" . $this->page;
    }

    public function absolute(): string
    {
        return CMSIMPLE_URL . "?" . $this->page;
    }
}
