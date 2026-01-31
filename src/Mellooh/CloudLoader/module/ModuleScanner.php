<?php

namespace Mellooh\CloudLoader\module;

use DevTools\FolderPluginLoader;
use Mellooh\CloudLoader\log\LoggerProxy;
use Mellooh\CloudLoader\log\LogSettings;

class ModuleScanner{

    public function __construct(private LoggerProxy $logger, private LogSettings $logs, private FolderPluginLoader $folderLoader){}

    public function scan(string $baseDir): ModuleScanResult{
        $modules = [];
        $errors = [];

        if(!is_dir($baseDir)){
            return new ModuleScanResult([], []);
        }

        $this->scanDir(rtrim($baseDir, "/\\") . DIRECTORY_SEPARATOR, $modules, $errors);

        if($this->logs->scan){
            $this->logger->info("scan complete: " . count($modules) . " module(s) found");
        }

        return new ModuleScanResult($modules, $errors);
    }

    private function scanDir(string $dir, array &$modules, array &$errors): void{
        $entries = @scandir($dir);
        if($entries === false){
            $errors[] = "Cannot read directory: $dir";
            return;
        }

        foreach($entries as $entry){
            if($entry === "." || $entry === ".."){
                continue;
            }

            if($entry !== "" && $entry[0] === "."){
                continue;
            }

            $path = $dir . $entry;
            if(!is_dir($path)){
                continue;
            }

            if($this->folderLoader->canLoadPlugin($path)){
                try{
                    $description = $this->folderLoader->getPluginDescription($path);
                    if($description === null){
                        $errors[] = "Invalid plugin description at $path";
                        continue;
                    }

                    $name = $description->getName();

                    if(isset($modules[$name])){
                        $errors[] = "Duplicate module name '$name' at $path";
                        continue;
                    }

                    $modules[$name] = new ModuleInfo(
                        $name,
                        $path,
                        $description->getDepend(),
                        $description->getSoftDepend(),
                        $description->getLoadBefore()
                    );
                }catch(\Throwable $e){
                    $errors[] = "Error reading plugin at $path: " . $e->getMessage();
                }
                continue;
            }

            $this->scanDir($path . DIRECTORY_SEPARATOR, $modules, $errors);
        }
    }
}