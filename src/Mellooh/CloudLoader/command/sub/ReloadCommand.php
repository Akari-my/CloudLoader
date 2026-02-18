<?php

namespace Mellooh\CloudLoader\command\sub;

use Mellooh\CloudLoader\CloudLoader;
use Mellooh\CloudLoader\libs\CommandoX\BaseSubCommand;
use Mellooh\CloudLoader\libs\CommandoX\CommandContext;
use pocketmine\plugin\Plugin;

class ReloadCommand extends BaseSubCommand{

    public function __construct(Plugin $plugin) {
        parent::__construct($plugin, "reload", "ReloadCommand CloudLoader modules");
    }

    protected function configure(): void {
    }

    public function onRun(CommandContext $context): void {
        /** @var CloudLoader $plugin */
        $plugin = $context->getPlugin();
        $sender = $context->getSender();

        $mm = $plugin->moduleManager();
        $mm->reloadModules();

        $sender->sendMessage("CloudLoader reload complete");
    }
}