<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;

use Register\Value\User;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\View;

class ShowLoginFormTest extends TestCase
{
    public function testLoginForm()
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $subject = new ShowLoginForm($conf, $lang, new View("./", $lang));

        $request = $this->createStub(Request::class);
        $request->expects($this->any())->method("url")->willReturn(new Url("/", "Foo"));
        $response = $subject(null, $request);

        Approvals::verifyHtml($response->output());
    }

    public function testLoggedInForm()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $user = new User("jane", "", [], "Jane Doe", "jane@example.com", "activated");
        $subject = new ShowLoginForm([], $lang, new View("./", $lang));

        $request = $this->createStub(Request::class);
        $request->expects($this->any())->method("url")->willReturn(new Url("/", "Foo"));
        $response = $subject($user, $request);

        Approvals::verifyHtml($response->output());
    }
}