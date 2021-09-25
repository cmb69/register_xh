<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use XH\CSRFProtection as CsrfProtector;

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

    public function setUp(): void
    {
        $this->users = [
            "john" => new User("john", "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq", [], "John Doe", "john@example.com", "activated"),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "locked"),
        ];
        $conf = [
            "senderemail" => "senderemail",
        ];
        $lang = [
            "email" => "email",
            "emailprefsupdated" => "emailprefsupdated",
            "err_cannot_write_csv" => "err_cannot_write_csv",
            "err_old_password_wrong" => "err_old_password_wrong",
            "err_username_does_not_exist" => "err_username_does_not_exist",
            "fromip" => "fromip",
            "name" => "name",
            "prefsemailsubject" => "prefsemailsubject",
            "prefsupdated" => "prefsupdated",
            "user_locked" => "user_locked",
            "username" => "username",
        ];
        $this->csrfProtector = $this->createMock(CsrfProtector::class);
        $validationService = $this->createStub(ValidationService::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->view = $this->createMock(View::class);
        $mailService = $this->createStub(MailService::class);
        $this->subject = new UserPrefsController(
            $conf,
            $lang,
            $this->csrfProtector,
            $validationService,
            $this->userRepository,
            $this->view,
            $mailService
        );
    }

    public function testDefaultActionNoUser(): void
    {
        $_SESSION = ["username" => "john"];
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->defaultAction();
    }

    public function testDefaultActionUserIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->defaultAction();
    }

    public function testDefaultAction(): void
    {
        $_SESSION = ["username" => "john"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->view->expects($this->once())->method("render")->with("userprefs-form");
        $this->subject->defaultAction();
    }

    public function testEditActionNoUser(): void
    {
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->editAction();
    }

    public function testEditActionIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->editAction();
    }

    public function testEditActionWrongPassword(): void
    {
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "54321"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->editAction();
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
        $this->subject->editAction();
    }

    public function testDeleteActionNoUser(): void
    {
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->deleteAction();
    }

    public function testDeleteActionIsLocked(): void
    {
        $_SESSION = ["username" => "jane"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->deleteAction();
    }

    public function testDeleteActionWrongPassword(): void
    {
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "54321"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->deleteAction();
    }

    public function testDeleteActionCorrectPassword(): void
    {
        $_SESSION = ["username" => "john"];
        $_POST = ["oldpassword" => "12345"];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->userRepository->expects($this->once())->method("delete")->willReturn(false);
        $this->csrfProtector->expects($this->once())->method("check");
        $this->view->expects($this->once())->method("message")->with("fail");
        $this->subject->deleteAction();
    }
}
