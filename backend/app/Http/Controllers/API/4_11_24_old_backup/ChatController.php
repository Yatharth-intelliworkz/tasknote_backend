<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use DB;
use App\Events\NewChatMessage;
use App\Events\PushNotification;
use App\Services\FCMService;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receiver_id' => 'required',
            'message' => 'required',
            'companyId' => 'required', // Assuming companyId is required for each message
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $senderId = Auth::id(); // Get authenticated user's ID
    
        $message = new ChatMessage;
        $message->sender_id = $senderId;
        $message->receiver_id = $request->get('receiver_id');
        $message->message = $request->get('message');
        $message->companyId = $request->get('companyId'); // Use authenticated user's company ID
        $message->save();
         broadcast(new NewChatMessage($message))->toOthers();
            $messages = ChatMessage::where(function ($query) use ($senderId, $request) {
                $query->where('sender_id', $senderId)
                    ->where('receiver_id', $request->receiver_id);
            })->orWhere(function ($query) use ($senderId, $request) {
                $query->where('sender_id', $request->receiver_id)
                    ->where('receiver_id', $senderId);
            })->orderBy('created_at', 'asc')->get();
            
        
        $this->setupNotification($request->get('companyId'), $senderId, $request->get('receiver_id'));
        
        return response()->json(['message' => 'Message sent successfully', 'messages' => $messages], 200);
    }


    public function getMessageHistory($userId)
    {
        $senderId = Auth::id();
    
            $messages = ChatMessage::where(function ($query) use ($userId, $senderId) {
                $query->where('sender_id', $senderId)
                    ->where('receiver_id', $userId);
            })->orWhere(function ($query) use ($userId, $senderId) {
                $query->where('sender_id', $userId)
                    ->where('receiver_id', $senderId);
            })->orderBy('created_at', 'asc')->get();
    
        return response()->json(['messages' => $messages], 200);
    }

    public function loadChats($companyId)
    {
        // Fetch messages efficiently
        $messages = ChatMessage::where('companyId', $companyId)->get();
    
        return response()->json(['messages' => $messages], 200);
    }
    
    public function messageread(Request $request, $id)
    {
        $senderId = Auth::id();
    
        // Update message read status efficiently using a single query
        ChatMessage::where('sender_id', $id)
            ->where('receiver_id', $senderId)
            ->where('is_read', '0')
            ->update(['is_read' => '1']);
    
        return response()->json(['success' => true], 200);
    }
    
    public function unreadmesagecountsingle(Request $request, $companyId)
    {
            $userId = Auth::id();
            $users = User::where('company_id', $companyId)->whereHas(
                    'roles', function($q){
                        $q->where('name', 'user');
                    }
                )->pluck('id');
            $unreadCounts = []; // Initialize an empty array to store unread message counts
    
            foreach ($users as $user) {
                $count = ChatMessage::where('companyId', $companyId)
                    ->where('receiver_id', $userId)
                    ->where('sender_id', $user)
                    ->where('is_read', '0')
                    ->count();
                $unreadCounts[] = ['userId' => $user, 'count' => $count]; // Store the count for the current user ID
            }
    
            return response()->json(['unreadmessages' => $unreadCounts], 200);
    }
    
    function setupNotification($companyId, $formId, $toId){
        $userName = '';
        $user = User::where('id', $formId)->first();
        if($user){
            $userName = $user->name;
        }
        $project = [
            'company_id' => $companyId,
            'project_id' => NULL,
            'task_id' => NULL, 
            'note_id' => NULL, 
            'team_id' => NULL, 
            'form_id' => $formId, 
            'to_id' => $toId, 
            'massage' => 'You Have New Message from ' . $userName, 
        ];
        $project =  Notification::create($project);
        
        $toUser = User::where('id', $toId)->first();
        if($toUser){
            if($toUser->fcm_token){
                FCMService::send(
                    $toUser->fcm_token,
                    [
                        'title' => 'New Message',
                        'body' => 'You Have New Message from ' . $userName,
                        'data' => [
                            'type' => 'new_task',
                        ]
                    ]
                );
            }
        }
        
        $webNotificationPayloadForMember = [
            'title' => 'New Message',
            'body' => 'You Have New Message from ' . $userName,
            'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
            'url' => 'https://app.tasknote.in/',
            'status' => true,
            'userId' => $toId,
            'message' => 'task add successfully',
        ];
        broadcast(new PushNotification($webNotificationPayloadForMember))->toOthers();
        

        return true;
    }

}
