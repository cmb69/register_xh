<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class HandleSpecialPagesTest extends TestCase
{
    /** @var HandleSpecialPages */
    private $sut;

    /** @var array<string,string> */
    private $text;

    /** @var Request&MockObject */
    private $request;

    /** @var Url&MockObject */
    private $url;

    public function setUp(): void
    {
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $view = new View("./views/", $this->text);
        $pages = $this->createStub(Pages::class);
        $pages->method("has")->willReturnCallback(function (string $heading) {
            return in_array($heading, ["One", "Two", "Three"], true);
        });
        $pages->method("evaluate")->willReturnArgument(0);
        $this->sut = new HandleSpecialPages($conf, $this->text, $view, $pages);
        $this->request = $this->createStub(Request::class);
        $this->url = $this->createStub(Url::class);
        $this->request->method("url")->willReturn($this->url);
    }

    public function testRegistrationPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["register"];
        });
        $response = ($this->sut)($this->request);
        $this->assertEquals($this->text["register"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testPasswordForgottenPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["forgot_password"];
        });
        $response = ($this->sut)($this->request);
        $this->assertEquals($this->text["forgot_password"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testUserPrefsPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["user_prefs"];
        });
        $response = ($this->sut)($this->request);
        $this->assertEquals($this->text["user_prefs"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testNonSpecialPage(): void
    {
        $this->url->method("pageMatches")->willReturn(false);
        $response = ($this->sut)($this->request);
        $this->assertEmpty($response->output());
    }
}
