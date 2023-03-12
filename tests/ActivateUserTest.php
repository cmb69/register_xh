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
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class ActivateUserTest extends TestCase
{
    /** @var ActivateUser */
    private $subject;

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
        $this->view = new View("./views/", $text);
        $this->userRepository = $this->createMock(UserRepository::class);
        $mailer = $this->createStub(Mailer::class);
        $password = $this->createStub(Password::class);
        $this->subject = new HandleUserRegistration(
            $conf,
            $text,
            $random,
            $this->view,
            $this->userRepository,
            $mailer,
            $password
        );
        $this->request = $this->createStub(Request::class);
    }

    public function testActivateUserActionNoUser(): void
    {
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "john",
            "nonce" => "12345",
        ]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testActivateUserEmptyState(): void
    {
        $this->userRepository->method("findByUsername")->willReturn($this->users["john"]);
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "john",
            "nonce" => "12345",
        ]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testActivateUserInvalidState(): void
    {
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "54321",
        ]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }

    public function testActivateUserSuccess(): void
    {
        $this->userRepository->method("findByUsername")->willReturn($this->users["jane"]);
        $this->userRepository->expects($this->once())->method("update");
        $this->request->method("registerAction")->willReturn("activate");
        $this->request->method("activationParams")->willReturn([
            "username" => "jane",
            "nonce" => "12345",
        ]);
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
