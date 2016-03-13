<?php

namespace KlangTiny\App;

use KlangTiny\App;
use KlangTiny\App\Core\Request;

abstract class Controller {

    /**
     * Do not put anything in __construct / __destruct
     * Use preDispatch/postDispatch instead
     * There is a reason for that:
     * If you use the constructor, it will run for ALL controllers on every request
     * since they are initiated when registering controllers
     */
    final public function __construct(){

    }

    /**
     * @see: $this->__construct()
     */
    final public function __destruct(){

    }

    /**
     * This function will decide if the $request matches the current controller
     * Return true if this request matches, false otherwise.
     * @param Request $request
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function match(Request $request){
        return false;
    }

    /**
     * Use this to set other headers and load stuff before rendering
     */
    public function preDispatch(){

    }


    /**
     * Use this if you need to..
     */
    public function postDispatch(){

    }

    /**
     * Start rendering the page
     */
    public function render(){
        App::getResponse()->sendHeaders();
    }
}
