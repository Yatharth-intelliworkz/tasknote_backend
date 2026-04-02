<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\Task;
    use App\Models\Project;
    use App\Models\Service;
    use App\Models\TaskType;
    use App\Models\Attestment;
    use App\Models\TaskAssigne;
    use App\Models\SubTask;
    use App\Models\CompanyStatus;
    use App\Models\ProjectFavorite;
    use App\Models\CheckList;
    use App\Models\ProjectClient;
    use App\Models\ProjectCheckList;
    use App\Models\Note;
    use App\Models\Company;
    use App\Models\NoteShare;
    use App\Models\UserCheckList;
    use App\Models\Team;
    use App\Models\UserRole;
    use App\Models\TaskComment;
    use App\Models\TaskRemind;
    use App\Models\UserModel;
    use App\Models\TaskDelay;
    use App\Models\Notification;
    use App\Models\UserPermission;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Mail;
    use Illuminate\Support\Facades\Log;
    use Exception;
    use DB;
    use DateTime;
    use Carbon\Carbon;
    use App\Events\PushNotification;
    use App\Services\FCMService;
    use DateInterval;

    class WorkSheetController extends Controller{
        
        public function index(Request $request){
            $request->validate([
                'companyID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = Auth::user()->id;
            $curantDate = date('Y-m-d');
            // dd($curantDate);
            // $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
            // if ($ownerCheck) {
            //     $assignTask = Task::where('company_id', $companyId)->pluck('id')->toArray();
            // } else {
                $workTaskByAssign = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                
                $workTaskCreated = Task::where('user_id', $userId)->where('company_id', $companyId)->pluck('id')->toArray();
                
                $assignTask = array_merge($workTaskCreated, $workTaskByAssign);
            // }
            $assignTask = array_unique($assignTask);
            // dd($assignTask);
            $workData = [];
            // $worksTasksList = Task::where('completed', 0)->whereIn('id', $assignTask)->get();
            
            $worksTasksList = Task::join("user_check_lists", "user_check_lists.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->where('tasks.completed', 0)
                ->whereIn('tasks.id', $assignTask)
                ->where('user_check_lists.completed', 0)
                ->whereDate('tasks.start_date', '<=', $curantDate)
                ->whereNull('user_check_lists.deleted_at')
                ->get();
            foreach ($worksTasksList as $task) {
                $taskData = [
                    'task_id' => $task->task_id,
                    'description' => $task->description,
                    'checklist_name' => $task->checklist_name,
                    'checklist_id' => $task->id,
                    'completedNote' => $task->completedNote,
                    'user_hour' => $task->user_hour,
                    'user_minute' => $task->user_minute,
                    // 'completed' => $task->completed,
                ];
                $workData[] = $taskData;
            }
            if($workData){
                return response()->json([
                    'status' => true,
                    'message' => 'Work list successfully',
                    'data' => $workData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Work data not found',
                    'data' => []
                ]);
            }
        }
        
        public function workPendingTaskListOwner(Request $request){
            $request->validate([
                'userId' => 'required',
            ]);
            $userId = $request->userId;
            $UserData = User::where('id', $userId)->first();
            if($UserData){
                $companyId = $UserData->company_id;
                $curantDate = date('Y-m-d');
                
                $workTaskByAssign = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                
                $workTaskCreated = Task::where('user_id', $userId)->where('company_id', $companyId)->pluck('id')->toArray();
                
                $assignTask = array_merge($workTaskCreated, $workTaskByAssign);
                $assignTask = array_unique($assignTask);
                // dd($assignTask);
                $workData = [];
                // $worksTasksList = Task::where('completed', 0)->whereIn('id', $assignTask)->get();
                
                $worksTasksList = Task::join("user_check_lists", "user_check_lists.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.completed', 0)
                    ->whereIn('tasks.id', $assignTask)
                    ->where('user_check_lists.completed', 0)
                    ->whereDate('tasks.start_date', '<=', $curantDate)
                    ->whereNull('user_check_lists.deleted_at')
                    ->get();
                foreach ($worksTasksList as $task) {
                    $taskData = [
                        'task_id' => $task->task_id,
                        'description' => $task->description,
                        'checklist_name' => $task->checklist_name,
                        'checklist_id' => $task->id,
                        'completedNote' => $task->completedNote,
                        'user_hour' => $task->user_hour,
                        'user_minute' => $task->user_minute,
                        // 'completed' => $task->completed,
                    ];
                    $workData[] = $taskData;
                }
                if($workData){
                    return response()->json([
                        'status' => true,
                        'message' => 'Work list successfully',
                        'data' => $workData
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Work data not found',
                        'data' => []
                    ]);
                }    
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Work data not found',
                    'data' => []
                ]);
            }
            
        }
        
        public function workSheetUpdate(Request $request){
            // $request->validate([
            //     'completed' => 'required',
            //     'taskIds' => 'required',
            //     'checklistids' => 'required',
            // ]);
            $completed = 0;
            if($request->completed == true){
                $completed = 1;
            } 
            $userId = Auth::user()->id;
            $timerhour = $request->timerhour;
            $timerminute = $request->timerminute;
            $task = Task::where('id',$request->taskIds)->first();
            
            if($task){
                if($completed == 1){
                    if ($timerhour !== '00' && $timerminute !== '00') {
                        $timerhour = $timerhour ?? 00;
                        $timerminute = $timerminute ?? 00;
                        $oldTime = $task->actual_time;
                        $dateTime = new DateTime($oldTime);
                        $dateInterval = new DateInterval("PT{$timerhour}H{$timerminute}M");
                        $dateTime->add($dateInterval);
                        $newTime = $dateTime->format('H:i:s');
                        $task->actual_time = $newTime;
                        $task->save();
                    } else if($timerhour !== '00') {
                        $timerhour = $timerhour ?? 00;
                        $oldTime = $task->actual_time;
                        $dateTime = new DateTime($oldTime);
                        $dateInterval = new DateInterval("PT{$timerhour}H");
                        $dateTime->add($dateInterval);
                        $newTime = $dateTime->format('H:i:s');
                        $task->actual_time = $newTime;
                        $task->save();
                    } else if($timerminute !== '00'){
                        $timerminute = $timerminute ?? 00;
                        $oldTime = $task->actual_time;
                        $dateTime = new DateTime($oldTime);
                        $dateInterval = new DateInterval("PT{$timerminute}M");
                        $dateTime->add($dateInterval);
                        $newTime = $dateTime->format('H:i:s');
                        $task->actual_time = $newTime;
                        $task->save();
                    } else if ($timerhour == '00' && $timerminute == '00') {
                        $oldTime = $task->actual_time;
                        $newTime = $request->actualtime;
                        $totalSeconds = $this->timeToSeconds($oldTime) + $this->timeToSeconds($newTime);
                        $resultTime = $this->secondsToTime($totalSeconds);
                        $task->actual_time = $resultTime;
                        $task->save();
                    } 
                }  
                // dd('done');
                $userCheck = [
                    'user_id' => $userId, 
                    'task_id' => $request->taskIds, 
                    'completed' => $completed, 
                    'checklist_id' => $request->checklistids, 
                    'user_hour' => $request->timerhour, 
                    'user_minute' => $request->timerminute, 
                    'completedNote' => $request->completedNote, 
                ];
            
                $posts = UserCheckList::find($request->checklistids);
                $posts->user_id = $userId;
                $posts->completed = $completed;
                $posts->user_hour = $request->timerhour;
                $posts->user_minute = $request->timerminute;
                $posts->completedNote = $request->completedNote;
                $posts->update();
                
                
                if($completed == 1){
                    return response()->json([
                        'status' => true,
                        'message' => 'Work Sheet Update successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => true,
                        'message' => 'Work Sheet Update successfully',
                    ]);
                }
                
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data note found',
                ]);
            }
        }
        
        function timeToSeconds($time) {
            list($hours, $minutes, $seconds) = explode(':', $time);
            return $hours * 3600 + $minutes * 60 + $seconds;
        }
        
        function secondsToTime($seconds) {
            $hours = floor($seconds / 3600);
            $seconds %= 3600;
            $minutes = floor($seconds / 60);
            $seconds %= 60;
        
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
    }