<?php

namespace App\Wizard;

use App\Enums\RequestSource;
use Illuminate\Contracts\Cache\Repository;

// Где клиент остановился в анкете: отдельно по каждому каналу и чату
final readonly class WizardStore
{
    // Брошенная анкета с именем и телефоном не должна висеть вечно
    private const TTL_SECONDS = 12 * 60 * 60;

    public function __construct(private Repository $cache) {}

    public function get(RequestSource $channel, int $chatId): ?WizardState
    {
        $data = $this->cache->get($this->key($channel, $chatId));

        if (! is_array($data) || ! isset($data['step'])) {
            return null;
        }

        return WizardState::fromArray($data);
    }

    // Храним массив, а не объект: переименование классов не сломает
    public function put(RequestSource $channel, int $chatId, WizardState $state): void
    {
        $this->cache->put($this->key($channel, $chatId), $state->toArray(), self::TTL_SECONDS);
    }

    public function forget(RequestSource $channel, int $chatId): void
    {
        $this->cache->forget($this->key($channel, $chatId));
    }

    private function key(RequestSource $channel, int $chatId): string
    {
        return "wizard:{$channel->value}:{$chatId}";
    }
}
