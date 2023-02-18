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
use Register\Infra\CurrentUser;
use Register\Value\User;
use Register\Infra\Mailer;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ActivateUserTest extends TestCase
{
    /** @var ActivateUser */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var array<string,User> */
    private $users;

    /** @var View&MockObject */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    /** @var Request&MockObject */
    private $request;

    public function setUp(): void
    {
        $hash = "\$2y\$10\$f4ldVDiVXTkNrcPmBdbW7.g/.mw5GOEqBid650oN9hE56UC28aXSq";
        $this->currentUser = $this->createStub(CurrentUser::class);
        $this->users = [
            "john" => new User("john", $hash, [], "John Doe", "john@example.com", "", "secret"),
            "jane" => new User("jane", "", [], "Jane Doe", "jane@example.com", "12345", "secret"),
        ];
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $random = $this->createStub(Random::class);
        $random->method("bytes")->willReturn("0123456789ABCDEF");
        $this->view = new View("./", $text);
        $this->userRepository = $this->createMock(UserRepository::class);
        $mailer = $this->createStub(Mailer::class);
        $this->subject = new HandleUserRegistration(
            $this->currentUser,
            $conf,
            $text,
            $random,
            $this->view,
            $this->userRepository,
            $mailer
        );
        $this->request = $this->createStub(Request::class);
    }

    public function testActivateUserActionNoUser(): void
    {
        $_GET = [
            "action" => "register_activate_user",
            "username" => "john",
            "nonce" => "",
        ];
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testActivateUserEmptyState(): void
    {
        $_GET = [
            "action" => "register_activate_user",
            "username" => "john",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testActivateUserInvalidState(): void
    {
        $_GET = [
            "action" => "register_activate_user",
            "username" => "jane",
            "nonce" => "",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testActivateUserSuccess(): void
    {
        $_GET = [
            "action" => "register_activate_user",
            "username" => "jane",
            "nonce" => "12345",
        ];
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->userRepository->expects($this->once())->method("update");
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
