<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Request
{
    public function httpMethod(): string
    {
        return strtolower($_SERVER["REQUEST_METHOD"]);
    }

    public function url(): Url
    {
        global $sn, $su;

        return new Url($sn, $su);
    }
}
