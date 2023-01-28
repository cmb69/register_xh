<?php

/**
 * Copyright (c) 2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoginFormControllerTest extends TestCase
{
    /**
     * @MockObject
     */
    private $view;

    public function setUp(): void
    {
        $this->view = $this->createMock(View::class);
    }

    public function testLoginForm()
    {
        global $cf;

        $cf['uri']['word_separator'] = "|";
        $plugin_cf = XH_includeVar("./config/config.php", 'plugin_cf');
        $conf = $plugin_cf['register'];
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $subject = new LoginFormController(
            $conf,
            $lang,
            "/",
            "Foo",
            null,
            $this->view
        );
        $this->view->expects($this->once())->method("render")->with("loginform");
        $subject->execute();
    }

    public function testLoggedInForm()
    {
        global $cf;

        $cf['uri']['word_separator'] = "|";
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $subject = new LoginFormController(
            [],
            $lang,
            "/",
            "Foo",
            new User("jane", "", [], "Jane Doe", "jane@example.com", "activated"),
            $this->view
        );
        $this->view->expects($this->once())->method("render")->with("loggedin-area");
        $subject->execute();
    }
}