<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FcmService
{
    protected function getAccessToken(): string
    {
        $credentialsPath = base_path(config('services.firebase.credentials'));

        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Firebase credentials file not found at: {$credentialsPath}");
        }

        $jsonKey = json_decode(file_get_contents($credentialsPath), true);

        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claimSet = [
            'iss' => $jsonKey['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64UrlClaim = rtrim(strtr(base64_encode(json_encode($claimSet)), '+/', '-_'), '=');
        $signatureInput = $base64UrlHeader . '.' . $base64UrlClaim;

        openssl_sign($signatureInput, $signature, $jsonKey['private_key'], 'sha256WithRSAEncryption');
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        $jwt = $signatureInput . '.' . $base64UrlSignature;

        // Request access token from Google OAuth2
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion'  => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to get access token from Google: ' . $response->body());
        }

        $data = $response->json();

        return $data['access_token'];
    }

   public function sendToTokens(array $tokens, string $title, string $body, array $data = [])
{
    if (empty($tokens)) {
        return ['status' => 'no_tokens'];
    }

    $accessToken = $this->getAccessToken();
    $projectId   = config('services.firebase.project_id');

    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    $results = [];

    foreach ($tokens as $token) {
        // ننظف التوكن من مسافات / أسطر زيادة
        $cleanToken = trim($token);

        $message = [
            'token' => $cleanToken,
            'notification' => [
                'title' => $title,
                'body'  => $body,
            ],
        ];

        // فقط لو عندنا data، نضيفها كـ map (key => string value)
        if (!empty($data)) {
            $dataStrings = [];
            foreach ($data as $key => $value) {
                // لازم كل القيم تكون strings في HTTP v1
                $dataStrings[$key] = (string) $value;
            }

            $message['data'] = $dataStrings;
        }

        $payload = ['message' => $message];

        $res = Http::withToken($accessToken)->post($url, $payload);

        $results[] = [
            'token'    => $cleanToken,
            'status'   => $res->status(),
            'response' => $res->json(),
        ];
    }

    return $results;
}


    public function sendToUser(\App\Models\User $user, string $title, string $body, array $data = [])
    {
        $tokens = $user->deviceTokens()->pluck('token')->toArray();
        return $this->sendToTokens($tokens, $title, $body, $data);
    }
}
