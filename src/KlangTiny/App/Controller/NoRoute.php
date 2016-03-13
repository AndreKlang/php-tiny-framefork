<?php

namespace KlangTiny\App\Controller;

use KlangTiny\App;
use KlangTiny\App\Core\Request;
use KlangTiny\App\Controller;

class NoRoute extends Controller {

    public function preDispatch() {
        parent::preDispatch();

        App::getResponse()->setResponseCode(404);
    }

    // this controller will always match
    public function match(Request $request){
        return true;
    }

    public function render(){
        parent::render();
        echo "404";
    }
}
