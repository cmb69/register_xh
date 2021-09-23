<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PasswordForgottenControllerTest extends TestCase
{
    /**
     * @var PasswordForgottenController
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
        $conf = [
            "senderemail" => "webmaster@example.com",
        ];
        $lang = [
            "email" => "email",
            "emailtext1" => "emailtext1",
            "emailtext3" => "emailtext3",
            "err_email" => "err_email",
            "err_email_invalid" => "err_email_invalid",
            "err_status_invalid" => "err_status_invalid",
            "err_username_does_not_exist" => "err_username_does_not_exist",
            "name" => "name",
            "password" => "password",
            "reminderemailsubject" => "reminderemailsubject",
            "remindersent" => "remindersent",
            "remindersent_reset" => "remindersent_reset",
            "senderemail" => "senderemail",
            "username" => "username",
        ];
        $this->view = $this->createMock(View::class);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->subject = new ForgotPasswordController(
            $conf,
            $lang,
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
            $this->equalTo("err_email")
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
            $this->equalTo("err_email_invalid")
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
            $this->equalTo("remindersent_reset")
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
            $this->equalTo("remindersent_reset")
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
            $this->equalTo("err_username_does_not_exist")
        );
        $this->subject->resetPasswordAction();
    }

    public function testResetPasswordActionWrongNonce(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "54321",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("fail"),
            $this->equalTo("err_status_invalid")
        );
        $this->subject->resetPasswordAction();
    }

    public function testResetPasswordActionSuccess(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "12345",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
        $this->view->expects($this->once())->method("message")->with(
            $this->equalTo("success"),
            $this->equalTo("remindersent")
        );
        $this->subject->resetPasswordAction();
    }
}
