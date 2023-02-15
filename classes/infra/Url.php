<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

class Url
{
    /** @var string */
    private $base;

    /** @var string */
    private $page;

    /** @var array<string,string> */
    private $params = [];

    public function __construct(string $base, string $page)
    {
        $this->base = $base;
        $this->page = $page;
    }

    public function pageMatches(string $other): bool
    {
        return $this->page === $this->uenc($other);
    }

    public function withPage(string $page): self
    {
        $that = clone $this;
        $that->page = $this->uenc($page);
        return $that;
    }

    private function uenc(string $page): string
    {
        global $cf;

        if (!isset($cf['uri']['word_separator'])) {
            $cf['uri']['word_separator'] = "-";
        }
        return uenc($page);
    }

    public function withEncodedPage(string $page): self
    {
        $that = clone $this;
        $that->page = $page;
        return $that;
    }

    /** @param array<string,string> $params */
    public function withParams(array $params): self
    {
        $that = clone $this;
        $that->params = $params;
        return $that;
    }

    public function relative(): string
    {
        if (($queryString = $this->queryString())) {
            return $this->base . "?" . $queryString;
        }
        return $this->base;
    }

    public function absolute(): string
    {
        if (($queryString = $this->queryString())) {
            return CMSIMPLE_URL . "?" . $queryString;
        }
        return CMSIMPLE_URL;
    }

    private function queryString(): string
    {
        $rest = http_build_query($this->params, "", "&", PHP_QUERY_RFC3986);
        if ($rest) {
            $rest = "&" . $rest;
        }
        return $this->page . $rest;
    }
}
