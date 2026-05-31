<?php
/**
 * Shared text input validation — character limit and bad-word filter.
 */

const TEXT_INPUT_MAX_CHARS = 50;

/**
 * @return list<string>
 */
function text_input_bad_words(): array
{
    return [
        'damn', 'hell', 'shit', 'fuck', 'fucking', 'bitch', 'bastard', 'asshole',
        'crap', 'piss', 'dick', 'cock', 'pussy', 'whore', 'slut', 'idiot', 'stupid',
        'moron', 'retard', 'retarded', 'nigger', 'faggot', 'cunt', 'bollocks', 'wanker',
        'suck', 'noob', 'cibai', 'pundek', 'babi', 'masturbate',
    ];
}

function text_input_count_chars(string $text): int
{
    return mb_strlen(trim($text), 'UTF-8');
}

function text_input_contains_bad_words(string $text): bool
{
    $text = strtolower(trim($text));
    if ($text === '') {
        return false;
    }
    foreach (text_input_bad_words() as $word) {
        if ($word === '') {
            continue;
        }
        if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $text)) {
            return true;
        }
    }
    return false;
}

/**
 * @return array{valid: bool, error: string, char_count: int}
 */
function text_input_validate(string $text, bool $required = false, int $max_chars = TEXT_INPUT_MAX_CHARS): array
{
    $text = trim($text);
    $char_count = text_input_count_chars($text);

    if ($required && $text === '') {
        return ['valid' => false, 'error' => 'This field is required.', 'char_count' => 0];
    }

    if ($text !== '' && $char_count > $max_chars) {
        return [
            'valid' => false,
            'error' => "Maximum {$max_chars} characters allowed (you entered {$char_count}).",
            'char_count' => $char_count,
        ];
    }

    if ($text !== '' && text_input_contains_bad_words($text)) {
        return [
            'valid' => false,
            'error' => 'Inappropriate language is not allowed. Please remove offensive words.',
            'char_count' => $char_count,
        ];
    }

    return ['valid' => true, 'error' => '', 'char_count' => $char_count];
}
