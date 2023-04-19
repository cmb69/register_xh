<?php

/**

* Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Random
{
    /** @codeCoverageIgnore */
    public function bytes(int $length): string
    {
        assert($length > 0);
        return random_bytes($length);
    }
}
