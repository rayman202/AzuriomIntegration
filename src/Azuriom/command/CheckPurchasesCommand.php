<?php

namespace Azuriom\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use Azuriom\Store\CheckPurchaseTask;

class CheckPurchasesCommand extends Command {

    private $plugin;

    public function __construct(PluginBase $plugin) {
        parent::__construct(
            "checkpurchases",
            "Manually checks for new purchases from the Azuriom store.",
            "/checkpurchases [player]",
            []
        );
        $this->setPermission("azuriom.command.check");
        $this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, $commandLabel, array $args) {
        if (!$this->testPermission($sender)) {
            return true;
        }

        $targetPlayerName = "";
        if (isset($args[0])) {
            $targetPlayerName = $args[0];
        } elseif ($sender instanceof Player) {
            $targetPlayerName = $sender->getName();
        } else {
            $sender->sendMessage(TextFormat::RED . "Please specify a player name.");
            return false;
        }

        $sender->sendMessage(TextFormat::YELLOW . "Manually checking purchases for " . $targetPlayerName . "...");

        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask(
            new CheckPurchaseTask(
                $this->plugin->getConfig()->getAll(),
                $this->plugin->getRankConfig()->getAll(),
                $targetPlayerName
            )
        );

        return true;
    }
}