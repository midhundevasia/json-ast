<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use JsonAst\Token\Tokenizer;

final class TokenizerTest extends TestCase
{
	public function testEmptyInputReturnEmptyTokenArray(): void
    {
        $config = ['loc' => null, 'source' => true];
    	$tokenizer = new Tokenizer();
        $this->assertIsArray(
            $tokenizer->tokenize('', $config)
        );
    }

    public function testTokenizeValidJson(): void
    {
        $config = ['loc' => null, 'source' => true];
    	$tokenizer = new Tokenizer();
        $this->assertNotEmpty(
            $tokenizer->tokenize('{"df": true}', $config)
        );
    }

    public function testUnexpectedTokenException()
    {
        $this->expectExceptionMessage('Unexpected symbol <<> at 1:1');
        $tokenizer = new Tokenizer();
        $tokenizer->tokenize(
            file_get_contents(__DIR__ . '/Fixtures/Invalid/unexpcted_symbol.json'),
            $config = ['loc' => null, 'source' => true]
        );
    }
}