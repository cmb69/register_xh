<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Register\Value\User;
use Register\Infra\Mailer;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ResetPasswordTest extends TestCase
{
    /** @var ResetPassword */
    private $subject;

   /** @var View&MockObject */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var Request&MockObject */
    private $request;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./", $text);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailer = $this->createMock(Mailer::class);
        $this->subject = new HandlePasswordForgotten(
            $conf,
            $this->view,
            $this->userRepository,
            $this->mailer
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("/", ""));
        $this->request->method("time")->willReturn(1637449200);
    }

    public function testUnknownUsername(): void
    {
        $_GET = ["action" => "registerResetPassword", "username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testWrongMac(): void
    {
        $_GET = [
            "action" => "registerResetPassword",
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
            "action" => "registerResetPassword",
            "username" => "john",
            "time" => 1637449800,
            "mac" => "3pjbpRHFI9OO3gUHV42CHT3IHL8",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testReportsExpiration(): void
    {
        $_GET = [
            "action" => "registerResetPassword",
            "username" => "john",
            "time" => 1637445599,
            "mac" => "TLIb1A2yKWBs_ZGmC0l0V4w6bS8",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
