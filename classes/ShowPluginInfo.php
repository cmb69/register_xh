<?php

/**
 * Copyright (c) 2012-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

use Register\Infra\SystemChecker;
use Register\Infra\View;

class ShowPluginInfo
{
    /** @var string */
    private $pluginsFolder;

    /** @var string */
    private $pluginFolder;

    /** @var array<string,string> */
    private $lang;

    /** @var string */
    private $dataFolder;

    /** @var SystemChecker */
    private $systemChecker;

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
        $this->pluginFolder = $this->pluginsFolder . "register/";
        $this->lang = $lang;
        $this->dataFolder = $dataFolder;
        $this->systemChecker = $systemChecker;
        $this->view = $view;
    }

    public function __invoke(): string
    {
        return $this->view->render('info', [
            'version' => Plugin::VERSION,
            'checks' => $this->getChecks(),
        ]);
    }

    /**
     * @return array<int,array{state:string,label:string,stateLabel:string}>
     */
    public function getChecks()
    {
        return array(
            $this->checkPhpVersion('7.0.2'),
            $this->checkExtension('hash'),
            $this->checkExtension('session'),
            $this->checkXhVersion('1.7.0'),
            $this->checkWritability($this->pluginFolder . "css/"),
            $this->checkWritability($this->pluginFolder . "config/"),
            $this->checkWritability($this->pluginFolder . "languages/"),
            $this->checkWritability($this->dataFolder)
        );
    }

    /**
     * @param string $version
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkPhpVersion($version)
    {
        $state = $this->systemChecker->checkVersion(PHP_VERSION, $version) ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_phpversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
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
        $label = sprintf($this->lang['syscheck_extension'], $extension);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $version
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkXhVersion($version)
    {
        $state = $this->systemChecker->checkVersion(CMSIMPLE_XH_VERSION, "CMSimple_XH $version") ? 'success' : 'fail';
        $label = sprintf($this->lang['syscheck_xhversion'], $version);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }

    /**
     * @param string $folder
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkWritability($folder)
    {
        $state = $this->systemChecker->checkWritability($folder) ? 'success' : 'warning';
        $label = sprintf($this->lang['syscheck_writable'], $folder);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }
}
