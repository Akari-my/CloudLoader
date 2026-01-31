<?php

namespace Mellooh\CloudLoader\module;

use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\log\LogSettings;
use pocketmine\plugin\PluginManager;

class ModuleLoader{

    public function __construct(
        private PluginManager $pluginManager,
        private LoggerProxy $logger,
        private LogSettings $logs,
        private StagingArea $staging,
        private bool $cleanup
    ){}

    public function loadAll(ResolutionResult $resolution): void{
        $this->staging->reset();

        foreach($resolution->order as $module){
            if($this->pluginManager->getPlugin($module->name) !== null){
                continue;
            }
            $this->staging->stage($module->path, $module->name);
        }

        $loadErrorCount = 0;
        $this->pluginManager->loadPlugins($this->staging->path(), $loadErrorCount);

        foreach($resolution->order as $module){
            $plugin = $this->pluginManager->getPlugin($module->name);

            if($plugin === null){
                if($this->logs->errors){
                    $this->logger->error("Module not loaded: {$module->name}");
                }
                continue;
            }

            if(!$plugin->isEnabled()){
                $this->pluginManager->enablePlugin($plugin);
            }

            if($this->logs->modulesLoaded){
                $this->logger->info("Module loaded: {$module->name}");
            }
        }

        if($this->cleanup){
            $this->staging->cleanup();
        }
    }
}