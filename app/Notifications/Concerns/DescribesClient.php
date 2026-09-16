<?php

namespace App\Notifications\Concerns;

use App\Models\Request as RequestModel;
use Illuminate\Support\Str;

trait DescribesClient
{
    // В иностранный канал данные клиента уходят, только если заявка
    // сама пришла из иностранного источника: новой юрисдикции не появляется
    protected function clientLines(
        RequestModel $request,
        bool $foreignChannel,
        int $problemLimit,
        bool $withCar = false,
    ): array {
        if ($foreignChannel && ! $request->source->isForeign()) {
            return ['Данные клиента — в карточке по ссылке ниже'];
        }

        $lines = [
            'Имя: ' . ($request->client_name ?? '—'),
            'Телефон: ' . $request->phone,
        ];

        if ($withCar) {
            $lines[] = 'Авто: ' . ($request->car_info ?? '—');
        }

        $lines[] = 'Проблема: ' . Str::limit($request->problem, $problemLimit);

        return $lines;
    }
}
