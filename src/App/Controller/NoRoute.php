<?php

namespace App\Controller;

use App\Controller;

class NoRoute extends Controller {

    function preDispatch() {
        parent::preDispatch();

        \App::getRequest()->setResponseCode(404);
    }

    function render(){
        parent::render();
        echo "404";
    }
}