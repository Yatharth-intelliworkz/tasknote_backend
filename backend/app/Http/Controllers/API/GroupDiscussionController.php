<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GroupDiscussion;
use App\Models\Groups;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Company;
use DB;
use App\Events\GroupMessage;

class GroupDiscussionController extends Controller
{
    public function creategroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'companyId' => 'required',
            'group_name' => 'required',
            'project_id' => 'required', // Remove max length validation since it's an array
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = Auth::user()->id;
        $project_id = null; // Initialize the variables
        $members_id = null;
        
        
        if (isset($request->is_sender)) {
            if($request->project_id){
                $data = json_decode($request->project_id, true);
                $itemIds = array_column($data, 'item_id');
                $project_id = implode(",", $itemIds);
            }
            if ($request->group_members) {
                $data = json_decode($request->group_members, true); // Use correct field name
                $itemIds = array_column($data, 'item_id');
                $members_id = implode(",", $itemIds);
                $mem = $userId . ',' . $members_id;
            }
        } else {
            if ($request->project_id) {
                $data = $request->project_id;
                $itemIds = array_column($data, 'item_id');
                $project_id = implode(",", $itemIds);
            }
    
            if ($request->group_members) {
                $data = $request->group_members; // Use correct field name
                $itemIds = array_column($data, 'item_id');
                $members_id = implode(",", $itemIds);
                $mem = $userId . ',' . $members_id;
            }
        }
        
        

        $post = new Groups;
        $post->companyId = $request->companyId;
        $post->group_name = $request->get('group_name');
        $post->project_id = $project_id;
        $post->created_id = $userId;
        $post->group_members = $mem;
        $post->save();


