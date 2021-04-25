<?php
namespace JsonAst\Token;

use JsonAst\State\StringStates;
use JsonAst\State\NumberStates;
use JsonAst\Helper\Location;
use JsonAst\Helper\NumberHelper;
use JsonAst\Exception\TokenizerException;

class Tokenizer
{
    // Lexeme: Token
    private const punctuatorTokensMap = [
        '{' => TokenTypes::T_LEFT_BRACE,
        '}' => TokenTypes::T_RIGHT_BRACE,
        '[' => TokenTypes::T_LEFT_BRACKET,
        ']' => TokenTypes::T_RIGHT_BRACKET,
        ':' => TokenTypes::T_COLON,
        ',' => TokenTypes::T_COMMA
    ];

    private const keywordTokensMap = [
        'true'  => TokenTypes::T_TRUE,
        'false' => TokenTypes::T_FALSE,
        'null'  => TokenTypes::T_NULL
    ];

    private const ESCAPES = [
        '"'     => 0,
        '\\'    => 1,
        '/'     => 2,
        'b'     => 3,
        'f'     => 4,
        'n'     => 5,
        'r'     => 6,
        't'     => 7,
        'u'     => 8
    ];

    public function tokenize($input, $config = [])
    {
        if (empty($input)) {
            return [];
        }

        $line     = 1;
        $column = 1;
        $index     = 0;
        $tokens = [];

        while ($index < strlen($input)) {
            $args = [$input, $index, $line, $column];
            $whitespace = $this->parseWhitespace(...$args);

            if (!empty($whitespace)) {
                $index = $whitespace['index'];
                $line = $whitespace['line'];
                $column = $whitespace['column'];
                continue;
            }

            $matched = (
                $this->parseChar(...$args) ??
                $this->parseKeyword(...$args) ??
                $this->parseString(...$args) ??
                $this->parseNumber(...$args)
            );

            if (!empty($matched)) {
                $token = [
                    'type' => $matched['type'],
                    'value' => $matched['value'],
                    'loc' => Location::loc(
                        $line,
                        $column,
                        $index,
                        $matched['line'],
                        $matched['column'],
                        $matched['index'],
                        $config['source']
                    )
                ];

                $tokens[] = $token;
                $index = $matched['index'];
                $line = $matched['line'];
                $column = $matched['column'];
            } else {
                new TokenizerException(
                    substr($input, $index, 1),
                    TokenizerException::UNEXPECTED_SYMBOL,
                    $input,
                    $line,
                    $column,
                    $config['source']
                );
            }
        }

        return $tokens;
    }

    protected function parseWhitespace($input, $index, $line, $column)
    {
        $char = substr($input, $index, 1);
        // CR Unix
        if ($char === "\r") {
            $index++;
            $line++;
            $column = 1;
            if (substr($input, $index, 1) === "\n") {
                $index++;
            }
        } elseif ($char === "\n") {
            // LF (MacOS)
            $index++;
            $line++;
            $column = 1;
        } elseif ($char === "\t" || $char === ' ') {
            $index++;
            $column++;
        } else {
            return null;
        }

        return ['index' => $index, 'line' => $line, 'column' => $column];
    }

    protected function parseChar($input, $index, $line, $column)
    {
        $char = substr($input, $index, 1);
        if (array_key_exists($char, self::punctuatorTokensMap)) {
            return [
                'type'         => self::punctuatorTokensMap[$char],
                'line'         => $line,
                'column'     => $column + 1,
                'index'     => $index + 1,
                'value'        => null
            ];
        }

        return null;
    }

    protected function parseKeyword($input, $index, $line, $column)
    {
        foreach (self::keywordTokensMap as $name => $_) {
            if (substr($input, $index, strlen($name)) === $name) {
                return [
                    'type'         => self::keywordTokensMap[$name],
                    'line'         => $line,
                    'column'     => $column + strlen($name),
                    'index'     => $index + strlen($name),
                    'value'        => $name
                ];
            }
        }

        return null;
    }

