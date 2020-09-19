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

function gFileUploadRemoveErrors(string $inputName) {
    if (!isset($_FILES[$inputName])) {
        error('Required.', 'form', $inputName);
    }

    $errors = [];
    foreach ($_FILES[$inputName]['error'] as $i => $errorCode) {
        $msg = '';
        switch ($errorCode) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                $msg = 'The uploaded file exceeds upload_max_filesize in php.ini.';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $msg = 'The uploaded file exceeds MAX_FILE_SIZE in the HTML form.';
                break;
            case  UPLOAD_ERR_PARTIAL:
                $msg = 'The uploaded file was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $msg = 'No file was uploaded. ';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $msg = 'Missing a temporary folder.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $msg = 'Failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $msg = 'A PHP extension stopped the file upload.';
                break;
            default:
                $msg = 'Unknown upload error.';
                break;
        }
        if ($msg) $errors[$i] = [
            'msg'  => $msg,
            'file' => $_FILES[$inputName]['name'][$i],
        ];
    }

    foreach ($errors as $i => $error)
        foreach ($_FILES[$inputName] as $propery => $values)
            unset($_FILES[$inputName][$propery][$i]);

    return $errors;
}
