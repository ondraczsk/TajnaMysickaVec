<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use pocketmine\Player;
use pocketmine\Server;

class SetRankQuery extends AsyncQuery{

    private $rank;
    private $time;

    public function __construct(MTCore $plugin, $player, $rank){
        $this->player = $player;
        $this->rank = $rank;
        switch($this->rank){
            case "vip":
            case "vip+":
                $this->time = time() + 30 * 24 * 3600;
                break;
            default:
                $this->time = 0;
                break;
        }

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        if($data != null){
            $this->setPlayer($this->player, "rank", $this->rank);
            $this->setPlayer($this->player, "doba", $this->time);
        }
    }

    public function onCompletion(Server $server) {
        $p = $server->getPlayer($this->player);
        if (!$p instanceof Player){
            return;
        }
        $p->sendMessage(MTCore::getPrefix()."§7Your rank was updated. You'll get the rank advantages after relog");
    }

}