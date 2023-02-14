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
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowPasswordForgottenFormTest extends TestCase
{
    /** @var ShowPasswordForgottenForm */
    private $subject;

    /** @var View */
    private $view;

    /** @var Request */
    private $request;

    public function setUp(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->userRepository = $this->createStub(UserRepository::class);
        $this->mailService = $this->createMock(MailService::class);
        $this->subject = new ShowPasswordForgottenForm(
            $this->view
        );
        $this->request = $this->createStub(Request::class);
        $this->request->method("url")->willReturn(new Url("", ""));
    }

    public function testSuccess(): void
    {
        $_POST["email"] = "john@example.com";
        $response = ($this->subject)($this->request);
        Approvals::verifyHtml($response);
    }
}
