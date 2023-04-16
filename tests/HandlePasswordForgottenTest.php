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
use Register\Infra\FakeRequest;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class HandlePasswordForgottenTest extends TestCase
{
    private $view;
    private $dbService;
    private $userRepository;
    private $mailer;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $this->view = new View("./views/", $text);
        $this->dbService = new FakeDbService("vfs://root/register/", "guest", $this->createMock(Random::class));
        $this->dbService->writeUsers([new User("john", "12345", ["guest"], "John Dow", "john@example.com", "activated", "secret")]);
        $this->userRepository = new UserRepository($this->dbService);
        $this->mailer = new FakeMailer(false, $text);
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

    public function testDoesNothingIfUserIsLoggedIn(): void
    {
        $request = new FakeRequest(["query" => "", "time" => 1637449200, "username" => "cmb"]);
        $response = $this->sut()($request);
        $this->assertEquals("", $response->output());
    }

    public function testRendersForm(): void
    {
        $request = new FakeRequest(["query" => "", "time" => 1637449200]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testForgotReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=forgot_password",
            "time" => 1637449200,
            "post" => ["email" => "invalid"]
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The given email address is invalid.", $response->output());
    }

    public function testReportsSuccessOnUnknownEmail(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=forgot_password",
            "time" => 1637449200,
            "post" => ["email" => "jane@example.com"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString(
            "If the email you specified exists in our system, we've sent a password reset link to it.",
            $response->output()
        );
    }

    public function testReportsSuccessOnKnownEmail(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=forgot_password",
            "time" => 1637449200,
            "post" => ["email" => "john@example.com"],
            "serverName" => "example.com",
        ]);
        $response = $this->sut()($request);
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertStringContainsString(
            "If the email you specified exists in our system, we've sent a password reset link to it.",
            $response->output()
        );
    }

    public function testResetReportsUnknownUser(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=reset_password&username=colt&time=&mac",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testResetReportsWrongMac(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=reset_password&username=john&time=1637449800&mac=54321",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testResetReportsExpiration(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=reset_password&username=john&time=1637445599&mac=TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The password reset has expired!", $response->output());
    }

    public function testResetRendersForm(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=reset_password&username=john&time=1637449800&mac=3pjbpRHFI9OO3gUHV42CHT3IHL8",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testChangeReportsUnknownUser(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=change_password&username=colt&time=&mac=",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("User 'colt' does not exist!", $response->output());
    }

    public function testChangeReportsWrongMac(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=change_password&username=john&time=1637449800&mac=54321",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testChangeReportsExpiration(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=change_password&username=john&time=1637445599&mac=TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
            "time" => 1637449200,
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The password reset has expired!", $response->output());
    }

    public function testChangeReportsPasswordValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=change_password&username=john&time=1637449800&mac=3pjbpRHFI9OO3gUHV42CHT3IHL8",
            "time" => 1637449200,
            "post" => ["password1" => "admin", "password2" => "amdin"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The two entered passwords do not match.", $response->output());
    }

    public function testChangeReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&register_action=change_password&username=john&time=1637449800&mac=3pjbpRHFI9OO3gUHV42CHT3IHL8",
            "time" => 1637449200,
            "post" => ["password1" => "admin", "password2" => "admin"],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testChangeReportsSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=change_password&username=john&time=1637449800&mac=3pjbpRHFI9OO3gUHV42CHT3IHL8",
            "time" => 1637449200,
            "post" => ["password1" => "admin", "password2" => "admin"],
            "serverName" => "example.com",
        ]);
        $response = $this->sut()($request);
        $this->assertTrue(password_verify("admin", $this->userRepository->findByUsername("john")->getPassword()));
        Approvals::verifyList($this->mailer->lastMail());
        $this->assertStringContainsString("An email has been sent to you with your user data.", $response->output());
    }
}
