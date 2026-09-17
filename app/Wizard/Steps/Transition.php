<?php

namespace App\Wizard\Steps;

enum Transition
{
    case Stay;
    case Next;
    case Submit;
    case Cancel;
}
