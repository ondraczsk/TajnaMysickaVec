<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use pocketmine\Player;
use pocketmine\Server;

class ChangePasswordQuery extends AsyncQuery {

    private $old;
    private $new;

    public function __construct(MTCore $plugin, $player, $old, $new) {
        $this->player = $player;
        $this->old = $old;
        $this->new = $new;

        parent::__construct($plugin);
    }

    public function onQuery(array $data) {
        $result = [
          "error" => false,
          "wrong" => false,
        ];
        if (empty($data)){
            $result["error"] = true;
            $this->setResult($result);
            return;
        }
        if ($data["heslo"] != $this->old){
            $result["wrong"] = true;
            $this->setResult($result);
            return;
        }
        $this->setPlayer($this->player, "heslo", $this->new);
    }

    public function onCompletion(Server $server) {

        $p = $server->getPlayerExact($this->player);
        if (!($p instanceof Player)){
            return;
        }

        if ($this->getResult()["error"]){
            $p->sendMessage(MTCore::getPrefix()."§cAn unknown error occured while trying to change password");
        }
        elseif ($this->getResult()["wrong"]){
            $p->sendMessage(MTCore::getPrefix()."§cWrong password.");
        }
        else {
            $p->sendMessage(MTCore::getPrefix()."§aPassword changed successfully!");
        }
    }
}