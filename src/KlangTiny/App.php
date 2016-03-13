<?php

namespace KlangTiny;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\PHPConsoleHandler;
use Pixie\QueryBuilder\QueryBuilderHandler;

class App {

    /** @var object */
    private static $_config = null;

    /** @var QueryBuilderHandler */
    private static $_mysql = null;

    /** @var Logger */
    private static $_log = null;

    /** @var \KlangTiny\App\Controller[]  */
    private static $_controllerRegistry = array();

    /** @var \KlangTiny\App\Controller|null  */
    public static $controllerNoRoute = null;

    /** @var \KlangTiny\App\Core\Request */
    private static $_request;

    /** @var \KlangTiny\App\Core\Response */
    private static $_response;

    private static $_configFilePath = "./config.json";

    private static $_logFolder = '/var/log';

    private static $_isDeveloperMode = false;

    public function __construct() {

        // add document root to path
        self::$_logFolder = $_SERVER['DOCUMENT_ROOT'].self::$_logFolder;

        if(!file_exists(self::$_logFolder)) {
            mkdir(self::$_logFolder, 0755, true);
        }

        // set up monolog
        self::$_log = new Logger("System");
        self::$_log->pushHandler(new StreamHandler(self::$_logFolder.'/system.log'));
        self::$_log->pushHandler(new PHPConsoleHandler());

        // initiate the request
        self::$_request = new \KlangTiny\App\Core\Request();

        // initiate the response
        self::$_response = new \KlangTiny\App\Core\Response();
    }

    /**
     * @return \KlangTiny\App\Core\Request
     */
    public static function getRequest() {
        return self::$_request;
    }

    /**
     * @return \KlangTiny\App\Core\Response
     */
    public static function getResponse() {
        return self::$_response;
    }

    /**
     * @return Logger
     */
    public static function logger(){
        return self::$_log;
    }

    /**
     * @param null|string $key
     * @return mixed
     */
    public static function getConfig($key = null, $default = null){

        $filename = realpath(self::$_configFilePath);

        // load config once
        if(self::$_config === null) {
            if($filename){
                self::$_config = json_decode(file_get_contents($filename));
            } else {
                self::$_config = new \stdClass();
            }
        }

        // return all if no key
        if($key === null) {
            return self::$_config;
        }

        // if specified does not exist key exists, return $default
        if(!isset(self::$_config->$key)) {
            return $default;
        }

        // if, after all, a key was found, return it
        return self::$_config->$key;

    }

    /**
     * Get a connection to the MySql DB
     * @return QueryBuilderHandler
     */
    public static function getMysql(){

        if(self::$_mysql != null) {
            return self::$_mysql;
        }

        $mysqlConfig = self::getConfig("db")->mysql;

        // support for docker or runtime configuration
        if(isset($_ENV["MYSQL_HOST"])) {
            $mysqlConfig->host = $_ENV["MYSQL_HOST"];
        }
        if(isset($_ENV["MYSQL_USER"])) {
            $mysqlConfig->user = $_ENV["MYSQL_USER"];
        }
        if(isset($_ENV["MYSQL_PASSWORD"])) {
            $mysqlConfig->password = $_ENV["MYSQL_PASSWORD"];
        }
        if(isset($_ENV["MYSQL_DATABASE"])) {
            $mysqlConfig->dbName = $_ENV["MYSQL_DATABASE"];
        }
        if(isset($_ENV["MYSQL_PORT"])) {
            $mysqlConfig->port = $_ENV["MYSQL_PORT"];
        }

        $config = array(
            'driver'    => 'mysql',
            'host'      => $mysqlConfig->host,
            'port'      => $mysqlConfig->port,
            'database'  => $mysqlConfig->dbName,
            'username'  => $mysqlConfig->user,
            'password'  => $mysqlConfig->password,
            'collation' => $mysqlConfig->encoding
        );

        $connection = new \Pixie\Connection('mysql', $config);
        self::$_mysql = new \Pixie\QueryBuilder\QueryBuilderHandler($connection);

        return self::$_mysql;
    }

    /**
     * Register a controller
     * @param string $uri
     * @param \KlangTiny\App\Controller $controller
     */
    public static function registerController(\KlangTiny\App\Controller $controller){
        self::$_controllerRegistry[] = $controller;
    }

    /**
     * Register the fallback (404) controller
     * @param \KlangTiny\App\Controller $controller
     */
    public static function registerNoRouteController(\KlangTiny\App\Controller $controller){
        self::$controllerNoRoute = $controller;
    }

    /**
     * Get all controllers
     * @return \KlangTiny\App\Controller[]
     */
    public static function getRegisteredControllers(){
        return self::$_controllerRegistry;
    }

    /**
     * Set custom path to config-file
     * @param string $configFilePath
     */
    public static function setConfigFilePath($configFilePath) {
        self::$_configFilePath = $configFilePath;
    }

    /**
     * @return boolean
     */
    public static function isIsDeveloperMode() {
        return self::$_isDeveloperMode;
    }

    /**
     * @param boolean $isDeveloperMode
     */
    public static function setIsDeveloperMode($isDeveloperMode) {
        self::$_isDeveloperMode = $isDeveloperMode;
    }

    /**
     * Run the app
     */
    public static function run(){
        self::$_request->render();
    }
}
