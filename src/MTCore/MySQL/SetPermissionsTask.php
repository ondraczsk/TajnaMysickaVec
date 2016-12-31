<?php

namespace MTCore\MySQL;

use MTCore\MTCore;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class SetPermissionsTask extends AsyncQuery{

    public function __construct(MTCore $plugin, $player){
        $this->player = $player;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        if (empty($data)){
            $this->setResult(["hrac", 0, ""]);
            $this->registerPlayer($this->player);
            $this->registerStats($this->player);
            return;
        }
        if ($this->getPlayer($this->player, "stats") === null){
            $this->registerStats($this->player);
        }
        $this->setResult($data["rank"]);
    }

    public function onCompletion(Server $server){
        $p = $server->getPlayerExact($this->player);
        if (!($p instanceof Player)){
            return;
        }
        $data = $this->getPlayer($p->getUniqueId(), "banlist");
        $ban = $data["expiration"];
        if ($ban > 0){
            if ($ban == 1){
                $p->kick("§l§1A§9t§3l§1a§9n§3t§1h§9y§3i§1d§3i§9a§bGames\n".
                    "§cZabanovan navzdy.\n".
                    "§bDuvod: §a".$data["reason"], false);
            }
            elseif ($ban < time()){}
            else {
                $p->kick("§l§1A§9t§3l§1a§9n§3t§1h§9y§3i§1d§3i§9a§bGames\n".
                    "§cZabanovan za §a".$data["reason"]."\n".
                    "§bBan vyprsi za".date('d.m.Y H:i:s', $ban), false);
            }
        }
        $true = [];
        $false = [];
        switch ($this->getResult()){
            case "owner":
                $true = ["minetox"];
                break;
            case "developper":
                $true = ["minetox"];
                $false = ["minetox.cmd.setrank", "minetox.owner", "minetox.break",
                    "minetox.place"];
                break;
            case "banner":
                $true = ["minetox.cmd.message", "minetox.banner", "minetox.ban",
                    "minetox.immune", "minetox.color", "minetox.waitbypass", "minetox.log"];
                break;
            case "builder":
                $true = ["minetox.cmd.message", "minetox.builder", "minetox.color",
                    "minetox.waitbypass", "minetox.break", "minetox.place", "minetox.log"];
                break;
            case "youtuber":
                $true = ["minetox.cmd.message", "minetox.youtuber", "minetox.color",
                    "minetox.log.full"];
                break;
            case "extra":
                $true = ["minetox.cmd.message", "minetox.extra", "minetox.color",
                    "minetox.log.full"];
                break;
            case "vip+":
                $true = ["minetox.cmd.message", "minetox.vip+", "minetox.color",
                    "minetox.log.full"];
                break;
            case "vip":
                $true = ["minetox.cmd.message", "minetox.vip", "minetox.color",
                    "minetox.log.full"];
                break;
        }
        /** @var MTCore $mtcore */
        $mtcore = $server->getPluginManager()->getPlugin("MTCore");
        if (!($mtcore instanceof Plugin)){
            return;
        }
        foreach ($true as $pr){
            $p->addAttachment($mtcore, $pr, true);
        }
        foreach ($false as $fr){
            $p->addAttachment($mtcore, $fr, false);
        }
    }

}
