<?php

namespace Azuriom\Store;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

class CheckPurchaseTask extends AsyncTask {

    private $dbConfig;
    private $rankConfig;
    private $playerName;

    public function __construct(array $dbConfig, array $rankConfig, $playerName) {
        $this->dbConfig = $dbConfig["database"];
        $this->rankConfig = $rankConfig;
        $this->playerName = $playerName;
    }

    public function onRun() {
        @$db = new \mysqli(
            $this->dbConfig["host"],
            $this->dbConfig["user"],
            $this->dbConfig["password"],
            $this->dbConfig["name"],
            $this->dbConfig["port"]
        );

        if ($db->connect_error) {
            $this->setResult(["error" => "Azuriom DB Connection Error: " . $db->connect_error]);
            return;
        }

        // This query now reads the 'variables' column to get the minecraft_username
        $query = "SELECT p.id, p.price, pi.variables FROM `shop_payments` AS p
                  JOIN `shop_payment_items` AS pi ON p.id = pi.payment_id
                  WHERE p.status = 'completed' AND p.processed = 0";

        $result = $db->query($query);

        if ($result === false) {
            $this->setResult(["error" => "Azuriom DB Query Error: " . $db->error]);
            $db->close();
            return;
        }

        $payments = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $variables = json_decode($row['variables'], true);
                // We only care about payments that have our custom variable
                if (isset($variables['minecraft_username'])) {
                    $row['minecraft_username'] = $variables['minecraft_username'];
                    $payments[] = $row;
                }
            }
        }
        $result->free();

        if (!empty($payments)) {
            $ids = array_column($payments, 'id');
            $db->query("UPDATE `shop_payments` SET `processed` = 1 WHERE `id` IN (" . implode(',', $ids) . ")");
        }

        $db->close();
        $this->setResult(["payments" => $payments]);
    }

    public function onCompletion(Server $server) {
        $result = $this->getResult();

        if (isset($result["error"])) {
            $server->getLogger()->error($result["error"]);
            return;
        }

        $plugin = $server->getPluginManager()->getPlugin("AzuriomIntegration");
        if ($plugin === null || !$plugin->isEnabled()) {
            return;
        }

        // --- DEBUG MODE ---
        $server->getLogger()->info("--- AzuriomIntegration Purchase Check ---");
        if (empty($result["payments"])) {
            $server->getLogger()->info("-> No new global purchases found.");
        }

        foreach ($result["payments"] as $payment) {
            $targetPlayerName = $payment['minecraft_username'];
            $server->getLogger()->info("-> Found purchase for player: " . $targetPlayerName);

            $player = $server->getPlayer($targetPlayerName);
            if ($player !== null) {
                $currentRank = $plugin->getRanksPlugin()->getRankManager()->getPlayerRank($player)->getName();
                $server->getLogger()->info(" -> Player is online. Current Rank: " . $currentRank);

                $price = number_format((float)$payment["price"], 2, '.', '');

                if (isset($this->rankConfig[$price])) {
                    $rankData = $this->rankConfig[$price];
                    $rankName = $rankData["name"];
                    $durationDays = (int)$rankData["duration_days"];

                    $durationInSeconds = null;
                    if ($durationDays > 0) {
                        $durationInSeconds = $durationDays * 86400;
                    }

                    $server->getLogger()->info(" -> Match found in ranks.yml! Granting rank: " . $rankName);
                    $rankManager = new RankManager($plugin);
                    $rankManager->grantRank($player, $rankName, $durationInSeconds);
                } else {
                    $server->getLogger()->info(" -> No rank configured for price " . $price . " in ranks.yml.");
                }
            } else {
                $server->getLogger()->info(" -> Player is not online. Rank will be given on next join.");
            }
        }
        $server->getLogger()->info("--- End of Purchase Check ---");
    }
}