<?php

namespace App\Wizard;

use App\Wizard\Steps\Step;
use InvalidArgumentException;

// Анкета: вопросы по порядку и фразы вокруг них
final readonly class Scenario
{
    /** @var list<Step> */
    private array $steps;

    /**
     * @param list<Step> $steps
     * @param string $submitted фраза после отправки, {id} — номер заявки
     */
    public function __construct(
        public string $greeting,
        array $steps,
        public string $submitted,
        public string $cancelled,
    ) {
        if ($steps === []) {
            throw new InvalidArgumentException('В анкете нет ни одного вопроса');
        }

        $ids = array_map(fn (Step $step) => $step->id(), $steps);

        // С повтором find() всегда находил бы первый из двух шагов
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Имена вопросов повторяются: ' . implode(', ', $ids));
        }

        $this->steps = array_values($steps);
    }

    public function first(): Step
    {
        return $this->steps[0];
    }

    // null - такого шага нет: например, состояние сохранено до деплоя с переименованием
    public function find(string $id): ?Step
    {
        foreach ($this->steps as $step) {
            if ($step->id() === $id) {
                return $step;
            }
        }

        return null;
    }

    // null - шаг последний или неизвестный
    public function after(string $id): ?Step
    {
        foreach ($this->steps as $index => $step) {
            if ($step->id() === $id) {
                return $this->steps[$index + 1] ?? null;
            }
        }

        return null;
    }
}
