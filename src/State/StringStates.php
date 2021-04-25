<?php
namespace JsonAst\State;

class StringStates
{
    const START                 = 0;
    const START_QUOTE_OR_CHAR     = 1;
    const ESCAPE                 = 2;
}
