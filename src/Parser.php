<?php
namespace JsonAst;

use JsonAst\Exception\ParserException;
use JsonAst\State\ArrayStates;
use JsonAst\Token\Tokenizer;
use JsonAst\Token\TokenTypes;
use JsonAst\State\ObjectStates;
use JsonAst\Helper\Location;
use JsonAst\State\PropertyStates;

class Parser
{
    const ESCAPES = [
        'b' => '\b',    // Backspace
        'f' => '\f',    // Form feed
        'n' => '\n',    // New line
        'r' => '\r',    // Carriage return
        't' => '\t'     // Horizontal tab
    ];

    const PASS_ESCAPES = ['"', '\\', '/'];

    public function parse($input, $config = [])
    {
        $config = array_merge(['loc' => true, 'source' => null], $config);
        $tokenizer = new Tokenizer();
        $tokenList = $tokenizer->tokenize($input, $config);

        if (empty($tokenList)) {
            new ParserException(
                ParserException::UNEXPECTED_END,
                $input,
                $tokenList,
                null,
                $config['source']
            );
        }
        $value = $this->parseValue($input, $tokenList, 0, $config);

        if ($value['index'] === count($tokenList)) {
            return $value['value'];
        }

        new ParserException(
            ParserException::UNEXPECTED_TOKEN,
            $input,
            $tokenList,
            $value['index'],
            $config['source']
        );
    }

    protected function parseValue($input, $tokens, $index, $config)
    {
        // value: literal | object | array
        $value = (
            $this->parseLiteral(...func_get_args()) ??
            $this->parseObject(...func_get_args()) ??
            $this->parseArray(...func_get_args())
        );

        if ($value) {
            return $value;
        } else {
            new ParserException(
                ParserException::UNEXPECTED_TOKEN,
                $input,
                $tokens,
                $index,
                $config['source']
            );
        }
    }

    protected function parseLiteral($input, $tokenList, $index, $config)
    {
        // literal: STRING | NUMBER | TRUE | FALSE | NULL
        $token = $tokenList[$index];
        $value = null;

        switch ($token['type']) {
        case TokenTypes::T_STRING:
            $value = $this->parseString(
                substr(
                    $input,
                    $token['loc']['start']['offset'] + 1,
                    ($token['loc']['end']['offset'] - 1) - ($token['loc']['start']['offset'] + 1)
                )
            );
            break;

        case TokenTypes::T_NUMBER:
            $value = $token['value'];
            break;

        case TokenTypes::T_TRUE:
            $value = true;
            break;
        case TokenTypes::T_FALSE:
            $value = false;
            break;

        case TokenTypes::T_NULL:
            $value = null;
            break;

        default:
            return null;
        }

        $literal = [
            'type' => 'Literal',
            'value' => $value,
            'raw'   => $token['value']
        ];

        if ($config['loc']) {
            $literal['loc'] = $token['loc'];
        }

        return [
            'value' => $literal,
            'index' => $index + 1
        ];
    }

    protected function parseString($string)
    {
        $result = '';

        for ($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);

            if ($char === '\\') {
                $i++;
                $nextChar = substr($string, $i, 1);
                if ($nextChar === 'u') {
                    $result .= $this->parseHexEscape(substr($string, $i + 1, 4));
                    $i += 4;
                } elseif (self::PASS_ESCAPES[$nextChar] >= 0) {
                    $result .= $nextChar;
                } elseif (in_array($nextChar, self::PASS_ESCAPES)) {
                    $result .= self::PASS_ESCAPES[$nextChar];
                } else {
                    break;
                }
            } else {
                $result .= $char;
            }
        }

