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
use Register\Infra\CurrentUser;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowPasswordForgottenFormTest extends TestCase
{
    /** @var ShowPasswordForgottenForm */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var View&MockObject */
    private $view;

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

    public function testLoggedInUserIsRedirected(): void
    {
        $this->currentUser->method("get")->willReturn(new User("", "", [], "", "", ""));
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
