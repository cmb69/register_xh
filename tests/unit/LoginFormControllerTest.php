<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginFormControllerTest extends TestCase
{
    /**
     * @MockObject
     */
    private $view;

    public function setUp(): void
    {
        $this->view = $this->createMock(View::class);
    }

    public function testLoginForm()
    {
        $conf = [
            "allowed_register" => "true",
            "password_forgotten" => "true",
            "remember_user" => "true",
        ];
        $lang = [
            "forgot_password" => "forgot_password",
            "register" => "register",
        ];
        $subject = new LoginFormController(
            $conf,
            $lang,
            "/",
            "Foo",
            null,
            $this->view
        );
        $this->view->expects($this->once())->method("render")->with("loginform");
        $subject->execute();
    }

    public function testLoggedInForm()
    {
        $lang = [
            "user_prefs" => "user_prefs",
        ];
        $subject = new LoginFormController(
            [],
            $lang,
            "/",
            "Foo",
            new User("jane", "", [], "Jane Doe", "jane@example.com", "activated"),
            $this->view
        );
        $this->view->expects($this->once())->method("render")->with("loggedin-area");
        $subject->execute();
    }
}