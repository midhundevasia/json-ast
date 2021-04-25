<?php
namespace JsonAst\State;

class NumberStates
{
    const START                 = 0;
    const MINUS                 = 1;
    const ZERO                     = 2;
    const DIGIT                 = 3;
    const POINT                 = 4;
    const DIGIT_FRACTION         = 5;
    const EXP                     = 6;
    const EXP_DIGIT_OR_SIGN     = 7;
}
