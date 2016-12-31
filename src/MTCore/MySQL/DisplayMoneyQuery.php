<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use pocketmine\Player;
use pocketmine\Server;

class DisplayMoneyQuery extends AsyncQuery{

    public function __construct(MTCore $plugin, $player){
        $this->player = $player;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $this->setResult([$data["tokens"]]);
    }

    public function onCompletion(Server $server){
        $p = $server->getPlayer($this->player);
        $money = $this->getResult()[0];

        if ($p instanceof Player && $p->isOnline()) {
            $p->sendMessage(MTCore::getPrefix()."§eTokens: §9".$money);
        }
    }

}