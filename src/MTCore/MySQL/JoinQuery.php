<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use MTCore\Object\PlayerData;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;

class JoinQuery extends AsyncQuery {

    private $ip;
    private $uuid;

    public function __construct(MTCore $plugin, $player, $ip, $uuid) {
        $this->player = $player;
        $this->ip = $ip;
        $this->uuid = $uuid;

        parent::__construct($plugin);
    }

    public function onQuery(array $data) {

        $result = [];
        $result["auth"] = false;
        $result["rank"] = "hrac";
        $result["register"] = false;
        $result["expired"] = null;

        switch ($data["rank"]) {
            case "vip":
                if (!(time() >= $data["doba"])) {
                    $time = round(($data["doba"] - time()) / 86400, 1);

                    $result["expired"] = "§l§4[§r§6MineTox§l§4] §aVIP rank expires in §e$time days";
                    break;
                }
                $result["expired"] = "§l§4[§r§6MineTox§l§4] §aVIP rank expired";
                $this->setPlayer($this->player, "rank", "hrac");
                break;
            case "vip+":
                if (!(time() >= $data["doba"])) {
                    $time = round(($data["doba"] - time()) / 86400, 1);

                    $result["expired"] = "§l§4[§r§6MineTox§l§4] §aVIP+ rank expires in §e$time days";
                    break;
                }
                $result["expired"] = "§l§4[§r§6MineTox§l§4] §aVIP+ rank expired";
                $this->setPlayer($this->player, "rank", "hrac");
                break;
        }

        $result["msg"] = "§7==========================================" . "\n" .
            "§e>> Welcome to " . "§l§4[§r§6MineTox§l§4]" . "§e, " . $this->player . "\n" .
            "§e>> This account is already registered" . "\n" .
            "§e>> Login with " . "§c/login " . "§eor change" . "\n" .
            "§e>> your name in the MCPE settings." . "\n" .
            "§7==========================================";

        if ($data["ip"] == $this->ip && $data["id"] == $this->uuid) {
            $result["auth"] = true;
            $result["msg"] = "§7==========================================" . "\n" .
                "§e>> Welcome to " . "§l§4[§r§6MineTox§l§4]" . "§e, " . $this->player . "\n" .
                "§7==========================================";

        } elseif (!is_string($data["heslo"]) || strlen($data["heslo"]) < 4) {
            $result["register"] = true;
            $result["msg"] = "§7==========================================" . "\n" .
                "§e>> Welcome to " . "§l§4[§r§6MineTox§l§4]" . "§e, " . $this->player . "\n" .
                "§e>> The account has not been registered\n" .
                "§e>> You can claim it with " . "§c/register <password> <password>\n" .
                "§7==========================================";
        }

        $this->setResult($result);
    }

    public function onCompletion(Server $server) {

        $result = $this->getResult();

        $p = $server->getPlayerExact($this->player);

        if (!$p instanceof Player || !$p->isOnline()) {
            return;
        }

        /** @var MTCore $plugin */
        $plugin = $server->getPluginManager()->getPlugin("MTCore");

        if (!$plugin instanceof MTCore || !$plugin->isEnabled()) {
            return;
        }

        if ($result["auth"]) {

            /** @var PlayerData $pla */
            $pla = $plugin->players[strtolower($p->getName())];
            $pla->setAuthed(true);

            foreach ($server->getOnlinePlayers() as $pl){
                $p->showPlayer($pl);
            }
            $p->removeAllEffects();
            $p->setDisplayName(MTCore::getDisplayRank($p).$p->getName());
            $p->setNameTag(MTCore::getDisplayRank($p).$p->getName());
        }

        $p->teleport($plugin->lobby);
        $p->setGamemode(0);
        $rot = $plugin->miniGame === "annihilation" ? 270 : 90;
        $p->setRotation(0, $rot);
        $p->setHealth(20);

        if($p->getInventory() instanceof PlayerInventory and $plugin->isAuthed($p)) {
            $p->getInventory()->clearAll();
            $p->getInventory()->setItem(0, Item::get(Item::CLOCK, 0, 1)->setCustomName("§r§eHide players"));
            $p->getInventory()->setItem(1, Item::get(Item::GOLD_INGOT, 0, 1));
            $p->getInventory()->sendContents($p);
        }

        $p->sendMessage($result["msg"]);

        if ($result["expired"] !== null) {
            $p->sendMessage($result["expired"]);
        }

    }

}