<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH_includeVar;

use ApprovalTests\Approvals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use Register\Value\User;
use Register\Infra\MailService;

class RegistrationControllerTest extends TestCase
{
    /**
     * @var RegistrationController
     */
    private $subject;

    /**
     * @var array<string,User>
     */
    private $users;

    /**
     * @var Stub
     */
    private $validationService;

    /**
     * @var MockObject
     */
    private $view;

    /**
     * @var MockObject
     */
    private $userRepository;

    public function setUp(): void
    {
        $this->users = [
            "john" => new User("john", "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq", [], "John Doe", "john@example.com", ""),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "12345"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->validationService = $this->createStub(ValidationService::class);
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createMock(UserRepository::class);
        $mailService = $this->createStub(MailService::class);
        $this->subject = new RegistrationController(
            $conf,
            $lang,
            $this->validationService,
            $this->view,
            $this->userRepository,
            $mailService
        );
    }

    public function testdefaultAction(): void
    {
        $response = $this->subject->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testRegisterActionValidationError(): void
    {
        $this->validationService->method("validateUser")->willReturn(["error"]);
        $response = $this->subject->registerUserAction();
        Approvals::verifyHtml($response);
    }

    public function testRegisterActionExistingUser(): void
    {
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = $this->subject->registerUserAction();
        Approvals::verifyHtml($response);
    }

    public function testRegisterActionExistingEmail(): void
    {
        global $cf;

        $cf['uri']['word_separator'] = "|";
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $this->userRepository->method("findByEmail")->willReturn($this->users["john"]);
        $response = $this->subject->registerUserAction();
        Approvals::verifyHtml($response);
    }

    public function testRegisterActionSuccess(): void
    {
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $this->userRepository->expects($this->once())->method("add")->willReturn(true);
        $response = $this->subject->registerUserAction();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserActionNoUser(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "",
        ];
        $response = $this->subject->activateUserAction();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserEmptyState(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = $this->subject->activateUserAction();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserInvalidState(): void
    {
        $_GET = [
            "username" => "jane",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = $this->subject->activateUserAction();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserSuccess(): void
    {
        $_GET = [
            "username" => "jane",
            "nonce" => "12345",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->userRepository->expects($this->once())->method("update");
        $response = $this->subject->activateUserAction();
        Approvals::verifyHtml($response);
    }
}
