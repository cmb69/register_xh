<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\DbService;
use Register\Infra\SystemChecker;
use Register\Infra\View;
use Register\Value\Response;

class ShowPluginInfo
{
    /** @var string */
    private $pluginFolder;

    /** @var DbService */
    private $dbService;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var View */
    private $view;

    public function __construct(
        string $pluginFolder,
        DbService $dbService,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->pluginFolder = $pluginFolder;
        $this->dbService = $dbService;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(): Response
    {
        return Response::create($this->view->render("info", [
            "version" => REGISTER_VERSION,
            "checks" => $this->getChecks($this->pluginFolder),
        ]));
    }

    /** @return list<array{class:string,key:string,arg:string}> */
    public function getChecks(string $pluginFolder): array
    {
        return [
            $this->checkPhpVersion("7.1.0"),
            $this->checkExtension("hash"),
            $this->checkXhVersion("1.7.0"),
            $this->checkWritability($pluginFolder . "css/"),
            $this->checkWritability($pluginFolder . "config/"),
            $this->checkWritability($pluginFolder . "languages/"),
            $this->checkWritability($this->dbService->dataFolder())
        ];
    }

    /** @return array{class:string,key:string,arg:string} */
    private function checkPhpVersion(string $version): array
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version);
        return [
            "class" => $state ? "xh_success" : "xh_fail",
            "key" => $state ? "syscheck_phpversion" : "syscheck_phpversion_no",
            "arg" => $version,
        ];
    }

    /** @return array{class:string,key:string,arg:string} */
    private function checkExtension(string $extension): array
    {
        $state = $this->systemChecker->checkExtension($extension);
        return [
            "class" => $state ? "xh_success" : "xh_fail",
            "key" => $state ? "syscheck_extension" : "syscheck_extension_no",
            "arg" => $extension,
        ];
    }

    /** @return array{class:string,key:string,arg:string} */
    private function checkXhVersion(string $version): array
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version");
        return [
            "class" => $state ? "xh_success" : "xh_fail",
            "key" => $state ? "syscheck_xhversion" : "syscheck_xhversion_no",
            "arg" => $version,
        ];
    }

    /** @return array{class:string,key:string,arg:string} */
    private function checkWritability(string $folder): array
    {
        $state = $this->systemChecker->checkWritability($folder);
        return [
            "class" => $state ? "xh_success" : "xh_warning",
            "key" => $state ? "syscheck_writable" : "syscheck_writable_no",
            "arg" => $folder,
        ];
    }
}
