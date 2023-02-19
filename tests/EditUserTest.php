<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use XH\CSRFProtection as CsrfProtector;

use Register\Value\User;
use Register\Infra\CurrentUser;
use Register\Infra\FakeMailer;
use Register\Infra\Logger;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class EditUserTest extends TestCase
{
    /** @var HandleUserPreferences */
    private $subject;

    /** @var array<string,User> */
    private $users;

    /** @var CurrentUser&MockObject */
    private $currentUser;

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
        $this->currentUser = $this->createStub(CurrentUser::class);
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./", $text);
        $this->mailer = new FakeMailer(false, $text);
        $logger = $this->createMock(Logger::class);
        $this->password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $this->currentUser,
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
    }

    public function testNoUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "submit" => ""];
        $this->currentUser->method("get")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testIsLocked(): void
    {
        $_POST = ["action" => "edit_user_prefs", "submit" => ""];
        $this->currentUser->method("get")->willReturn($this->users["jane"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testWrongPassword(): void
    {
        $_POST = ["action" => "edit_user_prefs", "submit" => "", "oldpassword" => "54321"];
        $this->currentUser->method("get")->willReturn($this->users["john"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(false);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
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
        $this->currentUser->method("get")->willReturn($this->users["john"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testCorrectPassword(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_POST = ["action" => "edit_user_prefs", "submit" => "", "oldpassword" => "12345"];
        $this->currentUser->method("get")->willReturn($this->users["john"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->userRepository->expects($this->once())->method("update")->willReturn(true);
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSendsEmailOnSuccess(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_POST = ["action" => "edit_user_prefs", "submit" => "", "oldpassword" => "12345"];
        $this->currentUser->method("get")->willReturn($this->users["john"]);
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
