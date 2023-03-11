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
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowPasswordForgottenFormTest extends TestCase
{
    /** @var ShowPasswordForgottenForm */
    private $subject;

    /** @var View&MockObject */
    private $view;

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

    public function testLoggedInUserIsRedirected(): void
    {
        $this->request->method("username")->willReturn("cmb");
        $response = ($this->subject)($this->request);
        $this->assertEquals("http://example.com/", $response->location());
    }

    public function testSuccess(): void
    {
        $_POST["email"] = "john@example.com";
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response->output());
    }
}
