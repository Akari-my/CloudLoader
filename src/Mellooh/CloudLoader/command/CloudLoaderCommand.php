<?php

namespace Mellooh\CloudLoader\command;

use Mellooh\CloudLoader\CloudLoader;
use Mellooh\CloudLoader\command\sub\DisableCommand;
use Mellooh\CloudLoader\command\sub\EnableCommand;
use Mellooh\CloudLoader\command\sub\GraphCommand;
use Mellooh\CloudLoader\command\sub\HelpCommand;
use Mellooh\CloudLoader\command\sub\InstallCommand;
use Mellooh\CloudLoader\command\sub\ListCommand;
use Mellooh\CloudLoader\command\sub\ReloadCommand;
use Mellooh\CloudLoader\command\sub\StatusCommand;
use Mellooh\CloudLoader\libs\CommandoX\BaseCommand;
use Mellooh\CloudLoader\libs\CommandoX\CommandContext;

class CloudLoaderCommand extends BaseCommand {

    public function __construct(CloudLoader $plugin, string $name = "cloudloader") {
        parent::__construct($plugin, $name, "CloudLoader management", []);
    }

    protected function configure(): void {
        $this->setPermission("cloudloader.command");
        $this->setPermissionMessageCustom("§cYou don't have permission to use /cloudloader.");

        $this->registerSubCommand(new ReloadCommand($this->plugin));
        $this->registerSubCommand(new ListCommand($this->plugin));
        $this->registerSubCommand(new StatusCommand($this->plugin));
        $this->registerSubCommand(new GraphCommand($this->plugin));
        $this->registerSubCommand(new EnableCommand($this->plugin));
        $this->registerSubCommand(new DisableCommand($this->plugin));
        $this->registerSubCommand(new HelpCommand($this->plugin));
    }

    public function onRun(CommandContext $context): void {
        /** @var CloudLoader $plugin */
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
