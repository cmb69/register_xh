<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;

use XH\CSRFProtection as CsrfProtector;

use Register\Value\User;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowUserPreferencesTest extends TestCase
{
    /** @var ShowUserPreferences */
    private $subject;

    /** @var array<string,User> */
    private $users;

    /** @var Session */
    private $session;

    /** @var CsrfProtector */
    private $csrfProtector;

    /** @var UserRepository */
    private $userRepository;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        $this->users = [
            "john" => new User("john", "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq", [], "John Doe", "john@example.com", "activated"),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "locked"),
        ];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->session = $this->createStub(Session::class);
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./", $lang);
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->subject = new ShowUserPreferences(
            $lang,
            $this->session,
            $this->csrfProtector,
            $this->userRepository,
            $this->view,
            "/User-Preferences"
        );
    }

    public function testNoUser(): void
    {
        $_SESSION = ["username" => "john"];
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }

    public function testUserIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }

    public function testSuccess(): void
    {
        $_SESSION = ["username" => "john"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }
}
