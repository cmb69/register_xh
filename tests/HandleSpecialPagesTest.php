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
use Register\Infra\View;

class HandleSpecialPagesTest extends TestCase
{
    /** @var HandleSpecialPages */
    private $sut;

    /** @var array<string,string> */
    private $lang;

    public function setUp(): void
    {
        $headings = ["One", "Two", "Three"];
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $this->lang = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $view = new View("./", $this->lang);
        $pages = $this->createStub(Pages::class);
        $pages->method("evaluate")->willReturnArgument(0);
        $this->sut = new HandleSpecialPages($headings, $conf, $this->lang, $view, $pages);
    }

    public function testRegistrationPage(): void
    {
        $response = $this->sut->registrationPageAction();
        $this->assertEquals($this->lang["register"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testPasswordForgottenPage(): void
    {
        $response = $this->sut->passwordForgottenPageAction();
        $this->assertEquals($this->lang["forgot_password"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testUserPrefsPage(): void
    {
        $response = $this->sut->userPrefsPageAction();
        $this->assertEquals($this->lang["user_prefs"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testLoginErrorPage(): void
    {
        $response = $this->sut->loginErrorPageAction();
        $this->assertTrue($response->forbidden());
        $this->assertEquals($this->lang["login_error"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testLogoutPage(): void
    {
        $response = $this->sut->logoutPageAction();
        $this->assertEquals($this->lang["loggedout"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testLoginPage(): void
    {
        $response = $this->sut->loginPageAction();
        $this->assertEquals($this->lang["loggedin"], $response->title());
        Approvals::verifyHtml($response->output());
    }

    public function testAccessErrorPage(): void
    {
        $response = $this->sut->accessErrorPageAction();
        $this->assertTrue($response->forbidden());
        $this->assertEquals($this->lang["access_error"], $response->title());
        Approvals::verifyHtml($response->output());
    }
}
