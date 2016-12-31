<?php

namespace MTCore\MySQL;

use MTCore\MTCore;

class SubstractTokensQuery extends AsyncQuery{

    private $tokens;

    public function __construct(MTCore $plugin, $player, $tokens){
        $this->player = $player;
        $this->tokens = $tokens;

        parent::__construct($plugin);
    }

    public function onQuery(array $data){
        if($data != null){
            $this->setPlayer($this->player, "tokens", $this->tokens, 2);
        }
    }

}