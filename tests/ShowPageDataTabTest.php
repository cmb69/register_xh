<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

namespace Register;

use PHPUnit\Framework\TestCase;
use ApprovalTests\Approvals;

use Register\Infra\View;

class ShowPageDataTabTest extends TestCase
{
    public function testRendersPageDataTab(): void
    {
        $sut = new ShowPageDataTab(
            "../../assets/css/",
            "Help",
            new View("./", XH_includeVar("./languages/en.php", "plugin_tx")["register"])
        );
        $response = $sut(["register_access" => "cmb"], "./?SomePage");
        Approvals::verifyHtml($response);
    }
}
