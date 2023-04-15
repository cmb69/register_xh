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
use Register\Infra\FakePassword;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class HandlePasswordForgottenTest extends TestCase
{
    private $view;
    private $dbService;
    private $userRepository;
    private $mailer;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $this->view = new View("./views/", $text);
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->writeUsers([new User("john", "12345", ["guest"], "John Dow", "john@example.com", "activated", "secret")]);
        $this->userRepository = new UserRepository($this->dbService);
        $this->mailer = new FakeMailer(false, $text);
        $this->request = $this->request();
    }

    private function sut()
    {
        return new HandlePasswordForgotten(
            XH_includeVar("./config/config.php", "plugin_cf")["register"],
            $this->view,
            $this->userRepository,
            new FakePassword,
            $this->mailer
        );
    }

    private function request()
    {
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", ""));
        $request->method("time")->willReturn(1637449200);
        return $request;
    }

    public function testDoesNothingIfUserIsLoggedIn(): void
    {
        $this->request->method("username")->willReturn("cmb");
        $response = $this->sut()($this->request);
        $this->assertEquals("", $response->output());
    }

    public function testRendersForm(): void
    {
        $response = $this->sut()($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testForgotReportsValidationErrors(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "invalid"]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The given email address is invalid.", $response->output());
    }

    public function testReportsSuccessOnUnknownEmail(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "jane@example.com"]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString(
            "If the email you specified exists in our system, we've sent a password reset link to it.",
            $response->output()
        );
    }

    public function testReportsSuccessOnKnownEmail(): void
    {
        $this->request->method("registerAction")->willReturn("forgot_password");
        $this->request->method("forgotPasswordPost")->willReturn(["email" => "john@example.com"]);
        $this->request->method("serverName")->willReturn("example.com");
        $response = $this->sut()($this->request);
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertStringContainsString(
            "If the email you specified exists in our system, we've sent a password reset link to it.",
            $response->output()
        );
    }

    public function testResetReportsUnknownUser(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "colt",
            "time" => "",
            "mac" => "",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testResetReportsWrongMac(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => 1637449800,
            "mac" => "54321",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testResetReportsExpiration(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => 1637445599,
            "mac" => "TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The password reset has expired!", $response->output());
    }

    public function testResetRendersForm(): void
    {
        $this->request->method("registerAction")->willReturn("reset_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => 1637449800,
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ]);
        $response = $this->sut()($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangeReportsUnknownUser(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "colt",
            "time" => "",
            "mac" => "",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangeReportsWrongMac(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637449800",
            "mac" => "54321",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testChangeReportsExpiration(): void
    {
        $this->request->method("registerAction")->willReturn("change_password");
        $this->request->method("resetPasswordParams")->willReturn([
            "username" => "john",
            "time" => "1637445599",
            "mac" => "TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The password reset has expired!", $response->output());
    }

    public function testChangeReportsPasswordValidationErrors(): void
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
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testChangeReportsFailureToSave(): void
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
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangeReportsSuccess(): void
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
        $response = $this->sut()($this->request);
        $this->assertTrue(password_verify("admin", $this->userRepository->findByUsername("john")->getPassword()));
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertStringContainsString("An email has been sent to you with your user data.", $response->output());
    }
}
