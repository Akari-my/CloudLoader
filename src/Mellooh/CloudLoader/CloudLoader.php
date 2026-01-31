<?php

namespace Mellooh\CloudLoader;

use DevTools\FolderPluginLoader;
use Mellooh\CloudLoader\command\CloudLoaderCommand;
use Mellooh\CloudLoader\config\CloudLoaderConfig;
use Mellooh\CloudLoader\core\CloudModuleManager;
use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\module\DependencyResolver;
use Mellooh\CloudLoader\module\ModuleLoader;
use Mellooh\CloudLoader\module\ModuleScanner;
use Mellooh\CloudLoader\module\ModuleScanResult;
use Mellooh\CloudLoader\module\StagingArea;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class CloudLoader extends PluginBase {

    private CloudLoaderConfig $settings;
    private CloudModuleManager $moduleManager;

    public function onEnable(): void{
        $this->saveDefaultConfig();
        $this->reloadConfig();

        $this->settings = new CloudLoaderConfig($this->getConfig());
        $this->moduleManager = new CloudModuleManager($this, $this->settings);

        @mkdir($this->moduleManager->getModulesDir(), 0777, true);

        $this->getServer()->getCommandMap()->register("cloudloader", new CloudLoaderCommand($this));

        if($this->settings->mode() === "delayed"){
            $this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(): void{
                $this->moduleManager->reloadModules();
            }), 1);
            return;
        }

        $this->moduleManager->reloadModules();
    }

    public function moduleManager(): CloudModuleManager{
        return $this->moduleManager;
    }
}