<?php

/**
 * Copyright (c) 2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use ApprovalTests\Approvals;

use Register\Infra\Pages;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class HandleSpecialPagesTest extends TestCase
{
    /** @var HandleSpecialPages */
    private $sut;

    /** @var array<string,string> */
    private $lang;

    /** @var Request&MockObject */
    private $request;

    /** @var Url&MockObject */
    private $url;

    public function setUp(): void
    {
        $headings = ["One", "Two", "Three"];
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $view = new View("./", $this->text);
        $pages = $this->createStub(Pages::class);
        $pages->method("evaluate")->willReturnArgument(0);
        $this->sut = new HandleSpecialPages($headings, $conf, $this->text, $view, $pages);
        $this->request = $this->createStub(Request::class);
        $this->url = $this->createStub(Url::class);
        $this->request->expects($this->any())->method("url")->willReturn($this->url);
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

    public function testLoginErrorPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["login_error"];
        });
        $response = ($this->sut)($this->request);
        $this->assertTrue($response->forbidden());
        $this->assertEquals($this->text["login_error"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testLogoutPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["loggedout"];
        });
        $response = ($this->sut)($this->request);
        $this->assertEquals($this->text["loggedout"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testLoginPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["loggedin"];
        });
        $response = ($this->sut)($this->request);
        $this->assertEquals($this->text["loggedin"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testAccessErrorPage(): void
    {
        $this->url->method("pageMatches")->willReturnCallback(function (string $other) {
            return $other === $this->text["access_error"];
        });
        $response = ($this->sut)($this->request);
        $this->assertTrue($response->forbidden());
        $this->assertEquals($this->text["access_error"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testNonSpecialPage(): void
    {
        $this->url->method("pageMatches")->willReturn(false);
        $response = ($this->sut)($this->request);
        $this->assertEmpty($response->output());
    }
}
