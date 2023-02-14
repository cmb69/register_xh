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
use Register\Infra\View;

class ShowLoginFormTest extends TestCase
{
    public function testLoginForm()
    {
        global $cf;

        $cf['uri']['word_separator'] = "|";
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $subject = new ShowLoginForm($conf, $lang, "/", "Foo", new View("./", $lang));

        $response = $subject(null);

        Approvals::verifyHtml($response);
    }

    public function testLoggedInForm()
    {
        global $cf;

        $cf['uri']['word_separator'] = "|";
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $user = new User("jane", "", [], "Jane Doe", "jane@example.com", "activated");
        $subject = new ShowLoginForm([], $lang, "/", "Foo", new View("./", $lang));

        $response = $subject($user);

        Approvals::verifyHtml($response);
    }
}