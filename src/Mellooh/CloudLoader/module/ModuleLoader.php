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
        private string $strategy){}

    public function stageAll(ResolutionResult $resolution, array $alreadyPresent): int{
        $count = 0;

        foreach($resolution->order as $module){
            if(isset($alreadyPresent[$module->name])){
                continue;
            }
            if($this->pluginManager->getPlugin($module->name) !== null){
                continue;
            }
            $this->staging->stage($module->path, $module->name, $this->strategy);
            $count++;
        }

        return $count;
    }

    public function loadFromStaging(string $stagingPath): void{
        $loadErrorCount = 0;
        $this->pluginManager->loadPlugins($stagingPath, $loadErrorCount);
    }

    public function enableOrdered(ResolutionResult $resolution): int{
        $enabled = 0;

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
                $enabled++;
            }

            if($this->logs->modulesLoaded){
                $this->logger->info("Module loaded: {$module->name}");
            }
        }

        return $enabled;
    }
}