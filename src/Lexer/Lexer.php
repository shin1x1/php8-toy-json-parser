<?php

declare(strict_types=1);

namespace Shin1x1\ToyJsonParser\Lexer;

use JetBrains\PhpStorm\Immutable;
use Shin1x1\ToyJsonParser\Lexer\Exception\LexerException;
use Shin1x1\ToyJsonParser\Lexer\Token\ColonToken;
use Shin1x1\ToyJsonParser\Lexer\Token\CommaToken;
use Shin1x1\ToyJsonParser\Lexer\Token\EofToken;
use Shin1x1\ToyJsonParser\Lexer\Token\FalseToken;
use Shin1x1\ToyJsonParser\Lexer\Token\LeftCurlyBracketToken;
use Shin1x1\ToyJsonParser\Lexer\Token\LeftSquareBracketToken;
use Shin1x1\ToyJsonParser\Lexer\Token\NullToken;
use Shin1x1\ToyJsonParser\Lexer\Token\NumberToken;
use Shin1x1\ToyJsonParser\Lexer\Token\RightCurlyBracketToken;
use Shin1x1\ToyJsonParser\Lexer\Token\RightSquareBracketToken;
use Shin1x1\ToyJsonParser\Lexer\Token\StringToken;
use Shin1x1\ToyJsonParser\Lexer\Token\Token;
use Shin1x1\ToyJsonParser\Lexer\Token\TrueToken;

final class Lexer
{
    private int $length;
    private int $position;

    public function __construct(#[Immutable] private string $json)
    {
        $this->length = mb_strlen($this->json);
        $this->position = 0;
    }

    public function getNextToken(): Token
    {
        do {
            $ch = $this->consume();
            if ($ch === null) {
                return new EofToken();
            }
        } while ($this->isSkipCharacter($ch));

        return match ($ch) {
            '[' => new LeftSquareBracketToken(),
            ']' => new RightSquareBracketToken(),
            '{' => new LeftCurlyBracketToken(),
            '}' => new RightCurlyBracketToken(),
            ':' => new ColonToken(),
            ',' => new CommaToken(),
            '"' => $this->getStringToken(),
            '-', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' => $this->getNumberToken($ch),
            't' => $this->getLiteralToken('true', TrueToken::class),
            'f' => $this->getLiteralToken('false', FalseToken::class),
            'n' => $this->getLiteralToken('null', NullToken::class),
            default => throw new LexerException('Invalid character ' . $ch),
        };
    }

    private function isSkipCharacter(?string $ch): bool
    {
        return $ch === ' ' || $ch === "\n" || $ch === "\r" || $ch === "\t";
    }

    private function getStringToken(): StringToken
    {
        $str = '';

        while (($ch = $this->consume()) !== null) {
            if ($ch === '"') {
                return new StringToken($str);
            }

            if ($ch !== '\\') {
                $str .= $ch;
                continue;
            }

            $str .= match ($ch = $this->consume()) {
                '"' => '"',
                '\\' => '\\',
                '/' => '/',
                'b' => chr(0x8),
                'f' => "\f",
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                default => '\\' . $ch,
            };
        }

        throw new LexerException('No end of string');
    }

    /**
     * @see https://github.com/shin1x1/php8-toy-json-parser/blob/master/diagrams/number.png
     */
    private function getNumberToken(string $ch): NumberToken
    {
        $number = $ch;

        while (true) {
            $ch = $this->current();
            if ('0' <= $ch && $ch <= '9') {
                $number .= $ch;
                $this->consume();
                continue;
            }

            break;
        }

        return new NumberToken((int)$number);
    }

    private function getLiteralToken(string $expectedName, string $klass): TrueToken|FalseToken|NullToken
    {
        $name = $expectedName[0];

        for ($i = 1; $i < strlen($expectedName); $i++) {
            $ch = $this->consume();

            if ($ch === null) {
                throw new LexerException('Unexpected end of text');
            }

            $name .= $ch;
        }

        if ($name !== $expectedName) {
            throw new LexerException('Unexpected literal ' . $name);
        }

        return new $klass;
    }

    private function current(): string
    {
        return mb_substr($this->json, $this->position, 1);
    }

    private function consume(): ?string
    {
        if ($this->length <= $this->position) {
            return null;
        }

        $ch = $this->current();
        $this->position++;

        return $ch;
    }
}
