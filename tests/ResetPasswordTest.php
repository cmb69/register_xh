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

use Register\Value\User;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ResetPasswordTest extends TestCase
{
    /** @var ResetPassword */
    private $subject;

    /** @var View */
    private $view;

    /** @var UserRepository */
    private $userRepository;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->subject = new ResetPassword(
            1637449200,
            $this->view,
            $this->userRepository
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("", ""));
    }

    public function testUnknownUsername(): void
    {
        $_GET = ["username" => "colt"];
        $this->userRepository->method("findByUsername")->willReturn(null);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }

    public function testWrongMac(): void
    {
        $_GET = [
            "username" => "john",
            "time" => 1637449800,
            "mac" => "54321",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }

    public function testSuccess(): void
    {
        $_GET = [
            "username" => "john",
            "time" => 1637449800,
            "mac" => "a19916c64ceb8942def3ed8b8a612e9d8a3e50b2",
        ];
        $john = new User("john", "12345", [], "John Dow", "john@example.com", "");
        $this->userRepository->method("findByUsername")->willReturn($john);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }
}
