<?php
namespace KlangTiny\App\Core;

use KlangTiny\App;
use KlangTiny\App\Controller;
use KlangTiny\App\Core\Response\StatusCode;

class Response {

    const METHOD_POST = "POST";
    const METHOD_GET = "GET";
    const METHOD_HEAD = "HEAD";
    const METHOD_PUT = "PUT";

    private $_responseCode = 200;
    private $_contentType = 'text/html';
    private $_charSet = 'utf-8';
    private $_extraHeaders = array();

    /**
     * Get the uri, but without first /
     * @return string
     */
    public function getUri(){
        return substr($_SERVER['REQUEST_URI'], 1);
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
     * @throws Response\Exception\UnknownStatusCode
     */
    public function getHeaders(){
        $headers = array();

        // content type & charset
        $headers[] = sprintf("Content-type: %s; charset= %s", $this->_contentType, $this->_charSet);

        // response code
        $headers[] = sprintf(
            "%s %s %s",
            (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0'),
            $this->_responseCode,
            (new StatusCode())->getText($this->_responseCode)
        );

        // extra headers
        foreach($this->_extraHeaders as $key => $value) {
            $headers[] = sprintf("%s: %s", $key, $value);
        }

        return $headers;
    }

    /**
     * Send all headers to browser
     * @return $this
     */
    public function sendHeaders(){
        foreach($this->getHeaders() as $header) {
            header($header);
        }
        return $this;
    }

    public function printException(\Exception $exception){
        if (!App::isIsDeveloperMode()) {
            return;
        }

        echo $exception->getMessage()."\n";
        echo get_class($exception)."\n";
        echo $exception->getTraceAsString();
    }
}
