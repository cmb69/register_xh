<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register\Infra;

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * @param array<string,string> $params
     * @dataProvider relativeUrls
     */
    public function testBuildsCorrectRelativeUrl(string $page, array $params, string $expected): void
    {
        $url = (new Url("/", $page))->withParams($params);
        $this->assertEquals($expected, $url->relative());
    }

    public function relativeUrls(): array
    {
        return [
            "empty" => ["", [], "/"],
            "page" => ["Page", [], "/?Page"],
            "param" => ["", ["foo" => "bar"], "/?&foo=bar"],
            "page+param" => ["Page", ["foo" => "bar"], "/?Page&foo=bar"],
            "space" => ["Page", ["foo" => "bar baz"], "/?Page&foo=bar%20baz"],
        ];
    }

    /**
     * @param array<string,string> $params
     * @dataProvider absoluteUrls
     */
    public function testBuildsCorrectAbsoluteUrl(string $page, array $params, string $expected): void
    {
        $url = (new Url("/", $page))->withParams($params);
        $this->assertEquals($expected, $url->absolute());
    }

    public function absoluteUrls(): array
    {
        return [
            "empty" => ["", [], "http://example.com/"],
            "page" => ["Page", [], "http://example.com/?Page"],
            "param" => ["", ["foo" => "bar"], "http://example.com/?&foo=bar"],
            "page+param" => ["Page", ["foo" => "bar"], "http://example.com/?Page&foo=bar"],
            "space" => ["Page", ["foo" => "bar baz"], "http://example.com/?Page&foo=bar%20baz"],
        ];
    }
}
