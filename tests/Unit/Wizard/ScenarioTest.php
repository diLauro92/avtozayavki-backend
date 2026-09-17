<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Scenario;
use App\Wizard\Steps\Step;
use App\Wizard\Steps\TextStep;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ScenarioTest extends TestCase
{
    public function test_first_returns_first_step(): void
    {
        $this->assertSame('name', $this->scenario()->first()->id());
    }

    public function test_after_returns_following_step(): void
    {
        $scenario = $this->scenario();

        $this->assertSame('car', $scenario->after('name')->id());
        $this->assertSame('problem', $scenario->after('car')->id());
    }

    public function test_after_last_or_unknown_is_null(): void
    {
        $scenario = $this->scenario();

        $this->assertNull($scenario->after('problem'));
        $this->assertNull($scenario->after('removed'));
    }

    public function test_find_returns_step_by_id_or_null(): void
    {
        $scenario = $this->scenario();

        $this->assertSame('car', $scenario->find('car')->id());
        $this->assertNull($scenario->find('removed'));
    }

    public function test_steps_keys_do_not_matter(): void
    {
        $scenario = new Scenario('Привет', [5 => $this->text('name'), 9 => $this->text('car')], 'Принято', 'Отменено');

        $this->assertSame('name', $scenario->first()->id());
        $this->assertSame('car', $scenario->after('name')->id());
    }

    public function test_rejects_empty_scenario(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Scenario('Привет', [], 'Принято', 'Отменено');
    }

    public function test_rejects_duplicate_step_ids(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Scenario('Привет', [$this->text('name'), $this->text('name')], 'Принято', 'Отменено');
    }

    private function scenario(): Scenario
    {
        return new Scenario(
            greeting: 'Привет',
            steps: [$this->text('name'), $this->text('car'), $this->text('problem')],
            submitted: 'Заявка №{id} принята',
            cancelled: 'Отменено',
        );
    }

    private function text(string $id): Step
    {
        return new TextStep(id: $id, field: $id, label: $id, question: "{$id}?", retry: 'Ещё раз');
    }
}
