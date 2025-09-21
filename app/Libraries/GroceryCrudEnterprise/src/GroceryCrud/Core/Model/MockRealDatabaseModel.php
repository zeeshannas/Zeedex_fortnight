<?php

namespace GroceryCrud\Core\Model;

use \GroceryCrud\Core\Model;
use Laminas\Db\Adapter\Adapter;

class MockRealDatabaseModel extends Model {

    public function setDatabaseConnection($configDb) {
        $this->adapter = new Adapter([
            'driver' => 'Pdo_Mysql',
            'database' => 'database',
            'username' => 'username',
            'password' => 'password',
            'charset' => 'utf8'
        ]);
    }
}