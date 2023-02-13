<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class InfoController
{
    /** @var string */
    private $pluginsFolder;

    /** @var array<string,string> */
    private $lang;

    /** @var string */
    private $dataFolder;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var SystemCheckService */
    private $systemCheckService;

    /**
     * @var View
     */
    private $view;

    /** @param array<string,string> $lang */
    public function __construct(
        string $pluginsFolder,
        array $lang,
        string $dataFolder,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->pluginsFolder = $pluginsFolder;
        $this->lang = $lang;
        $this->dataFolder = $dataFolder;
        $this->systemChecker = $systemChecker;
        $this->systemCheckService = new SystemCheckService(
            $this->pluginsFolder,
            $this->lang,
            $this->dataFolder,
            $this->systemChecker
        );
        $this->view = $view;
    }

    public function execute(): string
    {
        return $this->view->render('info', [
            'version' => Plugin::VERSION,
            'checks' => $this->systemCheckService->getChecks(),
        ]);
    }
}
