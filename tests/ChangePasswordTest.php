<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH_includeVar;

use ApprovalTests\Approvals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Register\Infra\CurrentUser;
use Register\Infra\FakeMailer;
use Register\Value\User;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ChangePasswordTest extends TestCase
{
    /** @var ChangePassword */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var View&MockObject */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var FakeMailer */
    private $mailer;

    /** @var Request&MockObject */
    private $request;

    public function setUp(): void
    {
        $this->currentUser = $this->createStub(CurrentUser::class);
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./", $text);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailer = new FakeMailer(false, $text);
        $this->subject = new HandlePasswordForgotten(
            $this->currentUser,
            $conf,
            $this->view,
            $this->userRepository,
            $this->mailer
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("/", ""));
        $this->request->expects($this->any())->method("time")->willReturn(1637449200);
    }

    public function testUnknownUsername(): void
    {
        $_GET = ["action" => "register_change_password", "username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testWrongMac(): void
    {
        $_GET = [
            "action" => "register_change_password",
            "username" => "john",
            "time" => 1637449800,
            "mac" => "54321",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSuccess(): void
    {
        $_GET = [
            "action" => "register_change_password",
            "username" => "john",
            "time" => 1637449800,
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ];
        $_POST = [
            "password1" => "admin",
            "password2" => "admin",
        ];
        $_SERVER['SERVER_NAME'] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSendsMailOnSuccess(): void
    {
        $_GET = [
            "action" => "register_change_password",
            "username" => "john",
            "time" => 1637449800,
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ];
        $_POST = [
            "password1" => "admin",
            "password2" => "admin",
        ];
        $_SERVER['SERVER_NAME'] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
        ($this->subject)($this->request);
        $this->assertEquals("john@example.com", $this->mailer->to());
        $this->assertEquals("Account data for example.com", $this->mailer->subject());
        Approvals::verifyHtml($this->mailer->message());
        $this->assertEquals(["From: postmaster@example.com"], $this->mailer->headers());
    }

    public function testReportsExpiration(): void
    {
        $_GET = [
            "action" => "register_change_password",
            "username" => "john",
            "time" => 1637445599,
            "mac" => "TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
        ];
        $_POST = [
            "password1" => "admin",
            "password2" => "admin",
        ];
        $_SERVER['SERVER_NAME'] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $this->userRepository->method("update")->willReturn(true);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
