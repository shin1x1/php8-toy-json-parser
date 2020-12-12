<?php

namespace Shin1x1\ToyJsonParser\Lexer\Token {

    use JetBrains\PhpStorm\Immutable;

    final class NumberToken implements Token
    {
        public function __construct(#[Immutable] private int|float $value)
        {
        }

        public function getValue(): int|float
        {
            return $this->value;
        }
    }
}

namespace Shin1x1\ToyJsonParser\Lexer\Token {

    final class EofToken implements Token
    {
    }

    final class NullToken implements Token
    {
    }

    interface Token
    {
    }

    final class FalseToken implements Token
    {
    }

    final class RightSquareBracketToken implements Token
    {
    }

    final class StringToken implements Token
    {
        public function __construct(#[Immutable] private string $value)
        {
        }

        public function getValue(): string
        {
            return $this->value;
        }
    }

    final class CommaToken implements Token
    {
    }

    final class LeftCurlyBracketToken implements Token
    {
    }

    final class ColonToken implements Token
    {
    }

    final class LeftSquareBracketToken implements Token
    {
    }

    final class TrueToken implements Token
    {
    }

    final class RightCurlyBracketToken implements Token
    {
    }
}


namespace Shin1x1\ToyJsonParser\Lexer {

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
                    'u' => $this->getCharacterByCodePoint(),
                    default => '\\' . $ch,
                };
            }

            throw new LexerException('No end of string');
        }

        private function getCharacterByCodePoint(): string
        {
            $codepoint = '';
            for ($i = 0; $i < 4; $i++) {
                $ch = $this->consume();
                if ($ch !== null
                    && ('0' <= $ch && $ch <= '9'
                        || 'A' <= $ch && $ch <= 'F'
                        || 'a' <= $ch && $ch <= 'f')) {
                    $codepoint .= $ch;
                    continue;
                }

                throw new LexerException('Invalid code point');
            }

            return mb_chr(hexdec($codepoint));
        }

        /**
         * @see https://github.com/shin1x1/php8-toy-json-parser/blob/master/diagrams/number.png
         */
        private function getNumberToken(string $ch): NumberToken
        {
            $number = $ch;
            $state = match ($ch) {
                '-' => 'MINUS',
                '0' => 'INT_ZERO',
                default => 'INT',
            };
            $isFloat = false;

            $isDigit19 = fn($ch) => '1' <= $ch && $ch <= '9';
            $isDigit = fn($ch) => '0' <= $ch && $ch <= '9';
            $isExp = fn($ch) => $ch === 'e' || $ch === 'E';

            while (true) {
                $ch = $this->current();
                switch ($state) {
                    case 'INT':
                        if ($isDigit($ch)) {
                            $number .= $this->consume();
                            break;
                        }

                        if ($ch === '.') {
                            $number .= $this->consume();
                            $state = 'DECIMAL_POINT';
                            break;
                        }

                        if ($isExp($ch)) {
                            $number .= $this->consume();
                            $state = 'EXP';
                            break;
                        }

                        break 2;
                    case 'MINUS':
                        if ($isDigit19($ch)) {
                            $number .= $this->consume();
                            $state = 'INT';
                            break;
                        }

                        if ($ch === '0') {
                            $number .= $this->consume();
                            $state = 'INT_ZERO';
                            break;
                        }

                        break 2;
                    case 'INT_ZERO':
                        if ($ch === '.') {
                            $number .= $this->consume();
                            $state = 'DECIMAL_POINT';
                            break;
                        }
                        if ($isDigit($ch)) {
                            throw new LexerException('Invalid number:' . $ch);
                        }

                        break 2;
                    case 'DECIMAL_POINT':
                        $isFloat = true;
                        if ($isDigit($ch)) {
                            $number .= $this->consume();
                            $state = 'DECIMAL_POINT_INT';
                            break;
                        }

                        break 2;
                    case 'DECIMAL_POINT_INT':
                        if ($isDigit($ch)) {
                            $number .= $this->consume();
                            break;
                        }

                        if ($isExp($ch)) {
                            $number .= $this->consume();
                            $state = 'EXP';
                            break;
                        }

                        break 2;
                    case 'EXP':
                        $isFloat = true;
                        if ($isDigit($ch) || $ch === '-' || $ch === '+') {
                            $number .= $this->consume();
                            $state = 'EXP_INT';
                            break;
                        }

                        break 2;
                    case 'EXP_INT':
                        if ($isDigit($ch)) {
                            $number .= $this->consume();
                            break;
                        }

                        break 2;
                    default:
                        break 2;
                }
            }

            $lastCh = mb_substr($number, -1, 1);
            if ('0' <= $lastCh && $lastCh <= '9') {
                return new NumberToken($isFloat ? (float)$number : (int)$number);
            }

            throw new LexerException('Invalid number:' . $ch);
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
}

namespace Shin1x1\ToyJsonParser\Lexer\Exception {

    use Exception;

    final class LexerException extends Exception
    {
        public function __construct(string $message)
        {
            parent::__construct($message);
        }
    }
}


