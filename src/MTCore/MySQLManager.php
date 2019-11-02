<?php

namespace MTCore;

class MySQLManager{

    public static function getMysqlConnection() : \mysqli{
        //not working database
        $database = new \mysqli("82.208.17.193", "pe_stats", "admin", "pe_stats");

        return $database;
    }

}
