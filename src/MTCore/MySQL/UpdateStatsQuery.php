<?php

namespace MTCore\MySQL;

use MTCore\MTCore;

class UpdateStatsQuery extends AsyncQuery{

    public $type;
    public $value;

    const BREAKS = 0;
    const PLACES = 1;
    const DEATHS = 2;
    const KILLS = 3;
    const CRAFTED = 4;
    const ENCHANTED = 5;
    const EATEN = 6;


    public function __construct(MTCore $plugin, $player, $type, $value = 1){
        $this->player = $player;
        $this->type = $type;
        $this->value = $value;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        $section = "";
        switch ($this->type){
            case self::BREAKS:
                $section = "breaks";
                break;
            case self::PLACES:
                $section = "places";
                break;
            case self::DEATHS:
                $section = "deaths";
                break;
            case self::KILLS:
                $section = "kills";
                break;
            case self::CRAFTED:
                $section = "crafted";
                break;
            case self::ENCHANTED:
                $section = "enchanted";
                break;
            case self::EATEN:
                $section = "eaten";
                break;
        }
        $this->setPlayer($this->player, $section, $this->value, 1, "stats");
        /*$this->setPlayer($this->player."|daily", $section, $this->value, 1, "stats");
        $this->setPlayer($this->player."|monthly", $section, $this->value, 1, "stats");
        $this->setPlayer($this->player."|monthly", $section, $this->value, 1, "stats");*/
    }


}