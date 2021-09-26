<?php

/**
 * Copyright 2011-2021 Christoph M. Becker
 *
 * This file is part of Register_XH.
 */

namespace Register;

class SystemCheckService
{
    /**
     * @var string
     */
    private $pluginsFolder;

    /**
     * @var string
     */
    private $pluginFolder;

    /**
     * @var array<string,string>
     */
    private $lang;

    /**
     * @var string
     */
    private $dataFolder;

    /**
     * @param array<string,string> $lang
     */
    public function __construct(string $pluginsFolder, array $lang, string $dataFolder)
    {
        $this->pluginsFolder = $pluginsFolder;
        $this->pluginFolder = "{$this->pluginsFolder}register";
        $this->lang = $lang;
        $this->dataFolder = $dataFolder;
    }

    /**
     * @return array<int,array{state:string,label:string,stateLabel:string}>
     */
    public function getChecks()
    {
        return array(
            $this->checkPhpVersion('7.0.2'),
            $this->checkExtension('session'),
            $this->checkXhVersion('1.7'),
            $this->checkWritability("$this->pluginFolder/css/"),
            $this->checkWritability("$this->pluginFolder/config/"),
            $this->checkWritability("$this->pluginFolder/languages/"),
            $this->checkWritability($this->dataFolder)
        );
    }

    /**
     * @param string $version
     * @return array{state:string,label:string,stateLabel:string}
     */
    private function checkPhpVersion($version)
    {
        $state = version_compare(PHP_VERSION, $version, 'ge') ? 'success' : 'fail';
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
        $state = extension_loaded($extension) ? 'success' : ($isMandatory ? 'fail' : 'warning');
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
        $state = version_compare(CMSIMPLE_XH_VERSION, "CMSimple_XH $version", 'ge') ? 'success' : 'fail';
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
        $state = is_writable($folder) ? 'success' : 'warning';
        $label = sprintf($this->lang['syscheck_writable'], $folder);
        $stateLabel = $this->lang["syscheck_$state"];
        return compact('state', 'label', 'stateLabel');
    }
}
