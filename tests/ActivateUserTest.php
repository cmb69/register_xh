<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

use Register\Value\User;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ActivateUserTest extends TestCase
{
    /** @var ActivateUser */
    private $subject;

    /** @var array<string,User> */
    private $users;

    /** @var View&MockObject */
    private $view;

    public function setUp(): void
    {
        $this->users = [
            "john" => new User("john", "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq", [], "John Doe", "john@example.com", ""),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "12345"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->subject = new ActivateUser(
            $conf,
            $this->userRepository,
            $this->view,
        );
    }

    public function testActivateUserActionNoUser(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "",
        ];
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserEmptyState(): void
    {
        $_GET = [
            "username" => "john",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserInvalidState(): void
    {
        $_GET = [
            "username" => "jane",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }

    public function testActivateUserSuccess(): void
    {
        $_GET = [
            "username" => "jane",
            "nonce" => "12345",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->userRepository->expects($this->once())->method("update");
        $response = ($this->subject)();
        Approvals::verifyHtml($response);
    }
}
