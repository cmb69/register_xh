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

class ShowUserPreferencesTest extends TestCase
{
    /** @var ShowUserPreferences */
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

    /** @var Request */
    private $request;

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
        $lang = $plugin_tx['register'];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./", $lang);
        $mailer = $this->createStub(Mailer::class);
        $logger = $this->createStub(Logger::class);
        $password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $this->currentUser,
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $mailer,
            $logger,
            $password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", "User-Preferences"));
    }

    public function testNoUser(): void
    {
        $this->currentUser->method("get")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testUserIsLocked(): void
    {
        $this->currentUser->method("get")->willReturn($this->users["jane"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSuccess(): void
    {
        $this->currentUser->method("get")->willReturn($this->users["john"]);
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
