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
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class HandlePasswordForgottenTest extends TestCase
{
    private $subject;

    private $view;
    private $userRepository;
    private $mailer;

    private $request;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./views/", $text);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailer = new FakeMailer(false, $text);
        $this->subject = new HandlePasswordForgotten(
            $conf,
            $this->view,
            $this->userRepository,
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
        $this->userRepository->method("findByEmail")->willReturn(null);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByEmail")->willReturn($john);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByEmail")->willReturn($john);
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "john@example.com"]);
        $this->request->method("serverName")->willReturn("example.com");
        ($this->subject)($this->request);
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testResetPasswordUnknownUsername(): void
    {
        $this->userRepository->method("findByUsername")->willReturn(null);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
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
        $this->userRepository->method("findByUsername")->willReturn(null);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
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
        $this->assertStringContainsString("An email has been sent to you with your user data.", $response->output());
    }

    public function testChangePasswordSendsMailOnSuccess(): void
    {
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->expects($this->once())->method("update")->willReturn(false);
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
