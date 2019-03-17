<?php

namespace Detrack\ElasticRoute;

class BadFieldException extends \Exception
{
    public function __construct($message, $obj = null, $code = null, $previous = null)
    {
        parent::__construct($message.' '.var_export($obj, true), $code, $previous);
    }
}
