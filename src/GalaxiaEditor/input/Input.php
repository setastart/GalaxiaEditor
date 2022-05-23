<?php


namespace GalaxiaEditor\input;


use DateTime;
use Galaxia\Flash;
use Galaxia\Text;
use Normalizer;
use function preg_replace;


class Input {

    public const PROTO_INPUT = [
        'label'       => '',
        'name'        => '',
        'nameFromDb'  => '',
        'type'        => 'textarea',
        'value'       => '',
        'valueFromDb' => '',
        'valueToDb'   => '',
        'options'     => [],
        'lang'        => '',
        'prefix'      => '',
        'cssClass'    => '',
        'dbUnique'    => false,
        // 'dbReciprocal' => false,
        'disabled'    => false,
        'errors'      => [],
        'infos'       => [],
        'translate'   => true,
        'nullable'    => false,
    ];

    public const ALLOWED_INPUT_TYPES = [
        'none',
        'password',
        'slug',
        'slugImage',
        'raw',
        'text',
        'email',
        'url',
        'number',
        'trix',
        'textarea',
        'datetime',
        'date',
        'time',
        'timestamp',
        'status',
        'radio',
        'select',
        'image',
        'importerJsonld',
        'importerYoutube',
        'importerVimeo',
    ];

    static public array $monthsLong = [
        'jan' => ['january', 'enero', 'janeiro'],
        'feb' => ['february', 'febrero', 'fevereiro'],
        'mar' => ['march', 'marzo', 'março'],
        'apr' => ['april', 'abril', 'abril'],
        'may' => ['may', 'mayo', 'maio'],
        'jun' => ['june', 'junio', 'junho'],
        'jul' => ['july', 'julio', 'julho'],
        'aug' => ['august', 'agosto', 'agosto'],
        'sep' => ['september', 'septiembre', 'setembro'],
        'oct' => ['october', 'octubre', 'outubro'],
        'nov' => ['november', 'noviembre', 'novembro'],
        'dec' => ['december', 'diciembre', 'dezembro'],
    ];

    static public array $monthsShort = [
        'jan'       => ['ene', 'jan'],
        'feb'       => ['feb', 'fev'],
        'mar'       => ['mar', 'mar'],
        'apr'       => ['abr', 'abr'],
        'may'       => ['may', 'mai'],
        'jun'       => ['jun', 'jun'],
        'jul'       => ['jul', 'jul'],
        'aug'       => ['ago', 'ago'],
        'sep'       => ['sep', 'set'],
        'oct'       => ['oct', 'out'],
        'nov'       => ['nov', 'nov'],
        'dec'       => ['dic', 'dez'],
        'now'       => ['agora', 'ahora'],
        'today'     => ['hoy', 'hoje'],
        'tomorrow'  => ['mañana', 'amanhã'],
        'yesterday' => ['ayer', 'ontem'],
        'day'       => ['día', 'dia'],
        'month'     => ['mes', 'mês'],
        'year'      => ['año', 'ano'],
        'last'      => ['ultimo', 'último'],
        'next'      => ['siguiente', 'seguinte', 'próximo'],
        'previous'  => ['anterior', 'prévio'],
        ''          => ['de'],
    ];

    // todo: FILTER_UNSAFE_RAW does nothing

