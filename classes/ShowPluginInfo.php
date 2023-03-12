<?php

/**
 * Copyright (c) 2012-2023 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\DbService;
use Register\Infra\Request;
use Register\Infra\SystemChecker;
use Register\Infra\View;
use Register\Value\Response;

class ShowPluginInfo
{
    /** @var array<string,string> */
    private $text;

    /** @var DbService */
    private $dbService;

    /** @var SystemChecker */
    private $systemChecker;

    /** @var View */
    private $view;

    /** @param array<string,string> $text */
    public function __construct(
        array $text,
        DbService $dbService,
        SystemChecker $systemChecker,
        View $view
    ) {
        $this->text = $text;
        $this->dbService = $dbService;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(Request $request): Response
    {
        return Response::create($this->view->render('info', [
            'version' => REGISTER_VERSION,
            'checks' => $this->getChecks($request->pluginsFolder() . "register/"),
        ]));
    }

    /**
     * @return array<int,array{state:string,label:string,stateLabel:string}>
     */
    public function getChecks(string $pluginFolder)
    {
        return array(
            $this->checkPhpVersion('7.1.0'),
            $this->checkExtension('hash'),
            $this->checkExtension('session'),
            $this->checkXhVersion('1.7.0'),
            $this->checkWritability($pluginFolder . "css/"),
            $this->checkWritability($pluginFolder . "config/"),
            $this->checkWritability($pluginFolder . "languages/"),
            $this->checkWritability($this->dbService->dataFolder())
        );
    }

    /**
     * @param string $version
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkPhpVersion($version)
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? 'success' : 'fail';
        $label = sprintf($this->text['syscheck_phpversion'], $version);
        $stateLabel = $this->text["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $extension
     * @param bool $isMandatory
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkExtension($extension, $isMandatory = true)
    {
        $state = $this->systemChecker->checkExtension($extension) ? 'success' : ($isMandatory ? 'fail' : 'warning');
        $label = sprintf($this->text['syscheck_extension'], $extension);
        $stateLabel = $this->text["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $version
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkXhVersion($version)
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? 'success' : 'fail';
        $label = sprintf($this->text['syscheck_xhversion'], $version);
        $stateLabel = $this->text["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $folder
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkWritability($folder)
    {
        $state = $this->systemChecker->checkWritability($folder) ? 'success' : 'warning';
        $label = sprintf($this->text['syscheck_writable'], $folder);
        $stateLabel = $this->text["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }
}
