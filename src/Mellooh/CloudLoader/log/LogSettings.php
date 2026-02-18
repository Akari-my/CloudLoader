<?php

namespace Mellooh\CloudLoader\log;

final class LogSettings{

    public function __construct(public bool $errors, public bool $scan, public bool $loadOrder, public bool $modulesLoaded, public bool $skipped, public bool $debug){}
}