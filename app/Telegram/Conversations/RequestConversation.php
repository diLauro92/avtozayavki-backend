<?php

namespace App\Telegram\Conversations;

use App\Services\PhotoService;
use App\Services\RequestService;
use App\Support\Phone;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;

use function Illuminate\Support\defer;

class RequestConversation extends Conversation
{
    private const MAX_PHOTOS = 10;

    public ?string $clientName = null;
    public ?string $phone = null;
    public ?string $carInfo = null;
    public ?string $problem = null;
    public ?string $urgency = null;
    public array $photoFileIds = [];

    public function start(Nutgram $bot)
    {
        $bot->sendMessage('Здравствуйте! Оставьте заявку - я задам несколько вопросов.');
        $bot->sendMessage('Как вас зовут?');

        $this->next('askPhone');
    }

    public function askPhone(Nutgram $bot)
    {
        $name = $this->textAnswer($bot);

        if ($name === null) {
            $bot->sendMessage('Напишите, пожалуйста, имя текстом.');

            return;
        }

        $this->clientName = mb_substr($name, 0, 255);

        $bot->sendMessage('Укажите номер телефона.');

        $this->next('askCar');
    }

    public function askCar(Nutgram $bot)
    {
        $phone = Phone::normalize($this->textAnswer($bot));

        if ($phone === null) {
            $bot->sendMessage('Не похоже на номер. Введите телефон в формате +7 999 123-45-67.');

            return;
        }

        $this->phone = $phone;

        $bot->sendMessage('Марка и модель авто? (можно пропустить - напишите «-»)');

        $this->next('askProblem');
    }

    public function askProblem(Nutgram $bot)
    {
        $car = $this->textAnswer($bot);

        if ($car === null) {
            $bot->sendMessage('Напишите марку и модель текстом или «-», чтобы пропустить.');

            return;
        }

        $this->carInfo = $car === '-' ? null : mb_substr($car, 0, 255);

        $bot->sendMessage('Опишите проблему.');

        $this->next('askPhoto');
    }

    public function askPhoto(Nutgram $bot)
    {
        $problem = $this->textAnswer($bot);

        if ($problem === null) {
            $bot->sendMessage('Пока принимаю только текст - опишите проблему словами.');

            return;
        }

        $this->problem = $problem;

        $bot->sendMessage(
            'Пришлите фото, если есть - так мастеру будет понятнее. Можно несколько, по одному снимку.',
            reply_markup: $this->photoKeyboard(),
        );

        $this->next('handlePhoto');
    }

    public function handlePhoto(Nutgram $bot)
    {
        if ($bot->isCallbackQuery()) {
            if ($this->buttonAnswer($bot, ['photo_done']) === null) {
                return;
            }

            $this->sendUrgency($bot);

            $this->next('handleUrgency');

            return;
        }

        $sizes = $bot->message()?->photo;

        if (empty($sizes)) {
            $bot->sendMessage('Пришлите именно фото, не файлом. Или нажмите кнопку под сообщением выше.');

            return;
        }

        if (count($this->photoFileIds) >= self::MAX_PHOTOS) {
            $bot->sendMessage('Уже достаточно фото, больше не нужно. Нажмите «Готово».');

            return;
        }

        $this->photoFileIds[] = end($sizes)->file_id;

        $bot->sendMessage(
            'Фото ' . count($this->photoFileIds) . ' принято. Пришлите ещё или нажмите «Готово».',
            reply_markup: $this->photoKeyboard(),
        );
    }

    public function handleUrgency(Nutgram $bot)
    {
        $urgency = $this->buttonAnswer($bot, ['today', 'soon', 'planned', 'emergency']);

        if ($urgency === null) {
            $bot->sendMessage('Выберите срочность кнопкой выше.');

            return;
        }

        $this->urgency = $urgency;

        $summary = $this->buildSummary();

        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('✅ Отправить', callback_data: 'confirm'),
                InlineKeyboardButton::make('❌ Отменить', callback_data: 'cancel'),
            );

        $bot->sendMessage($summary, reply_markup: $keyboard);

        $this->next('handleConfirm');
    }

    protected function sendUrgency(Nutgram $bot): void
    {
        $keyboard = InlineKeyboardMarkup::make()
            ->addRow(
                InlineKeyboardButton::make('Сегодня', callback_data: 'today'),
                InlineKeyboardButton::make('1–2 дня', callback_data: 'soon'),
            )
            ->addRow(
                InlineKeyboardButton::make('Планово', callback_data: 'planned'),
                InlineKeyboardButton::make('Аварийно', callback_data: 'emergency'),
            );

        $bot->sendMessage('Насколько срочно?', reply_markup: $keyboard);
    }

    protected function photoKeyboard(): InlineKeyboardMarkup
    {
        $label = $this->photoFileIds === [] ? 'Без фото' : 'Готово';

        return InlineKeyboardMarkup::make()
            ->addRow(InlineKeyboardButton::make($label, callback_data: 'photo_done'));
    }

    protected function buildSummary(): string
    {
        $lines = ['Проверьте заявку:', ''];
        $lines[] = 'Имя: ' . ($this->clientName ?? '—');
        $lines[] = 'Телефон: ' . $this->phone;
        $lines[] = 'Авто: ' . ($this->carInfo ?? '—');
        $lines[] = 'Проблема: ' . $this->problem;
        $lines[] = 'Срочность: ' . $this->urgencyLabel();
        $lines[] = 'Фото: ' . (count($this->photoFileIds) ?: '—');

        return implode("\n", $lines);
    }

    protected function urgencyLabel(): string
    {
        return match ($this->urgency) {
            'today' => 'Сегодня',
            'soon' => '1–2 дня',
            'planned' => 'Планово',
            'emergency' => 'Аварийно',
            default => '—',
        };
    }

    protected function textAnswer(Nutgram $bot): ?string
    {
        if ($bot->isCallbackQuery()) {
            $bot->answerCallbackQuery();

            return null;
        }

        $text = trim($bot->message()?->text ?? '');

        return $text === '' ? null : $text;
    }

    protected function buttonAnswer(Nutgram $bot, array $allowed): ?string
    {
        if (! $bot->isCallbackQuery()) {
            return null;
        }

        $bot->answerCallbackQuery();

        $data = $bot->callbackQuery()->data;

        return in_array($data, $allowed, true) ? $data : null;
    }

    public function handleConfirm(Nutgram $bot)
    {
        $choice = $this->buttonAnswer($bot, ['confirm', 'cancel']);

        if ($choice === null) {
            $bot->sendMessage('Нажмите «Отправить» или «Отменить» под заявкой.');

            return;
        }

        if ($choice === 'cancel') {
            $bot->sendMessage('Заявка отменена. Напишите /start, чтобы начать заново.');
            $this->end();

            return;
        }

        $request = app(RequestService::class)->create([
            'source' => 'telegram',
            'phone' => $this->phone,
            'problem' => $this->problem,
            'client_name' => $this->clientName,
            'car_info' => $this->carInfo,
            'urgency' => $this->urgency,
        ]);

        $fileIds = $this->photoFileIds;

        if ($fileIds !== []) {
            defer(fn () => app(PhotoService::class)->storeFromTelegram($bot, $fileIds, $request));
        }

        $bot->sendMessage("Заявка №{$request->id} принята! С вами свяжутся.");

        $this->end();
    }
}
