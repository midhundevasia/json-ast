<?php

namespace JsonAst\Exception;

use JsonAst\Helper\CodeFragment;

class Exception
{
    public function exceptionHandler($exception)
    {
        print 'SyntaxError: ' . $exception->getMessage() . PHP_EOL;
    }

    public function getError()
    {

    }

    protected function setErrorMessage($message, $input, $line, $column, $source)
    {
        $code = (new CodeFragment)::getFragment($input, $line, $column, []);
        $message = $line ? $message . "\n" . $code : $message;
        return $message;
    }
}
