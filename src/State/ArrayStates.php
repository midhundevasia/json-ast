<?php

namespace JsonAst\State;

class ArrayStates
{
    const START                 = 0;
    const OPEN_ARRAY             = 1;
    const VALUE                 = 2;
    const COMMA                 = 3;
}
