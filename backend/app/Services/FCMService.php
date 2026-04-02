<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class FCMService
{ 
    public static function send($token, $notification)
    {
        Http::acceptJson()->withToken('AAAAj14W_As:APA91bHiN0RizKCm1rytFDw7qmCNH_o81VmyWhc4cZjRNgmGkwjKMtF30oiDr2bKUz-VmSHlwB5gCFw_6ARJ4_mEqxQBQyQEhcM5meL2tc-YgRYJVkXMpGDpLKbuM6b3apx_n82jsYcZ')->post(
            'https://fcm.googleapis.com/fcm/send',
            [
                'to' => $token,
                'notification' => [
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                ],
                'data' => $notification['data'],
                // 'notification' => $notification,
                // 'data' => $data
            ]
        );
        // Http::acceptJson()->withToken(config('fcm.token'))->post(
        //     'https://fcm.googleapis.com/fcm/send',
        //     [
        //         'to' => $token,
        //         'notification' => $notification,
        //     ]
        // );
    }
}