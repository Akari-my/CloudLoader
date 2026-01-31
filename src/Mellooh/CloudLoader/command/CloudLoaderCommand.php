<?php

namespace Mellooh\CloudLoader\command;

use Mellooh\CloudLoader\CloudLoader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class CloudLoaderCommand extends Command {

    public function __construct(private CloudLoader $plugin){
        parent::__construct("cloudloader", "CloudLoader management", "/cloudloader reload", ["cl"]);
        $this->setPermission("cloudloader.command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$this->testPermission($sender)){
            return true;
        }

        $sub = strtolower($args[0] ?? "reload");
        if($sub !== "reload"){
            $sender->sendMessage("Usage: /cloudloader reload");
            return true;
        }

        $this->plugin->moduleManager()->reloadModules();
        $sender->sendMessage("CloudLoader reload complete");
        return true;
    }
}
