<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use XH\CSRFProtection as CsrfProtector;

use Register\Value\User;
use Register\Logic\ValidationService;
use Register\Infra\Logger;
use Register\Infra\LoginManager;
use Register\Infra\MailService;
use Register\Infra\Session;
use Register\Infra\UserRepository;
use Register\Infra\View;

class UserPrefsControllerTest extends TestCase
{
    /**
     * @var UserPrefsControllerController
     */
    private $subject;

    /**
     * @var array<string,User>
     */
    private $users;

    /** @var Session|MockObject */
    private $session;

    /**
     * @var MockObject
     */
    private $csrfProtector;

    /**
     * @var MockObject
     */
    private $userRepository;

    /**
     * @var MockObject
     */
    private $view;

    /**
     * @var MockObject
     */
    private $loginManager;

    /**
     * @var MockObject
     */
    private $logger;

    public function setUp(): void
    {
        $this->users = [
            "john" => new User("john", "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq", [], "John Doe", "john@example.com", "activated"),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "locked"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->session = $this->createStub(Session::class);
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $validationService = $this->createStub(ValidationService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = new View("./", $lang);
        $mailService = $this->createStub(MailService::class);
        $this->loginManager = $this->createStub(LoginManager::class);
        $this->logger = $this->createMock(Logger::class);
        $this->subject = new UserPrefsController(
            $conf,
            $lang,
            $this->session,
            $this->csrfProtector,
            $validationService,
            $this->userRepository,
            $this->view,
            $mailService,
            $this->loginManager,
            $this->logger,
            "/User-Preferences"
        );
    }

    public function testDefaultActionNoUser(): void
    {
        $_SESSION = ["username" => "john"];
        $response = $this->subject->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testDefaultActionUserIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = $this->subject->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testDefaultAction(): void
    {
        $_SESSION = ["username" => "john"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = $this->subject->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testEditActionNoUser(): void
    {
        $_SESSION['username'] = "cmb";
        $this->csrfProtector->expects($this->once())->method("check");
        $response = $this->subject->editAction();
        Approvals::verifyHtml($response);
    }

    public function testEditActionIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = $this->subject->editAction();
        Approvals::verifyHtml($response);
    }

    public function testEditActionWrongPassword(): void
    {
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "54321"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = $this->subject->editAction();
        Approvals::verifyHtml($response);
    }

    public function testEditActionCorrectPassword(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $_SERVER["REMOTE_ADDR"] = "127.0.0.1";
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "12345"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->userRepository->expects($this->once())->method("update")->willReturn(true);
        $response = $this->subject->editAction();
        Approvals::verifyHtml($response);
    }

    public function testDeleteActionNoUser(): void
    {
        $this->csrfProtector->expects($this->once())->method("check");
        $response = $this->subject->deleteAction();
        Approvals::verifyHtml($response);
    }

    public function testDeleteActionIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = $this->subject->deleteAction();
        Approvals::verifyHtml($response);
    }

    public function testDeleteActionWrongPassword(): void
    {
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "54321"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $response = $this->subject->deleteAction();
        Approvals::verifyHtml($response);
    }

    public function testDeleteActionCorrectPassword(): void
    {
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "12345"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->userRepository->expects($this->once())->method("delete")->willReturn(true);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->loginManager->expects($this->once())->method("logout")->with();
        $response = $this->subject->deleteAction();
        Approvals::verifyHtml($response);
    }
}
