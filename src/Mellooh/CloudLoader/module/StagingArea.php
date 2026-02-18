<?php

namespace Mellooh\CloudLoader\module;

use Mellooh\CloudLoader\log\LoggerProxy;

class StagingArea{

    public function __construct(
        private string $path,
        private LoggerProxy $logger
    ){}

    public function path(): string{
        return $this->path;
    }

    public function reset(): void{
        @mkdir($this->path, 0777, true);
        $this->clearDir($this->path);
    }

    public function stage(string $targetDir, string $name, string $strategy): string{
        $dst = rtrim($this->path, "/\\") . DIRECTORY_SEPARATOR . $name;

        if(file_exists($dst) || is_link($dst)){
            $this->deletePath($dst);
        }

        if($strategy === "symlink"){
            if(function_exists("symlink") && @symlink($targetDir, $dst)){
                return $dst;
            }
            if(PHP_OS_FAMILY === "Windows"){
                $this->logger->warning("Symlink failed on Windows for $name, falling back to copy");
            }else{
                $this->logger->warning("Symlink failed for $name, falling back to copy");
            }
        }

        $this->copyTree($targetDir, $dst);
        return $dst;
    }

    private function clearDir(string $dir): void{
        $entries = @scandir($dir);
        if($entries === false){
            return;
        }
        foreach($entries as $entry){
            if($entry === "." || $entry === ".."){
                continue;
            }
            $this->deletePath($dir . DIRECTORY_SEPARATOR . $entry);
        }
    }

    private function deletePath(string $path): void{
        if(is_link($path) || is_file($path)){
            @unlink($path);
            return;
        }
        if(is_dir($path)){
            $entries = @scandir($path);
            if($entries !== false){
                foreach($entries as $entry){
                    if($entry === "." || $entry === ".."){
                        continue;
                    }
                    $this->deletePath($path . DIRECTORY_SEPARATOR . $entry);
                }
            }
            @rmdir($path);
        }
    }

    private function copyTree(string $src, string $dst): void{
        @mkdir($dst, 0777, true);

        $entries = @scandir($src);
        if($entries === false){
            return;
        }

        foreach($entries as $entry){
            if($entry === "." || $entry === ".."){
                continue;
            }

            $from = $src . DIRECTORY_SEPARATOR . $entry;
            $to = $dst . DIRECTORY_SEPARATOR . $entry;

            if(is_dir($from)){
                $this->copyTree($from, $to);
                continue;
            }

            if(is_file($from)){
                @copy($from, $to);
            }
        }
    }
}