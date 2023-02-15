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

use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class ShowRegistrationFormTest extends TestCase
{
    /** @var ShowRegistrationForm */
    private $subject;

    /** @var View */
    private $view;

    public function setUp(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->subject = new ShowRegistrationForm($this->view);
    }

    public function testShowsRegistrationForm(): void
    {
        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("", ""));
        $response = ($this->subject)($request);
        Approvals::verifyHtml($response);
    }
}
