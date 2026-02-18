<?php
declare(strict_types=1);

namespace Mellooh\CloudLoader\config;

use Mellooh\CloudLoader\log\LogSettings;
use pocketmine\utils\Config;

final class CloudLoaderConfig{

    private string $dir;
    private string $mode;

    private string $stagingStrategy;
    private bool $stagingResetOnStart;

    private LogSettings $logs;

    public function __construct(Config $config){
        $this->dir = (string)$config->getNested("loader.dir", "modules");
        $this->mode = strtolower((string)$config->getNested("loader.mode", "delayed"));

        $strategy = strtolower((string)$config->getNested("staging.strategy", "symlink"));
        $this->stagingStrategy = ($strategy === "copy" || $strategy === "symlink") ? $strategy : "symlink";

        $this->stagingResetOnStart = (bool)$config->getNested("staging.reset_on_start", true);

        $this->logs = new LogSettings(
            (bool)$config->getNested("logs.errors", true),
            (bool)$config->getNested("logs.scan", true),
            (bool)$config->getNested("logs.load_order", true),
            (bool)$config->getNested("logs.modules_loaded", true),
            (bool)$config->getNested("logs.skipped", true),
            (bool)$config->getNested("logs.debug", false)
        );
    }

    public function dir(): string{
        return $this->dir;
    }

    public function mode(): string{
        return $this->mode;
    }

    public function stagingStrategy(): string{
        return $this->stagingStrategy;
    }

    public function stagingResetOnStart(): bool{
        return $this->stagingResetOnStart;
    }

    public function logs(): LogSettings{
        return $this->logs;
    }
}