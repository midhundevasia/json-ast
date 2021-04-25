<?php

namespace JsonAst\Exception;

class ParserException extends Exception
{
    const UNEXPECTED_END = 1;
    const UNEXPECTED_TOKEN = 2;

    public function __construct($type, $input, $tokenList, $index, $source)
    {
        switch ($type) {
            case self::UNEXPECTED_END:
                $this->unexpectedEnd($input, $tokenList, $source);
                break;

            case self::UNEXPECTED_TOKEN:
                $this->unexpectedToken($input, $tokenList, $index, $source);
                break;
        }
    }

    public function unexpectedEnd($input, $tokenList, $source)
    {
        $loc = count($tokenList) > 0
            ? $tokenList[count($tokenList) - 1]['loc']['end']
            : ['line' => 1, 'column' => 1];

        @set_exception_handler(array($this, 'exceptionHandler'));
        throw new \Exception(
            $this->setErrorMessage(
                'Unexpected end of input',
                $input,
                $loc['line'],
                $loc['column'],
                $source
            )
        );
    }

    public function unexpectedToken($input, $tokenList, $index, $source)
    {
        $line = $tokenList[$index]['loc']['start']['line'];
        $column = $tokenList[$index]['loc']['start']['column'];
        $startOffset = $tokenList[$index]['loc']['start']['offset'];
        $endOffset = $tokenList[$index]['loc']['end']['offset'];
        @set_exception_handler(array($this, 'exceptionHandler'));
        $token = substr($input, $startOffset , 1);
        throw new \Exception(
            $this->setErrorMessage(
                sprintf(
                    'Unexpected token <%s> at %d:%d', $token, $line, $column
                ),
                $input,
                $line,
                $column,
                $source
            )
        );
    }
}
