<?php

namespace Tuxed\Http;

class OutgoingHttpRequest {

    public function makeRequest(HttpRequest $request) {
        $httpResponse = new HttpResponse();

        $curlChannel = curl_init();
        curl_setopt($curlChannel, CURLOPT_URL, $request->getRequestUri()->getUri());
        curl_setopt($curlChannel, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curlChannel, CURLOPT_RETURNTRANSFER, 1);

        if ($request->getRequestMethod() === "POST") {
            curl_setopt($curlChannel, CURLOPT_POST, 1);
            curl_setopt($curlChannel, CURLOPT_POSTFIELDS, $request->getContent());
        }

        // Including HTTP headers in request
        $headers = $request->getHeaders(TRUE);
        if (!empty($headers)) {
            curl_setopt($curlChannel, CURLOPT_HTTPHEADER, $headers);
        }

        // Connect to SSL/TLS server, validate certificate and host
        if ($request->getRequestUri()->getScheme() === "https") {
            curl_setopt($curlChannel, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($curlChannel, CURLOPT_SSL_VERIFYHOST, 2);
        }

        // Callback to extract all the HTTP headers from the response...
        // In order to really correctly parse HTTP headers one would have to look at RFC 2616...
        curl_setopt($curlChannel, CURLOPT_HEADERFUNCTION, function($curlChannel, $header) use ($httpResponse) {
                    // Ignore Status-Line (RFC 2616, section 6.1)
                    if (0 === preg_match('|^HTTP/\d+.\d+ [1-5]\d\d|', $header)) {
                        // Only deal with header lines that contain a colon
                        if (strpos($header, ":") !== FALSE) {
                            // Only deal with header lines that contain a colon
                            list($key, $value) = explode(":", trim($header));
                            $httpResponse->setHeader(trim($key), trim($value));
                        }
                    }
                    return strlen($header);
                });

        $output = curl_exec($curlChannel);
        $httpResponse->setStatusCode(curl_getinfo($curlChannel, CURLINFO_HTTP_CODE));
        $httpResponse->setContent($output);
        curl_close($curlChannel);
        return $httpResponse;
    }

}
