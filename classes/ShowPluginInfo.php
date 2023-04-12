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
        return Response::create($this->view->render('info', [
            'version' => REGISTER_VERSION,
            'checks' => $this->getChecks($this->pluginFolder),
        ]));
    }

    /**
     * @return list<array{class:string,key:string,arg:string,statekey:string}>
     */
    public function getChecks(string $pluginFolder)
    {
        return [
            $this->checkPhpVersion('7.1.0'),
            $this->checkExtension('hash'),
            $this->checkExtension('session'),
            $this->checkXhVersion('1.7.0'),
            $this->checkWritability($pluginFolder . "css/"),
            $this->checkWritability($pluginFolder . "config/"),
            $this->checkWritability($pluginFolder . "languages/"),
            $this->checkWritability($this->dbService->dataFolder())
        ];
    }

    /**
     * @param string $version
     * @return array{class:string,key:string,arg:string,statekey:string}
     */
    private function checkPhpVersion($version)
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? 'success' : 'fail';
        return [
            "class" => "xh_$state",
            "key" => "syscheck_phpversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /**
     * @param string $extension
     * @param bool $isMandatory
     * @return array{class:string,key:string,arg:string,statekey:string}
     */
    private function checkExtension($extension, $isMandatory = true)
    {
        $state = $this->systemChecker->checkExtension($extension) ? 'success' : ($isMandatory ? 'fail' : 'warning');
        return [
            "class" => "xh_$state",
            "key" => "syscheck_extension",
            "arg" => $extension,
            "statekey" => "syscheck_$state",
        ];
    }

    /**
     * @param string $version
     * @return array{class:string,key:string,arg:string,statekey:string}
     */
    private function checkXhVersion($version)
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? 'success' : 'fail';
        return [
            "class" => "xh_$state",
            "key" => "syscheck_xhversion",
            "arg" => $version,
            "statekey" => "syscheck_$state",
        ];
    }

    /**
     * @param string $folder
     * @return array{class:string,key:string,arg:string,statekey:string}
     */
    private function checkWritability($folder)
    {
        $state = $this->systemChecker->checkWritability($folder) ? 'success' : 'warning';
        return [
            "class" => "xh_$state",
            "key" => "syscheck_writable",
            "arg" => $folder,
            "statekey" => "syscheck_$state",
        ];
    }
}
