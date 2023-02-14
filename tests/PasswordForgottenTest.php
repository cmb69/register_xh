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
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class PasswordForgottenTest extends TestCase
{
    /** @var PasswordForgotten */
    private $subject;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var MailService */
    private $mailService;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->subject = new PasswordForgotten(
            $conf,
            $lang,
            1637449200,
            $this->view,
            $this->userRepository,
            $this->mailService
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("", ""));
    }

    public function testEmptyEmail(): void
    {
        $_POST["email"] = "";
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }

    public function testInvalidEmail(): void
    {
        $_POST["email"] = "invalid";
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }

    public function testUnknownEmail(): void
    {
        $this->userRepository->method("findByEmail")->willReturn(null);
        $_POST["email"] = "jane@example.com";
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }

    public function testKnownEmail(): void
    {
        $_SERVER["SERVER_NAME"] = "example.com";
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByEmail")->willReturn($john);
        $_POST["email"] = "john@example.com";
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }
}
