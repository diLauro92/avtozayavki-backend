<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Button;
use App\Wizard\Input;
use App\Wizard\Steps\PhotosStep;
use App\Wizard\Steps\Transition;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhotosStepTest extends TestCase
{
    public function test_prompt_offers_skip_before_first_photo(): void
    {
        $reply = $this->step()->prompt(new WizardState('photos'));

        $this->assertSame('Пришлите фото.', $reply->text);
        $this->assertEquals([[new Button('Без фото', PhotosStep::DONE)]], $reply->buttons);
    }

    public function test_prompt_offers_done_after_photos(): void
    {
        $reply = $this->step()->prompt(new WizardState('photos', [], ['a']));

        $this->assertEquals([[new Button('Готово', PhotosStep::DONE)]], $reply->buttons);
    }

    public function test_accepts_photo_and_waits_for_more(): void
    {
        $outcome = $this->step()->handle(new WizardState('photos'), Input::photo('a'));

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame(['a'], $outcome->state->photos);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame('Фото 1 принято. Пришлите ещё или нажмите «Готово».', $outcome->replies[0]->text);
        $this->assertEquals([[new Button('Готово', PhotosStep::DONE)]], $outcome->replies[0]->buttons);
    }

    public function test_keeps_photos_in_order(): void
    {
        $first = $this->step()->handle(new WizardState('photos'), Input::photo('a'));

        $second = $this->step()->handle($first->state, Input::photo('b'));

        $this->assertSame(['a', 'b'], $second->state->photos);
        $this->assertSame('Фото 2 принято. Пришлите ещё или нажмите «Готово».', $second->replies[0]->text);
    }

    public function test_rejects_photo_over_limit(): void
    {
        $state = $this->stateWithPhotos(10);

        $outcome = $this->step()->handle($state, Input::photo('x'));

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertSame('Уже достаточно фото, больше не нужно. Нажмите «Готово».', $outcome->replies[0]->text);
        $this->assertSame([], $outcome->replies[0]->buttons);
    }

    public function test_accepts_last_photo_within_limit(): void
    {
        $outcome = $this->step()->handle($this->stateWithPhotos(9), Input::photo('x'));

        $this->assertCount(10, $outcome->state->photos);
    }

    public static function statesBeforeDone(): array
    {
        return [
            'с фото' => [new WizardState('photos', ['phone' => '79161234567'], ['a'])],
            'без фото' => [new WizardState('photos')],
        ];
    }

    #[DataProvider('statesBeforeDone')]
    public function test_done_button_moves_next_keeping_state(WizardState $state): void
    {
        $outcome = $this->step()->handle($state, Input::button(PhotosStep::DONE));

        $this->assertSame(Transition::Next, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertSame([], $outcome->replies);
    }

    public function test_foreign_button_is_ignored_silently(): void
    {
        $state = new WizardState('photos');

        $outcome = $this->step()->handle($state, Input::button('today'));

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertSame([], $outcome->replies);
    }

    public static function nonPhotoInputs(): array
    {
        return [
            'текст' => [Input::text('вот фото')],
            'стикер или файл' => [Input::other()],
        ];
    }

    #[DataProvider('nonPhotoInputs')]
    public function test_non_photo_asks_again(Input $input): void
    {
        $state = new WizardState('photos');

        $outcome = $this->step()->handle($state, $input);

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame(
            'Пришлите именно фото, не файлом. Или нажмите кнопку под сообщением выше.',
            $outcome->replies[0]->text,
        );
    }

    public static function summaries(): array
    {
        return [
            'нет фото' => [0, 'Фото: -'],
            'три фото' => [3, 'Фото: 3'],
        ];
    }

    #[DataProvider('summaries')]
    public function test_summary_shows_photo_count(int $count, string $expected): void
    {
        $this->assertSame($expected, $this->step()->summary($this->stateWithPhotos($count)));
    }

    private function step(): PhotosStep
    {
        return new PhotosStep(id: 'photos', label: 'Фото', question: 'Пришлите фото.');
    }

    private function stateWithPhotos(int $count): WizardState
    {
        // range(1, 0) вернёт [1, 0], а не пустой массив
        $photos = $count === 0 ? [] : array_map(fn (int $i) => "photo-{$i}", range(1, $count));

        return new WizardState('photos', [], $photos);
    }
}
