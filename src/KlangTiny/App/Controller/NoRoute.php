<?php

namespace KlangTiny\App\Controller;

use KlangTiny\App\Controller;

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