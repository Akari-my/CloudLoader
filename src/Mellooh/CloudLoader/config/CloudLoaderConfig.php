<?php

namespace Mellooh\CloudLoader\config;

use Mellooh\CloudLoader\log\LogSettings;
use pocketmine\utils\Config;

class CloudLoaderConfig{

    private string $dir;
    private string $mode;
    private bool $stagingCleanup;
    private LogSettings $logs;

    public function __construct(Config $config){
        $this->dir = (string) $config->getNested("loader.dir", "modules");
        $this->mode = strtolower((string) $config->getNested("loader.mode", "delayed"));
        $this->stagingCleanup = (bool) $config->getNested("staging.cleanup", true);

        $this->logs = new LogSettings(
            (bool) $config->getNested("logs.errors", true),
            (bool) $config->getNested("logs.scan", true),
            (bool) $config->getNested("logs.load_order", true),
            (bool) $config->getNested("logs.modules_loaded", true),
            false,
            (bool) $config->getNested("logs.skipped", true)
        );
    }

    public function dir(): string{
        return $this->dir;
    }

    public function mode(): string{
        return $this->mode;
    }

    public function stagingCleanup(): bool{
        return $this->stagingCleanup;
    }

    public function logs(): LogSettings{
        return $this->logs;
    }
}