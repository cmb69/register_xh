<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH_includeVar;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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
        $this->view = $this->createMock(View::class);
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
        $this->view->expects($this->once())->method("render")->with("registerform");
        $this->subject->defaultAction();
    }

    public function testRegisterActionValidationError(): void
    {
        $this->validationService->method("validateUser")->willReturn(["error"]);
        $this->view->expects($this->exactly(2))->method("render")->withConsecutive(
            ["error"],
            ["registerform"]
        );
        $this->subject->registerUserAction();
    }

    public function testRegisterActionExistingUser(): void
    {
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->registerUserAction();
    }

    public function testRegisterActionExistingEmail(): void
    {
        global $cf;

        $cf['uri']['word_separator'] = "|";
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $this->userRepository->method("findByEmail")->willReturn($this->users["john"]);
        $this->view->expects($this->once())->method("message")->with("success");
        $this->subject->registerUserAction();
    }

    public function testRegisterActionSuccess(): void
    {
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $this->userRepository->expects($this->once())->method("add")->willReturn(true);
        $this->view->expects($this->once())->method("message")->with("success");
        $this->subject->registerUserAction();
    }

    public function testActivateUserActionNoUser(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "",
        ];
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->activateUserAction();
    }

    public function testActivateUserEmptyState(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->activateUserAction();
    }

    public function testActivateUserInvalidState(): void
    {
        $_GET = [
            "username" => "jane",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->activateUserAction();
    }

    public function testActivateUserSuccess(): void
    {
        $_GET = [
            "username" => "jane",
            "nonce" => "12345",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->userRepository->expects($this->once())->method("update");
        $this->view->expects($this->once())->method("message")->with("success");
        $this->subject->activateUserAction();
    }
}
