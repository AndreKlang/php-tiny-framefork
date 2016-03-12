<?php
namespace KlangTiny\App\Core;

use KlangTiny\App;
use KlangTiny\App\Controller;
use KlangTiny\App\Core\Request\Exception\ControllerNotFound;
use KlangTiny\App\Core\Request\StatusCode;

class Request {

    const METHOD_POST = "POST";
    const METHOD_GET = "GET";
    const METHOD_HEAD = "HEAD";
    const METHOD_PUT = "PUT";

    private $_responseCode = 200;
    private $_contentType = 'text/html';
    private $_charSet = 'utf-8';
    private $_extraHeaders = array();

    function getUri(){
        return substr($_SERVER['REQUEST_URI'],1);
    }

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
    private function _getParam(array $context ,$key = null, $default = null){

        if($key === null) return $context;

        if(!isset($context[$key])) return $default;

        return $context[$key];
    }

    /**
     * Get a value from post, if it exists, $default otherwise
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    function getParamPost($key = null, $default = null){
        return $this->_getParam($_POST,$key,$default);
    }

    /**
     * Get a value from get, if it exists, $default otherwise
     * @param null|string $key
     * @param mixed $default
     * @return mixed
     */
    function getParamGet($key = null, $default = null){
        return $this->_getParam($_GET,$key,$default);
    }

    // TODO: deal with "put"
    function getParamPut(){
    }

    /**
     * Set the charset of the response
     * @param string $charSet
     * @return $this
     */
    public function setCharSet($charSet) {
        $this->_charSet = $charSet;

        return $this;
    }

    /**
     * Set the content type of the response
     * @param string $contentType
     * @return $this
     */
    public function setContentType($contentType) {
        $this->_contentType = $contentType;

        return $this;
    }

    /**
     * Add extra headers to the response
     * @param $key string
     * @param $value string
     * @return $this
     */
    public function addExtraHeaders($key, $value) {
        $this->_extraHeaders[$key] = $value;

        return $this;
    }

    /**
     * Set http response code
     * @param int $responseCode
     * @return $this
     */
    public function setResponseCode($responseCode) {
        $this->_responseCode = $responseCode;

        return $this;
    }

    /**
     * Get all headers as array of string
     * @return string[]
     * @throws Request\Exception\UnknownStatusCode
     */
    function getHeaders(){
        $headers = array();

        // content type & charset
        $headers[] = sprintf("Content-type: %s; charset= %s",$this->_contentType,$this->_charSet);

        // response code
        $headers[] = sprintf(
            "%s %s %s",
            (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'),
            $this->_responseCode,
            (new StatusCode())->getText($this->_responseCode)
        );

        // extra headers
        foreach($this->_extraHeaders as $key => $value){
            $headers[] = sprintf("%s: %s", $key, $value);
        }

        return $headers;
    }

    /**
     * Send all headers to browser
     * @return $this
     */
    function sendHeaders(){
        foreach($this->getHeaders() as $header){
            header($header);
        }
        return $this;
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
        foreach (\App::getRegisteredControllers() as $registeredController) {
            if($registeredController->match($this)){
                return $registeredController;
            }
        }

        if(App::$controllerNoRoute !== null) return App::$controllerNoRoute;

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

            \App::logger()->addError("No controller matching the request: ".$e->getMessage(),array(
                "exception" => $e,
                "request" => $this
            ));

            $this->printException($e);

        } catch (\MeekroDBException $e){

            \App::logger()->addError("DB-Exception: ".$e->getMessage(),array(
                "exception" => $e,
                "query" => $e->getQuery(),
                "trace" => $e->getTraceAsString(),
                "request" => $this
            ));

            $this->printException($e);

        } catch (\Exception $e){
            \App::logger()->addError("Uncaught exception: ".$e->getMessage(),array(
                "exception" => $e,
                "trace" => $e->getTraceAsString(),
                "request" => $this
            ));

            $this->printException($e);
        }

    }

    public function printException(\Exception $e){
        if (!App::isIsDeveloperMode()) return;

        echo $e->getMessage()."\n";
        if($e instanceof \MeekroDBException) echo "Query: ".$e->getQuery()."\n";
        echo $e->getTraceAsString();
    }
}