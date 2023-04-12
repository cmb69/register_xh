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

class HandleUserRegistrationTest extends TestCase
{
    private $subject;

    private $users;
    private $view;
    private $userRepository;
    private $mailer;

    private $request;

    public function setUp(): void
    {
        vfsStream::setup("root");
        $hash = "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq";
        $this->users = [
            "john" => new User("john", $hash, ["guest"], "John Doe", "john@example.com", "", "secret"),
            "jane" => new User("jane", $hash, ["guest"], "Jane Doe", "jane@example.com", "12345", "secret"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $random = $this->createStub(Random::class);
        $random->method("bytes")->willReturn("0123456789ABCDE");
        $this->view = new View("./views/", $text);
        $dbService = new FakeDbService("vfs://root/register/", "guest", $random);
        $dbService->writeUsers($this->users);
        $this->userRepository = new UserRepository($dbService);
        $this->mailer = new FakeMailer(false, $text);
        $password = $this->createStub(Password::class);
        $password->method("hash")->willReturnMap([
            ["test", "\$2y\$10\$aGzxwrdMUUbjt2f2Dbbowus.dcYuquRlSHfEFIuKMAoSyK4vW90aG"],
        ]);
        $this->subject = new HandleUserRegistration(
            $conf,
            $text,
            $random,
            $this->view,
            $this->userRepository,
            $this->mailer,
            $password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", ""));
    }

    public function testShowsRegistrationForm(): void
    {
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testValidationError(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "",
            "username" => "",
            "password1" => "",
            "password2" => "",
            "email" => "",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("Please enter your full name.", $response->output());
    }

    public function testExistingUser(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "Jane Smith",
            "username" => "jane",
            "password1" => "test",
            "password2" => "test",
            "email" => "jane.smith@example.com",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The chosen username exists already.", $response->output());
    }

    public function testExistingEmail(): void
    {
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "john@example.com",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "You have been registered successfully. An email has been sent to you containing a link to activate your new account.",
            $response->output()
        );
    }

    public function testSendsMailOnExistingEmail(): void
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
        ($this->subject)($this->request);
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("register");
        $this->request->method("registerUserPost")->willReturn([
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "js@example.com",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertNotNull($this->userRepository->findByUsername("js"));
        $this->assertStringContainsString(
            "You have been registered successfully. An email has been sent to you containing a link to activate your new account.",
            $response->output()
        );
    }

    public function testSendsEmailOnSuccess(): void
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
        ($this->subject)($this->request);
        $this->assertNotNull($this->userRepository->findByUsername("js"));
        Approvals::verifyList($this->mailer->lastMail());
    }

    public function testActivateUserActionNoUser(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "js",
            "nonce" => "12345",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The Username 'js' could not be found!", $response->output());
    }

    public function testActivateUserEmptyState(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "john",
            "nonce" => "12345",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The status of your username is empty.", $response->output());
    }

    public function testActivateUserInvalidState(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "54321",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("The entered validation code is invalid.", $response->output());
    }

    public function testActivateUserSuccess(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "12345",
        ]);
        $response = ($this->subject)($this->request);
        $this->assertTrue($this->userRepository->findByUsername("jane")->isActivated());
        $this->assertStringContainsString("You have successfully activated your new account.", $response->output());
    }
}
