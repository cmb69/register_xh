<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\Mailer;
use Register\Infra\Password;
use Register\Infra\Random;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowRegistrationFormTest extends TestCase
{
    /** @var HandleUserRegistration */
    private $subject;

    /** @var View */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    public function setUp(): void
    {
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
    }

    public function testShowsRegistrationForm(): void
    {
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", ""));
        $response = ($this->subject)($request);
        Approvals::verifyHtml($response->output());
    }
}
