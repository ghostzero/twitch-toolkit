<?php

namespace GhostZero\TwitchToolkit\TopicParser\Contracts;

use Exception;
use GhostZero\TwitchToolkit\TopicParser\Parsers\ParseException;
use GhostZero\TwitchToolkit\TopicParser\TwitchParsedTopic;
use GhostZero\TwitchToolkit\Utils\TwitchUserResolver;
use romanzipp\Twitch\Twitch;

abstract class TopicParser
{
    /**
     * @param array $data
     *
     * @return TwitchParsedTopic
     * @throws Exception
     */
    abstract public function parse(array $data): TwitchParsedTopic;

    /**
     * @param $id
     * @param $name
     * @param $revenue
     * @param $description
     * @param $data
     *
     * @return array
     * @throws Exception
     */
    protected function response($id, $name, $revenue, $description, $data): array
    {
        $resolver = $this->resolve($id, $name);

        return [
            'id' => $resolver['id'],
            'name' => $resolver['name'],
            'revenue' => $revenue,
            'description' => $description,
            'data' => $data,
        ];
    }

    /**
     * @param $id
     * @param $login
     *
     * @return array
     * @throws Exception
     */
    private function resolve($id, $login): array
    {
        if (empty($id) && empty($login)) {
            return ['id' => null, 'name' => null];
        }

        if (!empty($id) && !empty($login)) {
            return ['id' => $id, 'name' => $login];
        }

        /** @var Twitch $twitch */
        $twitch = resolve(Twitch::class);
        $requiredType = empty($login) ? 'login' : 'id';
        $existingType = empty($login) ? 'id' : 'login';

        $user = TwitchUserResolver::fetchUser($$existingType, $twitch);

        if (!$user) {
            throw ParseException::fromResolve($existingType, $$existingType);
        }

        $$requiredType = $user->{$requiredType};

        return [
            'id' => $id,
            'name' => $login,
        ];
    }
}
