<?php

namespace Tuxed\Http;

class HttpResponse {

    private $_headers;
    private $_content;
    private $_statusCode;
    private $_statusCodes = array(
        100 => "Continue",
        101 => "Switching Protocols",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        203 => "Non-Authoritative Information",
        204 => "No Content",
        205 => "Reset Content",
        206 => "Partial Content",
        300 => "Multiple Choices",
        301 => "Moved Permanently",
        302 => "Found",
        303 => "See Other",
        304 => "Not Modified",
        305 => "Use Proxy",
        306 => "(Unused)",
        307 => "Temporary Redirect",
        400 => "Bad Request",
        401 => "Unauthorized",
        402 => "Payment Required",
        403 => "Forbidden",
        404 => "Not Found",
        405 => "Method Not Allowed",
        406 => "Not Acceptable",
        407 => "Proxy Authentication Required",
        408 => "Request Timeout",
        409 => "Conflict",
        410 => "Gone",
        411 => "Length Required",
        412 => "Precondition Failed",
        413 => "Request Entity Too Large",
        414 => "Request-URI Too Long",
        415 => "Unsupported Media Type",
        416 => "Requested Range Not Satisfiable",
        417 => "Expectation Failed",
        500 => "Internal Server Error",
        501 => "Not Implemented",
        502 => "Bad Gateway",
        503 => "Service Unavailable",
        504 => "Gateway Timeout",
        505 => "HTTP Version Not Supported"
    );

    public function __construct($statusCode = 200) {
        $this->_headers = array();

        $this->setStatusCode($statusCode);
        $this->setContentType("text/html; charset=utf-8");
        $this->setContent(NULL);
    }

    public function getContent() {
        return $this->_content;
    }

    public function getStatusCode() {
        return $this->_statusCode;
    }

    public function getStatusReason() {
        return $this->_statusCodes[$this->_statusCode];
    }

    public function setContentType($contentType) {
        $this->setHeader("Content-Type", $contentType);
    }

    public function getContentType() {
        return $this->getHeader("Content-Type");
    }

    public function setContent($content) {
        $this->_content = $content;
    }

    public function setStatusCode($code) {
        if (!is_numeric($code) || !array_key_exists($code, $this->_statusCodes)) {
            throw new HttpResponseException("invalid status code");
        }
        $this->_statusCode = $code;
    }

    public function setHeader($headerKey, $headerValue) {
        $foundHeaderKey = $this->_getHeaderKey($headerKey);
        if ($foundHeaderKey === NULL) {
            $this->_headers[$headerKey] = $headerValue;
        } else {
            $this->_headers[$foundHeaderKey] = $headerValue;
        }
    }

    public function getHeaders() {
        return $this->_headers;
    }

    public function getHeader($headerKey) {
        $headerKey = $this->_getHeaderKey($headerKey);
        if ($headerKey === NULL) {
            throw new HttpResponseException("no such header");
        }
        return $this->_headers[$headerKey];
    }

    /**
     * Look for a header in a case insensitive way. It is possible to have a 
     * header key "Content-type" or a header key "Content-Type", these should
     * be treated as the same header.
     * 
     * @param headerName the name of the header to search for
     * @returns The name of the header as it was set (original case)
     *
     */
    private function _getHeaderKey($headerKey) {
        $headerKeys = array_keys($this->_headers);
        $keyPositionInArray = array_search(strtolower($headerKey), array_map('strtolower', $headerKeys));
        return ($keyPositionInArray === FALSE) ? NULL : $headerKeys[$keyPositionInArray];
    }

    public function getStatusLine() { 
        return "HTTP/1.1 " . $this->getStatusCode() . " " . $this->getStatusReason();
    }

    public function sendResponse() {
        header($this->getStatusLine());
        foreach ($this->getHeaders() as $k => $v) {
            header($k . ": " . $v);
        }
        echo $this->getContent();
    }

    public function __toString() {
        $s  = PHP_EOL;
        $s .= "*HttpResponse*" . PHP_EOL;
        $s .= "Status:" . PHP_EOL;
        $s .= "\t" . $this->getStatusLine() . PHP_EOL;
        $s .= "Headers:" . PHP_EOL;
        foreach ($this->getHeaders() as $k => $v) {
            $s .= "\t" . ($k . ": " . $v) . PHP_EOL;
        }
        $s .= "Content:" . PHP_EOL;
        $s .= "\t" . $this->getContent() . PHP_EOL;
        return $s;
    }

}
