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
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;
use XH\CSRFProtection as CsrfProtector;

class HandleUserPreferencesTest extends TestCase
{
    private $subject;

    private $users;
    private $csrfProtector;
    private $userRepository;
    private $view;
    private $mailer;
    private $logger;
    private $password;

    private $request;

    public function setUp(): void
    {
        $hash = "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq";
        $this->users = [
            "john" => new User("john", $hash, [], "John Doe", "john@example.com", "activated", "secret"),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "locked", "secret"),
        ];
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->csrfProtector->method("tokenInput")->willReturn("");
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./views/", $text);
        $this->mailer = new FakeMailer(false, $text);
        $this->logger = $this->createMock(Logger::class);
        $this->password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $this->mailer,
            $this->logger,
            $this->password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "User-Preferences"));
    }

    public function testNoUser(): void
    {
        $this->request->method("registerAction")->willReturn("");
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "This page is only accessible for members with appropriate permissions.",
            $response->output()
        );
    }

    public function testUserIsLocked(): void
    {
        $this->request->method("registerAction")->willReturn("");
        $this->request->method("username")->willReturn("jane");
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("");
        $this->request->method("username")->willReturn("john");
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testEditNoUser(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "This page is only accessible for members with appropriate permissions.",
            $response->output()
        );
    }

    public function testIsLocked(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
        $this->request->method("username")->willReturn("jane");
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testWrongPassword(): void
    {
        $this->request->method("registerAction")->willReturn("change_prefs");
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
        $this->request->method("registerAction")->willReturn("change_prefs");
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
        $this->request->method("registerAction")->willReturn("change_prefs");
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
        $this->request->method("registerAction")->willReturn("change_prefs");
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
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testUnregisterNoUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "This page is only accessible for members with appropriate permissions.",
            $response->output()
        );
    }

    public function testUnregisterIsLocked(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("jane");
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testUnregisterWrongPassword(): void
    {
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("john");
        $this->request->method("unregisterPost")->willReturn([
            "oldpassword" => "54321",
            "name" => "",
            "email" => "",
        ]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(false);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The old password you entered is wrong.", $response->output());
    }

    public function testUnregisterCorrectPassword(): void
    {
        $this->request->method("registerAction")->willReturn("unregister");
        $this->request->method("username")->willReturn("john");
        $this->request->method("unregisterPost")->willReturn([
            "oldpassword" => "12345",
            "name" => "",
            "email" => "",
        ]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->userRepository->expects($this->once())->method("delete")->willReturn(true);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User 'john' deleted!", $response->output());
    }
}
