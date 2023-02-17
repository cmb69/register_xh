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

use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ResetPasswordTest extends TestCase
{
    /** @var ResetPassword */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

   /** @var View&MockObject */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var Request&MockObject */
    private $request;

    public function setUp(): void
    {
        $this->currentUser = $this->createStub(CurrentUser::class);
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->subject = new HandlePasswordForgotten(
            $this->currentUser,
            $conf,
            $lang,
            1637449200,
            $this->view,
            $this->userRepository,
            $this->mailService
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("", ""));
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
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
            "mac" => "a19916c64ceb8942def3ed8b8a612e9d8a3e50b2",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
