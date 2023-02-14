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

use Register\Infra\View;

class RegistrationControllerTest extends TestCase
{
    /**
     * @var RegistrationController
     */
    private $subject;

    /**
     * @var MockObject
     */
    private $view;

    public function setUp(): void
    {
        $plugin_tx = XH_includeVar("./languages/en.php", 'plugin_tx');
        $lang = $plugin_tx['register'];
        $this->view = new View("./", $lang);
        $this->subject = new RegistrationController(
            "",
            "",
            $this->view
        );
    }

    public function testdefaultAction(): void
    {
        $response = $this->subject->defaultAction();
        Approvals::verifyHtml($response);
    }
}
