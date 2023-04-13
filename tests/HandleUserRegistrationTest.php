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

class HandleUserRegistrationTest extends TestCase
{
    private $view;
    private $dbService;
    private $userRepository;
    private $mailer;
    private $random;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $this->view = new View("./views/", $text);
        $this->random = $this->createStub(Random::class);
        $this->random->method("bytes")->willReturn("0123456789ABCDE");
        $this->dbService = $this->dbService();
        $this->userRepository = new UserRepository($this->dbService);
        $this->mailer = new FakeMailer(false, $text);
        $this->request = $this->request();
    }

    public function sut(): HandleUserRegistration
    {
        $password = new FakePassword;
        return new HandleUserRegistration(
            XH_includeVar("./config/config.php", "plugin_cf")["register"],
            XH_includeVar("./languages/en.php", "plugin_tx")["register"],
            $this->random,
            $this->view,
            $this->userRepository,
            $this->mailer,
            $password
        );
    }

    private function dbService()
    {
        $hash = "\$2y\$04\$FMR/.rF4uHySPVzW4ZSYDO.BMmJNLAsHdzrD.r8EufGEk7XkWuwzW";
        $users = [
            "john" => new User("john", $hash, ["guest"], "John Doe", "john@example.com", "", "secret"),
            "jane" => new User("jane", $hash, ["guest"], "Jane Doe", "jane@example.com", "12345", "secret"),
        ];
        $dbService = new FakeDbService("vfs://root/register/", "guest", $this->random);
        $dbService->writeUsers($users);
        return $dbService;
    }

    private function request()
    {
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", ""));
        return $request;
    }

    public function testDefaultRendersRegistrationForm(): void
    {
        $response = $this->sut()($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testRegisterReportsValidationErrors(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "",
            "username" => "",
            "password1" => "",
            "password2" => "",
            "email" => "",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testRegisterReportsExistingUser(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "Jane Smith",
            "username" => "jane",
            "password1" => "test",
            "password2" => "test",
            "email" => "jane.smith@example.com",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The chosen username exists already.", $response->output());
    }

    public function testRegisterRedirectsOnExistingEmail(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "john@example.com",
        ]);
        $this->request->method("serverName")->willReturn("example.com");
        $this->request->method("remoteAddress")->willReturn("127.0.0.1");
        $response = $this->sut()($this->request);
        $this->assertEquals("http://example.com/", $response->location());
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testRegisterReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "js@example.com",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testRegisterRedirectsOnSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "js@example.com",
        ]);
        $this->request->method("serverName")->willReturn("example.com");
        $this->request->method("remoteAddress")->willReturn("127.0.0.1");
        $response = $this->sut()($this->request);
        $this->assertNotNull($this->userRepository->findByUsername("js"));
        $this->assertEquals("http://example.com/", $response->location());
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testActivateReportsMissingNonce(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "js",
            "nonce" => "",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("No validation code supplied!", $response->output());
    }

    public function testActivateReportsNonExistentUser(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "js",
            "nonce" => "12345",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The Username 'js' could not be found!", $response->output());
    }

    public function testActivateReportsInvalidNonce(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "54321",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testActivateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "12345",
        ]);
        $response = $this->sut()($this->request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testActivateReportsSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "12345",
        ]);
        $response = $this->sut()($this->request);
        $this->assertTrue($this->userRepository->findByUsername("jane")->isActivated());
        $this->assertStringContainsString("You have successfully activated your new account.", $response->output());
    }
}
