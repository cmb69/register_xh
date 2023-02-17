<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH_includeVar;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;

use Register\Value\User;
use Register\Infra\CurrentUser;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class RegisterUserTest extends TestCase
{
    /** @var RegisterUser */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var array<string,User> */
    private $users;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $hash = "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq";
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->users = [
            "john" => new User("john", $hash, [], "John Doe", "john@example.com", ""),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "12345"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./", $text);
        $this->userRepository = $this->createMock(UserRepository::class);
        $mailService = $this->createStub(MailService::class);
        $this->subject = new HandleUserRegistration(
            $this->currentUser,
            $conf,
            $text,
            $this->view,
            $this->userRepository,
            $mailService
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("", ""));
    }

    public function testValidationError(): void
    {
        $_POST = ["action" => "register_user", "username" => ""];
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testExistingUser(): void
    {
        $_POST = [
            "action" => "register_user",
            "name" => "Jane Smith",
            "username" => "jane",
            "password1" => "test",
            "password2" => "test",
            "email" => "jane.smith@example.com",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testExistingEmail(): void
    {
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $_POST = [
            "action" => "register_user",
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "john@example.com",
        ];
        $this->userRepository->method("findByEmail")->willReturn($this->users["john"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSuccess(): void
    {
        $_SERVER["REMOTE_ADDR"] = "example.com";
        $_SERVER['SERVER_NAME'] = "example.com";
        $_POST = [
            "action" => "register_user",
            "name" => "John Smith",
            "username" => "js",
            "password1" => "test",
            "password2" => "test",
            "email" => "js@example.com",
        ];
        $this->userRepository->expects($this->once())->method("add")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
