<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BanQuery extends AsyncQuery {

    private $reason;
    private $banner;

    public function __construct(MTCore $plugin, $player, $banner, $reason) {
        $this->player = $player;
        $this->reason = $reason;
        $this->banner = $banner;

        parent::__construct($plugin);
    }

    public function onQuery(array $data) {
        $result = ["success" => false];

        $id = (is_array($data) && trim($data["id"]) != "") ? $data["id"] : null;

        if ($id != null) {
            $banData = $this->getPlayer($data["id"], "banlist");

            if ($banData !== null && $banData["expiration"] == 1){
                $result["success"] = true;
                $result["msg"] = MTCore::getPrefix()."§cNemuzu zabanovat hrace; Hrac uz je zabanovan";
            }
            else {
                $this->ban($id, $this->reason, $this->banner, $banData);
                $result["msg"] = $banData === null ? MTCore::getPrefix()."§cZabanovan hrac §b".
                    $this->player : MTCore::getPrefix()."§bHrac §f".$this->player." §bbyl zabanovan §9navzdy!";
                $result["success"] = true;
            }
        }

        else {
            $result["msg"] = MTCore::getPrefix()."§cStala se neocekavana chyba pri banovani hrace!";
        }


        $this->setResult($result);
    }

    public function onCompletion(Server $server) {
        $result = $this->getResult();

        if ($result["success"]) {

            $p = $server->getPlayerExact($this->player);

            if ($p instanceof Player && $p->isOnline()) {
                $p->kick(TextFormat::RED . "Byl jsi zabanovan. \n " . TextFormat::RED . "Duvod: " . TextFormat::AQUA . $this->reason, false);
            }

            $server->getLogger()->info($result["msg"]);
        }

        $pl = $server->getPlayer($this->banner);

        if ($pl instanceof Player && $pl->isOnline()) {
            $pl->sendMessage($result["msg"]);
        }

    }

}
