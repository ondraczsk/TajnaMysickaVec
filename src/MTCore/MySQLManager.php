<?php

namespace MTCore;

class MySQLManager{

    public static function getMysqlConnection() : \mysqli{
        $database = new \mysqli("82.208.17.193", "pe_stats", "centrum", "pe_stats");

        return $database;
    }

}
