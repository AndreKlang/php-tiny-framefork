<?php

namespace App;
use App;

class Controller {

    /**
     * Do not put anything in __construct / __destruct
     * Use preDispatch/postDispatch instead
     * There is a reason for that:
     * If you use the constructor, it will run for ALL controllers on every request
     * since they are initiated when registering controllers
     */
    final function __construct(){

    }

    /**
     * @see: $this->__construct()
     */
    final function __destruct(){

    }

    /**
     * Use this to set other headers and load stuff before rendering
     */
    function preDispatch(){

    }


    /**
     * Use this if you need to..
     */
    function postDispatch(){

    }

    function render(){
        App::getRequest()->sendHeaders();
    }

}