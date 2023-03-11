<?php

/**
 * Copyright 2016-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Value;

class Html
{
    public static function from(string $string): self
    {
        $that = new self;
        $that->string = $string;
        return $that;
    }

    /** @var string */
    private $string;

    public function __toString(): string
    {
        return $this->string;
    }
}
