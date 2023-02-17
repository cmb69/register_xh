<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Pages
{
    public function evaluate(string $content): string
    {
        return evaluate_plugincall($content);
    }

    /** @return void */
    public function setContentOf(int $pageNum, string $content)
    {
        global $c;

        $c[$pageNum] = $content;
    }
}