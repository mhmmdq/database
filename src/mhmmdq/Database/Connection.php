<?php

namespace Mhmmdq\Database;


use PDO , PDOException;

class Connection{

    private $config = array();
    
    protected static $pdo = null;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->config['driver'] = isset($config['driver']) ? $config['driver'] : 'mysql';
        $this->config['host'] = isset($config['host']) ? $config['host'] : 'localhost';
        $this->config['charset'] = isset($config['charset']) ? $config['charset'] : 'utf8mb4';
        $this->config['collation'] = isset($config['collation']) ? $config['collation'] : 'utf8mb4_general_ci';
        $this->config['port'] = isset($config['port'])
        ? $config['port']
        : (strstr($this->config['host'], ':') ? explode(':', $this->config['host'])[1] : '3306');
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ];
        $dsn = "{$this->config['driver']}:host={$this->config['host']};port={$this->config['port']};dbname={$this->config['database']};charset={$this->config['charset']}";
        
        try {
            self::$pdo = new PDO($dsn, $this->config['username'], $this->config['password'], $options);
       } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage() . $e->getFile() . $e->getLine());
       }
    }

    public static function getConnection() {
        return self::$pdo;
    }
}