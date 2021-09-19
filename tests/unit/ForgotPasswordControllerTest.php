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

    public function setUp(): void
    {
        $conf = [];
        $lang = [
            "err_email" => "err_email",
            "err_email_invalid" => "err_email_invalid",
        ];
        $this->view = $this->createMock(View::class);
        $dbService = $this->createStub(DbService::class);
        $mailService = $this->createMock(MailService::class);
        $this->subject = new ForgotPasswordController($conf, $lang, $this->view, $dbService, $mailService);
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
}
