<?php

namespace Mellooh\CloudLoader\module;

class ModuleInfo{

    public function __construct(public string $name, public string $path, public array $depend, public array $softDepend, public array $loadBefore){}
}