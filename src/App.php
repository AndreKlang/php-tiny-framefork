<?php

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

    /** @var \App\Controller[]  */
    private static $_controllerRegistry = array();

    /** @var \App\Core\Request */
    private static $_request;

    function __construct() {

        // set up monolog
        self::$_log = new Logger(__CLASS__);
        self::$_log->pushHandler(new StreamHandler('./Parse.log'));
        self::$_log->pushHandler(new PHPConsoleHandler());

        self::$_request = new \App\Core\Request();
    }

    /**
     * @return \App\Core\Request
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
        if(self::$_config === null) self::$_config = json_decode(file_get_contents("config.json"));

        if($key === null) return self::$_config;

        if(!isset(self::$_config->$key)) return null;

        return self::$_config->$key;

    }

    /**
     * Get a connection the MySql DB
     * @return MeekroDB
     */
    public static function getMysql(){

        if(self::$_mysql != null) return self::$_mysql;

        $mysqlConfig = self::getConfig("db")->mysql;

        $encoding = 'utf8_swedish_ci';

        return self::$_mysql = new MeekroDB($mysqlConfig->host, $mysqlConfig->user, $mysqlConfig->password, $mysqlConfig->dbName, $mysqlConfig->port, $encoding);
    }

    /**
     * Register a controller to an URI
     * @param string $uri
     * @param \App\Controller $controller
     */
    public static function registerController($uri, \App\Controller $controller){
        self::$_controllerRegistry[$uri] = $controller;
    }

    /**
     * Register the fallback (404) controller
     * @param \App\Controller $controller
     */
    public static function registerNoRouteController(\App\Controller $controller){
        self::registerController(self::NO_ROUTE_NAME,$controller);
    }

    /**
     * Get all controllers
     * @return \App\Controller[]
     */
    public static function getRegisteredControllers(){
        return self::$_controllerRegistry;
    }

    /**
     * Run the app
     */
    public static function run(){
        self::$_request->render();
    }

}