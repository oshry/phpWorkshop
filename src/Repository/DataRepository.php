<?php
namespace Repository;
use PDO;

class DataRepository {
    public static $instances = [];

    public static $default = 'default';

    public function __construct(array $config, $name){
        $this->instance = $name;
        $this->config = $config;
        try {
            $this->db = new PDO(
                $config['connection']['dsn'],
                $config['connection']['username'],
                $config['connection']['password'],
                $config['connection']['options']
            );
        }
        catch(PDOException $e){
            $this->error = $e->getMessage();
        }
    }

    public static function instance(array $config, $name = NULL)
    {
        if ($name === NULL)
        {
            $name = static::$default;
        }

        if ( ! isset(static::$instances[$name]))
        {
            static::$instances[$name] = new static($config[$name], $name);
        }
        return static::$instances[$name];
    }
    public function quote($value)
    {
        print_r($this->db->quote);
        die('sdsdssdd');
        return $this->db->quote($value);
    }

    public function query($sql){
        $query = $this->db->query($sql);
        $query->setFetchMode(PDO::FETCH_ASSOC);
        return $query->fetchAll();
    }
}
