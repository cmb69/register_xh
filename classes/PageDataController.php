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
     * @var View
     */
    private $view;

    /**
     * @param array<string,string> $pageData
     */
    public function __construct(array $pageData, View $view)
    {
        $this->pageData = $pageData;
        $this->view = $view;
    }

    /**
     * @return void
     */
    public function execute()
    {
        global $sn, $su;

        echo $this->view->render("page_data", [
            "action" => "$sn?$su",
            "helpIcon" => new HtmlString(XH_helpIcon($this->view->text("hint_accessgroups"))),
            "accessGroups" => $this->pageData["register_access"],
        ]);
    }
}
