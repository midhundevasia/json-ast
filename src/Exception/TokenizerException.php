<?php
namespace JsonAst\Exception;

class TokenizerException extends Exception
{
    const UNEXPECTED_SYMBOL = 1;

    public function __construct($message, $type, $input, $line, $column, $source)
    {
        $this->line = $line;
        $this->column = $column;
        $this->input = $input;
        $this->source = $source;

        switch ($type) {
        case self::UNEXPECTED_SYMBOL:
            $this->unexpectedSymbol($message, $input, $source);
            break;
        }
    }

    public function unexpectedSymbol($symbol)
    {
        @set_exception_handler(array($this, 'exceptionHandler'));
        throw new \Exception(
            $this->setErrorMessage(
                sprintf(
                    'Unexpected symbol <%s> at %d:%d',
                    $symbol,
                    $this->line,
                    $this->column
                ),
                $this->input,
                $this->line,
                $this->column,
                $this->source
            )
        );
    }
}
