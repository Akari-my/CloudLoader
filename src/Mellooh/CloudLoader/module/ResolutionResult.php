<?php

namespace Mellooh\CloudLoader\module;

class ResolutionResult{

    public function __construct(public array $order, public array $missingDependencies, public array $cycleNodes){}
}