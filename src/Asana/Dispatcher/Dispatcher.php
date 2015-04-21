<?php

namespace Asana\Dispatcher;

use \Httpful;
use \Httpful\Mime;

class Dispatcher
{
    public function request($method, $uri, $requestOptions)
    {
        if (isset($requestOptions['params'])) {
            $qs = http_build_query($requestOptions['params']);
            if (strlen($qs) > 0) {
                $uri .= "?" . $qs;
            }
        }

        $request = $this->createRequest()
            ->method($method)
            ->uri($uri)
            ->expectsJson();

        if (isset($requestOptions['headers'])) {
            $request->addHeaders($requestOptions['headers']);
        }

        if (isset($requestOptions['data'])) {
            $request->sendsJson()->body($requestOptions['data']);
        }

        $tmpFiles = array();
        if (isset($requestOptions['files'])) {
            foreach ($requestOptions['files'] as $name => $file) {
                $tmpFilePath = tempnam(null, null);
                $tmpFiles[] = $tmpFilePath;
                file_put_contents($tmpFilePath, $file[0]);
                $body[$name] = '@' . $tmpFilePath;
                if (isset($file[1]) && $file[1] != null) {
                    $body[$name] .= ';filename=' . $file[1];
                }
                if (isset($file[2]) && $file[2] != null) {
                    $body[$name] .= ';type=' . $file[2];
                }
            }
            $request->body($body)->sendsType(\Httpful\Mime::UPLOAD);
        }

        $this->authenticate($request);

        try {
            $result = $request->send();
            foreach ($tmpFiles as $tmpFilePath) {
                unlink($tmpFilePath);
            }
        } catch (Exception $e) {
            // fake "finally"
            foreach ($tmpFiles as $tmpFilePath) {
                unlink($tmpFilePath);
            }
            throw $e;
        }
        return $result;
    }

    protected function createRequest()
    {
        return \Httpful\Request::init();
    }

    protected function authenticate($request)
    {
        return $request;
    }
}