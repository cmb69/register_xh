<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeDbService;
use Register\Infra\FakeMailer;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class HandlePasswordForgottenTest extends TestCase
{
    private $subject;

    private $view;
    private $dbService;
    private $userRepository;
    private $password;
    private $mailer;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./views/", $text);
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->writeUsers([new User("john", "12345", ["guest"], "John Dow", "john@example.com", "activated", "secret")]);
        $this->userRepository = new UserRepository($this->dbService);
        $this->password = $this->createMock(Password::class);
        $this->mailer = new FakeMailer(false, $text);
        $this->subject = new HandlePasswordForgotten(
            $conf,
            $this->view,
            $this->userRepository,
            $this->password,
            $this->mailer
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", ""));
        $this->request->method("time")->willReturn(1637449200);
    }

    public function testLoggedInUserIsRedirected(): void
    {
        $this->request->method("username")->willReturn("cmb");
        $response = ($this->subject)($this->request);
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testRendersForm(): void
    {
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testEmptyEmail(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => ""]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("Please enter your email address.", $response->output());
    }

    public function testInvalidEmail(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "invalid"]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The given email address is invalid.", $response->output());
    }

    public function testUnknownEmail(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "jane@example.com"]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "If the email you specified exists in our system, we've sent a password reset link to it.",
            $response->output()
        );
    }

    public function testKnownEmail(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "john@example.com"]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "If the email you specified exists in our system, we've sent a password reset link to it.",
            $response->output()
        );
    }

    public function testSendsMailOnSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "john@example.com"]);
        $this->request->method("serverName")->willReturn("example.com");
        ($this->subject)($this->request);
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testResetPasswordUnknownUsername(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "colt",
            "time" => "",
            "mac" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testResetPasswordWrongMac(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => 1637449800,
            "mac" => "54321",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testResetPasswordSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => 1637449800,
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testResetPasswordReportsExpiration(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => 1637445599,
            "mac" => "TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The password reset has expired!", $response->output());
    }

    public function testUnknownUsername(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "colt",
            "time" => "",
            "mac" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testWrongMac(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637449800",
            "mac" => "54321",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testSuccess(): void
    {
        $this->password->method("hash")->willReturn("\$2y\$10\$OJrimnUX6ZTQUO5ZDwnn/u1xXB0fr96ul33wJO6jMCzdrhY5BcOe.");
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637449800",
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ]);
        $this->request->method("changePasswordPost")->willReturn([
            "password1" => "admin",
            "password2" => "admin",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertTrue(password_verify("admin", $this->userRepository->findByUsername("john")->getPassword()));
        $this->assertStringContainsString("An email has been sent to you with your user data.", $response->output());
    }

    public function testChangePasswordSendsMailOnSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637449800",
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ]);
        $this->request->method("changePasswordPost")->willReturn([
            "password1" => "admin",
            "password2" => "admin",
        ]);
        $this->request->method("serverName")->willReturn("example.com");
        ($this->subject)($this->request);
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testReportsExpiration(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637445599",
            "mac" => "TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The password reset has expired!", $response->output());
    }

    public function testReportsNotMatchingPasswords(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637449800",
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ]);
        $this->request->method("changePasswordPost")->willReturn([
            "password1" => "admin",
            "password2" => "amdin",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testReportsFailureToUpdateUser(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637449800",
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ]);
        $this->request->method("changePasswordPost")->willReturn([
            "password1" => "admin",
            "password2" => "admin",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }
}
