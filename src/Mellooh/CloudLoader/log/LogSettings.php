<?php

namespace Mellooh\CloudLoader\log;

class LogSettings{

    public function __construct(public bool $errors, public bool $scan, public bool $loadOrder, public bool $modulesLoaded, public bool $permissionsRegistered, public bool $skipped){}
}