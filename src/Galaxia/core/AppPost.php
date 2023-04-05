<?php


namespace Galaxia;


class AppPost {

    /** @var $inputs AppInput[] */
    static array $inputs = [];

    static array  $errors  = [];
    static string $message = '';

    static bool   $mailWasSent   = false;
    static string $mailSendError = '';


    static function reset(): void {
        self::$inputs        = [];
        self::$errors        = [];
        self::$message       = '';
        self::$mailWasSent   = false;
        self::$mailSendError = '';
    }

    static function initInputs(): void {
    }

    static function validate(): void {
        foreach (self::$inputs as $key => $input) {
            self::$inputs[$key]->validate(value: G::$req->post[$key] ?? '');
            foreach (self::$inputs[$key]->error as $error) {
                self::$errors[] = $error;
            }
        }
    }

    static function process(): void {
    }

    static function getInputsAsHtml(): string {
        $r = '';
        $i = 0;
        foreach (self::$inputs as $input) {
            if ($input->type == AppInput::typeButton) continue;
            $i++;
            $r .= '<strong>' . $i . ' - ' . Text::h(strip_tags($input->label)) . '</strong><br>';
            $r .= Text::h($input->value) . '<br><br>';
        }
        return $r;
    }

    static function showHeader(): bool {
        return self::$mailSendError || self::$errors || self::$mailWasSent || self::$message;
    }

}
