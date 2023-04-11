<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class FakeCsrfProtector extends CsrfProtector
{
    private $options;

    public function options(array $options)
    {
        $this->options = $options;
    }

    public function token(): string
    {
        return "0+pVtDm4xXAxUmA3/mrL";
    }

    public function check(): bool
    {
        return $this->options["check"] ?? true;
    }
}
