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

class ShowUserPreferencesTest extends TestCase
{
    /** @var ShowUserPreferences */
    private $subject;

    /** @var array<string,User> */
    private $users;

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
        $conf = XH_includeVar("./config/config.php", "plugin_cf")["register"];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->csrfProtector->method("tokenInput")->willReturn("");
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./views/", $lang);
        $mailer = $this->createStub(Mailer::class);
        $logger = $this->createStub(Logger::class);
        $password = $this->createStub(Password::class);
        $this->subject = new HandleUserPreferences(
            $conf,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            $mailer,
            $logger,
            $password
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", "User-Preferences"));
        $this->request->method("registerAction")->willReturn("");
    }

    public function testNoUser(): void
    {
        $this->request->method("username")->willReturn("");
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString(
            "This page is only accessible for members with appropriate permissions.",
            $response->output()
        );
    }

    public function testUserIsLocked(): void
    {
        $this->request->method("username")->willReturn("jane");
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)($this->request);
        $this->assertStringContainsString("User Preferences for 'jane' can't be changed!", $response->output());
    }

    public function testSuccess(): void
    {
        $this->request->method("username")->willReturn("john");
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
