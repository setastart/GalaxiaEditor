<?php
/* Copyright 2017-2020 Ino Detelić

 - Licensed under the EUPL, Version 1.2 only (the "Licence");
 - You may not use this work except in compliance with the Licence.

 - You may obtain a copy of the Licence at: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

 - Unless required by applicable law or agreed to in writing, software distributed
   under the Licence is distributed on an "AS IS" basis,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 - See the Licence for the specific language governing permissions and limitations under the Licence.
*/

const PROTO_INPUT = [
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
    'disabled'    => false,
    'errors'      => [],
    'infos'       => [],
    'translate'   => true,
    'nullable'    => false,
];

const ALLOWED_INPUT_TYPES = [
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


function validateInput($input, $value) {
    $input = array_merge(PROTO_INPUT, $input);
    $input['value'] = Normalizer::normalize($value);

    if ($input['nullable'] && $input['value'] == '') {
        $input['value'] = null;
        $input['valueToDb'] = null;
        return $input;
    }


    switch ($input['type']) {

        case 'datetime':
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
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
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
            $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']); // remove double spaces

            if ($input['value'] == '') $input['value'] = date('Y-m-d');

            $formatted = date_create_from_format('!Y#m#d', $input['value']);
            $dateTimeErrors = date_get_last_errors();
            if (!$formatted)
                $input['errors'][] = 'Invalid date / time format.';

            if (!empty($dateTimeErrors['warning_count']))
                foreach ($dateTimeErrors['warnings'] as $warning)
                    $input['errors'][] = $warning;

            if (empty($input['errors']))
                $input['value'] = date_format($formatted, 'Y-m-d');
            break;


        case 'time':
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
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
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
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
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], " \t\n\r\0\x0B");
            $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']);

            if (isset($input['options']['minlength']))
                if (mb_strlen($input['value']) < $input['options']['minlength'])
                    $input['errors'][] = sprintf('Too short. Min %d characters.', $input['options']['minlength']);

            if (isset($input['options']['maxlength']))
                if (mb_strlen($input['value']) > $input['options']['maxlength'])
                    $input['errors'][] = sprintf('Too long. Max %d characters.', $input['options']['maxlength']);
            break;

        case 'text':
            // $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], " \t\n\r\0\x0B");
            $input['value'] = preg_replace('/\s\s+/', ' ', $input['value']);

            if (isset($input['options']['minlength']))
                if (mb_strlen($input['value']) < $input['options']['minlength'])
                    $input['errors'][] = sprintf('Too short. Min %d characters.', $input['options']['minlength']);

            if (isset($input['options']['maxlength']))
                if (mb_strlen($input['value']) > $input['options']['maxlength'])
                    $input['errors'][] = sprintf('Too long. Max %d characters.', $input['options']['maxlength']);
            break;


        case 'password':
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);

            if (isset($input['options']['minlength']))
                if (mb_strlen($input['value']) < $input['options']['minlength'])
                    $input['errors'][] = sprintf('Too short. Min %d characters.', $input['options']['minlength']);

            if (isset($input['options']['maxlength']))
                if (mb_strlen($input['value']) > $input['options']['maxlength'])
                    $input['errors'][] = sprintf('Too long. Max %d characters.', $input['options']['maxlength']);
            break;


        case 'number':
            $input['value'] = trim($input['value'], " \t\n\r\0\x0B");
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
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], " \t\n\r\0\x0B");

            $minLength = max($input['options']['minlength'] ?? 0, 0);
            if ($minLength > 0)
                if (!filter_var($input['value'], FILTER_VALIDATE_EMAIL))
                    $input['errors'][] = 'Invalid email address.';

            if (mb_strlen($input['value']) > 255)
                $input['errors'][] = sprintf('Too long. Max %d characters.', 255);
            break;


        case 'url':
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], " \t\n\r\0\x0B");

            $minLength = max($input['options']['minlength'] ?? 0, 0);
            if ($minLength > 0)
                if (!filter_var($input['value'], FILTER_VALIDATE_URL))
                    $input['errors'][] = 'Invalid email address.';

            if (mb_strlen($input['value']) > 255)
                $input['errors'][] = sprintf('Too long. Max %d characters.', 255);
            break;


        case 'slug':
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            // $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
            $input['value'] = gFormatSlug($input['value']);

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
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
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
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], "- \t\n\r\0\x0B");
            if (!isset($input['options'][$input['value']]))
                $input['errors'][] = 'Invalid option.';
            break;


        case 'textarea':
        case 'trix':
            $input['value'] = strip_tags($input['value'], ALLOWED_TAGS);
            break;


        case 'link':
            $input['value'] = filter_var($input['value'], FILTER_SANITIZE_STRING);
            $input['value'] = trim($input['value'], " \t\n\r\0\x0B");

            if (!filter_var($input['value'], FILTER_VALIDATE_URL))
                $input['errors'][] = 'Invalid url.';
            break;


        case 'none':
            $input = PROTO_INPUT;
            break;


        default:
            $input['errors'][] = 'Invalid input.';
            break;

    }

    if (empty($input['errors'])) {
        $input['valueToDb'] = $input['value'];
    } else {
        foreach ($input['errors'] as $msg) {
            error($msg, 'form', $input['name']);
        }
    }
    return $input;
}

