<?php
// Copyright 2017-2023 Ino Detelić & Zaloa G. Ramos (setastart.com)
// Licensed under the European Union Public License, version 1.2 (EUPL-1.2)
// You may not use this work except in compliance with the Licence.
// Licence copy: https://joinup.ec.europa.eu/collection/eupl/eupl-text-11-12

namespace Galaxia\Service;

use Exception;
use JsonException;
use SensitiveParameter;


/**
 * Interact with Azure APIs using cURL
 * https://learn.microsoft.com/en-us/azure/cognitive-services/Translator/reference/v3-0-translate
 */
class ServiceAzure {

    static function translate(
        #[SensitiveParameter]
        string $key,
        string $location,
        string $text,
        string $langTarget,
    ): string {

        $params = http_build_query([
            'to'       => $langTarget,
            'textType' => 'html',
        ]);

        try {
            if (!$key) throw new Exception('Key is empty');

            $response = ServiceAzure::getCurlJsonArray(
                url: "https://api.cognitive.microsofttranslator.com/translate?api-version=3.0&{$params}",
                key: $key,
                location: $location,
                text: $text,
            );
            if (!$response) {
                throw new Exception('API request: Empty response');
            }
            if (isset($response['error'])) {
                throw new Exception('API request: ' . ($response['error']['message'] ?? '') . ' ' . ($response['error']['code'] ?? ''));
            }
            if (!isset($response[0]['translations'][0]['text'])) {
                throw new Exception('API request: Could not find translated text in result');
            }

            return $response[0]['translations'][0]['text'];

        } catch (JsonException $e) {
            return ServiceAzure::error('Translate: Could not decode json: ' . $e->getMessage());
        } catch (Exception $e) {
            return ServiceAzure::error('Translate: ' . $e->getMessage());
        }

    }




    static function getCurlJsonArray(
        #[SensitiveParameter]
        string $url,
        #[SensitiveParameter]
        string $key,
        #[SensitiveParameter]
        string $location,
        #[SensitiveParameter]
        string $text,
    ): array {
        $content = json_encode([['text' => $text]]);
        $headers = [
            'Ocp-Apim-Subscription-Key: ' . $key,
            'Content-type: application/json',
            'Content-length: ' . strlen($content),
        ];
        if ($location) {
            $headers[] = 'Ocp-Apim-Subscription-Region: ' . $location;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        $resultJson = curl_exec($ch);
        curl_close($ch);
        return json_decode(json: $resultJson, associative: true) ?? [];
    }




    private static function error(string $string): string {
        http_response_code(500);
        return "ServiceAzure $string" . PHP_EOL;
    }

}