        return response()->json(['success' => true, 'messages' => 'Group Created Successfully']);
    }

   public function sendgroupmessage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_id' => 'required|exists:groups,id',
            'message' => 'required|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        
        $senderId = Auth::id();
        
        $group = Groups::find($request->group_id);
        if (!$group) {
            return response()->json(['errors' => 'Group not found.'], 404);
        }
        
        $groupMembers = explode(',', $group->group_members);
        $index = array_search($senderId, $groupMembers);
        
        if ($index !== false) {
            unset($groupMembers[$index]);
        }
        
        $receiverIds = json_encode(array_values($groupMembers));
        $numberOfMembers = count($groupMembers);
        $isReadJson = json_encode(array_fill(0, $numberOfMembers, 0));
        
        DB::beginTransaction();
        
        try {
            $message = new GroupDiscussion();
            $message->sender_id = $senderId;
            $message->receiver_id = $receiverIds;
            $message->message = $request->message;
            $message->group_id = $request->group_id;
            $message->is_read = $isReadJson;
            $message->save();
        
            broadcast(new GroupMessage($message))->toOthers();
        
            DB::commit();
            $groupId = $request->group_id;
            $messages = GroupDiscussion::where('group_id', $groupId)->get();
        
            // Fetch sender name for each message
            foreach ($messages as $message) {
                $message->sender_name = User::where('id', $message->sender_id)->select('name')->first()->name;
            }
        
            return response()->json(['success' => true, 'messages' => 'Message stored successfully', 'messages' => $messages]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['errors' => 'Failed to store message.'], 500);
        }

    }

    public function loadgroupchats($groupId)
    {
        $userId = Auth::id();

        $messages = GroupDiscussion::where('group_id', $groupId)->get();

        foreach ($messages as $message) {
            $message->receiver_id = json_decode($message->receiver_id, true);
            $message->sender_name = User::where('id', $message->sender_id)->select('name')->first()->name;
        }

        return response()->json(['success' => true, 'messages' => 'Load Message successfully', 'messages' => $messages]);
    }

    public function unreadmesagecountgroup($companyId)
    {
        $userId = Auth::id();

        $groups = Groups::where('companyId', $companyId)
            ->whereRaw("FIND_IN_SET('$userId', group_members)")
            ->select('id')
            ->get();

        $unreadCounts = [];

        foreach ($groups as $group) {
            $groupchats = GroupDiscussion::where('group_id', $group->id)->get();

            $unreadCount = 0;
            foreach ($groupchats as $groupchat) {
                $receivers = json_decode($groupchat->receiver_id, true);
                $isRead = json_decode($groupchat->is_read, true);
                if (in_array($userId, $receivers)) {
                    $index = array_search($userId, $receivers);
                    if (isset($isRead[$index]) && $isRead[$index] === 0) {
                        $unreadCount++;
                    }
                }
            }

            $unreadCounts[] = ['group_id' => $group->id, 'unread_count' => $unreadCount];
        }

        return response()->json(['success' => true, 'messages' => 'Unread Message successfully', 'unread_counts' => $unreadCounts]);
    }



    public function discussionprojects($companyId)
    {
        $userId = Auth::user()->id;
        $data = Project::where('deleted_at', null)->where('company_id', $companyId)->whereRaw("FIND_IN_SET('$userId', members_id)")->orWhere('manager_id', $userId)->select('id', 'name')->get();

        return response()->json(['success' => true, 'messages' => 'Projects get successfully', 'data' => $data]);
    }

    public function groupLists($companyId)
    {
        $userId = Auth::user()->id;
        $owner = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if($owner != ''){
                $data = DB::table('groups')->where('companyId', $companyId)->select('id', 'group_name', 'created_id')->where('deleted_at', null)->get();
            } else {
                $data = DB::table('groups')->whereRaw("FIND_IN_SET('$userId', group_members)")->where('deleted_at', null)->where('companyId', $companyId)->select('id', 'group_name', 'created_id')->get();
            }
        $lists = [];
        foreach ($data as $datas) {
            if ($datas->created_id == $userId) {
                $edit = 1;
                $delete = 1;
            } else if ($owner != ''){
                $edit = 1;
                $delete = 1;
            } else {
                $edit = 0;
                $delete = 0;
            }
            $lists[] = [
                'id' => $datas->id,
                'name' => $datas->group_name,
                'edit' => $edit,
                'delete' => $delete
            ];
        }
        return response()->json(['success' => true, 'messages' => 'Group List get successfully', 'data' => $lists]);
    }

    public function getGroupMember(Request $request, $groupId)
    {
        $team = Groups::where('id', $groupId)->first();
        if ($team) {
            $members_id = explode(",", $team->group_members);
            $userData = User::select('id', 'name')->whereIn('id', $members_id)->get();
            foreach ($userData as $key => $value) {
                $words = explode(" ", $value->name);
                $acronym = "";
                foreach ($words as $key => $w) {
                    if ($key <= 1) {
                        $acronym .= mb_substr($w, 0, 1);
                    }
                }
                $m_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'setWords' => strtoupper($acronym),
                ];
                $memberData[] = $m_data;
            }

            if ($memberData) {
                return response()->json([
                    'status' => true,
                    'message' => 'Team member list successfully',
                    'data' => $memberData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Team member data not found',
                    'data' => null
                ]);
            }
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Team data not found',
                'data' => null
            ]);
        }
    }

    public function groupmessagesread($groupId){
        $userId = Auth::user()->id;

        $groups = GroupDiscussion::where('group_id', $groupId)->get();

        foreach ($groups as $group) {
            $receivers = json_decode($group->receiver_id, true);
            $isread = json_decode($group->is_read, true);

            if (in_array($userId, $receivers)) {
                $index = array_search($userId, $receivers);

                if (isset($isread[$index])) {
                    $isread[$index] = 1;
                }
            }
            $group->is_read = json_encode($isread);
            $group->update();
        }

        return response()->json(['success' => true, 'messages' => 'Message Read successfully']);
    }

    public function groupEdit($editId){
        $data = Groups::where('id', $editId)->select('id', 'project_id', 'group_name', 'group_members')->first();
        $data->projects = Project::select('id AS item_id', 'name AS item_text')->where('id',$data->project_id)->get();
        $userIds = explode(',', $data->group_members);
        $data->users = User::select('id AS item_id', 'name AS item_text')->whereIn('id',$userIds)->get();
        return response()->json(['status' => true, 'messages' => 'Message Read successfully', 'data' => $data]);
    }

    public function groupDelete(Request $request){
        $groupId = $request->get('groupId');

        $data = Groups::where('id', $groupId)->first();
        if($data){
            $data->delete();
        }
        return response()->json(['status' => true, 'messages' => 'Group Delete Successfully']);
    }

    public function updategroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'group_name' => 'required',
            'project_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = Auth::user()->id;
        $project_id = null;
        $members_id = null;
        
        if (isset($request->is_sender)) {
            if($request->project_id){
                $data = json_decode($request->project_id, true);
                $itemIds = array_column($data, 'item_id');
                $project_id = implode(",", $itemIds);
            }
            if ($request->group_members) {
                $data = json_decode($request->group_members, true); // Use correct field name
                $itemIds = array_column($data, 'item_id');
                $members_id = implode(",", $itemIds);
            }
        } else {
            if ($request->project_id) {
                $data = $request->project_id;
                $itemIds = array_column($data, 'item_id');
                $project_id = implode(",", $itemIds);
            }
    
            if ($request->group_members) {
                $data = $request->group_members; // Use correct field name
                $itemIds = array_column($data, 'item_id');
                $members_id = implode(",", $itemIds);
            }
        }
        
        // if ($request->project_id) {
        //     $data = $request->project_id;
        //     $itemIds = array_column($data, 'item_id');
        //     $project_id = implode(",", $itemIds);
        // }

        // if ($request->group_members) {
        //     $data = $request->group_members;
        //     $itemIds = array_column($data, 'item_id');
        //     $members_id = implode(",", $itemIds);
        // }

        $post = Groups::find($request->get('id'));
        $post->group_name = $request->get('group_name');
        $post->project_id = $project_id;
        $post->group_members = $members_id;
        $post->update();


        return response()->json(['success' => true, 'messages' => 'Group Updated Successfully']);
    }
}
