<?php

namespace Mellooh\CloudLoader\command\sub;

use Mellooh\CloudLoader\CloudLoader;
use Mellooh\CloudLoader\libs\CommandoX\argument\StringArgument;
use Mellooh\CloudLoader\libs\CommandoX\BaseSubCommand;
use Mellooh\CloudLoader\libs\CommandoX\CommandContext;
use pocketmine\plugin\Plugin;

class DisableCommand extends BaseSubCommand{

    public function __construct(Plugin $plugin) {
        parent::__construct($plugin, "disable", "Disable a module");
    }

    protected function configure(): void {
        $this->registerArgument(0, new StringArgument("plugin"));
    }

    public function onRun(CommandContext $context): void {
        /** @var CloudLoader $plugin */
        $plugin = $context->getPlugin();
        $sender = $context->getSender();

        $name = (string)$context->getArg("plugin");

        $mm = $plugin->moduleManager();
        $mm->disableModule($sender, $name);
    }
}