namespace Shin1x1\ToyJsonParser\Parser {

    use Shin1x1\ToyJsonParser\Lexer\Lexer;
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
    use Shin1x1\ToyJsonParser\Parser\Exception\ParserException;

    final class ArrayParser
    {
        private const STATE_START = 'START';
        private const STATE_COMMA = 'COMMA';
        private const STATE_VALUE = 'VALUE';

        /**
         * @see https://github.com/shin1x1/php8-toy-json-parser/blob/master/diagrams/array_parser.png
         */
        public static function parse(Lexer $lexer): array
        {
            $array = [];
            $state = self::STATE_START;

            while (true) {
                $token = $lexer->getNextToken();
                if ($token instanceof EofToken) {
                    break;
                }

                switch ($state) {
                    case self::STATE_START:
                        if ($token instanceof RightSquareBracketToken) {
                            return $array;
                        }
                        $array[] = ValueParser::parse($lexer, $token);
                        $state = self::STATE_VALUE;
                        break;
                    case self::STATE_VALUE:
                        if ($token instanceof RightSquareBracketToken) {
                            return $array;
                        }
                        if ($token instanceof CommaToken) {
                            $state = self::STATE_COMMA;
                            break;
                        }
                        throw new ParserException(token: $token);
                    case self::STATE_COMMA:
                        $array[] = ValueParser::parse($lexer, $token);
                        $state = self::STATE_VALUE;
                        break;
                    default:
                        throw new ParserException(token: $token);
                }
            }

            throw new ParserException(message: 'No end of array');
        }
    }

    final class Parser
    {
        public function __construct(private Lexer $lexer)
        {
        }

        public function parse(): array|string|int|float|bool|null
        {
            $ret = ValueParser::parse($this->lexer, $this->lexer->getNextToken());

            if ($this->lexer->getNextToken() instanceof EofToken) {
                return $ret;
            }

            throw new ParserException(message: 'Unparsed tokens detected');
        }
    }

    final class ObjectParser
    {
        private const STATE_START = 'START';
        private const STATE_KEY = 'KEY';
        private const STATE_COLON = 'COLON';
        private const STATE_COMMA = 'COMMA';
        private const STATE_VALUE = 'VALUE';

        /**
         * @see https://github.com/shin1x1/php8-toy-json-parser/blob/master/diagrams/object_parser.png
         */
        public static function parse(Lexer $lexer): array
        {
            $array = [];
            $key = '';
            $state = self::STATE_START;

            while (true) {
                $token = $lexer->getNextToken();
                if ($token instanceof EofToken) {
                    break;
                }

                switch ($state) {
                    case self::STATE_START:
                        if ($token instanceof RightCurlyBracketToken) {
                            return $array;
                        }
                        if ($token instanceof StringToken) {
                            $key = $token->getValue();
                            $state = self::STATE_KEY;
                            break;
                        }
                        throw new ParserException(token: $token);
                    case self::STATE_KEY:
                        if ($token instanceof ColonToken) {
                            $state = self::STATE_COLON;
                            break;
                        }
                        throw new ParserException(token: $token);
                    case self::STATE_COLON:
                        $array[$key] = ValueParser::parse($lexer, $token);
                        $state = self::STATE_VALUE;
                        break;
                    case self::STATE_VALUE:
                        if ($token instanceof RightCurlyBracketToken) {
                            return $array;
                        }
                        if ($token instanceof CommaToken) {
                            $state = self::STATE_COMMA;
                            break;
                        }
                        throw new ParserException(token: $token);
                    case self::STATE_COMMA:
                        if ($token instanceof StringToken) {
                            $key = $token->getValue();
                            $state = self::STATE_KEY;
                            break;
                        }
                        throw new ParserException(token: $token);
                    default:
                        throw new ParserException(token: $token);
                }
            }

            throw new ParserException(message: 'No end of object');
        }
    }

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
}

namespace Shin1x1\ToyJsonParser\Parser\Exception {
    use Exception;
    use Shin1x1\ToyJsonParser\Lexer\Token\NumberToken;
    use Shin1x1\ToyJsonParser\Lexer\Token\StringToken;
    use Shin1x1\ToyJsonParser\Lexer\Token\Token;

    final class ParserException extends Exception
    {
        public function __construct(Token $token = null, string $message = 'Syntax error')
        {
            if ($token instanceof Token) {
                if ($token instanceof StringToken || $token instanceof NumberToken) {
                    $message = sprintf('%s type=%s value=%s', $message, $token::class, $token->getValue());
                } else {
                    $message = sprintf('%s type=%s', $message, $token::class);
                }
            }

            parent::__construct($message);
        }
    }
}

namespace Shin1x1\ToyJsonParser {

    use Shin1x1\ToyJsonParser\Lexer\Lexer;
    use Shin1x1\ToyJsonParser\Parser\Parser;

    final class JsonParser
    {
        public function parse(string $json): array|string|int|float|bool|null
        {
            $lexer = new Lexer($json);
            $parser = new Parser($lexer);

            return $parser->parse();
        }
    }
}
