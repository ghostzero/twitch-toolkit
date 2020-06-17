<?php

namespace GhostZero\LPTHOOT\TopicParser\Contracts;

use Exception;
use GhostZero\LPTHOOT\TopicParser\TwitchParsedTopic;
use GhostZero\LPTHOOT\Utils\TwitchUserResolver;
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
    protected function response($id, $name, $revenue, $description, $data)
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
    private function resolve($id, $login)
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

        $$requiredType = $user->{$requiredType};

        return [
            'id' => $id,
            'name' => $login,
        ];
    }
}