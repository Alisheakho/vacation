<?php

namespace App\Services;

use App\Models\DeviceToken; // 👈 ضروري جداً عشان نقدر نحذف
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * توليد الـ Access Token يدوياً باستخدام ملف الـ JSON
     */
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

        // طلب التوكن من غوغل
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

    /**
     * إرسال لمجموعة توكنات مع معالجة الأخطاء وحذف التوكنات الميتة
     */
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
            $cleanToken = trim($token);

            // 1. تجهيز الرسالة
            $message = [
                'token' => $cleanToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
            ];

            // إضافة الداتا (Data Payload) كـ Strings فقط
            if (!empty($data)) {
                $dataStrings = [];
                foreach ($data as $key => $value) {
                    $dataStrings[$key] = (string) $value;
                }
                $message['data'] = $dataStrings;
            }

            $payload = ['message' => $message];

            // 2. محاولة الإرسال
            $res = Http::withToken($accessToken)->post($url, $payload);
            $responseBody = $res->json();

            // 3. 🔥🔥 منطقة "المكنسة" (تنظيف التوكنات) 🔥🔥
            if ($res->failed()) {
                $status = $res->status();
                // محاولة قراءة كود الخطأ الداخلي من غوغل
                $errorCode = $responseBody['error']['details'][0]['errorCode'] ?? 'UNKNOWN';

                // الحالات التي توجب الحذف:
                // 404: Not Found (الجهاز غير موجود أو التطبيق محذوف)
                // UNREGISTERED: التطبيق انحذف
                // INVALID_ARGUMENT: التوكن شكله غلط
                if ($status === 404 || $errorCode === 'UNREGISTERED' || $errorCode === 'INVALID_ARGUMENT') {
                    
                    DeviceToken::where('token', $cleanToken)->delete();
                    
                    Log::warning("🗑️ FCM Token Deleted: {$cleanToken} | Reason: {$errorCode} ($status)");
                } else {
                    // أخطاء أخرى (سيرفر، كوتا، الخ) لا نمسح التوكن
                    Log::error("⚠️ FCM Error (Not Deleted): {$cleanToken} | Reason: {$errorCode}");
                }
            }

            // 4. تسجيل النتيجة للعودة بها
            $results[] = [
                'token'    => $cleanToken,
                'status'   => $res->status(),
                'response' => $responseBody,
            ];
        }

        return $results;
    }

    /**
     * دالة مساعدة للإرسال لمستخدم معين مباشرة
     */
    public function sendToUser(\App\Models\User $user, string $title, string $body, array $data = [])
    {
        // جلب التوكنات كمصفوفة
        $tokens = $user->deviceTokens()->pluck('token')->toArray();
        return $this->sendToTokens($tokens, $title, $body, $data);
    }
}