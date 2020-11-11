<?php


namespace Galaxia;


class File {

    public static function uploadRemoveErrors(string $inputName) {
        if (!isset($_FILES[$inputName])) {
            Flash::error('Required.', 'form', $inputName);
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

}
