<?php

namespace Mellooh\CloudLoader\module;

class LoadReport{

    public function __construct(
        public int $discovered,
        public int $allowed,
        public int $alreadyPresent,
        public int $staged,
        public int $loadedEnabled,
        public int $skippedDisabled,
        public int $skippedMissingDeps,
        public int $skippedCycles,
        public int $errors){}

}