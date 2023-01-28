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
        $this->view = $this->createMock(View::class);
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
        $this->view->expects($this->once())->method("render")->with(
            $this->equalTo("forgotten-form"),
            $this->equalTo([
                "actionUrl" => "?",
                "email" => "john@example.com",
            ])
        );
        $_POST["email"] = "john@example.com";
        $this->subject->defaultAction();
    }

    public function testPasswordForgottenActionEmptyEmail(): void
    {
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("Please enter your email address.")
        );
        $this->view->expects($this->once())->method("render")->with(
            $this->equalTo("forgotten-form"),
            $this->equalTo([
                "actionUrl" => "?",
                "email" => "",
            ])
        );
        $_POST["email"] = "";
        $this->subject->passwordForgottenAction();
    }

    public function testPasswordForgottenActionInvalidEmail(): void
    {
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("The given email address is invalid.")
        );
        $this->view->expects($this->once())->method("render")->with(
            $this->equalTo("forgotten-form"),
            $this->equalTo([
                "actionUrl" => "?",
                "email" => "invalid",
            ])
        );
        $_POST["email"] = "invalid";
        $this->subject->passwordForgottenAction();
    }

    public function testPasswordForgottenActionUnknownEmail(): void
    {
        $this->userRepository->method("findByEmail")->willReturn(null);
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("success"),
            $this->equalTo("If the email you specified exists in our system, we've sent a password reset link to it.")
        );
        $_POST["email"] = "jane@example.com";
        $this->subject->passwordForgottenAction();
    }

    public function testPasswordForgottenActionKnownEmail(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByEmail")->willReturn($john);
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("success"),
            $this->equalTo("If the email you specified exists in our system, we've sent a password reset link to it.")
        );
        $_POST["email"] = "john@example.com";
        $this->subject->passwordForgottenAction();
    }

    public function testResetPasswordActionUnknownUsername(): void
    {
        $_GET = ["username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("The entered validation code is invalid.")
        );
        $this->subject->resetPasswordAction();
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
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("The entered validation code is invalid.")
        );
        $this->subject->resetPasswordAction();
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
        $this->view->expects($this->once())->method("render")->with(
            $this->equalTo("change_password")
        );
        $this->subject->resetPasswordAction();
    }

    public function testChangePasswordActionUnknownUsername(): void
    {
        $_GET = ["username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("The entered validation code is invalid.")
        );
        $this->subject->changePasswordAction();
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
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("The entered validation code is invalid.")
        );
        $this->subject->changePasswordAction();
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
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("success"),
            $this->equalTo("An email has been sent to you with your user data.")
        );
        $this->subject->changePasswordAction();
    }
}
