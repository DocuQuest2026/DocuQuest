<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class GmailAddress implements ValidationRule
{
    /**
     * Gmail usernames are 6 to 30 characters of letters, numbers and single periods, and never
     * start or end with a period. Plus-aliases are rejected so one inbox can't pose as many.
     */
    private const PATTERN = '/^(?=.{6,30}@)[a-z0-9]+(?:\.[a-z0-9]+)*@gmail\.com$/i';

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match(self::PATTERN, $value)) {
            $fail('Please enter a valid Gmail address (for example, juan.delacruz@gmail.com).');
        }
    }
}
