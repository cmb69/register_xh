<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Request
{
    public function method(): string
    {
        return strtolower($_SERVER["REQUEST_METHOD"]);
    }

    public function url(): Url
    {
        global $sn, $su;

        return new Url($sn, $su);
    }

    public function coreStyleFolder(): string
    {
        global $pth;

        return $pth["folder"]["corestyle"];
    }

    public function pluginsFolder(): string
    {
        global $pth;

        return $pth["folder"]["plugins"];
    }

    public function admin(): bool
    {
        return defined("XH_ADM") && XH_ADM;
    }

    public function edit(): bool
    {
        global $edit;

        return (bool) $edit;
    }

    public function function(): string
    {
        global $function;

        return $function;
    }

    public function time(): int
    {
        return time();
    }

    public function cookie(string $name): ?string
    {
        return $_COOKIE[$name] ?? null;
    }
}
