<?php
/**
 * Created by PhpStorm.
 * User: oshry
 * Date: 2019-07-28
 * Time: 16:42
 */
session_start();
require_once './env.php';
require DOCROOT.'vendor/autoload.php';
$config = include './config/db.php';
//singleton db
$db = Repository\DataRepository::instance($config, 'default');
$doc = [];

$doc['doc']['description'] = "blat";
$view = Classes\View::factory('method', $doc)->render();



die($view);
//die('test11'.DOCROOT.'vendor/autoload.php');
