<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Mail;
use App\Events\PushNotification;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    public function mailCheck()
    {
        $info = [
            'taskId' => 1,
            'mainName' => 'Dharmesh Kakadiya',
            'email' => 'developer4.intelliworkz@gmail.com',
            // 'email' => 'webdeveloper21.intelliworkz@gmail.com',
            'created' => 'Dharmesh Kakadiya', 
            'taskName' => 'email test task', 
            'dueDate' => '07 mar 2024', 
            'assignName' => 'dd, aa, cc', 
            'taskPriority' => 'low', 
            'cratedDate' => '07 mar 2024',
        ];
        Mail::send('mail.check_mail', ['info' => $info], function ($message) use ($info) {
            $message->to($info['email'])->subject('Task Manager');
        });
        dd($info);
       
    }
    
    public function webPushNotification()
    {
        $webNotificationPayload = [
            'title' => 'Push Notification from PHP',
            'body' => 'PHP to browser web push notification.',
            'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
            'url' => 'https://app.tasknote.in/',
            'status' => true,
            'userId' => 127,
            'message' => 'Notification list successfully',
        ];
    
        broadcast(new PushNotification($webNotificationPayload))->toOthers();
    
        return response()->json($webNotificationPayload);
    }


}
