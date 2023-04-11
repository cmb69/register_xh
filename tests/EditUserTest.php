<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeMailer;
use Register\Infra\Logger;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;
use XH\CSRFProtection as CsrfProtector;

class EditUserTest extends TestCase
{
    /** @var HandleUserPreferences */
    private $subject;

    /** @var array<string,User> */
    private $users;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    /** @var FakeMailer */
    private $mailer;

    /** @var Request */
    private $request;

    /** @var Password&MockObject */
    private $password;

    public function setUp(): void
    {
        $hash = "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq";
        $this->users = [
            "john" => new User("john", $hash, [], "John Doe", "john@example.com", "activated", "secret"),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "locked", "secret"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->csrfProtector->method("tokenInput")->willReturn("");
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./views/", $text);
        $this->mailer = new FakeMailer(false, $text);
        $logger = $this->createMock(Logger::class);
        $this->password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $this->mailer,
            $logger,
            $this->password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "User-Preferences"));
        $this->request->method("registerAction")->willReturn("change_prefs");
    }

    public function testNoUser(): void
    {
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "This page is only accessible for members with appropriate permissions.",
            $response->output()
        );
    }

    public function testIsLocked(): void
    {
        $this->request->method("username")->willReturn("jane");
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testWrongPassword(): void
    {
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "54321",
            "name" => "",
            "password1" => "",
            "password2" => "",
            "email" => "",
        ]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(false);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testPasswordConfirmationDoesNotMatch(): void
    {
        $_POST = [
            "action" => "edit_user_prefs",
            "submit" => "",
            "oldpassword" => "12345",
            "password1" => "one",
            "password2" => "two",
        ];
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "password1" => "one",
            "password2" => "two",
            "email" => "",
        ]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testCorrectPassword(): void
    {
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "password1" => "",
            "password2" => "",
            "email" => "",
        ]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->userRepository->expects($this->once())->method("update")->willReturn(true);
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "Your account information has been updated and sent to you via email.",
            $response->output()
        );
    }

    public function testSendsEmailOnSuccess(): void
    {
        $this->request->method("username")->willReturn("john");
        $this->request->method("changePrefsPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "password1" => "",
            "password2" => "",
            "email" => "",
        ]);
        $this->request->method("serverName")->willReturn("example.com");
        $this->request->method("remoteAddress")->willReturn("127.0.0.1");
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->userRepository->expects($this->once())->method("update")->willReturn(true);
        $this->password->method("verify")->willReturn(true);
        ($this->subject)($this->request);
        $this->assertEquals("john@example.com", $this->mailer->to());
        $this->assertEquals("Account data changed for example.com", $this->mailer->subject());
        Approvals::verifyHtml($this->mailer->message());
        $this->assertEquals(
            ["From: postmaster@example.com", "Cc: john@example.com, postmaster@example.com"],
            $this->mailer->headers()
        );
    }
}
