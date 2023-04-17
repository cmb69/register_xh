<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class FakeLogger extends Logger
{
    private $lastEntry;

    protected function logMessage(string $type, string $module, string $category, string $message)
    {
        $this->lastEntry = func_get_args();
    }

    public function lastEntry(): array
    {
        return $this->lastEntry;
    }
}