    protected function parseString($input, $index, $line, $column)
    {
        $startIndex = $index;
        $buffer = '';
        $state = StringStates::START;
        
        while ($index < strlen($input)) {
            $char = substr($input, $index, 1);
            switch ($state) {
            case StringStates::START:
                if ($char === '"') {
                    $index++;
                    $state = StringStates::START_QUOTE_OR_CHAR;
                } else {
                    return null;
                }
                break;
                
            case StringStates::START_QUOTE_OR_CHAR:
                if ($char === '\\') {
                    $buffer .= $char;
                    $index++;
                    $state = StringStates::ESCAPE;
                } elseif ($char === '"') {
                    $index++;
                    return [
                        'type'     => TokenTypes::T_STRING,
                        'line'     => $line,
                        'column' => $column + $index - $startIndex,
                        'index' => $index,
                        'value' => substr($input, $startIndex, ($index - $startIndex))
                    ];
                } else {
                    $buffer .= $char;
                    $index++;
                }
                break;

            case StringStates::ESCAPE:
                if (array_key_exists($char, self::ESCAPES)) {
                    $buffer .= $char;
                    $index++;
                    if ($char === 'u') {
                        for ($i = 0; $i < 4; $i++) {
                            $currentChar = substr($input, $index, 1);
                            if ($currentChar && NumberHelper::isHex($currentChar)) {
                                $buffer .= $currentChar;
                                $index++;
                            } else {
                                return null;
                            }
                        }
                    }
                    $state = StringStates::START_QUOTE_OR_CHAR;
                } else {
                    return null;
                }
                break;
            }
        }
    }

    public function parseNumber($input, $index, $line, $column)
    {
        $startIndex = $index;
        $passedValueIndex = $index;
        $state = NumberStates::START;

        while ($index < strlen($input)) {
            $char = substr($input, $index, 1);
            switch ($state) {
            case NumberStates::START:
                if ($char === '-') {
                    $state = NumberStates::MINUS;
                } elseif ($char === '0') {
                    $passedValueIndex = $index + 1;
                    $state = NumberStates::ZERO;
                } elseif (NumberHelper::isDigit1to9($char)) {
                    $passedValueIndex = $index + 1;
                    $state = NumberStates::DIGIT;
                } else {
                    return null;
                }

                break;

            case NumberStates::MINUS:
                if ($char === '0') {
                    $passedValueIndex = $index + 1;
                    $state = NumberStates::ZERO;
                } elseif (NumberHelper::isDigit1to9($char)) {
                    $passedValueIndex = $index + 1;
                    $state = NumberStates::DIGIT;
                } else {
                    return null;
                }
                break;

            case NumberStates::ZERO:
                if ($char === '.') {
                    $state = NumberStates::POINT;
                } elseif (NumberHelper::isExp($char)) {
                    $state = NumberStates::EXP;
                } else {
                    break 2;
                }
                break;

            case NumberStates::DIGIT:
                if (NumberHelper::isDigit($char)) {
                    $passedValueIndex = $index + 1;
                } elseif ($char === '.') {
                    $state = NumberStates::POINT;
                } elseif (NumberHelper::isExp($char)) {
                    $state = NumberStates::EXP;
                } else {
                    break 2;
                }
                break;

            case NumberStates::POINT:
                if (NumberHelper::isDigit($char)) {
                    $passedValueIndex = $index + 1;
                    $state = NumberStates::DIGIT_FRACTION;
                } else {
                    break 2;
                }
                break;

            case NumberStates::DIGIT_FRACTION:
                if (NumberHelper::isDigit($char)) {
                    $passedValueIndex = $index + 1;
                } elseif (NumberHelper::isExp($char)) {
                    $state = NumberStates::EXP;
                } else {
                    break 2;
                }
                break;

            case NumberStates::EXP:
                if ($char === '+' || $char === '-') {
                    $state = NumberStates::EXP_DIGIT_OR_SIGN;
                } elseif (NumberHelper::isDigit($char)) {
                    $passedValueIndex = $index + 1;
                    $state = NumberStates::EXP_DIGIT_OR_SIGN;
                } else {
                    break 2;
                }
                break;

            case NumberStates::EXP_DIGIT_OR_SIGN:
                if (NumberHelper::isDigit($char)) {
                    $passedValueIndex = $index + 1;
                } else {
                    break 2;
                }
                break;
            }

            $index++;
        }

        if ($passedValueIndex > 0) {
            return [
                'type' => TokenTypes::T_NUMBER,
                'line' => $line,
                'column' => $column + $passedValueIndex - $startIndex,
                'index' => $passedValueIndex,
                'value' => substr($input, $startIndex, $passedValueIndex - $startIndex)
            ];
        }

        return null;
    }
}
