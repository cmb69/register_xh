<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use XH_includeVar;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\FakeMailer;
use Register\Value\User;
use Register\Infra\Mailer;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class PasswordForgottenTest extends TestCase
{
    /** @var HandlePasswordForgotten */
    private $subject;

    /** @var View&MockObject */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var Mailer&MockObject */
    private $mailer;

    /** @var Request&MockObject */
    private $request;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./views/", $text);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailer = new FakeMailer(false, $text);
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
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByEmail")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testSendsMailOnSuccess(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $_POST = ["action" => "forgotten_password", "email" => "john@example.com"];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "", "secret");
        $this->userRepository->method("findByEmail")->willReturn($john);
        ($this->subject)($this->request);
        $this->assertEquals("john@example.com", $this->mailer->to());
        $this->assertEquals("Account data for example.com", $this->mailer->subject());
        Approvals::verifyHtml($this->mailer->message());
        $this->assertEquals(["From: postmaster@example.com"], $this->mailer->headers());
    }
}
