<?php

namespace Mellooh\CloudLoader\module;

class StagingArea{

    public function __construct(private string $path){}

    public function path(): string{
        return $this->path;
    }

    public function reset(): void{
        @mkdir($this->path, 0777, true);
        $this->clearDir($this->path);
    }

    public function cleanup(): void{
        $this->clearDir($this->path);
    }

    public function stage(string $targetDir, string $name): string{
        $link = rtrim($this->path, "/\\") . DIRECTORY_SEPARATOR . $name;

        if(file_exists($link) || is_link($link)){
            $this->deletePath($link);
        }

        if(function_exists("symlink")){
            if(@symlink($targetDir, $link)){
                return $link;
            }
        }

        $this->copyTree($targetDir, $link);
        return $link;
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

            if(is_link($from)){
                $real = @readlink($from);
                if(is_string($real) && $real !== ""){
                    @symlink($real, $to);
                }
                continue;
            }

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