<?php

namespace MTCore;

class MySQLManager{

    public static function getMysqlConnection() : \mysqli{
        $database = new \mysqli("db2.gameteam.cz", "pe_stats", "4aHHtzCPjZUtKdjS", "pe_stats");

        return $database;
    }

}