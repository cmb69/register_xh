<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 */

namespace Register;

use Register\Infra\View;

class PageDataController
{
    /** @var string */
    private $coreStyleFolder;

    /** @var string */
    private $helpText;

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
    public function __construct(string $coreStyleFolder, string $helpText, array $pageData, View $view)
    {
        $this->coreStyleFolder = $coreStyleFolder;
        $this->helpText = $helpText;
        $this->pageData = $pageData;
        $this->view = $view;
    }

    /**
     * @return void
     */
    public function execute()
    {
        /**
         * @var string $sn
         * @var string $su
         */
        global $sn, $su;

        echo $this->view->render("page_data", [
            "action" => "$sn?$su",
            "iconFilename" => $this->coreStyleFolder . "help_icon.png",
            "iconAlt" => $this->helpText,
            "accessGroups" => $this->pageData["register_access"],
        ]);
    }
}
