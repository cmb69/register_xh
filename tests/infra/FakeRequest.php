<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class FakeRequest extends Request
{
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    public function username(): string
    {
        return $this->options["username"] ?? "";
    }

    public function editMode(): bool
    {
        return $this->options["editMode"] ?? false;
    }

    protected function query(): string
    {
        return $this->options["query"] ?? "";
    }

    protected function post(): array
    {
        return $this->options["post"] ?? [];
    }

    public function time(): int
    {
        return $this->options["time"] ?? 0;
    }

    public function serverName(): string
    {
        return $this->options["serverName"] ?? "";
    }

    public function remoteAddress(): string
    {
        return $this->options["remoteAddress"] ?? "";
    }

    protected function cookies(): array
    {
        return $this->options["cookies"] ?? [];
    }
}
