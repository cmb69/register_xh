<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Value;

class Response
{
    public static function create(string $output = ""): self
    {
        $that = new self();
        $that->output = $output;
        return $that;
    }

    public static function forbid(string $output = ""): self
    {
        $that = new self();
        $that->output = $output;
        $that->forbidden = true;
        return $that;
    }

    public static function redirect(string $location): self
    {
        $that = new self();
        $that->location = $location;
        return $that;
    }

    /** @var string */
    private $output = "";

    /** @var string|null */
    private $title = null;

    /** @var list<array{string,string,int}> */
    private $cookies = [];

    /** @var bool */
    private $forbidden = false;

    /** @var string|null */
    private $location = null;

    public function withTitle(string $title): self
    {
        $that = clone $this;
        $that->title = $title;
        return $that;
    }

    public function withCookie(string $name, string $value, int $expires): self
    {
        $that = clone $this;
        $that->cookies[] = [$name, $value, $expires];
        return $that;
    }

    public function output(): string
    {
        return $this->output;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    /** @return list<array{string,string,int}> */
    public function cookies(): array
    {
        return $this->cookies;
    }

    public function isForbidden(): bool
    {
        return $this->forbidden;
    }

    public function location(): ?string
    {
        return $this->location;
    }
}
