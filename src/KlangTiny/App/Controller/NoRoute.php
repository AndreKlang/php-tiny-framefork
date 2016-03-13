<?php

namespace KlangTiny\App\Controller;

use KlangTiny\App\Core\Request;
use KlangTiny\App\Controller;

class NoRoute extends Controller {

    function preDispatch() {
        parent::preDispatch();

        \App::getRequest()->setResponseCode(404);
    }

    // this controller will always match
    public function match(Request $request){
        return true;
    }

    function render(){
        parent::render();
        echo "404";
    }
}
