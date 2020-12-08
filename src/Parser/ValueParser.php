<?php

declare(strict_types=1);

namespace Shin1x1\ToyJsonParser\Parser;

use Shin1x1\ToyJsonParser\Lexer\Lexer;
use Shin1x1\ToyJsonParser\Lexer\Token\FalseToken;
use Shin1x1\ToyJsonParser\Lexer\Token\LeftCurlyBracketToken;
use Shin1x1\ToyJsonParser\Lexer\Token\LeftSquareBracketToken;
use Shin1x1\ToyJsonParser\Lexer\Token\NullToken;
use Shin1x1\ToyJsonParser\Lexer\Token\NumberToken;
use Shin1x1\ToyJsonParser\Lexer\Token\StringToken;
use Shin1x1\ToyJsonParser\Lexer\Token\Token;
use Shin1x1\ToyJsonParser\Lexer\Token\TrueToken;
use Shin1x1\ToyJsonParser\Parser\Exception\ParserException;

final class ValueParser
{
    public static function parse(Lexer $lexer, Token $token): array|string|int|float|bool|null
    {
        return match ($token::class) {
            LeftSquareBracketToken::class => ArrayParser::parse($lexer),
            StringToken::class => $token->getValue(),
            NumberToken::class => $token->getValue(),
            TrueToken::class => true,
            FalseToken::class => false,
            NullToken::class => null,
            LeftCurlyBracketToken::class => ObjectParser::parse($lexer),
            default => throw new ParserException(token: $token),
        };
    }
}
