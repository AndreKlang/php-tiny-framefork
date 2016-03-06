<?php
namespace App\Core;

use App\Core\Request\Exception\ControllerNotFound;
use App\Core\Request\StatusCode;

class Request {

    private $_responseCode = 200;
    private $_contentType = 'text/html';
    private $_charSet = 'utf-8';
    private $_extraHeaders = array();

    private $_params = array(
        "get" => array(),
        "post" => array()
    );

    function __construct() {
        $this->_params["get"] = $_GET;
        $this->_params["post"] = $_POST;
    }

    /**
     * @param string $charSet
     * @return $this
     */
    public function setCharSet($charSet) {
        $this->_charSet = $charSet;

        return $this;
    }

    /**
     * @param string $contentType
     * @return $this
     */
    public function setContentType($contentType) {
        $this->_contentType = $contentType;

        return $this;
    }

    /**
     * @param $key string
     * @param $value string
     * @return $this
     */
    public function addExtraHeaders($key, $value) {
        $this->_extraHeaders[$key] = $value;

        return $this;
    }

    /**
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
     * Get a controller based on $uri (if set), based on request otherwise
     * @param null $uri
     * @return \App\Controller
     * @throws ControllerNotFound
     */
    function getController($uri = null){

        if($uri === null) $targetUri = $_SERVER["REQUEST_URI"];
        else $targetUri = $uri;

        // just for my dev env
        // TODO:remove
        $targetUri = str_replace("/fonder",'',$targetUri);

        /**
         * @var string $uri
         * @var \App\Controller $registeredController
         */
        foreach (\App::getRegisteredControllers() as $uri => $registeredController) {
            if($uri == $targetUri) return $registeredController;
        }

        throw new ControllerNotFound("Controller not found for uri: ". $targetUri);
    }

    /**
     * Render the controller
     * @throws ControllerNotFound
     */
    function render(){

        try{

            $controller = $this->getController();
            $controller->preDispatch();
            $controller->render();
            $controller->postDispatch();

        } catch (ControllerNotFound $e) {

            $controller = $this->getController(\App::NO_ROUTE_NAME);
            $controller->preDispatch();
            $controller->render();
            $controller->postDispatch();

        } catch (\Exception $e){
            \App::logger()->addError("Uncaught exception: ".$e->getMessage(),array(
                "exception" => $e,
                "trace" => $e->getTraceAsString(),
                "request" => $this
            ));
        }

    }
}