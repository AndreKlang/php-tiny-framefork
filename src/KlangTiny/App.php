<?php

namespace KlangTiny;

use MeekroDB;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\PHPConsoleHandler;

class App {

    const NO_ROUTE_NAME = 'noRoute';

    /** @var object */
    private static $_config = null;

    /** @var MeekroDB */
    private static $_mysql = null;

    /** @var Logger */
    private static $_log = null;

    /** @var \KlangTiny\App\Controller[]  */
    private static $_controllerRegistry = array();

    /** @var \KlangTiny\App\Core\Request */
    private static $_request;

    private static $_configFilePath = "./config.json";

    function __construct() {

        // set up monolog
        self::$_log = new Logger(__CLASS__);
        self::$_log->pushHandler(new StreamHandler('./Parse.log'));
        self::$_log->pushHandler(new PHPConsoleHandler());

        self::$_request = new \KlangTiny\App\Core\Request();
    }

    /**
     * @return \KlangTiny\App\Core\Request
     */
    public static function getRequest() {
        return self::$_request;
    }

    /**
     * @return Logger
     */
    public static function logger(){
        return self::$_log;
    }

    /**
     * @param null|string $key
     * @return object|string|null
     */
    public static function getConfig($key = null){

        $filename = realpath(self::$_configFilePath);

        if(self::$_config === null) self::$_config = json_decode(file_get_contents($filename));

        if($key === null) return self::$_config;

        if(!isset(self::$_config->$key)) return null;

        return self::$_config->$key;

    }

    /**
     * Get a connection to the MySql DB
     * @return MeekroDB
     */
    public static function getMysql(){

        if(self::$_mysql != null) return self::$_mysql;

        $mysqlConfig = self::getConfig("db")->mysql;

        // support for docker
        if(isset($_ENV["MYSQL_HOST"])) $mysqlConfig->host = $_ENV["MYSQL_HOST"];
        if(isset($_ENV["MYSQL_USER"])) $mysqlConfig->user = $_ENV["MYSQL_USER"];
        if(isset($_ENV["MYSQL_PASSWORD"])) $mysqlConfig->password = $_ENV["MYSQL_PASSWORD"];
        if(isset($_ENV["MYSQL_DATABASE"])) $mysqlConfig->dbName = $_ENV["MYSQL_DATABASE"];
        if(isset($_ENV["MYSQL_PORT"])) $mysqlConfig->port = $_ENV["MYSQL_PORT"];

        return self::$_mysql = new MeekroDB(
            $mysqlConfig->host,
            $mysqlConfig->user,
            $mysqlConfig->password,
            $mysqlConfig->dbName,
            $mysqlConfig->port,
            $mysqlConfig->encoding
        );
    }

    /**
     * Register a controller to an URI
     * @param string $uri
     * @param \KlangTiny\App\Controller $controller
     */
    public static function registerController($uri, \KlangTiny\App\Controller $controller){
        self::$_controllerRegistry[$uri] = $controller;
    }

    /**
     * Register the fallback (404) controller
     * @param \KlangTiny\App\Controller $controller
     */
    public static function registerNoRouteController(\KlangTiny\App\Controller $controller){
        self::registerController(self::NO_ROUTE_NAME,$controller);
    }

    /**
     * Get all controllers
     * @return \KlangTiny\App\Controller[]
     */
    public static function getRegisteredControllers(){
        return self::$_controllerRegistry;
    }

    /**
     * @param string $configFilePath
     */
    public static function setConfigFilePath($configFilePath) {
        self::$_configFilePath = $configFilePath;
    }

    /**
     * Run the app
     */
    public static function run(){
        self::$_request->render();
    }

}