<?php

namespace Tests\Unit\Wizard;

use App\Wizard\Input;
use App\Wizard\Steps\PhoneStep;
use App\Wizard\Steps\Transition;
use App\Wizard\WizardState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneStepTest extends TestCase
{
    public static function validPhones(): array
    {
        return [
            'с +7 и пробелами' => ['+7 916 123-45-67'],
            'с 8 и скобками' => ['8 (916) 123-45-67'],
            'десять цифр' => ['9161234567'],
            'слитно с +7' => ['+79161234567'],
        ];
    }

    #[DataProvider('validPhones')]
    public function test_normalizes_phone_and_moves_next(string $raw): void
    {
        $outcome = $this->step()->handle(new WizardState('phone'), Input::text($raw));

        $this->assertSame(Transition::Next, $outcome->transition);
        $this->assertSame('79161234567', $outcome->state->answer('phone'));
        $this->assertSame([], $outcome->replies);
    }

    public static function rejectedInputs(): array
    {
        return [
            'короткий номер' => [Input::text('12345')],
            'слова' => [Input::text('позвоните мне')],
            'пустой текст' => [Input::text('   ')],
            'кнопка' => [Input::button('today')],
            // Из цифр этого идентификатора normalize собрал бы 79161234567
            'фото с цифрами в id' => [Input::photo('AgAC7916abc1234567')],
            'стикер или файл' => [Input::other()],
        ];
    }

    #[DataProvider('rejectedInputs')]
    public function test_rejects_and_asks_again(Input $input): void
    {
        $state = new WizardState('phone');

        $outcome = $this->step()->handle($state, $input);

        $this->assertSame(Transition::Stay, $outcome->transition);
        $this->assertSame($state, $outcome->state);
        $this->assertCount(1, $outcome->replies);
        $this->assertSame('Не похоже на номер.', $outcome->replies[0]->text);
    }

    public function test_summary_shows_phone(): void
    {
        $state = new WizardState('confirm', ['phone' => '79161234567']);

        $this->assertSame('Телефон: 79161234567', $this->step()->summary($state));
    }

    private function step(): PhoneStep
    {
        return new PhoneStep(
            id: 'phone',
            field: 'phone',
            label: 'Телефон',
            question: 'Укажите телефон.',
            retry: 'Не похоже на номер.',
        );
    }
}
