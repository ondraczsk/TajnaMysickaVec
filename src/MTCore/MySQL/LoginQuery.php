<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use MTCore\Object\PlayerData;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class LoginQuery extends AsyncQuery {

    private $password;
    private $ip;
    private $uuid;

    public function __construct(MTCore $plugin, $player, $pass, $ip, $uuid) {
        $this->password = $pass;
        $this->ip = $ip;
        $this->uuid = $uuid;
        $this->player = $player;

        parent::__construct($plugin);
    }

    public function onQuery(array $data) {

        $result = [];
        $result["success"] = true;

        if (empty($data)) {
            $result["success"] = false;
            $result["msg"] = MTCore::getPrefix()."§cNejsi registrovan\n§cPouzij /register [heslo] [heslo]";
        }

        else {
            $pass = $data['heslo'];
            if (hash("sha1", $this->password . strtolower($this->player)) != $pass && $this->password != $pass) {

                $result["msg"] = MTCore::getPrefix() . TextFormat::RED . "Spatne heslo";
                $result["success"] = false;
            } else {

                $result["msg"] = MTCore::getPrefix() . TextFormat::GREEN . "Byl jsi uspesne prihlasen!";

                $this->setPlayer($this->player, "ip", $this->ip);
                $this->setPlayer($this->player, "id", $this->uuid);
            }
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

        if ($result["success"]) {
            /** @var PlayerData $pla */
            $pla = $plugin->players[strtolower($p->getName())];
            $pla->setAuthed(true);
            foreach ($server->getOnlinePlayers() as $pl){
                $p->showPlayer($pl);
            }
            $p->removeAllEffects();
            if($p->getInventory() instanceof PlayerInventory) {
                $p->getInventory()->clearAll();
                $p->getInventory()->setItem(0, Item::get(Item::CLOCK, 0, 1)->setCustomName("§r§bZobrazit hrace"));
                $p->getInventory()->setItem(1, Item::get(Item::GOLD_INGOT, 0, 1));
                $p->getInventory()->sendContents($p);
            }
        }

        $p->sendMessage($result["msg"]);
    }

}
