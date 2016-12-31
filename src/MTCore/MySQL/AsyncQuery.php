<?php

namespace MTCore\MySQL;

use MTCore\MySQLManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;

abstract class AsyncQuery extends AsyncTask{

    const MYSQLI_KEY = "MTCore.MySQL";

    protected $table = "freezecraft";
    protected $player;

    public function __construct(PluginBase $plugin){
        $plugin->getServer()->getScheduler()->scheduleAsyncTask($this);
    }

    public function onRun(){
        $data = $this->getPlayer($this->player, $this->table);

        $this->onQuery(is_array($data) ? $data : []);
    }

    protected function onQuery(array $data){

    }

    public function onCompletion(Server $server){

    }

    protected function getMysqli(){
        $mysqli = $this->getFromThreadStore(self::MYSQLI_KEY);

        if($mysqli !== null){
            return $mysqli;
        }

        $mysqli = MySQLManager::getMysqlConnection();
        $this->saveToThreadStore(self::MYSQLI_KEY, $mysqli);
        return $mysqli;
    }

    public function getPlayer($player, $table = "freezecraft"){

        $name = $this->getMysqli()->escape_string(trim(strtolower($player)));

        $result = $this->getMysqli()->query
        (
            "SELECT * FROM ".$table." WHERE name = '" . $name ."'"
        );
        if($result instanceof \mysqli_result){
            $data = $result->fetch_assoc();
            $result->free();
            if(isset($data["name"]) and $data["name"] === trim(strtolower($player))){
                unset($data["name"]);
                return $data;
            }
        }
        return null;
    }

    public function registerPlayer($player){
        $database = $this->getMysqli();
        $name = trim(strtolower($player));
        $data =
            [
                "name" => $name,
                "rank" => "hrac",
                "doba" => 0,
                "tokens" => 0,
            ];

        $database->query
        (
            "INSERT INTO freezecraft (
            name, rank, doba, tokens)
            VALUES
            ('".$database->escape_string($name)."', '".$data["rank"]."', '".$data["doba"].
                "', '".$data["tokens"]."')"
        );
        return $data;
    }

    public function registerStats($player){
        $database = $this->getMysqli();
        $name = trim(strtolower($player));
        $database->query
        (
            "INSERT INTO stats (name) VALUES ('".$database->escape_string($name)."')"
        );
        /*$database->query
        (
            "INSERT INTO stats (name) VALUES ('".$database->escape_string($name)."|daily"."')"
        );
        $database->query
        (
            "INSERT INTO stats (name) VALUES ('".$database->escape_string($name)."|weekly"."')"
        );
        $database->query
        (
            "INSERT INTO stats (name) VALUES ('".$database->escape_string($name)."|monthly"."')"
        );*/
    }

    public function setPlayer($player, $key, $value = 1, $action = 0, $table = "freezecraft"){
        $database = $this->getMysqli();
        if ($action === 0){
            $database->query(
                "UPDATE ".$table." SET ".$key." = '".$value."' WHERE name = '".$this->getMysqli()->escape_string(trim(strtolower($player)))."'"
            );
        }
        elseif ($action === 1){
            $database->query(
                "UPDATE ".$table." SET ".$key." = $key+'".$value."' WHERE name = '".$this->getMysqli()->escape_string(trim(strtolower($player)))."'"
            );
        }
        elseif ($action === 2){
            $database->query(
                "UPDATE ".$table." SET ".$key." = ".$key."-'".$value."' WHERE name = '".$this->getMysqli()->escape_string(trim(strtolower($player)))."'"
            );
        }
    }

    public function addKit($p, $kit){
        $database = $this->getMysqli();

        $database->query
        (
            "UPDATE annihilation SET kits = '".$this->getPlayer($p, "annihilation")["kits"]."|".$kit."' WHERE name = '".$database->escape_string(trim(strtolower($p)))."'"
        );
    }

    public function ban($p, $reason, $who, $banData){
        if($banData != null){
            $this->getMysqli()->query
            (
                "UPDATE banlist SET reason = '".$reason."', expiration = 1, banner = '".$who."' WHERE name = '".$this->getMysqli()->escape_string(trim(strtolower($p)))."'"
            );
        } else {
            $name = trim(strtolower($p));
            $data =
                [
                    "name" => $name,
                    "reason" => $reason,
                    "expiration" => time() + 7 * 24 * 3600,
                ];

            $this->getMysqli()->query
            (
                "INSERT INTO banlist (
            name, reason, expiration, banner)
            VALUES
            ('".$this->getMysqli()->escape_string($name)."', '".$data["reason"]."', '".$data["expiration"]."', '".$who."')"
            );
        }
    }

}