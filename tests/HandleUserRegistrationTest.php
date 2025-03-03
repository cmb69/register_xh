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
use Register\Infra\FakePassword;
use Register\Infra\FakeRequest;
use Register\Infra\Mailer;
use Register\Infra\Random;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\PHPMailer\PHPMailer;
use Register\Value\User;

class HandleUserRegistrationTest extends TestCase
{
    private $view;
    private $dbService;
    private $userRepository;
    /** @var PHPMailer&MockObject */
    private $phpMailer;
    private $mailer;
    private $random;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $text = XH_includeVar("./languages/en.php", "plugin_tx")["register"];
        $this->view = new View("./views/", $text);
        $this->random = $this->createStub(Random::class);
        $this->random->method("bytes")->willReturn("0123456789ABCDE");
        $this->dbService = $this->dbService();
        $this->userRepository = new UserRepository($this->dbService);
        $this->phpMailer = $this->getMockBuilder(PHPMailer::class)->onlyMethods(["send"])->getMock();
        $this->mailer = new Mailer($conf, $this->phpMailer);
    }

    public function sut(): HandleUserRegistration
    {
        $password = new FakePassword;
        return new HandleUserRegistration(
            XH_includeVar("./config/config.php", "plugin_cf")["register"],
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

    public function testReportsUnauthorizedAccessToLoggedInUsers(): void
    {
        $request = new FakeRequest(["username" => "cmb"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("You are not authorized for this action!", $response->output());
    }

    public function testDefaultRendersRegistrationForm(): void
    {
        $request = new FakeRequest();
        $response = $this->sut()($request);
        Approvals::verifyHtml($response->output());
    }

    public function testRegisterReportsValidationErrors(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=register",
            "post" => [
                "name" => "",
                "username" => "",
                "password1" => "",
                "password2" => "",
                "email" => "",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testRegisterReportsExistingUser(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=register",
            "post" => [
                "name" => "Jane Smith",
                "username" => "jane",
                "password1" => "test",
                "password2" => "test",
                "email" => "jane.smith@example.com",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The chosen username exists already.", $response->output());
    }

    public function testRegisterRedirectsOnExistingEmail(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=register",
            "post" => [
                "name" => "John Smith",
                "username" => "js",
                "password1" => "test",
                "password2" => "test",
                "email" => "john@example.com",
            ],
            "serverName" => "example.com",
            "remoteAddress" => "127.0.0.1",
        ]);
        $this->phpMailer->expects($this->any())->method("send")->willReturn(true);
        $response = $this->sut()($request);
        $this->assertEquals("postmaster@example.com", $this->phpMailer->From);
        $this->assertEquals([["john@example.com", ""]], $this->phpMailer->getToAddresses());
        $this->assertEquals([["postmaster@example.com", ""]], $this->phpMailer->getCcAddresses());
        $this->assertEquals("Your user account at example.com", $this->phpMailer->Subject);
        Approvals::verifyString($this->phpMailer->Body);
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testRegisterReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest([
            "query" => "&register_action=register",
            "post" => [
                "name" => "John Smith",
                "username" => "js",
                "password1" => "test",
                "password2" => "test",
                "email" => "js@example.com",
            ],
        ]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testRegisterRedirectsOnSuccess(): void
    {
        $request = new FakeRequest([
            "query" => "&register_action=register",
            "post" => [
                "name" => "John Smith",
                "username" => "js",
                "password1" => "test",
                "password2" => "test",
                "email" => "js@example.com",
            ],
            "serverName" => "example.com",
            "remoteAddress" => "127.0.0.1",
        ]);
        $this->phpMailer->expects($this->any())->method("send")->willReturn(true);
        $response = $this->sut()($request);
        $this->assertEquals("postmaster@example.com", $this->phpMailer->From);
        $this->assertEquals([["js@example.com", ""]], $this->phpMailer->getToAddresses());
        $this->assertEquals([["postmaster@example.com", ""]], $this->phpMailer->getCcAddresses());
        $this->assertEquals("Your user account at example.com", $this->phpMailer->Subject);
        Approvals::verifyString($this->phpMailer->Body);
        $this->assertNotNull($this->userRepository->findByUsername("js"));
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testActivateReportsMissingNonce(): void
    {
        $request = new FakeRequest(["query" => "&register_action=activate&register_username=js&register_nonce="]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("No verification code supplied!", $response->output());
    }

    public function testActivateReportsNonExistentUser(): void
    {
        $request = new FakeRequest(["query" => "&register_action=activate&register_username=js&register_nonce=12345"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The Username 'js' could not be found!", $response->output());
    }

    public function testActivateReportsInvalidNonce(): void
    {
        $request = new FakeRequest(["query" => "&register_action=activate&register_username=jane&register_nonce=54321"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("The entered verification code is invalid.", $response->output());
    }

    public function testActivateReportsFailureToSave(): void
    {
        $this->dbService->options(["writeUsers" => false]);
        $request = new FakeRequest(["query" => "&register_action=activate&register_username=jane&register_nonce=12345"]);
        $response = $this->sut()($request);
        $this->assertStringContainsString("Saving CSV file failed.", $response->output());
    }

    public function testActivateReportsSuccess(): void
    {
        $request = new FakeRequest(["query" => "&register_action=activate&register_username=jane&register_nonce=12345"]);
        $response = $this->sut()($request);
        $this->assertTrue($this->userRepository->findByUsername("jane")->isActivated());
        $this->assertStringContainsString("You have successfully activated your new account.", $response->output());
    }
}
