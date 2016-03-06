<?php
require_once "vendor/autoload.php";

use Klang\Tiny\App\Controller;
use Klang\Tiny\App\Controller\NoRoute;

$app = new App();

App::registerController("/user",new Controller\User());
App::registerNoRouteController(new NoRoute());

App::run();