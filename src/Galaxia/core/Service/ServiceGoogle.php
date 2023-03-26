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
 * Interact with Google Cloud Platform (GCP) APIs using cURL
 * https://developers.google.com/identity/protocols/oauth2/service-account#httprest
 */
class ServiceGoogle {

    const scopeFull        = 'https://www.googleapis.com/auth/cloud-platform';
    const scopeTranslation = 'https://www.googleapis.com/auth/cloud-translation';

    static function translate(
        #[SensitiveParameter]
        string $serviceFilePath,
        string $text,
        string $langTarget,
        string $scope = ServiceGoogle::scopeTranslation
    ): string {

        try {
            if (!is_readable($serviceFilePath)) throw new Exception('Service file unreadable');

            $service = json_decode(
                json: file_get_contents($serviceFilePath),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );

            if (!isset($service['token_uri'])) throw new Exception('Service json: Missing token_uri');
            if (!isset($service['client_email'])) throw new Exception('Service json: Missing client_email');
            if (!isset($service['private_key'])) throw new Exception('Service json: Missing private_key');

            $accessToken = ServiceGoogle::getAccessToken(
                service: $service,
                scope: $scope
            );

            $response = ServiceGoogle::getCurlJsonArray(
                url: 'https://translation.googleapis.com/language/translate/v2?access_token=' . urlencode($accessToken),
                query: http_build_query(data: [
                    'q'      => $text,
                    'target' => $langTarget,
                ])
            );
            if (isset($response['error'])) {
                throw new Exception('API request: ' . ($response['error']['message'] ?? '') . ' ' . ($response['error']['status'] ?? ''));
            }
            if (!isset($response['data']['translations'][0]['translatedText'])) {
                throw new Exception('API request: Could not find translated text in result');
            }

            return $response['data']['translations'][0]['translatedText'] ?? '';

        } catch (JsonException $e) {
            return ServiceGoogle::error('Translate: Could not decode json: ' . $e->getMessage());
        } catch (Exception $e) {
            return ServiceGoogle::error('Translate: ' . $e->getMessage());
        }

    }




    /**
     * @throws Exception
     */
    static function getAccessToken(
        #[SensitiveParameter]
        array  $service,
        string $scope = ServiceGoogle::scopeTranslation
    ): string {
        $jwt = ServiceGoogle::getGServiceJwt(
            service: $service,
            scope: $scope,
        );

        $jwtResponse = ServiceGoogle::getCurlJsonArray(
            url: $service['token_uri'],
            query: 'grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=' . $jwt
        );

        if (isset($jwtResponse['error'])) {
            throw new Exception('getAccessToken error: ' . $jwtResponse['error'] . ' - ' . ($jwtResponse['error_description'] ?? ''));
        }
        $accessToken = $jwtResponse['access_token'] ?? '';

        if (!$accessToken) throw new Exception('Empty access token');

        return $accessToken;
    }




    static function getGServiceJwt(
        #[SensitiveParameter]
        array  $service,
        string $scope
    ): string {

        $jwtHeaderArray = [
            "alg" => "RS256",
            "typ" => "JWT",
        ];

        $jwtClaimArray = [
            "iss"   => $service['client_email'],
            "scope" => $scope,
            "aud"   => $service['token_uri'],
            "exp"   => time() + 3600,
            "iat"   => time(),
        ];

        $jwtHeader = ServiceGoogle::base64_encode_url(json_encode($jwtHeaderArray));
        $jwtClaim  = ServiceGoogle::base64_encode_url(json_encode($jwtClaimArray));

        $privateKey = openssl_pkey_get_private($service['private_key']);
        openssl_sign($jwtHeader . '.' . $jwtClaim, $jwtSignature, $privateKey, "sha256");
        $jwtSignature = ServiceGoogle::base64_encode_url($jwtSignature);

        return $jwtHeader . '.' . $jwtClaim . '.' . $jwtSignature;
    }




    static function getCurlJsonArray(
        #[SensitiveParameter]
        string $url,
        #[SensitiveParameter]
        string $query
    ): array {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
        $resultJson = curl_exec($ch);
        curl_close($ch);
        return json_decode(json: $resultJson, associative: true) ?? [];
    }




    static function base64_encode_url(
        #[SensitiveParameter]
        string $input
    ): string {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }




    private static function error(string $string): string {
        http_response_code(500);
        return "ServiceGoogle $string";
    }

}
