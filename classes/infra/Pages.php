<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Pages
{
    public function count(): int
    {
        global $cl;

        return $cl;
    }

    public function heading(int $pageNum): string
    {
        global $h;

        return $h[$pageNum];
    }

    public function url(int $pageNum): string
    {
        global $u;

        return $u[$pageNum];
    }

    public function level(int $pageNum): int
    {
        global $l;

        return $l[$pageNum];
    }

    /** @return array<int,array<string,string>> */
    public function data(): array
    {
        global $pd_router;

        return $pd_router->find_all();
    }

    public function evaluate(string $content): string
    {
        return evaluate_plugincall($content);
    }

    public function content(int $pageNum): string
    {
        global $c;
        return $c[$pageNum];
    }

    /** @return void */
    public function setContentOf(int $pageNum, string $content)
    {
        global $c;

        $c[$pageNum] = $content;
    }
}
