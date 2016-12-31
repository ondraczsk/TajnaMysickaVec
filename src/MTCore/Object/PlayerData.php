<?php

namespace MTCore\Object;

use pocketmine\Player;

class PlayerData{

    /** @var Player $player */
    public $player;

    private $authed = false;

    private $inLobby = true;

    /** @var int $tick */
    private $tick = 0;

    public $visible = false;

    public function __construct(Player $p){
        $this->player = $p;
    }

    public function getPlayer() : Player{
        return $this->player;
    }

    public function isAuthed() {
        return $this->authed;
    }

    public function setAuthed($value){
        $this->authed = $value;
    }

    public function inLobby() {
        return $this->inLobby;
    }

    public function setInLobby($value){
        $this->inLobby = $value;
    }

    public function setTick($tick){
        $this->tick = $tick;
    }

    public function getChatTick() {
        return $this->tick;
    }

    public function setPlayersVisible($value){
        $this->visible = $value;
    }

    public function isPlayersVisible()  {
        return $this->visible;
    }
}