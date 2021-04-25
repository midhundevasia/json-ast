# json-ast : JSON to AST parser in PHP

[![Latest Stable Version](https://img.shields.io/packagist/v/midhundevasia/json-ast.svg?style=flat-square)](https://packagist.org/packages/midhundevasia/json-ast)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.0-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://travis-ci.com/midhundevasia/json-ast.svg?branch=master)](https://travis-ci.com/midhundevasia/json-ast)

## Install
    $ composer require midhundevasia/json-ast
    
## Usage
    <?php
    use JsonAst\Parser;
    $parser = new Parser();
    $parser->parse(
        '{"hello" : "World"}',
        ['loc' => true, 'source' => null]
    );


## Tests
    $ ./vendor/bin/phpunit --testdox tests

## Todo
 - write more test cases
 - code coverage
 
## License
json-ast is licensed under GNU General Public License (GPLv3) - see the `LICENSE` file for details.

## Credits
Inspired from following repositories.
  
    https://github.com/vtrushin/json-to-ast
    https://github.com/vtrushin/code-error-fragment
    
##### Development
    $ vendor/bin/phpcbf src
    $ vendor/bin/php-cs-fixer fix src
