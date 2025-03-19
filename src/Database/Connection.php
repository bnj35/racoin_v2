<?php

namespace Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class Connection {

    public static function CreateConnection() {
        $config = parse_ini_file(__DIR__ . '/../../config/config.ini');
        if ($config === false) {
            throw new \Exception('Failed to read database configuration file.');
        }

        $capsule = new DB;
        $capsule->addConnection($config);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}
