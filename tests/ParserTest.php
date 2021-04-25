<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use JsonAst\Parser;

final class ParserTest extends TestCase
{

    public function testParseValidJson(): void
    {
        $this->assertInstanceOf(
            Parser::class,
            new Parser
        );
    }

    public function testParseInvalidJson(): void
    {
        $this->assertInstanceOf(
            Parser::class,
            new Parser
        );
    }

    public function testUnexpectedEndOfInputOnEmpty()
    {
        $this->expectExceptionMessage('Unexpected end of input');
        $parser = new Parser();
        $parser->parse(
            file_get_contents(__DIR__ . '/Fixtures/Invalid/empty.json'),
            ['loc' => true]
        );
    }

    public function testUnexpectedTokenException()
    {
        $this->expectExceptionMessage('Unexpected token <}> at 1:9');
        $parser = new Parser();
        $parser->parse(
            file_get_contents(__DIR__ . '/Fixtures/Invalid/redundant-symbols.json'),
            ['loc' => true]
        );
    }

    public function testUnexpectedTokenExceptionOnArrayTrailingComma()
    {
        $this->expectExceptionMessage('Unexpected token <,> at 1:3');
        $parser = new Parser();
        $parser->parse(
            file_get_contents(__DIR__ . '/Fixtures/Invalid/array-trailing-comma.json'),
            ['loc' => true]
        );
    }
}
