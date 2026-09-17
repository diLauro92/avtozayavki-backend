<?php

namespace App\Wizard;

enum InputKind: string
{
    case Text = 'text';
    case Button = 'button';
    case Photo = 'photo';
    case Other = 'other';
}
