<?php

namespace JsonAst\State;

class ObjectStates
{
    const START                 = 0;
    const OPEN_OBJECT             = 1;
    const PROPERTY                 = 2;
    const COMMA                 = 3;
}
