<?php
/**
 * Created by PhpStorm.
 * User: oshry
 * Date: 5/17/15
 * Time: 1:35 PM
 */
return [
    'default' => [
        'connection' => [
            'dsn'        => 'mysql:host=172.22.0.2;dbname=my_db',
            'username'   => 'root',
            'password'   => 'password',
            'persistent' => FALSE,
            'options'    => [
                //'PDO::ATTR_PERSISTENT' => true,
                'PDO::ATTR_ERRMODE'    => 'PDO::ERRMODE_EXCEPTION'
            ],
        ],
        'charset'      => 'utf8',
        'caching'      => FALSE,
    ]
];
