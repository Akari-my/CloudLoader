<?php

namespace Mellooh\CloudLoader\core;

use DevTools\FolderPluginLoader;
use Mellooh\CloudLoader\CloudLoader;
use Mellooh\CloudLoader\config\CloudLoaderConfig;
use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\module\DependencyResolver;
use Mellooh\CloudLoader\module\ModuleLoader;
use Mellooh\CloudLoader\module\ModuleScanner;
use Mellooh\CloudLoader\module\ModuleScanResult;
use Mellooh\CloudLoader\module\StagingArea;

class CloudModuleManager{

    public function __construct(
        private CloudLoader $plugin,
        private CloudLoaderConfig $settings
    ){}

    public function reloadModules(): void{
        $log = new LoggerProxy($this->plugin->getLogger());

        $folderLoader = new FolderPluginLoader($this->plugin->getServer()->getLoader());

        $scanner = new ModuleScanner($log, $this->settings->logs(), $folderLoader);
        $scan = $scanner->scan($this->getModulesDir());

        foreach($scan->errors as $error){
            if($this->settings->logs()->errors){
                $log->error($error);
            }
        }

        $filtered = [];
        foreach($scan->modules as $name => $module){
            if($this->plugin->getServer()->getPluginManager()->getPlugin($name) !== null){
                continue;
            }
            $filtered[$name] = $module;
        }
        $scan = new ModuleScanResult($filtered, $scan->errors);

        $resolver = new DependencyResolver($this->plugin->getServer()->getPluginManager(), $log, $this->settings->logs());
        $resolution = $resolver->resolve($scan->modules);

        $staging = new StagingArea($this->plugin->getDataFolder() . ".staging" . DIRECTORY_SEPARATOR);

        $loader = new ModuleLoader(
            $this->plugin->getServer()->getPluginManager(),
            $log,
            $this->settings->logs(),
            $staging,
            $this->settings->stagingCleanup()
        );
        $loader->loadAll($resolution);

        if($this->settings->logs()->skipped){
            foreach($resolution->missingDependencies as $moduleName => $missing){
                $log->warning("Skipped $moduleName: missing dependencies: " . implode(", ", $missing));
            }
            if(count($resolution->cycleNodes) > 0){
                $log->warning("Dependency cycle detected among: " . implode(", ", $resolution->cycleNodes));
            }
        }
    }

    public function getModulesDir(): string{
        $dir = trim($this->settings->dir(), "/\\");
        return $this->plugin->getDataFolder() . $dir . DIRECTORY_SEPARATOR;
    }

    public function getSettings(): CloudLoaderConfig{
        return $this->settings;
    }
}