<?php

namespace Azuriom;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use Azuriom\command\CheckPurchasesCommand;

class Main extends PluginBase {

    private $kxMySQL;
    private $kxRanks;
    public $rankConfig;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->saveResource("ranks.yml");

        $this->rankConfig = new Config($this->getDataFolder() . "ranks.yml", Config::YAML);

        $this->kxMySQL = $this->getServer()->getPluginManager()->getPlugin("KXMySQL");
        $this->kxRanks = $this->getServer()->getPluginManager()->getPlugin("KXRanks");

        if ($this->kxMySQL === null || $this->kxRanks === null) {
            $this->getLogger()->error("This plugin requires KXMySQL and KXRanks to function. Disabling...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getServer()->getCommandMap()->register("azuriom", new CheckPurchasesCommand($this));

        $this->getLogger()->info("AzuriomIntegration has been enabled successfully.");
    }

    /** @return \kxleph\kxranks\KXRanks|null */
    public function getRanksPlugin() {
        return $this->kxRanks;
    }

    /** @return Config */
    public function getRankConfig() {
        return $this->rankConfig;
    }
}