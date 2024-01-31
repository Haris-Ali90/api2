<?php

namespace App\Classes;


class Fcm
{
    public static function sendPush($subject, $message, $click_action, $refid, $deviceIds)
    {
        $SERVER_API_KEY = "AAAAWN-KHF8:APA91bFQOwqFGxkeymlWP0OStCHvb-NoCxfTCbMsxqhDVDfckUHTKZirCF9kJ-p3ZNMirkpRkV6ZdaFqDQhcNt4Bz9s9kupCA1aRhaWXw5vfziwt62kN4I9RSxikE0uLGbvkykHr7yNr";//config('services.fcm.key');

        $data = [
            "registration_ids" => $deviceIds,
            "data" => [
                "title" => $subject,
                "message" => $message,
                'click_action' => $click_action,
                'sound' => 'default',
                'tracking' => $refid
            ]
        ];

        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
        curl_exec($ch);
    }


}
