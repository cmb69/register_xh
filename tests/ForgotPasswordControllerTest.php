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
use Register\Infra\UserRepository;

class ForgotPasswordControllerTest extends TestCase
{
    /**
     * @var ForgotPasswordController
     */
    private $subject;

    /**
     * @var MockObject
     */
    private $view;

    /**
     * @var Stub
     */
    private $userRepository;

    /**
     * @var MockObject
     */
    private $mailService;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->subject = new ForgotPasswordController(
            $conf,
            $lang,
            1637449200,
            $this->view,
            $this->userRepository,
            $this->mailService
        );
    }

    public function testDefaultAction(): void
    {
        $_POST["email"] = "john@example.com";
        $response = $this->subject->defaultAction();
        Approvals::verifyHtml($response);
    }

    public function testPasswordForgottenActionEmptyEmail(): void
    {
        $_POST["email"] = "";
        $response = $this->subject->passwordForgottenAction();
        Approvals::verifyHtml($response);
    }

    public function testPasswordForgottenActionInvalidEmail(): void
    {
        $_POST["email"] = "invalid";
        $response = $this->subject->passwordForgottenAction();
        Approvals::verifyHtml($response);
    }

    public function testPasswordForgottenActionUnknownEmail(): void
    {
        $this->userRepository->method("findByEmail")->willReturn(null);
        $_POST["email"] = "jane@example.com";
        $response = $this->subject->passwordForgottenAction();
        Approvals::verifyHtml($response);
    }

    public function testPasswordForgottenActionKnownEmail(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByEmail")->willReturn($john);
        $_POST["email"] = "john@example.com";
        $response = $this->subject->passwordForgottenAction();
        Approvals::verifyHtml($response);
    }

    public function testResetPasswordActionUnknownUsername(): void
    {
        $_GET = ["username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $response = $this->subject->resetPasswordAction();
        Approvals::verifyHtml($response);
    }

    public function testResetPasswordActionWrongMac(): void
    {
        $_GET = [
            "username" => "john",
            "time" => 1637449800,
            "mac" => "54321",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = $this->subject->resetPasswordAction();
        Approvals::verifyHtml($response);
    }

    public function testResetPasswordActionSuccess(): void
    {
        $_GET = [
            "username" => "john",
            "time" => 1637449800,
            "mac" => "oZkWxkzriULe8-2LimEunYo-ULI",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = $this->subject->resetPasswordAction();
        Approvals::verifyHtml($response);
    }

    public function testChangePasswordActionUnknownUsername(): void
    {
        $_GET = ["username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $response = $this->subject->changePasswordAction();
        Approvals::verifyHtml($response);
    }

    public function testChangePasswordActionWrongMac(): void
    {
        $_GET = [
            "username" => "john",
            "time" => 1637449800,
            "mac" => "54321",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = $this->subject->changePasswordAction();
        Approvals::verifyHtml($response);
    }

    public function testChangePasswordActionSuccess(): void
    {
        $_GET = [
            "username" => "john",
            "time" => 1637449800,
            "mac" => "oZkWxkzriULe8-2LimEunYo-ULI",
        ];
        $_POST = [
            "password1" => "admin",
            "password2" => "admin",
        ];
        $_SERVER['SERVER_NAME'] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
        $response = $this->subject->changePasswordAction();
        Approvals::verifyHtml($response);
    }
}
