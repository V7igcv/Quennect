<?php

namespace App\Enums;

enum QuestionType: string
{
    case MULTIPLE_CHOICE = 'MULTIPLE_CHOICE';
    case LIKERT = 'LIKERT';
}