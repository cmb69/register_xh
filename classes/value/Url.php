<?php

/**
 * Copyright 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Value;

class Url
{
    public static function from(string $url): self
    {
        $that = new self();
        $parts = parse_url($url);
        assert(isset($parts["scheme"], $parts["host"], $parts["path"]));
        $that->base = $parts["scheme"] . "://" . $parts["host"];
        $that->path = (string) preg_replace('/index\.php$/', "", $parts["path"]);
        $match = preg_match('/^(?:([^=&]*)(?=&|$))?(.*)/', $parts["query"] ?? "", $matches);
        assert($match !== false);
        assert(isset($matches[1]));
        assert(isset($matches[2]));
        $that->page = $matches[1];
        $that->params = self::parseQuery($matches[2]);
        return $that;
    }

    /** @return array<string,string|array<string>> */
    private static function parseQuery(string $query): array
    {
        parse_str($query, $result);
        self::assertStringKeys($result);
        return $result;
    }

    /**
     * @param array<int|string,array<mixed>|string> $array
     * @phpstan-assert array<string,string|array<string>> $array
     */
    private static function assertStringKeys(array $array): void
    {
        foreach ($array as $key => $value) {
            assert(is_string($key));
        }
    }

    /** @var string */
    private $base;

    /** @var string */
    private $path;

    /** @var string */
    private $page;

    /** @var array<string,string|array<string>> */
    private $params;

    public function page(): string
    {
        return $this->page;
    }

    /** @return string|array<string>|null */
    public function param(string $name)
    {
        return $this->params[$name] ?? null;
    }

    public function withPage(string $page): self
    {
        $that = clone $this;
        $that->page = $page;
        $that->params = [];
        return $that;
    }

    public function with(string $name, string $value = ""): self
    {
        $that = clone $this;
        $that->params[$name] = $value;
        return $that;
    }

    public function without(string $name): self
    {
        $that = clone $this;
        unset($that->params[$name]);
        return $that;
    }

    public function withoutParams(): self
    {
        $that = clone $this;
        $that->params = [];
        return $that;
    }

    public function relative(): string
    {
        $query = $this->queryString();
        if ($query === "") {
            return $this->path;
        }
        return $this->path . "?" . $query;
    }

    public function absolute(): string
    {
        $query = $this->queryString();
        if ($query === "") {
            return $this->base . $this->path;
        }
        return $this->base . $this->path . "?" . $query;
    }

    private function queryString(): string
    {
        $query = preg_replace('/=(?=&|$)/', "", http_build_query($this->params, "", "&"));
        if ($query === "") {
            return $this->page;
        }
        return $this->page . "&" . $query;
    }
}
