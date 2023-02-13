<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 */

namespace Register\Infra;

class Session
{
    /** @return void */
    public function start()
    {
        XH_startSession();
    }
}
