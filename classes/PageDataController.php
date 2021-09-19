<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

namespace Register;

class PageDataController
{
    /**
     * @var array<string,string>
     */
    private $pageData;

    /**
     * @param array<string,string> $pageData
     */
    public function __construct(array $pageData)
    {
        $this->pageData = $pageData;
    }

    /**
     * @return void
     */
    public function execute()
    {
        global $sn, $su;

        $view = new View();
        $view->render("page_data", [
            "action" => "$sn?$su",
            "helpIcon" => new HtmlString(XH_helpIcon($view->text("hint_accessgroups"))),
            "accessGroups" => $this->pageData["register_access"],
        ]);
    }
}
