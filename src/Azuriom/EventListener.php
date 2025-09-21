<?php

namespace Azuriom;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use Azuriom\Store\CheckPurchaseTask;

class EventListener implements Listener {

    private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * @param PlayerJoinEvent $event
     */
    public function onPlayerJoin(PlayerJoinEvent $event) {
        $player = $event->getPlayer();
        // Correctly pass both configs and the player name to the task
        $this->plugin->getServer()->getScheduler()->scheduleAsyncTask(
            new CheckPurchaseTask(
                $this->plugin->getConfig()->getAll(),
                $this->plugin->getRankConfig()->getAll(),
                $player->getName()
            )
        );
    }
}