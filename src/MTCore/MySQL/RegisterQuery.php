<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use MTCore\Object\PlayerData;
use pocketmine\Player;
use pocketmine\Server;

class RegisterQuery extends AsyncQuery {

    private $password;
    private $ip;
    private $uuid;

    public function __construct(MTCore $plugin, $player, $pass, $ip, $uuid){
        $this->player = $player;
        $this->password = $pass;
        $this->ip = $ip;
        $this->uuid = $uuid;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $result = ["already" => false];

        if ($data != null && strlen($data["heslo"]) >= 4) {
            $result["already"] = true;
        }

        else {
            $this->setPlayer($this->player, "heslo", $this->password);
            $this->setPlayer($this->player, "ip", $this->ip);
            $this->setPlayer($this->player, "id", $this->uuid);
        }

        $this->setResult($result);
        return;
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

        if($result["already"]) {
            $p->sendMessage(MTCore::getPrefix()."§6You are already registered");
            return;
        }

        /** @var PlayerData $pl */
        $pl = $plugin->players[strtolower($p->getName())];
        $pl->setAuthed(true);

        $p->removeAllEffects();
        $p->sendMessage(MTCore::getPrefix()."§aYou have been successfully registered");
    }

}