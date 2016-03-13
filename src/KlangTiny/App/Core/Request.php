<?php
namespace KlangTiny\App\Core;

use KlangTiny\App;
use KlangTiny\App\Controller;
use KlangTiny\App\Core\Request\Exception\ControllerNotFound;

class Request {

    const METHOD_POST = "POST";
    const METHOD_GET = "GET";
    const METHOD_HEAD = "HEAD";
    const METHOD_PUT = "PUT";

    /**
     * Get the uri, but without first /
     * @return string
     */
    function getUri(){
        return substr($_SERVER['REQUEST_URI'], 1);
    }

    /**
     * Get request method
     * one of:
     *      self::METHOD_POST
     *      self::METHOD_GET
     *      self::METHOD_HEAD
     *      self::METHOD_PUT
     * @return string
     */
    function getMethod(){
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Get a value from $context if it exists, $default otherwise
     * @param array $context
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    private function _getParam(array $context, $key = null, $default = null){

        if($key === null) {
            return $context;
        }

        if(!isset($context[$key])) {
            return $default;
        }

        return $context[$key];
    }

    /**
     * Get a value from post, if it exists, $default otherwise
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    function getParamPost($key = null, $default = null){
        return $this->_getParam($_POST, $key, $default);
    }

    /**
     * Get a value from get, if it exists, $default otherwise
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    function getParamGet($key = null, $default = null){
        return $this->_getParam($_GET, $key, $default);
    }

    // TODO: deal with "put"
    function getParamPut(){
    }

    /**
     * @return array|false
     */
    function getHeaders(){
        return getallheaders();
    }

    /**
     * Get the request body
     * @return string
     */
    public function getBody(){
        return file_get_contents('php://input');
    }

    /**
     * Get a controller that matches the response
     * @return Controller
     * @throws ControllerNotFound
     */
    function getController(){

        /**
         * @var string $uri
         * @var Controller $registeredController
         */
        foreach (App::getRegisteredControllers() as $registeredController) {
            if($registeredController->match($this)) {
                return $registeredController;
            }
        }

        if(App::$controllerNoRoute !== null) {
            return App::$controllerNoRoute;
        }

        throw new ControllerNotFound("Controller not found for uri");
    }

    /**
     * Render matching controller
     * @throws ControllerNotFound
     */
    function render(){

        try{

            $controller = $this->getController();
            $controller->preDispatch();
            $controller->render();
            $controller->postDispatch();

        } catch (ControllerNotFound $e) {

            \App::logger()->addError("No controller matching the request: ".$e->getMessage(), array(
                "exception" => $e,
                "request" => $this
            ));

            \App::getResponse()->printException($e);

        } catch (\PDOException $e) {

            \App::logger()->addError("DB-Exception: ".$e->getMessage(), array(
                "exception" => $e,
                "trace" => $e->getTraceAsString(),
                "request" => $this
            ));

            \App::getResponse()->printException($e);

        } catch (\Exception $e) {
            \App::logger()->addError("Uncaught exception: ".$e->getMessage(), array(
                "exception" => $e,
                "trace" => $e->getTraceAsString(),
                "request" => $this
            ));

            \App::getResponse()->printException($e);
        }

    }
}
