<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\Logger;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;
use XH\CSRFProtection as CsrfProtector;

class UnregisterUserTest extends TestCase
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

    /** @var Logger */
    private $logger;

    /** @var Request */
    private $request;

    /** @var Password */
    private $password;

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
        $this->view = new View("./", $text);
        $mailer = $this->createStub(Mailer::class);
        $this->logger = $this->createMock(Logger::class);
        $this->password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $mailer,
            $this->logger,
            $this->password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "User-Preferences"));
    }

    public function testNoUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testIsLocked(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->request->method("username")->willReturn("jane");
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testWrongPassword(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => "", "oldpassword" => "54321"];
        $this->request->method("username")->willReturn("john");
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(false);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testCorrectPassword(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => "", "oldpassword" => "12345"];
        $this->request->method("username")->willReturn("john");
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->userRepository->expects($this->once())->method("delete")->willReturn(true);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
