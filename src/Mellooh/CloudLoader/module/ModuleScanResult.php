<?php

namespace Mellooh\CloudLoader\module;

class ModuleScanResult{

    public function __construct(public array $modules, public array $errors){}
}