<?php

namespace Mellooh\CloudLoader\module;

class ModuleRegistry {

    private array $enabled = [];
    private array $disabled = [];

    public function __construct(
        private string $file
    ){
        $this->load();
    }

    public function load(): void{
        if(!is_file($this->file)){
            $this->enabled = [];
            $this->disabled = [];
            return;
        }

        $data = @yaml_parse_file($this->file);
        if(!is_array($data)){
            $this->enabled = [];
            $this->disabled = [];
            return;
        }

        $this->enabled = $this->normalizeList($data["enabled"] ?? null);
        $this->disabled = $this->normalizeList($data["disabled"] ?? null);
    }

    public function save(): void{
        @mkdir(dirname($this->file), 0777, true);

        $data = [];
        if($this->enabled !== []){
            $data["enabled"] = array_values($this->enabled);
        }
        if($this->disabled !== []){
            $data["disabled"] = array_values($this->disabled);
        }

        $yaml = yaml_emit($data, YAML_UTF8_ENCODING);
        file_put_contents($this->file, $yaml);
    }

    public function whitelistMode(): bool{
        return count($this->enabled) > 0;
    }

    public function isAllowed(string $name): bool{
        if($this->whitelistMode()){
            return in_array($name, $this->enabled, true);
        }
        return !in_array($name, $this->disabled, true);
    }

    public function enable(string $name): void{
        $this->disabled = array_values(array_filter($this->disabled, static fn(string $v) => $v !== $name));
        if($this->whitelistMode()){
            if(!in_array($name, $this->enabled, true)){
                $this->enabled[] = $name;
                sort($this->enabled, SORT_STRING);
            }
        }
        $this->save();
    }

    public function disable(string $name): void{
        $this->enabled = array_values(array_filter($this->enabled, static fn(string $v) => $v !== $name));
        if(!in_array($name, $this->disabled, true)){
            $this->disabled[] = $name;
            sort($this->disabled, SORT_STRING);
        }
        $this->save();
    }

    public function enabledList(): array{
        return $this->enabled;
    }

    public function disabledList(): array{
        return $this->disabled;
    }

    private function normalizeList(mixed $value): array{
        if($value === null){
            return [];
        }
        if(is_string($value)){
            $value = [$value];
        }
        if(!is_array($value)){
            return [];
        }
        $out = [];
        foreach($value as $v){
            if(is_string($v) && $v !== ""){
                $out[] = $v;
            }
        }
        $out = array_values(array_unique($out));
        sort($out, SORT_STRING);
        return $out;
    }
}