    static function validate($input, $value, $itemId = null): array {
        $input          = array_merge(self::PROTO_INPUT, $input);
        $input['value'] = Normalizer::normalize($value);

        if ($input['nullable'] && $input['value'] == '') {
            $input['value']     = null;
            $input['valueToDb'] = null;

            return $input;
        }

        // if ($input['dbReciprocal']) {
        //     if (!is_null($itemId) && $value == $itemId) {
        //         $input['errors'][] = 'Cannot select itemId in reciprocal input.';
        //     }
        //
        //     if (isset($input['options'][$itemId])) unset($input['options'][$itemId]);
        // }

        switch ($input['type']) {

            case 'datetime':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
                $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']); // remove double spaces

                if ($input['value'] == '') $input['value'] = date('Y-m-d 00:00');

                ($formatted = date_create_from_format('!Y#m#d', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d H', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d H#i', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d H#i#s', $input['value']));
                $dateTimeErrors = date_get_last_errors();
                if (!$formatted)
                    $input['errors'][] = 'Invalid date / time format.';

                if (!empty($dateTimeErrors['warning_count']))
                    foreach ($dateTimeErrors['warnings'] as $warning)
                        $input['errors'][] = $warning;

                if (empty($input['errors']))
                    $input['value'] = date_format($formatted, 'Y-m-d H:i:s');
                break;


            case 'date':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
                $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']); // remove double spaces

                if ($input['value'] == '') $input['value'] = date('Y-m-d');

                ($formatted = date_create_from_format('!Y', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d', $input['value'])) ||
                ($formatted = date_create(Input::strtotimeLocale($input['value']))) ||
                ($formatted = date_create(Input::strtotimeLocale('1 ' . $input['value'])));
                $dateTimeErrors = date_get_last_errors();
                if (!$formatted) {
                    geD(Input::strtotimeLocale($input['value']));
                    $input['errors'][] = 'Invalid date / time format.';
                }

                if (!empty($dateTimeErrors['warning_count']))
                    foreach ($dateTimeErrors['warnings'] as $warning)
                        $input['errors'][] = $warning;

                if (empty($input['errors']))
                    $input['value'] = date_format($formatted, 'Y-m-d');
                break;


            case 'time':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
                $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']); // remove double spaces

                if ($input['value'] == '') $input['value'] = '00:00';

                ($formatted = date_create_from_format('H', $input['value'])) ||
                ($formatted = date_create_from_format('H#i', $input['value'])) ||
                ($formatted = date_create_from_format('H#i#s', $input['value']));
                $dateTimeErrors = date_get_last_errors();
                if (!$formatted)
                    $input['errors'][] = 'Invalid date / time format.';
                else
                    $input['value'] = date_format($formatted, 'H:i:s');

                if (!empty($dateTimeErrors['warning_count']))
                    foreach ($dateTimeErrors['warnings'] as $warning)
                        $input['errors'][] = $warning;

                break;


            case 'timestamp':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
                $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']); // remove double spaces

                if ($input['value'] == '') $input['value'] = date('Y-m-d 00:00');

                ($formatted = date_create_from_format('!Y#m#d', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d H', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d H#i', $input['value'])) ||
                ($formatted = date_create_from_format('!Y#m#d H#i#s', $input['value']));
                $dateTimeErrors = date_get_last_errors();

                if (!$formatted)
                    $input['errors'][] = 'Invalid date / time format.';

                if (!empty($dateTimeErrors['warning_count']))
                    foreach ($dateTimeErrors['warnings'] as $warning)
                        $input['errors'][] = $warning;

                $timestamp = date_timestamp_get($formatted);
                if ($timestamp < 0 || $timestamp > 2147483647)
                    $input['errors'][] = 'Invalid timestamp. Valid dates: 1970-2038.';

                if (empty($input['errors']))
                    $input['value'] = date_format($formatted, 'Y-m-d H:i:s');
                break;


            case 'raw':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value']);
                $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']);

                if (isset($input['options']['minlength']))
                    if (mb_strlen($input['value']) < $input['options']['minlength'])
                        $input['errors'][] = sprintf('Too short. Min %d characters.', $input['options']['minlength']);

                if (isset($input['options']['maxlength']))
                    if (mb_strlen($input['value']) > $input['options']['maxlength'])
                        $input['errors'][] = sprintf('Too long. Max %d characters.', $input['options']['maxlength']);
                break;

            case 'text':
                // $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value']);
                $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']);

                if (isset($input['options']['minlength']))
                    if (mb_strlen($input['value']) < $input['options']['minlength'])
                        $input['errors'][] = sprintf('Too short. Min %d characters.', $input['options']['minlength']);

                if (isset($input['options']['maxlength']))
                    if (mb_strlen($input['value']) > $input['options']['maxlength'])
                        $input['errors'][] = sprintf('Too long. Max %d characters.', $input['options']['maxlength']);
                break;


            case 'password':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);

                if (isset($input['options']['minlength']))
                    if (mb_strlen($input['value']) < $input['options']['minlength'])
                        $input['errors'][] = sprintf('Too short. Min %d characters.', $input['options']['minlength']);

                if (isset($input['options']['maxlength']))
                    if (mb_strlen($input['value']) > $input['options']['maxlength'])
                        $input['errors'][] = sprintf('Too long. Max %d characters.', $input['options']['maxlength']);
                break;


            case 'number':
                $input['value'] = trim($input['value']);
                $input['value'] = filter_var($input['value'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                if (!is_numeric($input['value'])) $input['errors'][] = 'Must be a number.';

                if (isset($input['options']['max']))
                    if ((int)$input['value'] > (int)$input['options']['max'])
                        $input['errors'][] = sprintf('Max: %d', $input['options']['maxlength']);

                if (isset($input['options']['min']))
                    if ((int)$input['value'] < (int)$input['options']['min'])
                        $input['errors'][] = sprintf('Min: %d', $input['options']['maxlength']);
                break;


            case 'email':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value']);

                $minLength = max($input['options']['minlength'] ?? 0, 0);
                if ($minLength > 0)
                    if (!filter_var($input['value'], FILTER_VALIDATE_EMAIL))
                        $input['errors'][] = 'Invalid email address.';

                if (mb_strlen($input['value']) > 255)
                    $input['errors'][] = sprintf('Too long. Max %d characters.', 255);
                break;


            case 'url':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value']);

                $minLength = max($input['options']['minlength'] ?? 0, 0);
                if ($minLength > 0)
                    if (!filter_var($input['value'], FILTER_VALIDATE_URL))
                        $input['errors'][] = 'Invalid url.';

                if (mb_strlen($input['value']) > 255)
                    $input['errors'][] = sprintf('Too long. Max %d characters.', 255);
                break;


            case 'slug':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                // $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
                $input['value'] = Text::formatSlug($input['value']);

                $minLength = max($input['options']['minlength'] ?? 0, 0);
                if (mb_strlen($input['value']) < $minLength)
                    $input['errors'][] = sprintf('Too short. Min %d characters.', $minLength);

                $maxLength = min($input['options']['maxlength'] ?? 255, 255);
                if (mb_strlen($input['value']) > $maxLength)
                    $input['errors'][] = sprintf('Too long. Max %d characters.', $maxLength);

                if (!preg_match('/^[a-z0-9\-]*$/', $input['value']))
                    $input['errors'][] = 'Invalid Slug. Use only a-z, 0-9 and hyphen (-).';
                break;


            case 'slugImage':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");

                $minLength = max($input['options']['minlength'] ?? 0, 0);
                if (mb_strlen($input['value']) < $minLength)
                    $input['errors'][] = sprintf('Too short. Min %d characters.', $minLength);

                $maxLength = min($input['options']['maxlength'] ?? 255, 255);
                if (mb_strlen($input['value']) > $maxLength)
                    $input['errors'][] = sprintf('Too long. Max %d characters.', $maxLength);

                if (!preg_match('/^[a-z0-9\-]*$/', $input['value']))
                    $input['errors'][] = 'Invalid Slug. Use only a-z, 0-9 and hyphen (-).';
                break;


            case 'select':
            case 'radio':
            case 'status':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
                if (!isset($input['options'][$input['value']]))
                    $input['errors'][] = 'Invalid option.';
                break;


            case 'textarea':
            case 'trix':
                $input['value'] = strip_tags($input['value'], Text::ALLOWED_TAGS);
                break;


            case 'link':
                $input['value'] = filter_var($input['value'], FILTER_UNSAFE_RAW);
                $input['value'] = trim($input['value']);

                if (!filter_var($input['value'], FILTER_VALIDATE_URL))
                    $input['errors'][] = 'Invalid url.';
                break;


            case 'none':
                $input = self::PROTO_INPUT;
                break;


            default:
                $input['errors'][] = 'Invalid input.';
                break;

        }

        if (empty($input['errors'])) {
            $input['valueToDb'] = $input['value'];
        } else {
            foreach ($input['errors'] as $msg) {
                Flash::error($msg, 'form', $input['name']);
            }
        }

        return $input;
    }




    public static function prepare($input, $extras): array {

        if ($input['type'] == 'select' && isset($input['geExtraOptions'])) {
            foreach ($input['geExtraOptions'] as $key => $val) {
                foreach ($val as $colKey => $colVals) {
                    if (!isset($extras[$key])) continue;
                    if (is_string($colVals)) $colVals = [$colVals];
                    foreach ($extras[$key] as $option) {
                        $add = true;
                        if (isset($input['geExtraOptionHas'][$key])) {
                            foreach ($input['geExtraOptionHas'][$key] as $constraintKey => $constraintVals) {
                                if (is_string($constraintVals)) $constraintVals = [$constraintVals];
                                foreach ($constraintVals as $constraintVal) {
                                    if ($option[$constraintKey] != $constraintVal) {
                                        $add = false;
                                    }
                                }
                            }
                        }

                        $found        = [];
                        $optionColVal = [];
                        foreach ($colVals as $colVal) {
                            if (substr($colVal, -3, 1) == '_') {
                                $canonical = substr($colVal, 0, -2);
                                if (empty($option[$colVal])) continue;
                                if (in_array($canonical, $found)) continue;
                                $found[] = substr($colVal, 0, -2);
                            }

                            $optionColVal[] = Text::unsafet($option[$colVal]);
                        }
                        $optionColVal = array_filter($optionColVal);

                        if ($add) $input['options'][$option[$colKey]] = ['label' => implode(' / ', $optionColVal)];
                    }
                }
            }
        }

        if (isset($input['options']['prefill'])) {
            switch ($input['options']['prefill']) {
                case 'week':
                    $todayDt = new DateTime();
                    $afterDt = new DateTime();
                    $afterDt->modify('+1 week');
                    $today          = $todayDt->format('Y-m-d');
                    $after          = $afterDt->format('Y-m-d');
                    $input['value'] = substr($today, 0, strspn($today ^ $after, "\0"));
                    break;

                case 'day':
                    $todayDt        = new DateTime();
                    $input['value'] = $todayDt->format('Y-m-d');
                    break;

                default:
                    break;
            }
        }

        return $input;
    }


    private static function strtotimeLocale(string $date): string {
        foreach (Input::$monthsLong as $dest => $sources) {
            foreach ($sources as $source) {
                $date = preg_replace("~[\s\d]($source)[\s\d]?~i", " $dest ", $date);
            }
        }
        foreach (Input::$monthsShort as $dest => $sources) {
            foreach ($sources as $source) {
                $date = preg_replace("~[\s\d]($source)[\s\d]?~i", " $dest ", $date);
            }
        }
        return $date;
    }

}
