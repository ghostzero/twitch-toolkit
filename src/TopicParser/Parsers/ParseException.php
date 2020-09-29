<?php

namespace GhostZero\TwitchToolkit\TopicParser\Parsers;

use Exception;
use GhostZero\TwitchToolkit\TopicParser\Contracts\TopicParser;

class ParseException extends Exception
{
    public static function fromParser(TopicParser $parser, Exception $exception): self
    {
        return new self(
            sprintf('[%s]: %s', class_basename($parser), $exception->getMessage()),
            (int)$exception->getCode(),
            $exception
        );
    }

    public static function fromTopic($topic): self
    {
        return new self(sprintf('No topic parser found for %s.', $topic));
    }

    public static function fromResolve(string $existingType, $param): self
    {
        return new self(sprintf('Cannot resolve user by %s with %s.', $existingType, $param));
    }
}
