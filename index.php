<?php
require_once "vendor/autoload.php";

use App\Controller;
use App\Controller\NoRoute;

$app = new App();

App::registerController("/user",new Controller\User());
App::registerNoRouteController(new NoRoute());

App::run();