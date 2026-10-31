<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Illuminate\Support\Facades\Log;

class FirebaseNotificationService
{
    /**
     * Send Push Notification to a user via FCM.
     */
    public static function sendToUser(?User $user, string $title, string $body, array $data = []): bool
    {
        if (!$user || empty($user->fcm_token)) {
            return false;
        }

        try {
            $credentialsPath = base_path(env('FIREBASE_CREDENTIALS'));
            if (!file_exists($credentialsPath)) {
                Log::error("FCM Error: Firebase credentials file not found at " . $credentialsPath);
                return false;
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $messaging = $factory->createMessaging();

            $notification = FcmNotification::create($title, $body);

            $androidConfig = AndroidConfig::fromArray([
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => 'vocasia_high_importance_channel',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ]);

            // Ensure all data values are string for FCM specification
            $stringData = array_map(function($val) {
                return is_array($val) ? json_encode($val) : (string) $val;
            }, $data);

            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withAndroidConfig($androidConfig)
                ->withData($stringData);

            $messaging->send($message);
            Log::info("FCM Sent successfully to User ID {$user->id} ({$user->email})");
            return true;
        } catch (\Throwable $e) {
            Log::error("FCM Send Error for User ID {$user->id}: " . $e->getMessage());

            // If token is invalid or no longer registered, clear it from database
            if (str_contains($e->getMessage(), 'NotRegistered') || 
                str_contains($e->getMessage(), 'not found') || 
                str_contains($e->getMessage(), 'INVALID_ARGUMENT')) {
                $user->update(['fcm_token' => null]);
                Log::warning("FCM Token cleared for User ID {$user->id} due to invalid/unregistered token.");
            }
            return false;
        }
    }
}
