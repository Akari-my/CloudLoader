<?php

namespace Mellooh\CloudLoader\command\sub;

use Mellooh\CloudLoader\libs\CommandoX\BaseSubCommand;
use Mellooh\CloudLoader\libs\CommandoX\CommandContext;
use pocketmine\plugin\Plugin;

class HelpCommand extends BaseSubCommand{

    public function __construct(Plugin $plugin) {
        parent::__construct($plugin, "help", "Show CloudLoader help");
    }

    protected function configure(): void {
    }

    public function onRun(CommandContext $context): void {
        $sender = $context->getSender();

        $sender->sendMessage("       §l§7CLOUDLOADER       ");
        $sender->sendMessage("§7 - /cloudloader reload");
        $sender->sendMessage("§7 - /cloudloader list");
        $sender->sendMessage("§7 - /cloudloader status <PluginName>");
        $sender->sendMessage("§7 - /cloudloader graph");
        $sender->sendMessage("§7 - /cloudloader enable <PluginName>");
        $sender->sendMessage("§7 - /cloudloader disable <PluginName>");
        $sender->sendMessage("       §l§7CLOUDLOADER       ");
    }
}