<?php

namespace KlangTiny\App\Controller;

use KlangTiny\App\Controller;
use KlangTiny\App\Model\User as UserModel;

class User extends Controller {

    function preDispatch() {
        parent::preDispatch();

        \App::getRequest()->setContentType("text/plain");
    }

    function render(){
        parent::render();

        $user = new UserModel();
        $user->load(1);

        var_dump($user->getName());
    }
}