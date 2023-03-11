<?php

/**
 * Copyright (c) 2021-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use ApprovalTests\Approvals;
use PHPUnit\Framework\TestCase;
use Register\Infra\Request;
use Register\Infra\Url;
use Register\Infra\UserRepository;
use Register\Infra\View;
use Register\Value\User;

class ShowLoginFormTest extends TestCase
{
    public function testLoginForm()
    {
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $userRepository = $this->createStub(UserRepository::class);
        $subject = new ShowLoginForm($conf, $text, $userRepository, new View("./views/", $text));

        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", "Foo"));
        $response = $subject($request);

        Approvals::verifyHtml($response->output());
    }

    public function testLoggedInForm()
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $text = $plugin_tx['register'];
        $user = new User("jane", "", [], "Jane Doe", "jane@example.com", "activated", "secret");
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method("findByUsername")->willReturn($user);
        $subject = new ShowLoginForm([], $text, $userRepository, new View("./views/", $text));

        $request = $this->createStub(Request::class);
        $request->method("url")->willReturn(new Url("/", "Foo"));
        $request->method("username")->willReturn("jane");
        $response = $subject($request);

        Approvals::verifyHtml($response->output());
    }
}
