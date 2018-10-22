<?php

namespace App\Tests;

use Nette\Http\Request;
use Nette\Http\RequestFactory as NetteRequestFactory;

class RequestFactory extends NetteRequestFactory {

    private $myRawBodyCallback;

    public function setRawBodyCallback(callable $callback) {
        $this->myRawBodyCallback = $callback;
    }

    public function createHttpRequest() {
        $r = parent::createHttpRequest();
        return new Request(
                $r->getUrl(), null, $r->getPost(), $r->getFiles(), $r->getCookies(), $r->getHeaders(), $r->getMethod(), $r->getRemoteAddress(), $r->getRemoteHost(), $this->myRawBodyCallback ? $this->myRawBodyCallback : function() use ($r) {
            return $r->getRawBody();
        });
    }

}
