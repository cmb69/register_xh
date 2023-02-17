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
use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class PasswordForgottenTest extends TestCase
{
    /** @var HandlePasswordForgotten */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var View&MockObject */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var MailService&MockObject */
    private $mailService;

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
        $this->mailService = $this->createMock(MailService::class);
        $this->subject = new HandlePasswordForgotten(
            $this->currentUser,
            $conf,
            $text,
            1637449200,
            $this->view,
            $this->userRepository,
            $this->mailService
        );
        $this->request = $this->createStub(Request::class);
        $this->request->expects($this->any())->method("url")->willReturn(new Url("", ""));
    }

    public function testEmptyEmail(): void
    {
        $_POST = ["action" => "forgotten_password", "email" => ""];
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testInvalidEmail(): void
    {
        $_POST = ["action" => "forgotten_password", "email" => "invalid"];
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testUnknownEmail(): void
    {
        $_POST = ["action" => "forgotten_password", "email" => "jane@example.com"];
        $this->userRepository->method("findByEmail")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testKnownEmail(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $_POST = ["action" => "forgotten_password", "email" => "john@example.com"];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByEmail")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
