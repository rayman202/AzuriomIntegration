<?php

namespace Azuriom\Store;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class RankManager {

    private $plugin;

    public function __construct(PluginBase $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Grants a specific rank to a player for a set duration.
     *
     * @param Player $player The player to grant the rank to.
     * @param string $rankName The name of the rank to grant.
     * @param int|null $duration The duration of the rank in seconds. Null for permanent.
     */
    public function grantRank(Player $player, $rankName, $duration = null) {
        $ranksPlugin = $this->plugin->getRanksPlugin();

        if ($ranksPlugin !== null) {
            // Correctly calls the KXRanks API
            $ranksPlugin->getRankManager()->setPlayerRank($player, $rankName, $duration);

            $durationText = $duration === null ? "permanently" : "for " . ($duration / 86400) . " days";

            $player->sendMessage(TextFormat::GOLD . "Thank you for your support! You have received the " . TextFormat::AQUA . $rankName . TextFormat::GOLD . " rank " . $durationText . "!");
            $this->plugin->getServer()->broadcastMessage(TextFormat::GREEN . $player->getName() . " has just supported the server!");
        }
    }
}