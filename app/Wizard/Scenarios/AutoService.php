<?php

namespace App\Wizard\Scenarios;

use App\Wizard\Button;
use App\Wizard\Scenario;
use App\Wizard\Steps\ChoiceStep;
use App\Wizard\Steps\ConfirmStep;
use App\Wizard\Steps\PhoneStep;
use App\Wizard\Steps\PhotosStep;
use App\Wizard\Steps\TextStep;

// Анкета автосервиса
final class AutoService
{
    public static function make(): Scenario
    {
        $name = new TextStep(
            id: 'name',
            field: 'client_name',
            label: 'Имя',
            question: 'Как вас зовут?',
            retry: 'Напишите, пожалуйста, имя текстом.',
        );

        $phone = new PhoneStep(
            id: 'phone',
            field: 'phone',
            label: 'Телефон',
            question: 'Укажите номер телефона.',
            retry: 'Не похоже на номер. Введите телефон в формате +7 999 123-45-67.',
        );

        $car = new TextStep(
            id: 'car',
            field: 'car_info',
            label: 'Авто',
            question: 'Марка и модель авто? (можно пропустить - напишите «-»)',
            retry: 'Напишите марку и модель текстом или «-», чтобы пропустить.',
            skip: '-',
        );

        $problem = new TextStep(
            id: 'problem',
            field: 'problem',
            label: 'Проблема',
            question: 'Опишите проблему.',
            retry: 'Пока принимаю только текст - опишите проблему словами.',
            maxLength: null,
        );

        $photos = new PhotosStep(
            id: 'photos',
            label: 'Фото',
            question: 'Пришлите фото, если есть - так мастеру будет понятнее. Можно несколько, по одному снимку.',
        );

        $urgency = new ChoiceStep(
            id: 'urgency',
            field: 'urgency',
            label: 'Срочность',
            question: 'Насколько срочно?',
            retry: 'Выберите срочность кнопкой выше.',
            rows: [
                [new Button('Сегодня', 'today'), new Button('1–2 дня', 'soon')],
                [new Button('Планово', 'planned'), new Button('Аварийно', 'emergency')],
            ],
        );

        // В сводке срочность идет раньше фото, хотя спрашивается позже
        $confirm = new ConfirmStep(
            id: 'confirm',
            summaryOf: [$name, $phone, $car, $problem, $urgency, $photos],
        );

        return new Scenario(
            greeting: 'Здравствуйте! Оставьте заявку - я задам несколько вопросов.',
            steps: [$name, $phone, $car, $problem, $photos, $urgency, $confirm],
            submitted: 'Заявка №{id} принята! С вами свяжутся.',
            cancelled: 'Заявка отменена. Напишите /start, чтобы начать заново.',
        );
    }
}
