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
use Register\Infra\CurrentUser;
use Register\Infra\MailService;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;

class ShowRegistrationFormTest extends TestCase
{
    /** @var HandleUserRegistration */
    private $subject;

    /** @var CurrentUser&MockObject */
    private $currentUser;

    /** @var View */
    private $view;

    /** @var UserRepository&MockObject */
    private $userRepository;

    public function setUp(): void
    {
        $this->currentUser = $this->createStub(CurrentUser::class);
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $this->view = new View("./", $text);
        $this->userRepository = $this->createMock(UserRepository::class);
        $mailService = $this->createStub(MailService::class);
        $this->subject = new HandleUserRegistration(
            $this->currentUser,
            $conf,
            $text,
            $this->view,
            $this->userRepository,
            $mailService
        );
    }

    public function testShowsRegistrationForm(): void
    {
        $request = $this->createStub(Request::class);
        $request->expects($this->any())->method("url")->willReturn(new Url("", ""));
        $response = ($this->subject)($request);
        Approvals::verifyHtml($response->output());
    }
}