        return $result;
    }

    protected function parseHexEscape($hexCode)
    {
        $charCode = 0;

        for ($i = 0; $i < 4; $i++) {
            $charCode = $charCode * 16 + intval($hexCode[$i], 16);
        }

        return chr($charCode);
    }

    public function parseObject($input, $tokens, $index, $config)
    {
        // object: LEFT_BRACE (property (COMMA property)*)? RIGHT_BRACE
        $startToken = '';
        $object = [
            'type' => 'Object',
            'children' => []
        ];

        $state = ObjectStates::START;

        while ($index < count($tokens)) {
            $token = $tokens[$index];

            switch ($state) {
            case ObjectStates::START:
                if ($token['type'] === TokenTypes::T_LEFT_BRACE) {
                    $startToken = $token;
                    $state = ObjectStates::OPEN_OBJECT;
                    $index++;
                } else {
                    return null;
                }
                break;

            case ObjectStates::OPEN_OBJECT:
                if ($token['type'] === TokenTypes::T_RIGHT_BRACE) {
                    if ($config['loc']) {
                        $object['loc'] = Location::loc(
                            $startToken['loc']['start']['line'],
                            $startToken['loc']['start']['column'],
                            $startToken['loc']['start']['offset'],
                            $token['loc']['end']['line'],
                            $token['loc']['end']['column'],
                            $token['loc']['end']['offset'],
                            $config['source']
                        );
                    }

                    return [
                        'value' => $object,
                        'index' => $index + 1
                    ];
                } else {
                    $property = $this->parseProperty($input, $tokens, $index, $config);
                    $object['children'][] = $property['value'];
                    $state = ObjectStates::PROPERTY;
                    $index = $property['index'];
                }
                break;

            case ObjectStates::PROPERTY:
                if ($token['type'] === TokenTypes::T_RIGHT_BRACE) {
                    if ($config['loc']) {
                        $object['loc'] = Location::loc(
                            $startToken['loc']['start']['line'],
                            $startToken['loc']['start']['column'],
                            $startToken['loc']['start']['offset'],
                            $token['loc']['end']['line'],
                            $token['loc']['end']['column'],
                            $token['loc']['end']['offset'],
                            $config['source']
                        );
                    }

                    return [
                        'value' => $object,
                        'index' => $index + 1
                    ];
                } elseif ($token['type'] === TokenTypes::T_COMMA) {
                    $state = ObjectStates::COMMA;
                    $index++;
                } else {
                    new ParserException(
                        ParserException::UNEXPECTED_TOKEN,
                        $input,
                        $tokens,
                        $index,
                        $config['source']
                    );
                }
                break;

            case ObjectStates::COMMA:
                $property = $this->parseProperty($input, $tokens, $index, $config);
                if ($property) {
                    $index = $property['index'];
                    $object['children'][] = $property['value'];
                    $state = ObjectStates::PROPERTY;
                } else {
                    new ParserException(
                        ParserException::UNEXPECTED_TOKEN,
                        $input,
                        $tokens,
                        $index,
                        $config['source']
                    );
                }
                break;
            }
        }

        new ParserException(
            ParserException::UNEXPECTED_END,
            $input,
            $tokens,
            $index,
            $config['source']
        );
    }

    protected function parseProperty($input, $tokens, $index, $config)
    {
        // property: STRING COLON value
        $startToken = '';
        $property = [
            'type'  => 'Property',
            'key'   => null,
            'value' => null
        ];

        $state = PropertyStates::START;

        while ($index < count($tokens)) {
            $token = $tokens[$index];

            switch ($state) {
            case PropertyStates::START:
                if ($token['type'] === TokenTypes::T_STRING) {
                    $key = [
                        'type' => 'Identifier',
                        'value' => $this->parseString(
                            substr(
                                $input,
                                $token['loc']['start']['offset'] + 1,
                                ($token['loc']['end']['offset'] - 1) - ($token['loc']['start']['offset'] + 1)
                            )
                        ),
                        'raw' => $token['value']
                    ];

                    if ($config['loc']) {
                        $key['loc'] = $token['loc'];
                    }

                    $startToken = $token;
                    $property['key'] = $key;
                    $state = PropertyStates::KEY;
                    $index++;
                } else {
                    return null;
                }
                break;

            case PropertyStates::KEY:
                if ($token['type'] === TokenTypes::T_COLON) {
                    $state = PropertyStates::COLON;
                    $index++;
                } else {
                    new ParserException(
                        ParserException::UNEXPECTED_TOKEN,
                        $input,
                        $tokens,
                        $index,
                        $config['source']
                    );
                }
                break;

            case PropertyStates::COLON:
                $value = $this->parseValue($input, $tokens, $index, $config);
                $property['value'] = $value['value'];
                if ($config['loc']) {
                    $property['loc'] = Location::loc(
                        $startToken['loc']['start']['line'],
                        $startToken['loc']['start']['column'],
                        $startToken['loc']['start']['offset'],
                        $token['loc']['end']['line'],
                        $token['loc']['end']['column'],
                        $token['loc']['end']['offset'],
                        $config['source']
                    );
                }

                return [
                        'value' => $property,
                        'index' => $value['index']
                    ];
            }
        }
    }

    public function parseArray($input, $tokenList, $index, $config)
    {
        // array: LEFT_BRACKET (value (COMMA value)*)? RIGHT_BRACKET
        $startToken = '';
        $array = [
            'type' => 'Array',
            'children' => []
        ];
        $state = ArrayStates::START;
        $token = '';

        while ($index < count($tokenList)) {
            $token = $tokenList[$index];

            switch ($state) {
            case ArrayStates::START:
                if ($token['type'] === TokenTypes::T_LEFT_BRACKET) {
                    $startToken = $token;
                    $state = ArrayStates::OPEN_ARRAY;
                    $index++;
                } else {
                    return null;
                }
                break;

            case ArrayStates::OPEN_ARRAY:
                if ($token['type'] === TokenTypes::T_RIGHT_BRACKET) {
                    if ($config['loc']) {
                        $array['loc'] = Location::loc(
                            $startToken['loc']['start']['line'],
                            $startToken['loc']['start']['column'],
                            $startToken['loc']['start']['offset'],
                            $token['loc']['end']['line'],
                            $token['loc']['end']['column'],
                            $token['loc']['end']['offset'],
                            $config['source']
                        );
                    }
                    return [
                        'value' => $array,
                        'index' => $index +1
                    ];
                } elseif ($token['type'] === TokenTypes::T_COMMA) {
                    $state = ObjectStates::COMMA;
                    $index++;
                } else {
                    $value = $this->parseValue($input, $tokenList, $index, $config);
                    $index = $value['index'];
                    $array['children'][] = $value['value'];
                    $state = ArrayStates::VALUE;
                }
                break;

            case ArrayStates::VALUE:
                if ($token['type'] === TokenTypes::T_RIGHT_BRACKET) {
                    if ($config['loc']) {
                        $array['loc'] = Location::loc(
                            $startToken['loc']['start']['line'],
                            $startToken['loc']['start']['column'],
                            $startToken['loc']['start']['offset'],
                            $token['loc']['end']['line'],
                            $token['loc']['end']['column'],
                            $token['loc']['end']['offset'],
                            $config['source']
                        );
                    }
                    return  [
                        'value' => $array,
                        'index' => $index + 1
                    ];
                } else {
                    new ParserException(
                        ParserException::UNEXPECTED_TOKEN,
                        $input,
                        $tokenList,
                        $index,
                        $config['source']
                    );
                }
                break;

            case ArrayStates::COMMA:
                $value = $this->parseValue($input, $tokenList, $index, $config);
                $index = $value['index'];
                $array['children'][] = $value['value'];
                $state = ArrayStates::VALUE;
                break;

            }
        }

        new ParserException(
            ParserException::UNEXPECTED_END,
            $input,
            $tokenList,
            $index,
            $config['source']
        );
    }
}
