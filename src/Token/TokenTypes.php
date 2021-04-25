<?php
namespace JsonAst\Token;

class TokenTypes
{
    const T_LEFT_BRACE         = 0; // {
    const T_RIGHT_BRACE     = 1; // }
    const T_LEFT_BRACKET     = 2; // [
    const T_RIGHT_BRACKET     = 3; // ]
    const T_COLON             = 4; // :
    const T_COMMA             = 5; // ,
    const T_STRING             = 6;
    const T_NUMBER             = 7;
    const T_TRUE             = 8;  // true
    const T_FALSE             = 9;  // false
    const T_NULL             = 10; // null
}
