<?php

namespace MTCore;

use MTCore\MySQL\ChangePasswordQuery;
use MTCore\MySQL\LoginQuery;
use MTCore\MySQL\RegisterQuery;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class AuthManager {

    public $plugin;

    public function __construct(MTCore $plugin) {
        $this->plugin = $plugin;
    }

    public function register(Player $p, $heslo) {

        if ($this->plugin->isAuthed($p)) {
            $p->sendMessage($this->plugin->getPrefix() . TextFormat::AQUA . "Uz si prihlasen");
            return;
        }
        if (strlen($heslo) < 4 || strlen($heslo) > 20) {
            $p->sendMessage($this->plugin->getPrefix() . TextFormat::RED . "Heslo musi byt dlouhe od 4 do 20 znaku dlouhe");
            return;
        }

        new RegisterQuery($this->plugin, $p->getName(), $heslo, $p->getAddress(), $p->getUniqueId());
    }

    public function login(Player $p, $heslo) {

        if ($this->plugin->isAuthed($p)) {
            $p->sendMessage(MTCore::getPrefix() . TextFormat::RED . "Uz si prihlasen");
            return;
        }

        new LoginQuery($this->plugin, $p->getName(), $heslo, $p->getAddress(), $p->getUniqueId());
    }

    public function changePassword(Player $p, $old, $new) {
        new ChangePasswordQuery($this->plugin, $p->getName(), $old, $new);
    }

}
