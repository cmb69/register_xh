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
use Register\Infra\Logger;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class UnregisterUserTest extends TestCase
{
    /** @var HandleUserPreferences */
    private $subject;

    /** @var array<string,User> */
    private $users;

    /** @var CurrentUser */
    private $currentUser;

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
        $this->currentUser = $this->createStub(CurrentUser::class);
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./", $text);
        $mailer = $this->createStub(Mailer::class);
        $this->logger = $this->createMock(Logger::class);
        $this->password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $this->currentUser,
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $mailer,
            $this->logger,
            $this->password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "User-Preferences"));
    }

    public function testNoUser(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->currentUser->method("get")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testIsLocked(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => ""];
        $this->currentUser->method("get")->willReturn($this->users["jane"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testWrongPassword(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => "", "oldpassword" => "54321"];
        $this->currentUser->method("get")->willReturn($this->users["john"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->password->method("verify")->willReturn(false);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testCorrectPassword(): void
    {
        $_POST = ["action" => "edit_user_prefs", "delete" => "", "oldpassword" => "12345"];
        $this->currentUser->method("get")->willReturn($this->users["john"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->userRepository->expects($this->once())->method("delete")->willReturn(true);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->currentUser->method("logout")->with();
        $this->password->method("verify")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
