<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Lock
{
    /** @var resource */
    private $stream;

    /** @param resource $stream */
    public function __construct($stream)
    {
        $this->stream = $stream;
    }

    public function __destruct()
    {
        flock($this->stream, LOCK_UN);
    }
}
