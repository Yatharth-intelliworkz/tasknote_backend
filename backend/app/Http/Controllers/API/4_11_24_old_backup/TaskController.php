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

    class TaskController extends Controller{
        
        public function index(Request $request){
            $request->validate([
                'companyID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = Auth::user()->id;
            
            $add = 1;
            $edit = 1;
            $delete = 1;
            $userData = User::where('id',$userId)->first();
            if($userData){
                $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',2)->first();
                if($userModel){
                    $add = $userModel->add;
                    $edit = $userModel->edit;
                    $delete = $userModel->delete;
                }
            }
            
            // Check if the user is the owner of the company
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
            
            $assignTask = [];
            if($request->userID){
                if (isset($request->is_sender)) {
                    $data = json_decode($request->userID, true);
                } else {
                    $data = $request->userID;
                }
                $itemIds = array_column($data, 'item_id');
                foreach ($itemIds as $val) {
                    $taskAssigneMemberData = TaskAssigne::whereRaw('FIND_IN_SET(?, members_id)', [$val])
                        ->pluck('task_id')
                        ->toArray();
            
                    $teamIds = Team::where('company_id', $companyId)
                        ->whereRaw('FIND_IN_SET(?, members_id)', [$val])
                        ->pluck('id')
                        ->toArray();
            
                    $taskAssigneTeamData = TaskAssigne::whereIn('team_id', $teamIds)
                        ->pluck('task_id')
                        ->toArray();
            
                    $taskMyData = Task::where('user_id', $val)
                        ->where('company_id', $companyId)
                        ->pluck('id')
                        ->toArray();
            
                    $subTaskAssigneMemberData = SubTask::whereRaw('FIND_IN_SET(?, assigne_id)', [$val])
                        ->pluck('task_id')
                        ->toArray();
            
                    $assignTask = array_merge($taskAssigneMemberData, $taskAssigneTeamData, $taskMyData, $subTaskAssigneMemberData);
                }
            } else {
                if ($ownerCheck) {
                    // User is the owner, get all tasks of the company
                    $assignTask = Task::where('company_id', $companyId)->pluck('id')->toArray();
                } else {
                    // User is not the owner, get tasks assigned to user directly, through teams, and subtasks
                    $taskAssigneMemberData = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                    
                    $teamIds = Team::where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('id')->toArray();
                    $taskAssigneTeamData = TaskAssigne::whereIn('team_id', $teamIds)->pluck('task_id')->toArray();
                    
                    $taskMyData = Task::where('user_id', $userId)->where('company_id', $companyId)->pluck('id')->toArray();
                    
                    $subTaskAssigneMemberData = SubTask::whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->pluck('task_id')->toArray();
                    
                    // Combine all task IDs
                    $assignTask = array_merge($taskAssigneMemberData, $taskAssigneTeamData, $taskMyData, $subTaskAssigneMemberData);
                }
            }
            
            
            // Filter out duplicate task IDs
            $assignTask = array_unique($assignTask);
            
            // Fetch all tasks once to minimize database queries
            $allTasks = Task::whereIn('id', $assignTask)->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees']);
            if($request->rangetyped == 1){
                $checkFilter = 1;
                if($request->duedate == true){
                    $rangeStartFormat = new DateTime($request->rangeStart);
                    $rangeEndFormat = new DateTime($request->rangeEnd);
                    
                    $startRangeFormet = $rangeStartFormat->format('Y-m-d');
                    $endRangeFormet = $rangeEndFormat->format('Y-m-d');
                    // dd($rangeEndFormat);
                    $allTasks->whereDate('tasks.due_date', '>=', $startRangeFormet);
                    $allTasks->whereDate('tasks.due_date', '<=', $endRangeFormet);
                }

                if($request->createddate == true){
                    $rangeStartFormat = new DateTime($request->rangeStart);
                    $rangeEndFormat = new DateTime($request->rangeEnd);

                    $startRangeFormet = $rangeStartFormat->format('Y-m-d');
                    $endRangeFormet = $rangeEndFormat->format('Y-m-d');

                    $allTasks->whereDate('tasks.created_at', '>=', $startRangeFormet);
                    $allTasks->whereDate('tasks.created_at', '<=', $endRangeFormet);
                }

                if($request->closeddate == true){
                        $rangeStartFormat = new DateTime($request->rangeStart);
                        $rangeEndFormat = new DateTime($request->rangeEnd);

                        $startRangeFormet = $rangeStartFormat->format('Y-m-d');
                        $endRangeFormet = $rangeEndFormat->format('Y-m-d');
                        // dd($startRangeFormet);
                        // $taskList->where('tasks.completed', 1);
                        $allTasks->whereDate('tasks.completed_date', '>=', $startRangeFormet);
                        $allTasks->whereDate('tasks.completed_date', '<=', $endRangeFormet);
                    }
            } elseif($request->rangetyped == 2) {
                $checkFilter = 1;
                if($request->duedate == true){
                    $beforDate = new DateTime($request->befordate);

                    $beforFormat = $beforDate->format('Y-m-d');

                    $allTasks->whereDate('tasks.due_date', '<=', $beforFormat);
                }
                if($request->createddate == true){

                    $beforDate = new DateTime($request->befordate);

                    $beforFormat = $beforDate->format('Y-m-d');

                    $allTasks->whereDate('tasks.created_at', '<=', $beforFormat);
                }
                if($request->closeddate == true){

                    $beforDate = new DateTime($request->befordate);

                    $beforFormat = $beforDate->format('Y-m-d');

                    $allTasks->where('tasks.completed', 1);
                    $allTasks->whereDate('tasks.completed_date', '<=', $beforFormat);
                }
            
            } elseif($request->rangetyped == 3) {
                $checkFilter = 1;
                if($request->duedate == true){
                    $onDate = new DateTime($request->ondate);

                    $onFormat = $onDate->format('Y-m-d');

                    $allTasks->whereDate('tasks.due_date', $onFormat);
                }
                if($request->createddate == true){
                    $onDate = new DateTime($request->ondate);

                    $onFormat = $onDate->format('Y-m-d');

                    $allTasks->whereDate('tasks.created_at', $onFormat);
                }
                if($request->closeddate == true){
                    $onDate = new DateTime($request->ondate);

                    $onFormat = $onDate->format('Y-m-d');

                    $allTasks->where('tasks.completed', 1);
                    $allTasks->whereDate('tasks.completed_date', $onFormat);
                }
            } elseif($request->rangetyped == 4) {
                $checkFilter = 1;
                if($request->duedate == true){
                    $afterDate = new DateTime($request->afterdate);

                    $afterFormat = $afterDate->format('Y-m-d');

                    $allTasks->whereDate('tasks.due_date', '>=', $afterFormat);
                }
                if($request->createddate == true){
                    $afterDate = new DateTime($request->afterdate);

                    $afterFormat = $afterDate->format('Y-m-d');

                    $allTasks->whereDate('tasks.created_at', '>=', $afterFormat);
                }
                if($request->closeddate == true){
                    $afterDate = new DateTime($request->afterdate);

                    $afterFormat = $afterDate->format('Y-m-d');

                    $allTasks->where('tasks.completed', 1);
                    $allTasks->whereDate('tasks.completed_date', '>=', $afterFormat);
                }
            }
            if($request->statusID){
                $statusId = $request->statusID;
                $statusIds = explode(',', $statusId);
                $allTasks->whereIn('tasks.status', $statusIds);
            }
            $allTasks = $allTasks->get();
            
            $response = [
                'pinTaskData' => [],
                'todayTaskData' => [],
                'overDueTaskData' => [],
                'upcomingTaskData' => [],
                'completedTaskData' => [],
            ];
            
            $paymentDate = now()->toDateString(); // Current date
            foreach ($allTasks as $task) {
                // dd($task->assignees);
                $projectName = optional($task->project)->name;
                $clientName = optional($task->client)->name;
                $serviceName = optional($task->service)->title;
                $createdName = optional($task->user)->name;
            
                // Determine task dates
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
            
                // Check task status
                $status = CompanyStatus::where('id', $task->status)->first();
                if($status){
                    $statusName = $status->status;
                } else {
                    $statusName = 'Pending';
                }
            
                // Check task due date relative to current date
                $carbonDate = Carbon::parse($dueDate);
                $checkDate = $carbonDate->toDateString();
                // $checkDate = $task->due_date->toDateString();
                $todayTask = $checkDate === $paymentDate ? 1 : 0;
                $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
            
                // Process subtasks
                $totalSubTask = $task->subtasks->count();
            
                // Process assignees
                $memberData = [];
                $checkFilter = 0;
                foreach ($task->assignees as $val) {
                    if($val->members_id){
                        $members_id = explode(",", $val->members_id);
                        $userData = User::whereIn('id',$members_id)->get();
                        foreach ($userData as $key => $v) {
                            if($v->profile){
                                $profile = asset('public/images/profilePhoto/'. $v->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            $pro_data = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'profile' => $profile,
                            ];
                            $memberData[] = $pro_data;
                        }
                    } else {
                        $team_id = explode(",", $val->team_id);
                        $taskTeamData = Team::whereIn('id', $team_id)->get();
                        foreach ($taskTeamData as $key => $v) {
                            $team_members_id = explode(",", $v->members_id);
                            if($team_members_id){
                                $userData = User::whereIn('id',$team_members_id)->get();
                                foreach ($userData as $key => $v) {
                                    if($v->profile){
                                        $profile = asset('public/images/profilePhoto/'. $v->profile);
                                    } else {
                                        $profile = asset('public/images/user_avatar.png');
                                    }
                                    $pro_data = [
                                        'id' => $v->id,
                                        'name' => $v->name,
                                        'profile' => $profile,
                                    ];
                                    $memberData[] = $pro_data;
                                }
                            }
                            
                        }
                    }
                }
                if($add == 1){
                    $subTask = SubTask::where('task_id', $task->id)->get();
                } else {
                    $subTask = SubTask::where('task_id', $task->id)->whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->get();
                }
                $checkList = CheckList::where('project_id', $task->project_id)->get();
                if($checkList){
                    foreach ($checkList as $key => $val) {
                        $c_data = [
                            'id' => $val->id,
                            'remark' => $val->remark,
                            'ckluser_id' => $val->assigne_id,
                            'hours' => $val->hour,
                            'minutes' => $val->minute,
                        ];
                        $checkData[] = $c_data;
                    }
                }
                // Prepare task data
                $taskData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'createdName' => $createdName,
                    'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                    'service' => $serviceName,
                    'pinTask' => $task->pin,
                    'completed' => $task->completed,
                    'status' => $statusName,
                    'statusId' => $task->status,
                    'priority' => $task->priority,
                    'todayTask' => $todayTask,
                    'overDueTask' => $overDueTask,
                    'upcomingTask' => $upcomingTask,
                    'subTaskList' => $subTask,
                    'memberData' => $memberData,
                    'created_at' => $created_at,
                    'isSubTask' => $totalSubTask,
                    'checkList' => $checkList,
                ];
            
                // Categorize task based on due date and status
                if ($task->completed === 0) {
                    if ($task->pin === 1) {
                        $response['pinTaskData'][] = $taskData;
                    } elseif ($checkDate === $paymentDate) {
                        $response['todayTaskData'][] = $taskData;
                    } elseif ($checkDate < $paymentDate) {
                        $response['overDueTaskData'][] = $taskData;
                    } elseif ($checkDate > $paymentDate) {
                        $response['upcomingTaskData'][] = $taskData;
                    }
                } elseif ($checkFilter == 1) {
                    $startOfMonth = now()->startOfMonth()->toDateString();
                    $endOfMonth = now()->endOfMonth()->toDateString();
                    if ($task->completed_date >= $startOfMonth && $task->completed_date <= $endOfMonth) {
                        $response['completedTaskData'][] = $taskData;
                    }
                } else {
                    $startOfMonth = now()->startOfMonth()->toDateString();
                    $endOfMonth = now()->endOfMonth()->toDateString();
                    // dd($startOfMonth);
                    if ($task->completed_date >= $startOfMonth && $task->completed_date <= $endOfMonth) {
                        $response['completedTaskData'][] = $taskData;
                    }
                }
            }



            
            
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Task list successfully',
                    'add' => $add,
                    'edit' => $edit,
                    'delete' => $delete,
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                    'data' => []
                ]);
            }
        }
        
        public function myTaskList(Request $request){
            $request->validate([
                'companyID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = Auth::user()->id;
            
            
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
            
            $assignTask = [];
            $subData = [];
            $add = 1;
            $edit = 1;
            $delete = 1;
            $userData = User::where('id',$userId)->first();
            if($userData){
                $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',2)->first();
                if($userModel){
                    $add = $userModel->add;
                    $edit = $userModel->edit;
                    $delete = $userModel->delete;
                }
            }
            
            
            if ($ownerCheck) {
                $assignTask = Task::where('company_id', $companyId)->pluck('id')->toArray();
            } else {
                // User is not the owner, get tasks assigned to user directly, through teams, and subtasks
                $taskAssigneMemberData = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                
                $teamIds = Team::where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('id')->toArray();
                $taskAssigneTeamData = TaskAssigne::whereIn('team_id', $teamIds)->pluck('task_id')->toArray();
                
                $taskMyData = Task::where('user_id', $userId)->where('company_id', $companyId)->pluck('id')->toArray();
                
                $subTaskAssigneMemberData = SubTask::whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->pluck('task_id')->toArray();
                
                // Combine all task IDs
                $assignTask = array_merge($taskAssigneMemberData, $taskAssigneTeamData, $taskMyData, $subTaskAssigneMemberData);
            }
            
            
            
            
            // Filter out duplicate task IDs
            $assignTask = array_unique($assignTask);
            
            $paymentDate = now()->toDateString();
            
            $response = [
                'pinTaskData' => [],
                'todayTaskData' => [],
                'overDueTaskData' => [],
                'upcomingTaskData' => [],
                'completedTaskData' => [],
            ];
            
            // Pin Task
            $assignTaskIds = implode(',', $assignTask);
            $checkData = [];
            $pinTasks = DB::select('CALL GetPinnedTasks(?)', [$assignTaskIds]);
            // $pinTasks = Task::where('pin', 1)->where('completed', 0)->whereIn('id', $assignTask)->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])->get();
            foreach ($pinTasks as $task) {
                $projectName = $task->project_name;
                $clientName = $task->client_name;
                $serviceName = $task->service_name;
                $createdName = $task->created_name;
            
                // Determine task dates
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
            
                // Check task status
                $status = CompanyStatus::where('id', $task->status)->first();
                if($status){
                    $statusName = $status->status;
                } else {
                    $statusName = 'Pending';
                }
            
                // Check task due date relative to current date
                $carbonDate = Carbon::parse($dueDate);
                $checkDate = $carbonDate->toDateString();
                // $checkDate = $task->due_date->toDateString();
                $todayTask = $checkDate === $paymentDate ? 1 : 0;
                $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
            
                // Process assignees
                $memberData = [];
                $checkFilter = 0;
                // foreach ($task->task_assignes as $val) {
                    if(!empty($task->members_id)){
                        $members_id = explode(",", $task->members_id);
                        $userData = User::whereIn('id',$members_id)->get();
                        foreach ($userData as $key => $v) {
                            if($v->profile){
                                $profile = asset('public/images/profilePhoto/'. $v->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            $pro_data = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'is_login' => $v->is_login,
                                'profile' => $profile,
                            ];
                            $memberData[] = $pro_data;
                        }
                    } 
                // }
                
                $typeCheckList = ProjectCheckList::where('project_id', $task->project_id)->where('taskTypeId', $task->type_id)->get();
                $typeCheckData = [];
                if($typeCheckList){
                    foreach ($typeCheckList as $key => $val) {
                        $checkcompleted = UserCheckList::where('checklist_id', $val->id)->where('task_id', $task->id)->first();
                        if (is_null($checkcompleted)) {
                            $m_data = [
                                'id' => $val->id,
                                'taskTypeId' => $val->taskTypeId,
                                'tasktypechecklist' => $val->tasktypechecklist,
                                'tasktyperemark' => $val->tasktyperemark,
                                'is_document' => $val->is_document,
                            ];
                            $typeCheckData[] = $m_data;
                        }
                    }
                }
                $createdProfile = null;
                $createdUser = User::where('id',$task->user_id)->first();
                if($createdUser){
                    $createdProfile = $createdUser->profile ? asset('public/images/profilePhoto/'. $createdUser->profile) : null;
                }
                $taskData = [
                    'task_id' => $task->id,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'createdName' => $createdName,
                    'createdProfile' => $createdProfile,
                    'service' => $serviceName,
                    'pinTask' => $task->pin,
                    'completed' => $task->completed,
                    'status' => $statusName,
                    'statusId' => $task->status,
                    'priority' => $task->priority,
                    'todayTask' => $todayTask,
                    'overDueTask' => $overDueTask,
                    'upcomingTask' => $upcomingTask,
                    'memberData' => $memberData,
                    'created_at' => $created_at,
                    'checkList' => $typeCheckData,
                ];
            
                // Categorize task based on due date and status
                
                $response['pinTaskData'][] = $taskData;
                    
            }
            
            // Completed Task List
            
            
            $startOfMonth = now()->startOfMonth()->toDateString();
            $endOfMonth = now()->endOfMonth()->toDateString();
            $completedTasks = DB::select('CALL GetCompletedTasks(?, ?, ?)', [
                $startOfMonth,
                $endOfMonth,
                $assignTaskIds,
            ]);
            // $completedTasks = Task::where('completed', 1)
            // ->whereIn('id', $assignTask)
            // ->whereDate('completed_date', '>=', $startOfMonth)
            // ->whereDate('completed_date', '<=', $endOfMonth)
            // ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
            // ->get();
            foreach ($completedTasks as $task) {
                // dd($task->assignees);
                $projectName = $task->project_name;
                $clientName = $task->client_name;
                $serviceName = $task->service_name;
                $createdName = $task->created_name;
            
                // Determine task dates
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
            
                $status = CompanyStatus::where('id', $task->status)->first();
                if($status){
                    $statusName = $status->status;
                } else {
                    $statusName = 'Pending';
                }
            
                // Check task due date relative to current date
                $carbonDate = Carbon::parse($dueDate);
                $checkDate = $carbonDate->toDateString();
                // $checkDate = $task->due_date->toDateString();
                $todayTask = $checkDate === $paymentDate ? 1 : 0;
                $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
            
                // Process assignees
                $memberData = [];
                // foreach ($task->assignees as $val) {
                    if(!empty($task->members_id)){
                        $members_id = explode(",", $task->members_id);
                        $userData = User::whereIn('id',$members_id)->get();
                        foreach ($userData as $key => $v) {
                            if($v->profile){
                                $profile = asset('public/images/profilePhoto/'. $v->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            $pro_data = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'is_login' => $v->is_login,
                                'profile' => $profile,
                            ];
                            $memberData[] = $pro_data;
                        }
                    }
                // }
                
                $typeCheckList = ProjectCheckList::where('project_id', $task->project_id)->where('taskTypeId', $task->type_id)->get();
                $typeCheckData = [];
                if($typeCheckList){
                    foreach ($typeCheckList as $key => $val) {
                        $checkcompleted = UserCheckList::where('checklist_id', $val->id)->where('task_id', $task->id)->first();
                        if (is_null($checkcompleted)) {
                            $m_data = [
                                'id' => $val->id,
                                'taskTypeId' => $val->taskTypeId,
                                'tasktypechecklist' => $val->tasktypechecklist,
                                'tasktyperemark' => $val->tasktyperemark,
                                'is_document' => $val->is_document,
                            ];
                            $typeCheckData[] = $m_data;
                        }
                    }
                }
                $createdProfile = null;
                $createdUser = User::where('id',$task->user_id)->first();
                if($createdUser){
                    $createdProfile = $createdUser->profile ? asset('public/images/profilePhoto/'. $createdUser->profile) : null;
                }
                $taskData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'createdName' => $createdName,
                    'createdProfile' => $createdProfile,
                    'service' => $serviceName,
                    'pinTask' => $task->pin,
                    'completed' => $task->completed,
                    'status' => $statusName,
                    'statusId' => $task->status,
                    'priority' => $task->priority,
                    'todayTask' => $todayTask,
                    'overDueTask' => $overDueTask,
                    'upcomingTask' => $upcomingTask,
                    'memberData' => $memberData,
                    'created_at' => $created_at,
                    'checkList' => $typeCheckData,
                ];
            
                // Categorize task based on due date and status
                
                $response['completedTaskData'][] = $taskData;
                    
            }
            
            
            // todayTask List
            
            
            $todayTasks = DB::select('CALL GetTodayTasks(?, ?)', [
                $paymentDate,
                $assignTaskIds,
            ]);
            // $todayTasks = Task::where('pin', 0)
            // ->where('completed', 0)
            // ->whereIn('id', $assignTask)
            // ->whereDate('due_date', $paymentDate)
            // ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
            // ->get();
            foreach ($todayTasks as $task) {
                $projectName = $task->project_name;
                $clientName = $task->client_name;
                $serviceName = $task->service_name;
                $createdName = $task->created_name;
            
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
            
                // Check task status
                $status = CompanyStatus::where('id', $task->status)->first();
                if($status){
                    $statusName = $status->status;
                } else {
                    $statusName = 'Pending';
                }
            
                // Check task due date relative to current date
                $carbonDate = Carbon::parse($dueDate);
                $checkDate = $carbonDate->toDateString();
                // $checkDate = $task->due_date->toDateString();
                $todayTask = $checkDate === $paymentDate ? 1 : 0;
                $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
            
                // Process assignees
                $memberData = [];
                $checkFilter = 0;
                
                // foreach ($task->assignees as $val) {
                    if(!empty($task->members_id)){
                        $members_id = explode(",", $val->members_id);
                        $userData = User::whereIn('id',$members_id)->get();
                        foreach ($userData as $key => $v) {
                            if($v->profile){
                                $profile = asset('public/images/profilePhoto/'. $v->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            $pro_data = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'is_login' => $v->is_login,
                                'profile' => $profile,
                            ];
                            $memberData[] = $pro_data;
                        }
                    } 
                // }
                
                $typeCheckList = ProjectCheckList::where('project_id', $task->project_id)->where('taskTypeId', $task->type_id)->get();
                $typeCheckData = [];
                if($typeCheckList){
                    foreach ($typeCheckList as $key => $val) {
                        $checkcompleted = UserCheckList::where('checklist_id', $val->id)->where('task_id', $task->id)->first();
                        if (is_null($checkcompleted)) {
                            $m_data = [
                                'id' => $val->id,
                                'taskTypeId' => $val->taskTypeId,
                                'tasktypechecklist' => $val->tasktypechecklist,
                                'tasktyperemark' => $val->tasktyperemark,
                                'is_document' => $val->is_document,
                            ];
                            $typeCheckData[] = $m_data;
                        }
                    }
                }
                
                $createdProfile = null;
                $createdUser = User::where('id',$task->user_id)->first();
                if($createdUser){
                    $createdProfile = $createdUser->profile ? asset('public/images/profilePhoto/'. $createdUser->profile) : null;
                }
                
                // Prepare task data
                $taskData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'createdProfile' => $createdProfile,
                    'createdName' => $createdName,
                    'service' => $serviceName,
                    'pinTask' => $task->pin,
                    'completed' => $task->completed,
                    'status' => $statusName,
                    'statusId' => $task->status,
                    'priority' => $task->priority,
                    'todayTask' => $todayTask,
                    'overDueTask' => $overDueTask,
                    'upcomingTask' => $upcomingTask,
                    'memberData' => $memberData,
                    'created_at' => $created_at,
                    'checkList' => $typeCheckData,
                ];
            
                // Categorize task based on due date and status
                
                $response['todayTaskData'][] = $taskData;
                    
            }
            
            
            // overDueTaskData
            
            
            $overDueTasks = DB::select('CALL GetOverDueTasks(?, ?)', [
                $paymentDate,
                $assignTaskIds,
            ]);
            // $overDueTasks = Task::where('pin', 0)
            // ->where('completed', 0)
            // ->whereIn('id', $assignTask)
            // ->whereDate('due_date', '<', $paymentDate)
            // ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
            // ->get();
            foreach ($overDueTasks as $task) {
                $projectName = $task->project_name;
                $clientName = $task->client_name;
                $serviceName = $task->service_name;
                $createdName = $task->created_name;
            
                // Determine task dates
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
            
                // Check task status
                $status = CompanyStatus::where('id', $task->status)->first();
                if($status){
                    $statusName = $status->status;
                } else {
                    $statusName = 'Pending';
                }
            
                // Check task due date relative to current date
                $carbonDate = Carbon::parse($dueDate);
                $checkDate = $carbonDate->toDateString();
                // $checkDate = $task->due_date->toDateString();
                $todayTask = $checkDate === $paymentDate ? 1 : 0;
                $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
            
                $memberData = [];
                $checkFilter = 0;
                
                // foreach ($task->assignees as $val) {
                    if(!empty($task->members_id)){
                        $members_id = explode(",", $task->members_id);
                        $userData = User::whereIn('id',$members_id)->get();
                        foreach ($userData as $key => $v) {
                            if($v->profile){
                                $profile = asset('public/images/profilePhoto/'. $v->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            $pro_data = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'is_login' => $v->is_login,
                                'profile' => $profile,
                            ];
                            $memberData[] = $pro_data;
                        }
                    }
                // }
                
                $typeCheckList = ProjectCheckList::where('project_id', $task->project_id)->where('taskTypeId', $task->type_id)->get();
                $typeCheckData = [];
                if($typeCheckList){
                    foreach ($typeCheckList as $key => $val) {
                        $checkcompleted = UserCheckList::where('checklist_id', $val->id)->where('task_id', $task->id)->first();
                        if (is_null($checkcompleted)) {
                            $m_data = [
                                'id' => $val->id,
                                'taskTypeId' => $val->taskTypeId,
                                'tasktypechecklist' => $val->tasktypechecklist,
                                'tasktyperemark' => $val->tasktyperemark,
                                'is_document' => $val->is_document,
                            ];
                            $typeCheckData[] = $m_data;
                        }
                    }
                }
                
                $createdProfile = null;
                $createdUser = User::where('id',$task->user_id)->first();
                if($createdUser){
                    $createdProfile = $createdUser->profile ? asset('public/images/profilePhoto/'. $createdUser->profile) : null;
                }
                
                $taskData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'createdName' => $createdName,
                    'createdProfile' => $createdProfile,
                    'service' => $serviceName,
                    'pinTask' => $task->pin,
                    'completed' => $task->completed,
                    'status' => $statusName,
                    'statusId' => $task->status,
                    'priority' => $task->priority,
                    'todayTask' => $todayTask,
                    'overDueTask' => $overDueTask,
                    'upcomingTask' => $upcomingTask,
                    'memberData' => $memberData,
                    'created_at' => $created_at,
                    'checkList' => $typeCheckData,
                ];
            
                // Categorize task based on due date and status
                
                $response['overDueTaskData'][] = $taskData;
                    
            }
            
            
            // upcomingTaskData
            
            
            $upcomingTasks = DB::select('CALL GetUpcomingTasks(?, ?)', [
                $paymentDate,
                $assignTaskIds,
            ]);
            // $upcomingTasks = Task::where('pin', 0)
            // ->where('completed', 0)
            // ->whereIn('id', $assignTask)
            // ->whereDate('due_date', '>', $paymentDate)
            // ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
            // ->get();
            foreach ($upcomingTasks as $task) {
                $projectName = $task->project_name;
                $clientName = $task->client_name;
                $serviceName = $task->service_name;
                $createdName = $task->created_name;
            
                // Determine task dates
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
            
                // Check task status
                $status = CompanyStatus::where('id', $task->status)->first();
                if($status){
                    $statusName = $status->status;
                } else {
                    $statusName = 'Pending';
                }
            
                // Check task due date relative to current date
                $carbonDate = Carbon::parse($dueDate);
                $checkDate = $carbonDate->toDateString();
                // $checkDate = $task->due_date->toDateString();
                $todayTask = $checkDate === $paymentDate ? 1 : 0;
                $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
            
                
                // Process assignees
                $memberData = [];
                $checkFilter = 0;
                // foreach ($task->assignees as $val) {
                    if(!empty($task->members_id)){
                        $members_id = explode(",", $val->members_id);
                        $userData = User::whereIn('id',$members_id)->get();
                        foreach ($userData as $key => $v) {
                            if($v->profile){
                                $profile = asset('public/images/profilePhoto/'. $v->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            $pro_data = [
                                'id' => $v->id,
                                'name' => $v->name,
                                'is_login' => $v->is_login,
                                'profile' => $profile,
                            ];
                            $memberData[] = $pro_data;
                        }
                    }
                // }
                $typeCheckList = ProjectCheckList::where('project_id', $task->project_id)->where('taskTypeId', $task->type_id)->get();
                $typeCheckData = [];
                if($typeCheckList){
                    foreach ($typeCheckList as $key => $val) {
                        $checkcompleted = UserCheckList::where('checklist_id', $val->id)->where('task_id', $task->id)->first();
                        if (is_null($checkcompleted)) {
                            $m_data = [
                                'id' => $val->id,
                                'taskTypeId' => $val->taskTypeId,
                                'tasktypechecklist' => $val->tasktypechecklist,
                                'tasktyperemark' => $val->tasktyperemark,
                                'is_document' => $val->is_document,
                            ];
                            $typeCheckData[] = $m_data;
                        }
                    }
                }
            
                $createdProfile = null;
                $createdUser = User::where('id',$task->user_id)->first();
                if($createdUser){
                    $createdProfile = $createdUser->profile ? asset('public/images/profilePhoto/'. $createdUser->profile) : null;
                }
                
                $taskData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'createdName' => $createdName,
                    'createdProfile' => $createdProfile,
                    'service' => $serviceName,
                    'pinTask' => $task->pin,
                    'completed' => $task->completed,
                    'status' => $statusName,
                    'statusId' => $task->status,
                    'priority' => $task->priority,
                    'todayTask' => $todayTask,
                    'overDueTask' => $overDueTask,
                    'upcomingTask' => $upcomingTask,
                    'memberData' => $memberData,
                    'created_at' => $created_at,
                    'checkList' => $typeCheckData,
                ];
            
                // Categorize task based on due date and status
                
                $response['upcomingTaskData'][] = $taskData;
                    
            }
            
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Task list successfully',
                    'add' => $add,
                    'edit' => $edit,
                    'delete' => $delete,
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                    'data' => []
                ]);
            }
        }
// public function index(Request $request){
//     $request->validate([
//         'companyID' => 'required',
//     ]);

//     $companyId = $request->companyID;
//     $userId = Auth::user()->id;

//     // Fetch assigned task IDs based on user and team memberships
//     $assignTask = $this->getAssignedTaskIds($companyId, $userId);

//     // Fetch tasks based on assigned task IDs and filter criteria
//     $myTaskData = $this->getFilteredTasks($assignTask, $companyId, $request);

//     // Organize tasks into different categories
//     $taskCategories = $this->organizeTasks($myTaskData);

//     // Fetch user permissions
//     $permissions = $this->getUserPermissions($userId);

//     if ($taskCategories) {
//         return response()->json([
//             'status' => true,
//             'message' => 'Task list successfully',
//             'permissions' => $permissions,
//             'data' => $taskCategories,
//             'add' => $permissions['add'],
//             'edit' => $permissions['edit'],
//             'delete' => $permissions['delete']
//         ]);
//     } else {
//         return response()->json([
//             'status' => false,
//             'message' => 'Task data not found',
//             'data' => []
//         ]);
//     }
// }

protected function getAssignedTaskIds($companyId, $userId) {
    $ownerCheck = Company::where('id', $companyId)
                         ->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')
                         ->exists();

    if ($ownerCheck) {
        return Task::where('company_id', $companyId)
                   ->orderBy('id', 'DESC')
                   ->pluck('id')
                   ->toArray();
    } else {
        // Fetch team IDs where the user is a member
        $teamIds = Team::where('company_id', $companyId)
                       ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
                       ->pluck('id')
                       ->toArray();

        // Fetch tasks assigned to teams where the user is a member
        $teamAssignTasks = TaskAssigne::whereIn('team_id', $teamIds)
                                      ->orderBy('id', 'DESC')
                                      ->pluck('task_id')
                                      ->toArray();

        // Fetch tasks assigned directly to the user
        $userAssignTasks = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
                                      ->orderBy('id', 'DESC')
                                      ->pluck('task_id')
                                      ->toArray();

        // Fetch tasks assigned to the user
        $userTasks = Task::where('user_id', $userId)
                         ->where('company_id', $companyId)
                         ->pluck('id')
                         ->toArray();

        // Fetch subtasks assigned to the user
        $subTaskAssignTasks = SubTask::whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')
                                      ->orderBy('id', 'DESC')
                                      ->pluck('task_id')
                                      ->toArray();

        // Merge all task arrays
        return array_unique(array_merge($teamAssignTasks, $userAssignTasks, $userTasks, $subTaskAssignTasks));
    }
}

protected function getFilteredTasks($assignTask, $companyId, $request) {
    $taskList = Task::orderBy('id', 'DESC')
                    ->whereIn('id', $assignTask)
                    ->where('company_id', $companyId);

    // Apply filtering based on the selected range type
    // Code to apply filtering goes here...

    // Apply status filtering if status IDs are provided
    if ($request->statusID) {
        $statusIds = explode(',', $request->statusID);
        $taskList->whereIn('status', $statusIds);
    }

    // Execute the query to fetch the tasks
    return $taskList->get();
}

protected function organizeTasks($myTaskData) {
    $taskCategories = [
        'pinTaskData' => [],
        'todayTaskData' => [],
        'overDueTaskData' => [],
        'upcomingTaskData' => [],
        'completedTaskData' => []
    ];

    foreach ($myTaskData as $value) {
    // foreach ($item as $value) {
        // dd($value);
        $projectData = Project::find($value->project_id);
        $projectName = $projectData ? $projectData->name : null;

        $clientData = ProjectClient::find($value->client_id);
        $clientName = $clientData ? $clientData->name : null;

        $serviceData = Service::find($value->service_id);
        $serviceName = $serviceData ? $serviceData->title : null;

        $checkDate = date('Y-m-d', strtotime($value->due_date));
        $subTask = SubTask::where('task_id', $value->id)->get();
        $memberData = [];
        $taskAssigneData = TaskAssigne::where('task_id', $value->id)->get();
        
        // Extract member data
        foreach ($taskAssigneData as $taskAssigne) {
            $memberIds = $taskAssigne->members_id ? explode(",", $taskAssigne->members_id) : [];
            $teamIds = $taskAssigne->team_id ? explode(",", $taskAssigne->team_id) : [];
            $memberIds = array_merge($memberIds, $teamIds);
            
            $userData = User::whereIn('id', $memberIds)->get();
            
            foreach ($userData as $user) {
                $profile = $user->profile ? asset('public/images/profilePhoto/'. $user->profile) : asset('public/images/user_avatar.png');
                $memberData[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'profile' => $profile,
                ];
            }
        }

        // Calculate task status
        $todayTask = ($checkDate == date("Y-m-d")) ? 1 : 0;
        $overDueTask = ($checkDate < date("Y-m-d")) ? 1 : 0;
        $upcomingTask = ($checkDate > date("Y-m-d")) ? 1 : 0;

        $dueDate = date('d-m-Y', strtotime($value->due_date));
        $startDate = date('d-m-Y', strtotime($value->start_date));
        $created_at = date('d-m-Y', strtotime($value->created_at));

        $status = CompanyStatus::find($value->status);
        $statusName = $status ? $status->status : 'Pending';

        $checkDueDate = date('Y-m-d', strtotime($value->due_date));
        $paymentDate = date('Y-m-d');

        $createdData = User::find($value->user_id);
        $createdName = $createdData ? $createdData->name : '';
        $createdProfile = $createdData ? ($createdData->profile ? asset('public/images/profilePhoto/'. $createdData->profile) : null) : null;

        $totalSubTask = SubTask::where('task_id', $value->id)->count();

        $taskData = [
            'task_id' => $value->id,
            'title' => $value->title,
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'description' => $value->description,
            'projectName' => $projectName,
            'clientName' => $clientName,
            'createdName' => $createdName,
            'createdProfile' => $createdProfile,
            'service' => $serviceName,
            'pinTask' => $value->pin,
            'completed' => $value->completed,
            'status' => $statusName,
            'priority' => $value->priority,
            'todayTask' => $todayTask,
            'overDueTask' => $overDueTask,
            'upcomingTask' => $upcomingTask,
            'subTaskList' => $subTask,
            'memberData' => $memberData,
            'created_at' => $created_at,
            'isSubTask' => $totalSubTask,
        ];

        // Determine task category and append to respective array
        if ($value->completed == 0) {
            if ($value->pin == 1) {
                $taskCategories['pinTaskData'][] = $taskData;
            } elseif ($checkDueDate == $paymentDate) {
                $taskCategories['todayTaskData'][] = $taskData;
            } elseif ($checkDueDate < $paymentDate) {
                $taskCategories['overDueTaskData'][] = $taskData;
            } elseif ($checkDueDate > $paymentDate) {
                $taskCategories['upcomingTaskData'][] = $taskData;
            }
        } else {
            $taskCategories['completedTaskData'][] = $taskData;
        }
    // }
    }
    
    
        return $taskCategories;
    }
    
    protected function getUserPermissions($userId) {
        $add = $edit = $delete = 1;
    
        $userData = User::where('id', $userId)->first();
    
        if ($userData) {
            $userModel = UserPermission::where('user_role_id', $userData->assignRole)
                                       ->where('user_model_id', 2)
                                       ->first();
    
            if ($userModel) {
                $add = $userModel->add;
                $edit = $userModel->edit;
                $delete = $userModel->delete;
            }
        }
    
        return compact('add', 'edit', 'delete');
    }

        public function taskAdd(Request $request){
            $userId = Auth::user()->id;
            if($request->start){
                $startfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->start));
                $startDateTime = new DateTime($startfromDate);
                $startDate = $startDateTime->format("Y-m-d H:i:s");
            }
            if($request->targettimehour || $request->targettimemin){
                $joingTime = $request->targettimehour . ':' . $request->targettimemin;
                $parts = explode(":", $joingTime);
                $hours = str_pad($parts[0], 2, "0", STR_PAD_LEFT);
                $minutes = str_pad($parts[1], 2, "0", STR_PAD_LEFT);
                
                $target_time = $hours . ':' . $minutes . ':00';
            } else {
                $target_time = '';
            }
            if($request->due_date){
                $duefromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->due_date));
                $dueDateTime = new DateTime($duefromDate);
                $dueDate = $dueDateTime->format("Y-m-d H:i:s");
            }
            if (isset($request->is_sender)) {
                if($request->project_id){
                    $data = json_decode($request->project_id, true);
                    $itemIds = array_column($data, 'item_id');
                    $project_id = implode(",", $itemIds);
                }
                if($request->client_id){
                    $client = json_decode($request->client_id, true);
                    $clientIds = array_column($client, 'item_id');
                    $client_id = implode(",", $clientIds);
                }
                if($request->responsible_person){
                    $person = json_decode($request->responsible_person, true);
                    $personIds = array_column($person, 'item_id');
                    $responsible_person = implode(",", $personIds);
                }
                if($request->tasktype){
                    $persons = json_decode($request->tasktype, true);
                    $personIdsa = array_column($persons, 'item_id');
                    $tasktypeId = implode(",", $personIdsa);
                }
            } else {
                if($request->project_id){
                    // $data = json_decode($request->project_id, true);
                    $data = $request->project_id;
                    $itemIds = array_column($data, 'item_id');
                    $project_id = implode(",", $itemIds);
                }
                if($request->client_id){
                    // $client = json_decode($request->client_id, true);
                    $client = $request->client_id;
                    $clientIds = array_column($client, 'item_id');
                    $client_id = implode(",", $clientIds);
                }
                $responsible_person = 0;
                if($request->responsible_person){
                    // $person = json_decode($request->responsible_person, true);
                    $person = $request->responsible_person;
                    $personIds = array_column($person, 'item_id');
                    $responsible_person = implode(",", $personIds);
                }
                 if($request->tasktype){
                    $persons = $request->tasktype;
                    $personIdsa = array_column($persons, 'item_id');
                    $tasktypeId = implode(",", $personIdsa);
                }
            }
            $status = 0;
            if($request->status){
                $status = $request->status;
            }
            
            $periodicDate = null;
            if($request->periodic_date){
                $periodicfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->periodic_date));
                $periodicDateTime = new DateTime($periodicfromDate);
                $periodicDate = $periodicDateTime->format("Y-m-d");
            }
            // dd($request->all());    
            
            // dd($target_time);
            $projectData = Project::where('id', $project_id)->first();
            if(isset($request->files) && !empty($request->files)){
                $task = [
                    'user_id' => $userId, 
                    'description' => $request->description, 
                    'start_date' =>$startDate, 
                    'due_date' =>$dueDate, 
                    'periodic_date' =>$periodicDate, 
                    'project_id' => $project_id,
                    'client_id'=>$client_id,
                    'company_id'=>$projectData->company_id,
                    'status'=>$status, 
                    'priority'=>$request->priority, 
                    'is_recurring'=>$request->is_recurring, 
                    'recurring_time'=>$request->recurring_time, 
                    'target_time'=>$target_time, 
                    'responsible_person'=>$responsible_person,
                    'remainingtotalCost'=>$request->remainingtotalCost,
                    'type_id'=>$tasktypeId,
                ];
                // dd($task);
                $task = Task::create($task);
                if ($request->hasFile('files')) {
                    $sheets = $request->file('files');
                    $upload_files = [];
                    foreach ($sheets as $sheet) {
                        $filename = $sheet->getClientOriginalName();
                        $extension = $sheet->getClientOriginalExtension();
                        $filename = mt_rand(10000000000,99999999999) . '.' . $extension;
                        // $filename = str_replace(' ', '_', time(), $filename); // replace spaces with underscores
                        $path = public_path('images/all');
                        if ($sheet->move($path, $filename)) {
                            $image = new Attestment;
                            $image->file = $filename;
                            $image->type_id = $task->id;
                            $image->type = 'tasks';
                            $image->save();
                        }
                    }
                }
            } else {
                $task = [
                    'user_id' => $userId, 
                    'description' => $request->description, 
                    'type_id' => $request->type_id, 
                    'start_date' =>$startDate, 
                    'due_date' =>$dueDate, 
                    'periodic_date' =>$periodicDate, 
                    'project_id' => $project_id,
                    'client_id'=>$client_id,
                    'company_id'=>$projectData->company_id,
                    'status'=>$status, 
                    'priority'=>$request->priority, 
                    'is_recurring'=>$request->is_recurring, 
                    'recurring_time'=>$request->recurring_time, 
                    'target_time'=>$target_time, 
                    'responsible_person'=>$responsible_person, 
                    'remainingtotalCost'=>$request->remainingtotalCost, 
                    'type_id'=>$tasktypeId,
                ];
                $task = Task::create($task);
            }
            $members_id = NULL;
            if (isset($request->is_sender)) {
                if($request->members_id){
                    if($request->members_id){
                        $data = json_decode($request->members_id, true);
                        $itemIds = array_column($data, 'item_id');
                        $members_id = implode(",", $itemIds);
                    }
                } else if($request->team_id){
                    if($request->team_id){
                        $data = json_decode($request->team_id, true);
                        $itemIds = array_column($data, 'item_id');
                        $team_id = implode(",", $itemIds);
                    }
                }
            } else {
                if($request->members_id){
                    if($request->members_id){
                        $data = $request->members_id;
                        $itemIds = array_column($data, 'item_id');
                        $members_id = implode(",", $itemIds);
                    }
                }
                if($request->is_cocuments){
                    foreach ($request->is_cocuments as $key => $val) {
                        $checkUpDocument = ProjectCheckList::where('id', $val['id'])->first();
                        if($val['state'] == 1){
                            $checkUpDocument->is_document = 'Document Required';
                        } else {
                            $checkUpDocument->is_document = 'No Document Required';
                        }
                        
                        $checkUpDocument->save();
                    }
                }
                
                
                // if ($request->pricesestimate) {
                //     if ($request->pricesestimate) {
                //         // $priceses = json_decode($request->pricesestimate, true);
                //         $priceses = $request->pricesestimate;
                //         foreach ($priceses as $key => $val) {
                //             $assigne = new UserCheckList;
                //             $assigne->task_id = $task->id;
                //             $assigne->user_id = $val['userids'];
                //             $assigne->checklist_id = $val['checklistid'];
                //             $assigne->user_hour = $val['targettimehours'] ? $val['targettimehours'] : 00;
                //             $assigne->user_minute = $val['targettimemins'] ? $val['targettimemins'] : 00;
                //             $assigne->toatal_money = $val['totalpricesuser'];
                //             $assigne->save();
                //         }
                //     } 
                //     // dd($priceses);
                // }
                
            }
            $assigne = new TaskAssigne;
            $assigne->task_id = $task->id;
            $assigne->project_id = $project_id;
            $assigne->members_id = $members_id;
            $assigne->team_id = NULL;
            $assigne->save();
            $checkUser = TaskComment::where('user_id', $userId)->whereNull('task_id')->get();
            if($checkUser){
                foreach ($checkUser as $key => $val) {
                    TaskComment::where('id', $val->id)->update([
                        'task_id' => $task->id,
                        'temporary_id' => null,
                    ]);
                }
            }
            
            if (isset($request->is_sender)) {
                if($request->members_id){
                    $data = json_decode($request->members_id, true);
                    $itemIds = array_column($data, 'item_id');
                    $this->setupNotification($projectData->company_id, $task->id, $userId, $itemIds, $request->description);
                }
            } else {
                if($request->members_id){
                    $data = $request->members_id;
                    $itemIds = array_column($data, 'item_id');
                    $this->setupNotification($projectData->company_id, $task->id, $userId, $itemIds, $request->description);
                }
            }
            
            $myUserData = User::where('id', $userId)->first();
            if($myUserData){
                // $taskData = Task::where('id', $task->id)->first();
                // if($taskData->priority == 0){
                //     $priority = 'Low';
                // } else if($taskData->priority == 1) {
                //     $priority = 'High';
                // } else {
                //     $priority = 'Medium';
                // }
                // $taskAssigneData = TaskAssigne::where('task_id', $task->id)->first();
                // $names = '';
                // if($taskAssigneData){
                //     $members_id = explode(",", $taskAssigneData->members_id);
                    
                //     if($members_id){
                //         $assignUserData = User::whereIn('id',$members_id)->get();
                //         $names = $assignUserData->pluck('name')->sort()->implode(', ');
                //     }
                // }
                // if($taskAssigneData){
                //     $members_id = explode(",", $taskAssigneData->members_id);
                //     if($members_id){
                //         $assignUserData = User::whereIn('id',$members_id)->get();
                //         $emailAddresses = $assignUserData->pluck('email')->toArray();
                //         $assignInfo = [
                //             'taskId' => $task->id,
                //             'email' => $emailAddresses,
                //             'created' => $myUserData->name, 
                //             'taskName' => $taskData->description, 
                //             'dueDate' => date('d M Y',strtotime($taskData->due_date)), 
                //             'assignName' => $names, 
                //             'taskPriority' => $priority, 
                //             'cratedDate' => date('d M Y',strtotime($taskData->created_at)),
                //             'ownerEmail' => $myUserData->email, 
                //         ];
                //         Mail::send('mail.new_task_assign', ['info' => $assignInfo], function ($message) use ($assignInfo) {
                //             $message->to($assignInfo['email'])->subject('New Task Assign in Task Note');
                //             $message->cc($assignInfo['ownerEmail']);
                //         });
                //     }
                // }
                
                // $taskData = Task::where('id', $task->id)->first();
                // if($taskData->follow_id){
                //     $followUserData = User::where('id',$taskData->follow_id)->first();
                //     if($followUserData){
                //         $mainInfo = [
                //             'taskId' => $task->id,
                //             'email' => $followUserData->email,
                //             'created' => $myUserData->name, 
                //             'taskName' => $taskData->description, 
                //             'dueDate' => date('d M Y',strtotime($taskData->due_date)), 
                //             'assignName' => $names, 
                //             'taskPriority' => $priority, 
                //             'cratedDate' => date('d M Y',strtotime($taskData->created_at)),
                //             'ownerEmail' => $myUserData->email, 
                //         ];
                //         Mail::send('mail.new_task_assign', ['info' => $mainInfo], function ($message) use ($mainInfo) {
                //             $message->to($mainInfo['email'])->subject('New Task by following');
                //         });
                //     }
                // }
            }
            
            return response()->json([
                'status' => true,
                'task_id' => $task->id,
                'message' => 'Task add successfully',
            ]);
        }
        
        function setupNotification($companyId, $lastId, $formId, $toId, $name){
            $userName = '';
            $user = User::where('id', $formId)->first();
            if($user){
                $userName = $user->name;
            }
            $cratedData = [
                'company_id' => $companyId,
                'project_id' => NULL,
                'task_id' => $lastId, 
                'note_id' => NULL, 
                'team_id' => NULL, 
                'form_id' => $formId, 
                'to_id' => NULL, 
                'massage' => 'Task Created By ' . $userName . ' Named ' . $name, 
            ];
            Notification::create($cratedData);

            foreach ($toId as $key => $val) {
                $project = [
                    'company_id' => $companyId,
                    'project_id' => NULL,
                    'task_id' => $lastId, 
                    'note_id' => NULL, 
                    'team_id' => NULL, 
                    'form_id' => $formId, 
                    'to_id' => $val, 
                    'massage' => 'You Have Been Assign New Task By ' . $userName . ' For Task '  . $name, 
                ];
                $project =  Notification::create($project);
                
                $toUser = User::where('id', $val)->first();
                if($toUser){
                    if($toUser->fcm_token){
                        FCMService::send(
                            $toUser->fcm_token,
                            [
                                'title' => 'New task add',
                                'body' => 'You Have Been Assign New Task By ' . $userName . ' For Task '  . $name,
                                'data' => [
                                    'type' => 'new_task',
                                ]
                            ]
                        );
                    }
                }
                
                $webNotificationPayloadForMember = [
                    'title' => 'New task add',
                    'body' => 'You Have Been Assign New Task By ' . $userName . ' For Task'  . $name,
                    'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
                    'url' => 'https://app.tasknote.in/',
                    'status' => true,
                    'userId' => $val,
                    'message' => 'task add successfully',
                ];
                broadcast(new PushNotification($webNotificationPayloadForMember))->toOthers();
            }

            return true;
        }
        
        public function taskEdit(Request $request){
            $userId = Auth::user()->id;
            // $request->validate([
            //     'taskID' => 'required',
            //     'title' => 'required',
            //     'description' => 'required',
            //     'start' => 'required',
            //     'due_date' => 'required',
            //     'project_id' => 'required',
            //     'service_id' => 'required',
            //     'status' => 'required',
            //     'priority' => 'required',
            // ]);
            $taskID = $request->taskID;
            $taskData = Task::where('id', $taskID)->first();
            // dd($request->all());
            if($taskData){
                if($request->start){
                    $startfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->start));
                    $startDateTime = new DateTime($startfromDate);
                    $startDate = $startDateTime->format("Y-m-d");
                }
                if($request->targettimehour || $request->targettimemin){
                    $joingTime = $request->targettimehour . ':' . $request->targettimemin;
                    $parts = explode(":", $joingTime);
                    $hours = str_pad($parts[0], 2, "0", STR_PAD_LEFT);
                    $minutes = str_pad($parts[1], 2, "0", STR_PAD_LEFT);
                    
                    $target_time = $hours . ':' . $minutes . ':00';
                } else {
                    $target_time = '';
                }
                if($request->due_date){
                    $duefromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->due_date));
                    $dueDateTime = new DateTime($duefromDate);
                    $dueDate = $dueDateTime->format("Y-m-d");
                }
                if (isset($request->is_sender)) {
                    if($request->project_id){
                        $data = json_decode($request->project_id, true);
                        $itemIds = array_column($data, 'item_id');
                        $project_id = implode(",", $itemIds);
                    }
                    if($request->client_id){
                        $client = json_decode($request->client_id, true);
                        $clientIds = array_column($client, 'item_id');
                        $client_id = implode(",", $clientIds);
                    }
                    if($request->responsible_person){
                        $person = json_decode($request->responsible_person, true);
                        $personIds = array_column($person, 'item_id');
                        $responsible_person = implode(",", $personIds);
                    }
                    if($request->tasktype){
                        $persons = json_decode($request->tasktype, true);
                        $personIdsa = array_column($persons, 'item_id');
                        $tasktypeId = implode(",", $personIdsa);
                    }
                } else {
                    if($request->project_id){
                        $data = $request->project_id;
                        $itemIds = array_column($data, 'item_id');
                        $project_id = implode(",", $itemIds);
                    }
                    if($request->client_id){
                        $client = $request->client_id;
                        $clientIds = array_column($client, 'item_id');
                        $client_id = implode(",", $clientIds);
                    }
                    if($request->responsible_person){
                        $person = $request->responsible_person;
                        $personIds = array_column($person, 'item_id');
                        $responsible_person = implode(",", $personIds);
                    }
                    $tasktypeId = '';
                    if($request->tasktype){
                        $persons = $request->tasktype;
                        $personIdsa = array_column($persons, 'item_id');
                        $tasktypeId = implode(",", $personIdsa);
                    }
                }
                $periodicDate = null;
                if($request->periodic_date){
                    $periodicfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->periodic_date));
                    $periodicDateTime = new DateTime($periodicfromDate);
                    $periodicDate = $periodicDateTime->format("Y-m-d");
                }
                $projectData = Project::where('id', $project_id)->first();
                if(isset($request->files) && !empty($request->files)){
                    $taskData->description = $request->description;
                    $taskData->start_date = $startDate;
                    $taskData->due_date = $dueDate;
                    $taskData->project_id = $project_id;
                    $taskData->client_id = $client_id;
                    if($request->status != ''){
                    $taskData->status = $request->status;    
                    }
                    
                    $taskData->priority = $request->priority;
                    $taskData->is_recurring = $request->is_recurring;
                    $taskData->recurring_time = $request->recurring_time;
                    $taskData->remainingtotalCost = $request->remainingtotalCost;
                    $taskData->target_time = $target_time;
                    $taskData->periodic_date = $periodicDate;
                    $taskData->responsible_person = $responsible_person;
                    $taskData->type_id = $tasktypeId;
                    $taskData->save();
                    if ($request->hasFile('files')) {
                        $sheets = $request->file('files');
                        $upload_files = [];
                        foreach ($sheets as $sheet) {
                            $filename = $sheet->getClientOriginalName();
                            $extension = $sheet->getClientOriginalExtension();
                            $filename = mt_rand(10000000000,99999999999) . '.' . $extension;
                            $path = public_path('images/all');
                            if ($sheet->move($path, $filename)) {
                                $image = new Attestment;
                                $image->file = $filename;
                                $image->type_id = $taskData->id;
                                $image->type = 'tasks';
                                $image->save();
                            }
                        }
                    }
                } else {
                    $taskData->description = $request->description;
                    $taskData->type_id = $request->type_id;
                    $taskData->start_date = $startDate;
                    $taskData->due_date = $dueDate;
                    $taskData->project_id = $project_id;
                    $taskData->client_id = $client_id;
                    if($request->status != ''){
                    $taskData->status = $request->status;    
                    }
                    $taskData->priority = $request->priority;
                    $taskData->is_recurring = $request->is_recurring;
                    $taskData->recurring_time = $request->recurring_time;
                    $taskData->remainingtotalCost = $request->remainingtotalCost;
                    $taskData->target_time = $target_time;
                    $taskData->periodic_date = $periodicDate;
                    $taskData->responsible_person = $responsible_person;
                     $taskData->type_id = $tasktypeId;
                    $taskData->save();
                }
                $members_id = '';
                $team_id = '';
                if (isset($request->is_sender)) {
                    if($request->members_id){
                        if($request->members_id){
                            $data = json_decode($request->members_id, true);
                            $itemIds = array_column($data, 'item_id');
                            $members_id = implode(",", $itemIds);
                            
                            $userName = '';
                            $user = User::where('id', $taskData->user_id)->first();
                            if($user){
                                $userName = $user->name;
                            }
                            foreach ($itemIds as $key => $val) {
                                $toUser = User::where('id', $val)->first();
                                if($toUser){
                                    if($toUser->fcm_token){
                                        FCMService::send(
                                            $toUser->fcm_token,
                                            [
                                                'title' => 'Update Task',
                                                'body' => 'Task Has Been Updated By ' . $userName . ' For '  . $taskData->title,
                                                'data' => [
                                                    'type' => 'update_task',
                                                ]
                                            ]
                                        );
                                    }
                                }
                                
                                $webNotificationPayloadForMember = [
                                    'title' => 'Update Task',
                                    'body' => 'Task Has Been Updated By ' . $userName . ' For '  . $taskData->title,
                                    'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
                                    'url' => 'https://app.tasknote.in/',
                                    'status' => true,
                                    'userId' => $val,
                                    'message' => 'task update successfully',
                                ];
                                broadcast(new PushNotification($webNotificationPayloadForMember))->toOthers();
                            }
                        }
                    } else if($request->team_id){
                        if($request->team_id){
                            $data = json_decode($request->team_id, true);
                            $itemIds = array_column($data, 'item_id');
                            $team_id = implode(",", $itemIds);
                        }
                    }
                    if($request->is_cocuments){
                        $is_cocuments = json_decode($request->is_cocuments, true);
                        foreach ($is_cocuments as $key => $val) {
                            $checkUpDocument = ProjectCheckList::where('id', $val['id'])->first();
                            if($val['state'] == 1){
                                $checkUpDocument->is_document = 'Document Required';
                            } else {
                                $checkUpDocument->is_document = 'No Document Required';
                            }
                            
                            $checkUpDocument->save();
                        }
                    }
                } else {
                    if($request->members_id){
                        if($request->members_id){
                            $data = $request->members_id;
                            $itemIds = array_column($data, 'item_id');
                            $members_id = implode(",", $itemIds);
                            
                            
                            $userName = '';
                            $user = User::where('id', $taskData->user_id)->first();
                            if($user){
                                $userName = $user->name;
                            }
                            foreach ($itemIds as $key => $val) {
                                $toUser = User::where('id', $val)->first();
                                if($toUser){
                                    if($toUser->fcm_token){
                                        FCMService::send(
                                            $toUser->fcm_token,
                                            [
                                                'title' => 'Update Task',
                                                'body' => 'Task Has Been Updated By ' . $userName . ' For '  . $taskData->title,
                                                'data' => [
                                                    'type' => 'update_task',
                                                ]
                                            ]
                                        );
                                    }
                                }
                                
                                $webNotificationPayloadForMember = [
                                    'title' => 'Update Task',
                                    'body' => 'Task Has Been Updated By ' . $userName . ' For '  . $taskData->title,
                                    'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
                                    'url' => 'https://app.tasknote.in/',
                                    'status' => true,
                                    'userId' => $val,
                                    'message' => 'task update successfully',
                                ];
                                broadcast(new PushNotification($webNotificationPayloadForMember))->toOthers();
                            }
                        }
                    }
                    if($request->is_cocuments){
                        foreach ($request->is_cocuments as $key => $val) {
                            $checkUpDocument = ProjectCheckList::where('id', $val['id'])->first();
                            if($val['state'] == 1){
                                $checkUpDocument->is_document = 'Document Required';
                            } else {
                                $checkUpDocument->is_document = 'No Document Required';
                            }
                            
                            $checkUpDocument->save();
                        }
                    }
                    // if ($request->pricesestimate) {
                    //     if ($request->pricesestimate) {
                    //         // $priceses = json_decode($request->pricesestimate, true);
                    //         $priceses = $request->pricesestimate;
                    //         UserCheckList::where('task_id', $taskData->id)->delete();
                    //         foreach ($priceses as $key => $val) {
                    //             $assigne = new UserCheckList;
                    //             $assigne->task_id = $taskData->id;
                    //             $assigne->user_id = $val['userids'];
                    //             $assigne->checklist_id = $val['checklistid'];
                    //             $assigne->user_hour = $val['targettimehours'] ? $val['targettimehours'] : 00;
                    //             $assigne->user_minute = $val['targettimemins'] ? $val['targettimemins'] : 00;
                    //             $assigne->toatal_money = $val['totalpricesuser'];
                    //             $assigne->save();
                    //         }
                    //     } 
                    // }
                }
                TaskAssigne::where('task_id', $taskData->id)->delete();
                $assigne = new TaskAssigne;
                $assigne->task_id = $taskData->id;
                $assigne->project_id = $project_id;
                $assigne->members_id = $members_id;
                $assigne->team_id = $team_id;
                $assigne->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Task edit successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                ]);
            }
        }
        
        public function actualTimeUpdate(Request $request){
            $request->validate([
                'stoptime' => 'required',
                'taskids' => 'required',
            ]);
            $task = Task::find($request->taskids);
            if($task){
                $task->actual_time = $request->stoptime;
                $task->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Status updated successfully',
                ]);
            }
            else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status updated unsuccessfully',
                ]);
            }
        }

        public function taskStatusUpdate(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'taskID' => 'required',
                'status' => 'required',
            ]);
            $task = Task::find($request->taskID);
            if($task){
                $task->status = $request->status;
                $task->status_date = date('Y-m-d H:i:s');
                $task->save();
                $user = User::where('id', $userId)->first();
                if($user){
                    $userName = $user->name;
                }
                $status = CompanyStatus::where('id', $request->status)->first();
                if($status){
                    $statusName = $status->status;
                }
                
                $cratedData = [
                    'company_id' => $task->company_id,
                    'project_id' => NULL,
                    'task_id' => $task->id, 
                    'note_id' => NULL, 
                    'team_id' => NULL, 
                    'form_id' => $task->user_id, 
                    'to_id' => NULL, 
                    'massage' => $userName . ' change status to ' . $statusName . ' for task ' . $task->title, 
                ];
                
                $webNotificationPayload = [
                    'title' => 'Task status update',
                    'body' => $userName . ' changed status to ' . $statusName . ' for task ' . $task->title,
                    'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
                    'url' => 'https://app.tasknote.in/',
                    'status' => true,
                    'userId' => $task->user_id,
                    'message' => 'Notification list successfully',
                ];
                $taskOwaner = User::where('id', $task->user_id)->first();
                if($taskOwaner){
                    FCMService::send(
                        $taskOwaner->fcm_token,
                        [
                            'title' => 'Task status update',
                            'body' => $userName . ' changed status to ' . $statusName . ' for task ' . $task->title,
                            'data' => [
                                'type' => 'status_update',
                            ]
                        ]
                    );
                }
                if($task->follow_id){
                    $webNotificationPayloadFollow = [
                        'title' => 'Task status update',
                        'body' => $userName . ' changed status to ' . $statusName . ' for task ' . $task->title,
                        'icon' => 'https://app.tasknote.in/assets/tasknote_Favicon.svg',
                        'url' => 'https://app.tasknote.in/',
                        'status' => true,
                        'userId' => $task->follow_id,
                        'message' => 'Notification list successfully',
                    ];
                    broadcast(new PushNotification($webNotificationPayloadFollow))->toOthers();
                    
                    $taskFofllow = User::where('id', $task->follow_id)->first();
                    if($taskFofllow){
                        FCMService::send(
                            $taskFofllow->fcm_token,
                            [
                                'title' => 'Task status update',
                                'body' => $userName . ' changed status to ' . $statusName . ' for task ' . $task->title,
                                'data' => [
                                    'type' => 'status_update',
                                ]
                            ]
                        );
                    }
                }
                // $this->webPushNotification($webNotificationPayload);
                Notification::create($cratedData);
                broadcast(new PushNotification($webNotificationPayload))->toOthers();
                return response()->json([
                    'status' => true,
                    'message' => 'Status updated successfully',
                    'data' => $webNotificationPayload
                ]);
                // 
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status updated unsuccessfully',
                ]);
            }
        }

        // public function webPushNotification($webNotificationPayload)
        // {
        //     Log::info('Web push notification payload:', $webNotificationPayload);
        //     return true;
        // }

        public function taskPriorityUpdate(Request $request){
            // dd($request->all());
            $request->validate([
                'taskID' => 'required',
                'priority' => 'required',
            ]);
            $task = Task::find($request->taskID);
            if($task){
                $task->priority = $request->priority;
                $task->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Priority updated successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Priority updated unsuccessfully',
                ]);
            }
        }

        public function taskPinUpdate(Request $request){
            // dd($request->all());
            $request->validate([
                'taskID' => 'required',
                'pin' => 'required',
            ]);
            $task = Task::find($request->taskID);
            if($task){
                $task->pin = $request->pin;
                $task->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Pin updated successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Pin updated unsuccessfully',
                ]);
            }
        }
        
        public function taskCompleted(Request $request){
            $userId = Auth::user()->id;
            $userData = User::where('id',$userId)->first();
            $request->validate([
                'taskID' => 'required',
                'completed' => 'required',
            ]);
            $task = Task::where('id',$request->taskID)->first();
            if($task){
                $task->completed = $request->completed;
                $task->completed_date = date('Y-m-d');
                $task->save();
                $taskAssigneData = TaskAssigne::where('task_id', $task->id)->first();
                if($taskAssigneData){
                    $members_id = explode(",", $taskAssigneData->members_id);
                    if($members_id){
                        $assignUserData = User::whereIn('id',$members_id)->get();
                        $createdData = User::where('id',$task->user_id)->first();
                        foreach ($assignUserData as $key => $val) {
                            $assignInfo = [
                                'taskId' => $task->id,
                                'mainName' => $val->name,
                                'taskName' => $task->description,
                                'email' => $val->email,
                                'completedBy' => $userData->name, 
                                'dueDate' => date('d M Y',strtotime($task->due_date)), 
                                'createdBy' => $createdData->name, 
                                'cratedDate' => date('d M Y',strtotime($task->created_at)),
                            ];
                            // $data['thankyou'] = 'Thank you ' . $info['created'] . ' for New Task.';
                            Mail::send('mail.task_completed', ['info' => $assignInfo], function ($message) use ($assignInfo) {
                                $message->to($assignInfo['email'])->subject('Task Completion Notification: '. $assignInfo['taskName']);
                            });
                        }
                    }
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Task completed successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                ]);
            }
        }
        
        public function subTaskCompleted(Request $request){
            // dd($request->all());
            $request->validate([
                'subTaskID' => 'required',
                'completed' => 'required',
            ]);
            $task = SubTask::where('id',$request->subTaskID)->first();
            if($task){
                $task->completed = $request->completed;
                $task->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Sub task completed successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                ]);
            }
        }
        
        public function checkListCompleted(Request $request){
            // dd($request->all());
            $request->validate([
                'checkListID' => 'required',
                'completed' => 'required',
            ]);
            $task = CheckList::where('id',$request->checkListID)->where('completed',0)->first();
            if($task){
                $task->completed = $request->completed;
                $task->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Check list completed successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Check list already completed unsuccessfully',
                ]);
            }
        }
        
        public function taskComment(Request $request){
            $request->validate([
                'is_comment' => 'required',
                'comment' => 'required',
            ]);
            $userId = Auth::user()->id;
            $isComment = $request->is_comment;
            // $isComment = 'text';
            if($isComment == 'text'){
                if (isset($request->task_id)) {
                    $taskId = $request->task_id;
                    $comment = [
                        'task_id' => $taskId, 
                        'user_id' => $userId, 
                        'comment' => $request->comment, 
                        'is_comment' => $isComment, 
                    ];
                    $comment = TaskComment::create($comment);
                    $toId = UserCheckList::where('task_id', $taskId)->select('user_id')->get();
                    foreach ($toId as $key => $val) {
                        if($val->user_id != $userId){
                    $webNotificationPayloadForMember = [
                        'title' => 'New Comment add',
                        'body' => $request->comment,
                        'icon' => 'https://msasa.tasknote.in/assets/tasknote_Favicon.svg',
                        'url' => 'https://msasa.tasknote.in/tasks',
                        'status' => true,
                        'userId' => $val->user_id,
                        'message' => 'comment add successfully',
                    ];
                    broadcast(new PushNotification($webNotificationPayloadForMember))->toOthers();
                        }
                 }
                    
                } else {
                    $checkUser = TaskComment::where('user_id', $userId)->whereNull('task_id')->first();
                    if($checkUser){
                        $temporary = $checkUser->temporary_id;
                        $comment = [
                            'temporary_id' => $temporary, 
                            'user_id' => $userId, 
                            'comment' => $request->comment,
                            'is_comment' => $isComment,  
                        ];
                        $comment = TaskComment::create($comment);
                    } else {
                        $temporary = mt_rand(10000000, 99999999);
                        $comment = [
                            'temporary_id' => $temporary, 
                            'user_id' => $userId, 
                            'comment' => $request->comment,
                            'is_comment' => $isComment,  
                        ];
                        $comment = TaskComment::create($comment);
                    }
                    
                }
            } else if($isComment == 'file'){
                if (isset($request->task_id)) {
                    $taskId = $request->task_id;
                    if($request->comment){
                        $extension = $request->extension;
                        $image = explode('base64,',$request->comment);
                        $image = end($image);
                        $image = str_replace(' ', '+', $image);
                        $imageName = $request->filename . '_' .  mt_rand(1000, 9999) . '.' . $extension;
                        // $file = "/images/taskComment/" . $imageName;
                        $file = public_path('images/taskComment/') . $imageName;
                        $set = file_put_contents($file,base64_decode($image));
                        $comment = [
                            'task_id' => $taskId, 
                            'user_id' => $userId,
                            'is_comment' => $isComment,  
                            'comment' => $imageName,
                        ];
                        $comment = TaskComment::create($comment);
                    }
                } else {
                    $checkUser = TaskComment::where('user_id', $userId)->whereNull('task_id')->first();
                    if($checkUser){
                        $temporary = $checkUser->temporary_id;
                        if($request->comment){
                            $extension = $request->extension;
                            $image = explode('base64,',$request->comment);
                            $image = end($image);
                            $image = str_replace(' ', '+', $image);
                            $imageName = $request->filename . '_' .  mt_rand(1000, 9999) . '.' . $extension;
                            // $file = "/images/taskComment/" . $imageName;
                            $file = public_path('images/taskComment/') . $imageName;
                            $set = file_put_contents($file,base64_decode($image));
                            $comment = [
                                'temporary_id' => $temporary, 
                                'user_id' => $userId,
                                'is_comment' => $isComment,  
                                'comment' => $imageName,
                            ];
                            $comment = TaskComment::create($comment);
                        }
                    } else {
                        $temporary = mt_rand(10000000, 99999999);
                        if($request->comment){
                            $extension = $request->extension;
                            $image = explode('base64,',$request->comment);
                            $image = end($image);
                            $image = str_replace(' ', '+', $image);
                            $imageName = $request->filename . '_' .  mt_rand(1000, 9999) . '.' . $extension;
                            // $file = "/images/taskComment/" . $imageName;
                            $file = public_path('images/taskComment/') . $imageName;
                            $set = file_put_contents($file,base64_decode($image));
                            $comment = [
                                'temporary_id' => $temporary, 
                                'user_id' => $userId,
                                'is_comment' => $isComment,  
                                'comment' => $imageName,
                            ];
                            $comment = TaskComment::create($comment);
                        }
                    }
                    
                }
            } else if($isComment == 'audio'){
                if (isset($request->task_id)) {
                    $taskId = $request->task_id;
                    $binaryFile = $request->file('comment');
                    if ($binaryFile->getSize() > 0) {
                        $binaryData = file_get_contents($binaryFile->getRealPath());
                        $fileNameWithoutExtension = pathinfo($binaryFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $binaryFile->getClientOriginalExtension();
                        $path = public_path('images/taskComment/') . $newFileName;
                        file_put_contents($path, $binaryData);
                    }
                    $comment = [
                        'task_id' => $taskId, 
                        'user_id' => $userId, 
                        'comment' => $newFileName, 
                        'is_comment' => $isComment, 
                    ];
                    $comment = TaskComment::create($comment);
                } else {
                    $checkUser = TaskComment::where('user_id', $userId)->whereNull('task_id')->first();
                    if($checkUser){
                        $temporary = $checkUser->temporary_id;
                        $binaryFile = $request->file('comment');
                        if ($binaryFile->getSize() > 0) {
                            $binaryData = file_get_contents($binaryFile->getRealPath());
                            $fileNameWithoutExtension = pathinfo($binaryFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $binaryFile->getClientOriginalExtension();
                            $path = public_path('images/taskComment/') . $newFileName;
                            file_put_contents($path, $binaryData);
                        }
                        $comment = [
                            'temporary_id' => $temporary, 
                            'user_id' => $userId, 
                            'comment' => $newFileName,
                            'is_comment' => $isComment,  
                        ];
                        $comment = TaskComment::create($comment);
                    } else {
                        $temporary = mt_rand(10000000, 99999999);
                        $binaryFile = $request->file('comment');
                        if ($binaryFile->getSize() > 0) {
                            $binaryData = file_get_contents($binaryFile->getRealPath());
                            $fileNameWithoutExtension = pathinfo($binaryFile->getClientOriginalName(), PATHINFO_FILENAME);
                            $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $binaryFile->getClientOriginalExtension();
                            $path = public_path('images/taskComment/') . $newFileName;
                            file_put_contents($path, $binaryData);
                        }
                        $comment = [
                            'temporary_id' => $temporary, 
                            'user_id' => $userId, 
                            'comment' => $newFileName,
                            'is_comment' => $isComment,  
                        ];
                        $comment = TaskComment::create($comment);
                    }
                }
            } 
            if (isset($request->task_id)) {
                $checkUser = TaskComment::where('task_id', $request->task_id)->get();
                // $checkUser = TaskComment::where('user_id', $userId)->where('task_id', $request->task_id)->get();
            } else {
                $checkUser = TaskComment::where('user_id', $userId)->whereNull('task_id')->get();
            }
            $commentDataByDate = [];
            $filenames = '';
            foreach ($checkUser as $key => $val) {
                $userName = User::where('id', $val->user_id)->first();
                if($val->is_comment == 'text'){
                    $comment = $val->comment;
                } else if($val->is_comment == 'file') {
                    $comment = asset('public/images/taskComment/'. $val->comment);
                    $filenames = $val->comment;
                } else {
                    $comment = asset('public/images/taskComment/'. $val->comment);
                }
                $created_at = $val->created_at;
                $comment_date = date("Y-m-d", strtotime($created_at));
                if (!isset($commentDataByDate[$comment_date])) {
                    $commentDataByDate[$comment_date] = [
                        'date' => $comment_date,
                        'comments' => [],
                    ];
                }
                                
                $comment_data = [
                    'user_id' => $val->user_id,
                    'userName' => $userName->name,
                    'comment' => $comment,
                    'is_comment' => $val->is_comment,
                    'time' => date("H:i:s", strtotime($created_at)),
                    'date' => date("Y-m-d", strtotime($created_at))
                ];
                
                    if (isset($filenames)) {
                        $comment_data['filename'] = $filenames;
                    } else {
                        $comment_data['filename'] = ''; 
                    }
                
                $commentDataByDate[$comment_date]['comments'][] = $comment_data;
            }
            if($commentDataByDate){
                return response()->json([
                    'status' => true,
                    'message' => 'Task comment add successfully',
                    'data' => $commentDataByDate
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task comment add unsuccessfully',
                ]);
            }
        }
        
        public function taskCommentDelete(){
            $userId = Auth::user()->id;
            $data = TaskComment::where('user_id', $userId)->whereNull('task_id')->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Temporary Task Deleted Successfully',
                ]);
        }
        
        public function taskCommentList(Request $request){
            $userId = Auth::user()->id;
            $taskId = $request->task_id;
            $tskCommentList = TaskComment::where('task_id', $taskId)->get();
            $tskData = Task::where('id', $taskId)->first();
            if($tskCommentList){
                $commentData = [];
                $commentDataByDate = [];
                foreach ($tskCommentList as $key => $val) {
                    $userName = User::where('id', $val->user_id)->first();
                    if($val->is_comment == 'text'){
                        $comment = $val->comment;
                    } else if($val->is_comment == 'file') {
                        $comment = asset('public/images/taskComment/'. $val->comment);
                        $filenames = $val->comment;
                    } else {
                        $comment = asset('public/images/taskComment/'. $val->comment);
                    }
                    $created_at = $val->created_at;
                    $comment_date = date("Y-m-d", strtotime($created_at));
                    if (!isset($commentDataByDate[$comment_date])) {
                        $commentDataByDate[$comment_date] = [
                            'date' => $comment_date,
                            'comments' => [],
                        ];
                    }
                    $comment_data = [
                        'user_id' => $val->user_id,
                        'userName' => $userName->name,
                        'comment' => $comment,
                        'is_comment' => $val->is_comment,
                        'time' => date("H:i:s",strtotime($created_at)),
                        'date' => date("Y-m-d",strtotime($created_at))
                    ];
                    
                    
                    if (isset($filenames)) {
                        $comment_data['filename'] = $filenames;
                    } else {
                        $comment_data['filename'] = ''; 
                    }
                
                $commentDataByDate[$comment_date]['comments'][] = $comment_data;
                }
                if($commentDataByDate){
                    return response()->json([
                        'status' => true,
                        'message' => 'Task comment list successfully',
                        'taskTitle' => $tskData->title,
                        'data' => $commentDataByDate
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task comment data not found',
                        'taskTitle' => $tskData->title,
                    ]);
                }

            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task comment data not found',
                    'taskTitle' => $tskData->title,
                ]);
            }
        }

        public function fileUpload(Request $request){
            // dd($request->all());
            $request->validate([
                'files' => 'required',
                'task_id' => 'required',
            ]);
            // if($request->hasFile('files') && $request->file('files')->isValid()){
            //     $imageName = mt_rand(10000000000,99999999999).'.'.$request->files->extension();  
            //     $request->files->move(public_path('images/company'), $imageName);
            //     $image = new Attestment;
            //     $image->file = $imageName;
            //     $image->type_id = $request->task_id;
            //     $image->type = 'tasks';
            //     $image->save();
            // } 
            if ($request->hasFile('files') && $request->file('files')->isValid()) {
                $file = $request->file('files'); 
                $imageName = $file->name() . '_' . mt_rand(1000, 9999) . '.' . $file->extension();
                $file->move(public_path('images/all'), $imageName);
            
                $image = new Attestment;
                $image->file = $imageName;
                $image->type_id = $request->task_id;
                $image->type = 'tasks';
                $image->save();
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Please select a file',
                ]);
            }
            if($image){
                return response()->json([
                    'status' => true,
                    'message' => 'File uploaded successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data not found',
                ]);
            }
        }
        
        public function fileDelete(Request $request){
            $request->validate([
                'fileID' => 'required',
            ]);
            $file = Attestment::where('id', $request->fileID)->first();
            if($file){
                $file->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'File deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'File deleted unsuccessfully',
                ]);
            }
        }

        public function taskDetail(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'taskID' => 'required|string|max:255',
            ]);
            $task = Task::find($request->taskID);
            
            if($task){
                $add = 1;
                $edit = 1;
                $delete = 1;
                $userData = User::where('id',$userId)->first();
                if($userData){
                    $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',1)->first();
                    if($userModel){
                        $add = $userModel->add;
                        $edit = $userModel->edit;
                        $delete = $userModel->delete;
                    }
                }
                $projectData = Project::find($task->project_id);
                $clientData = ProjectClient::find($task->client_id);
                $serviceData = Service::find($task->service_id);
                $checkDate = date('Y-m-d',strtotime($task->due_date));
                $dueDate = date('m/d/Y',strtotime($task->due_date));
                $startDate = date('m/d/Y',strtotime($task->start_date));
                if($task->periodic_date){
                    $periodicDate = date('m/d/Y',strtotime($task->periodic_date));
                } else {
                    $periodicDate = null;
                }
                
                if($add == 1){
                    $subTask = SubTask::where('task_id', $task->id)->get();
                } else {
                    $subTask = SubTask::where('task_id', $task->id)->whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->get();
                }
                
                $tSubData = [];
                // if($subTask){
                //     foreach ($subTask as $key => $val) {
                //       $assigne_id = explode(",", $val->assigne_id);
                //         $team_id = explode(",", $val->team_id);
                //         $status = CompanyStatus::where('id', $val->status)->first();
                //         if($status){
                //             $sName = $status->status;
                //         } else {
                //             $sName = '';
                //         }
                //         if($val->target_time){
                //             $target_time = explode(":", $val->target_time);
                //         } else {
                //             $target_time[0] = 00;
                //             $target_time[1] = 00;
                //         }
                //         // dd($assigne_id);
                //         $subData = [
                //             'id' => $val->id,
                //             'title' => $val->title,
                //             'assigne_id' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$assigne_id)->get(),
                //             'team_id' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$team_id)->get(),
                //             'due_date' => $val->due_date,
                //             'status' => $sName,
                //             'priority' => $val->priority,
                //             'pin' => $val->pin,
                //             'completed' => $val->completed,
                //             'targettimehour' => $target_time[0],
                //             'targettimemin' => $target_time[1],
                //         ];
                //         $tSubData[] = $subData;
                //     }
                // }
                
                
                 
                $Attestment = Attestment::where('type_id', $task->id)->where('type', 'tasks')->get();
                $allAtt = [];
                if($Attestment){
                    foreach ($Attestment as $key => $val) {
                        if($val->file){
                            $files = asset('public/images/all/'. $val->file);
                            $extension = strtolower(pathinfo($files, PATHINFO_EXTENSION));
                            switch ($extension) {
                                case 'jpg':
                                case 'jpeg':
                                case 'png':
                                case 'gif':
                                case 'webp':
                                    $tag = 'image';
                                    break;
                                default:
                                    $tag = 'file';
                                    break;
                            }
                        } else {
                            $tag = '';
                            $files = 'File Not Fiund';
                        }
                        $m_data = [
                            'id' => $val->id,
                            'tag' => $tag,
                            'file' => $files,
                            'fileName' => $val->file,
                            'created_at' => date('M d, Y',strtotime($val->created_at)),
                        ];
                        $allAtt[] = $m_data;
                    }
                }
                $taskAssigne = TaskAssigne::where('task_id', $task->id)->first();
                if($taskAssigne){
                    $members_id = explode(",", $taskAssigne->members_id);
                    $team_id = $taskAssigne->team_id;
                } else {
                    $members_id = [];
                    $team_id = '';
                }
                if (isset($request->is_sender)) {
                    $sallData = $subTask;
                } else {
                    $sallData = $tSubData;
                }
                if($clientData){
                    $clientName = $clientData->name;
                } else {
                    $clientName = null;
                }
                $project_id = explode(",", $task->project_id);
                $client_id = explode(",", $task->client_id);
                $typeId = explode(",", $task->type_id);
                $responsible_person = explode(",", $task->responsible_person);
                if($task->target_time){
                    $target_time = explode(":", $task->target_time);
                } else {
                    $target_time[0] = 00;
                    $target_time[1] = 00;
                }
                $allCheck = [];
                $checkList = UserCheckList::where('task_id', $task->id)->get();
                foreach ($checkList as $key => $check) {
                    $checkUser = User::where('id', $check->user_id)->first();
                    $hour_per_cost = '';
                    $userName = '';
                    if( $checkUser){
                        $userName = $checkUser->name;
                        $hour_per_cost = $checkUser->hour_per_cost;
                    }
                    $checkListData = CheckList::where('id', $check->checklist_id)->first();
                    $remark = '';
                    if($checkListData){
                        $remark = $checkListData->remark;
                    }
                    $check_data = [
                            'id' => $check->id,
                            'user_id' => $check->user_id,
                            'checklist_id' => $check->checklist_id,
                            'user_hour' => $check->user_hour,
                            'user_minute' => $check->user_minute,
                            'toatal_money' => $check->toatal_money,
                            'hour_per_cost' => $hour_per_cost,
                            'remark' => $remark,
                            'userName' => $userName,
                        ];
                        $allCheck[] = $check_data;
                }
                
                $response = [
                    'id' => $task->id,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'project_id' => Project::select('id AS item_id', 'name AS item_text')->whereIn('id',$project_id)->get(),
                    'type_id' => TaskType::select('id AS item_id', 'title AS item_text')->whereIn('id',$typeId)->get(),
                    'client_id' => ProjectClient::select('id AS item_id', 'name AS item_text')->whereIn('id',$client_id)->get(),
                    'responsible_person' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$responsible_person)->get(),
                    'taskMembers' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$members_id)->get(),
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'remainingtotalCost' => intval($task->remainingtotalCost),
                    'pin' => $task->pin,
                    'checkList' => $allCheck,
                    'files' => $allAtt,
                    'periodic_date' => $periodicDate,
                    'targettimehour' => $target_time[0],
                    'targettimemin' => $target_time[1],
                ];
                
                
                return response()->json([
                    'status' => true,
                    'message' => 'Task data successfully',
                    'add' => $add,
                    'edit' => $edit,
                    'delete' => $delete,
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                ]);
            }
        }

        public function notesList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            $ownerCheck = Company::where('id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if($ownerCheck){
                $note = Note::where('company_id', $companyID)->get();
            } else {
                $note = Note::where('user_id', $userId)->where('company_id', $companyID)->get();
            }
            $noteData = [];
            foreach ($note as $key => $value) {
                $sahreNoteChcek = NoteShare::where('note_id', $value->id)->first();
                if($sahreNoteChcek){
                    $is_shared = '1';
                } else {
                    $is_shared = '0';
                }
                $pro_data = [
                    'id' => $value->id,
                    'title' => $value->title,
                    'description' => $value->description,
                    'pin' => $value->pin,
                    'color' => $value->color,
                    'is_shared' => $is_shared,
                ];
                $noteData[] = $pro_data;
            }
            $shareData = [];
            $sahrenote = NoteShare::where('user_id', $userId)->get();
             
            foreach ($sahrenote as $key => $value) {
                $sNote = Note::where('id', $value->note_id)->where('company_id', $companyID)->first();
                // dd($sNote);
                if($sNote){
                    $share_data = [
                        'id' => $sNote->id,
                        'title' => $sNote->title,
                        'description' => $sNote->description,
                        'pin' => $sNote->pin,
                        'color' => $sNote->color,
                        'edited' => $value->edited,
                        'deleted' => $value->deleted,
                    ];
                    $shareData[] = $share_data;
                }
            }
            $add = 1;
            $edit = 1;
            $delete = 1;
            $userData = User::where('id',$userId)->first();
            if($userData){
                $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',4)->first();
                if($userModel){
                    $add = $userModel->add;
                    $edit = $userModel->edit;
                    $delete = $userModel->delete;
                }
            }
            $response['createdNote'] = $noteData;
            $response['sharedNote'] = $shareData;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Notes list successfully',
                    'add' => $add,
                    'edit' => $edit,
                    'delete' => $delete,
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes data not found',
                    'data' => []
                ]);
            }
        }
        
        public function getNoteUserList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;    
            $member = User::select('users.*')
                ->Join("companies","companies.id","=","users.company_id")
                ->where('users.id', '!=' ,$userId)
                ->where('users.company_id', $companyID)->whereHas(
                    'roles', function($q){
                        $q->where('name', 'user');
                    }
                )->get();
            $memberData = [];
            foreach ($member as $key => $value) {
                 $m_data = [
                     'id' => $value->id,
                     'name' => $value->name,
                 ];
                 $memberData[] = $m_data;
            }
        
            if($memberData){
                return response()->json([
                    'status' => true,
                    'message' => 'Member list successfully',
                    'data' => $memberData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Member data not found',
                    'data' => []
                ]);
            }
        }
        
        public function getSharedNoteUserList(Request $request){
            $companyID = $request->companyID;
            $id = request('noteID'); 
            $userId = Auth::user()->id; 
            $member = User::select('users.*')
                ->Join("companies","companies.id","=","users.company_id")
                ->where('users.id', '!=' ,$userId)
                ->where('users.company_id', $companyID)->whereHas(
                    'roles', function($q){
                        $q->where('name', 'user');
                    }
                )->get();
            $memberData = [];
            foreach ($member as $key => $value) {
                $noteShare = NoteShare::where('user_id', $value->id)->where('note_id', $id)->first();
                $edited = 0;
                $deleted = 0;
                $is_share = 0;
                if($noteShare){
                    $is_share = 1;
                    $edited = $noteShare->edited;
                    $deleted = $noteShare->deleted;
                }
                 $m_data = [
                     'id' => $value->id,
                     'name' => $value->name,
                     'is_share' => $is_share,
                     'edited' => $edited,
                     'deleted' => $deleted,
                 ];
                 $memberData[] = $m_data;
            }

            if($memberData){
                return response()->json([
                    'status' => true,
                    'message' => 'Member list successfully',
                    'data' => $memberData,
                    'noteID'=>$id
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Member data not found',
                    'data' => []
                ]);
            }
        }

        public function noteGet(){
            $id = request('noteID');
           # dd($id);
            $note = Note::where('id', $id)->first();
            if($note){
                $task_id = explode(",", $note->task_id);  
                $response = [
                    'id' => $note->id,
                    'title' => $note->title,
                    'description' => $note->description,
                    'pin' => $note->pin,
                    'color' => $note->color,
                    'is_expiry' => $note->is_expiry,
                    'expiry_date' => date('d-m-Y',strtotime($note->expiry_date)),
                    'task_id' => Task::select('id AS item_id', 'description AS item_text')->whereIn('id',$task_id)->get(),
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Notes list successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Notes data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes data not found',
                    'data' => []
                ]);
            }
        }

        public function noteAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'title' => 'required',
                    'companyID' => 'required',
                    'description' => 'required',
                ]);
                if($request->color){
                    $color = $request->color;
                } else {
                    $color = '#ef6e05';
                }
                
                list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
                $a = '0.2';
                $rgbColor = 'rgba('.$r. ', ' .$g. ', ' .$b. ', ' . $a .')';
                
                $userData = User::where('id', $userId)->first();
                $is_expiry = 0;
                $expiryDate = NULL;
                if($request->is_expiry == true){
                    $is_expiry = 1;
                    if($request->expiry_date){
                        $expiryfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->expiry_date));
                        $expiryDateTime = new DateTime($expiryfromDate);
                        $expiryDate = $expiryDateTime->format("Y-m-d");
                    }
                }
                $task_id = 0;
                if (isset($request->is_sender)) {
                    if($request->task){
                        $data = json_decode($request->task, true);
                        $itemIds = array_column($data, 'item_id');
                        $task_id = implode(",", $itemIds);
                    }
                } else {
                    if($request->task){
                        $data = $request->task;
                        $itemIds = array_column($data, 'item_id');
                        $task_id = implode(",", $itemIds);
                    }
                }
                
                
                $note = [
                    'user_id' => $userId, 
                    'company_id' => $request->companyID, 
                    'title' => $request->title, 
                    'description' => $request->description, 
                    'color' => $rgbColor,
                    'is_expiry' => $is_expiry,
                    'expiry_date' => $expiryDate,
                    'task_id' => $task_id,
                ];
                // dd($request->all());
                $note = Note::create($note);
                if($note){
                    $userName = '';
                    $user = User::where('id', $userId)->first();
                    if($user){
                        $userName = $user->name;
                    }
                    $cratedData = [
                        'company_id' => $userData->company_id,
                        'project_id' => NULL,
                        'task_id' => NULL, 
                        'note_id' => $note->id, 
                        'team_id' => NULL, 
                        'form_id' => $userId, 
                        'to_id' => NULL, 
                        'massage' => 'Note Created By ' . $userName . ' Named ' . $request->title, 
                    ];
                    Notification::create($cratedData);
                    return response()->json([
                        'status' => true,
                        'message' => 'Notes added successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Notes added unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function noteEdit(Request $request){
            $request->validate([
                'noteID' => 'required',
                'title' => 'required',
                'description' => 'required',
            ]);
            // if($request->color){
            //     $color = $request->color;
            // } else {
            //     $color = '#ef6e05';
            // }
            
            // list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
            // $a = '0.2';
            // $rgbColor = 'rgba('.$r. ', ' .$g. ', ' .$b. ', ' . $a .')';
            
            $is_expiry = 0;
            $expiryDate = NULL;
            if($request->is_expiry == true){
                $is_expiry = 1;
                if($request->expiry_date){
                    $expiryfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->expiry_date));
                    $expiryDateTime = new DateTime($expiryfromDate);
                    $expiryDate = $expiryDateTime->format("Y-m-d");
                }
            }
            $task_id = 0;
            if (isset($request->is_sender)) {
                if($request->task){
                    $data = json_decode($request->task, true);
                    $itemIds = array_column($data, 'item_id');
                    $task_id = implode(",", $itemIds);
                }
            } else {
                if($request->task){
                    $data = $request->task;
                    $itemIds = array_column($data, 'item_id');
                    $task_id = implode(",", $itemIds);
                }
            }
            $note = Note::find($request->noteID);
            $note->title = $request->title;
            $note->description = $request->description;
            $note->is_expiry = $is_expiry;
            $note->expiry_date = $expiryDate;
            $note->task_id = $task_id;
            $note->save();
            $email = Auth::user()->email;
            
            $noteData = [
                'name' => Auth::user()->name,
                ];
             Mail::send('mail.noteUpdate', ['info' => $noteData], function ($message) use ($noteData, $email) {
                $message->to($email)->subject('Task Note');
            });

            if($note){
                return response()->json([
                    'status' => true,
                    'message' => 'Notes Edited successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes Edited unsuccessfully',
                ]);
            }
        }

        public function notePinUpdate(Request $request){
            $request->validate([
                'noteID' => 'required',
                'pin' => 'required',
            ]);
            
            $note = Note::find($request->noteID);
            $note->pin = $request->pin;
            $note->save();
            if($note){
                return response()->json([
                    'status' => true,
                    'message' => 'Notes pin updated successfully',
                    'pinStatus' => $request->pin
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes pin updated unsuccessfully',
                ]);
            }
        }
        
        public function noteColorUpdate(Request $request){
            $request->validate([
                'noteID' => 'required',
                'color' => 'required',
            ]);
            $color = $request->color;
            list($r, $g, $b) = sscanf($color, "#%02x%02x%02x");
            $a = '0.2';
            $rgbColor = 'rgba('.$r. ', ' .$g. ', ' .$b. ', ' . $a .')';
            
            $note = Note::find($request->noteID);
            $note->color = $rgbColor;
            $note->save();
            if($note){
                return response()->json([
                    'status' => true,
                    'message' => 'Notes color updated successfully',
                    'noteColor' => $request->color
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes color updated unsuccessfully',
                ]);
            }
        }
        
        public function noteShare(Request $request){
            $request->validate([
                'noteID' => 'required',
                'shareUser' => 'required',
            ]);

            if (isset($request->is_sender)) {
                $shareUserData = json_decode($request->shareUser, true);
            } else {
                $shareUserData = $request->shareUser;
            }
            $note = Note::find($request->noteID);
            $userName = '';
            $userData = User::find($note->user_id);
            if($userData){
                $userName = $userData->name;
            }
            // dd($shareUserData);
            NoteShare::where('note_id', $request->noteID)->delete();
            foreach ($shareUserData as $key => $value) {
                $share = new NoteShare;
                $share->note_id = $request->noteID;
                $share->user_id = $key;
                $share->save();
                foreach ($value as $key => $val) {
                    $setPer = NoteShare::find($share->id);
                    $setPer->edited = $val['edited'];
                    $setPer->deleted = $val['deleted'];
                    $setPer->save();
                }
                $Notification = [
                    'company_id' => $note->company_id,
                    'project_id' => NULL,
                    'task_id' => NULL, 
                    'note_id' => $note->id, 
                    'team_id' => NULL, 
                    'form_id' => $note->user_id, 
                    'to_id' => $key, 
                    'massage' => 'Notes has been shared to you by '  . $userName . ' on '  . $note->title, 
                ];
                $Notification =  Notification::create($Notification);
            }
            if($share){
                return response()->json([
                    'status' => true,
                    'message' => 'Notes share successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes share unsuccessfully',
                ]);
            }
        }

        public function noteDelete(Request $request){
            $request->validate([
                'noteID' => 'required',
            ]);
            $note = Note::where('id', $request->noteID)->first();
            if($note){
                $note->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Notes deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notes deleted unsuccessfully',
                ]);
            }
        }
        
        public function userByTaskList(Request $request){
            $request->validate([
                'userID' => 'required',
            ]);
            $userId = $request->userID;
            $teamData = [];
            $team = Team::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
            foreach ($team as $key => $value) {
                $ta_data = [
                    'teamId' => $value->id,
                ];
                $teamData[] = $ta_data;
            }
            $taskAssigneTeamData = [];
            $taskAssigneMemberData = [];
            if($teamData){
                foreach ($teamData as $key => $value) {
                    $teamId = $value['teamId'];
                    if($value['teamId']){
                        $taskAssigneTeam = TaskAssigne::whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
                        foreach ($taskAssigneTeam as $key => $value) {
                            $taskAssigneTeamData[] =  $value->task_id;
                        }
                    }
                }
            }
            $taskAssigneMember = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
            foreach ($taskAssigneMember as $key => $value) {
                $taskAssigneMemberData[] = $value->task_id;
            }
            $ta_data = [];
            $assignTask = array_merge($taskAssigneTeamData,$taskAssigneMemberData);
            $myTaskData = [];
            foreach ($assignTask as $key => $value) {
                $taskList = Task::where('id', $value);
                if($request->dueDateType == 'range'){
                    if ($request->dueDate != null) {
                        $rangeDate = (explode("/",$request->dueDate));
                        // dd($rangeDate);
                        $taskList->whereDate('tasks.due_date', '>=', date('Y-m-d', strtotime($rangeDate[0])));
                        $taskList->whereDate('tasks.due_date', '<=', date('Y-m-d', strtotime($rangeDate[1])));
                    }
                    if ($request->createdDate != null) {
                        $rangeDate = (explode("/",$request->createdDate));
                        $taskList->whereDate('tasks.created_at', '>=', date('Y-m-d', strtotime($rangeDate[0])));
                        $taskList->whereDate('tasks.created_at', '<=', date('Y-m-d', strtotime($rangeDate[1])));
                    }
                    if ($request->closedDate != null) {
                        $rangeDate = (explode("/",$request->closedDate));
                        $taskList->where('tasks.status', 2);
                        $taskList->whereDate('tasks.status_date', '>=', date('Y-m-d', strtotime($rangeDate[0])));
                        $taskList->whereDate('tasks.status_date', '<=', date('Y-m-d', strtotime($rangeDate[1])));
                    }
                } elseif($request->dueDateType == 'before') {
                    if ($request->dueDate != null) {
                        $taskList->whereDate('tasks.due_date', '<=', date('Y-m-d', strtotime($request->dueDate)));
                    }
                    if ($request->createdDate != null) {
                        $taskList->whereDate('tasks.created_at', '<=', date('Y-m-d', strtotime($request->dueDate)));
                    }
                    if ($request->closedDate != null) {
                        $taskList->where('tasks.status', 2);
                        $taskList->whereDate('tasks.status_date', '<=', date('Y-m-d', strtotime($request->dueDate)));
                    }
                } elseif($request->dueDateType == 'on') {
                    if ($request->dueDate != null) {
                        $taskList->whereDate('tasks.due_date', date('Y-m-d', strtotime($request->dueDate)));
                    }
                    if ($request->createdDate != null) {
                        $taskList->whereDate('tasks.created_at', date('Y-m-d', strtotime($request->dueDate)));
                    }
                    if ($request->closedDate != null) {
                        $taskList->where('tasks.status', 2);
                        $taskList->whereDate('tasks.status_date', date('Y-m-d', strtotime($request->dueDate)));
                    }
                } else {
                    if ($request->dueDate != null) {
                        $taskList->whereDate('tasks.due_date', '>=', date('Y-m-d', strtotime($request->dueDate)));
                    }
                    if ($request->createdDate != null) {
                        $taskList->whereDate('tasks.created_at', '>=', date('Y-m-d', strtotime($request->dueDate)));
                    }
                    if ($request->closedDate != null) {
                        $taskList->where('tasks.status', 2);
                        $taskList->whereDate('tasks.status_date', '>=', date('Y-m-d', strtotime($request->dueDate)));
                    }
                }
                $taskList = $taskList->get();
                $ta_data = [
                    'allTask' => $taskList,
                ];
                $myTaskData[] = $ta_data;
            }
            $upcomingTaskData = [];
            $overDueTaskData = [];
            $todayTaskData = [];
            $pinTaskData = [];
            $taskData = [];
            foreach ($myTaskData as $item) {
                $tasksCollection = $item['allTask'];
                foreach ($tasksCollection as $key => $value) {
                    $projectData = Project::find($value->project_id);
                    if($projectData){
                        $projectName = $projectData->name;
                    } else {
                        $projectName = null;
                    }
                    $clientData = ProjectClient::find($value->client_id);
                    if($clientData){
                        $clientName = $clientData->name;
                    } else {
                        $clientName = null;
                    }
                    $serviceData = Service::find($value->service_id);
                    $checkDate = date('Y-m-d',strtotime($value->due_date));
                    $subTask = SubTask::where('task_id', $value->id)->get();
                    $memberData = [];
                    $taskAssigneData = TaskAssigne::where('task_id', $value->id)->get();
                    foreach ($taskAssigneData as $key => $val) {
                        if($val->members_id){
                            $members_id = explode(",", $val->members_id);
                            $userData = User::whereIn('id',$members_id)->get();
                            foreach ($userData as $key => $v) {
                                if($v->profile){
                                    $profile = asset('public/images/profilePhoto/'. $v->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        } else {
                            $team_id = explode(",", $val->team_id);
                            $taskTeamData = Team::whereIn('id', $team_id)->get();
                            foreach ($taskTeamData as $key => $v) {
                                $team_members_id = explode(",", $v->members_id);
                                if($team_members_id){
                                    $userData = User::whereIn('id',$team_members_id)->get();
                                    foreach ($userData as $key => $v) {
                                        if($v->profile){
                                            $profile = asset('public/images/profilePhoto/'. $v->profile);
                                        } else {
                                            $profile = asset('public/images/user_avatar.png');
                                        }
                                        $pro_data = [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'profile' => $profile,
                                        ];
                                        $memberData[] = $pro_data;
                                    }
                                }
                                
                            }
                        }
                    }
                    
                    $todayTask = 0;
                    $overDueTask = 0;
                    $upcomingTask = 0;
                    if($checkDate == date("Y-m-d")){
                        $todayTask = 1;
                    }elseif ($checkDate < date("Y-m-d")) {
                        $overDueTask = 1;
                    }elseif($checkDate > date("Y-m-d")){
                        $upcomingTask = 1;
                    }
                    $dueDate = date('Y-m-d',strtotime($value->due_date));
                    if($value->pin == 1){
                        $pin_data = [
                            'task_id' => $value->id,
                            'title' => $value->title,
                            'due_date' => $checkDate,
                            'description' => $value->description,
                            'projectName' => $projectName,
                            'clientName' => $clientName,
                            'service' => $serviceData->title,
                            'pinTask' => $value->pin,
                            'status' => $value->status,
                            'priority' => $value->priority,
                            'todayTask' => $todayTask,
                            'overDueTask' => $overDueTask,
                            'upcomingTask' => $upcomingTask,
                            'subTaskList' => $subTask,
                            'memberData' => $memberData,
                        ];
                        $pinTaskData[] = $pin_data;
                    } elseif ($dueDate == date("Y-m-d")) {
                       
                        $today_data = [
                            'task_id' => $value->id,
                            'title' => $value->title,
                            'due_date' => $checkDate,
                            'description' => $value->description,
                            'projectName' => $projectName,
                            'clientName' => $clientName,
                            'service' => $serviceData->title,
                            'pinTask' => $value->pin,
                            'status' => $value->status,
                            'priority' => $value->priority,
                            'todayTask' => $todayTask,
                            'overDueTask' => $overDueTask,
                            'upcomingTask' => $upcomingTask,
                            'subTaskList' => $subTask,
                            'memberData' => $memberData,
                        ];
                        $todayTaskData[] = $today_data;

                    } elseif ($dueDate < date("Y-m-d")) {
                        $overDue_data = [
                            'task_id' => $value->id,
                            'title' => $value->title,
                            'due_date' => $checkDate,
                            'description' => $value->description,
                            'projectName' => $projectName,
                            'clientName' => $clientName,
                            'service' => $serviceData->title,
                            'pinTask' => $value->pin,
                            'status' => $value->status,
                            'priority' => $value->priority,
                            'todayTask' => $todayTask,
                            'overDueTask' => $overDueTask,
                            'upcomingTask' => $upcomingTask,
                            'subTaskList' => $subTask,
                            'memberData' => $memberData,
                        ];
                        $overDueTaskData[] = $overDue_data;

                    } elseif ($dueDate > date("Y-m-d")){
                        $upcoming_data = [
                            'task_id' => $value->id,
                            'title' => $value->title,
                            'due_date' => $checkDate,
                            'description' => $value->description,
                            'projectName' => $projectName,
                            'clientName' => $clientName,
                            'service' => $serviceData->title,
                            'pinTask' => $value->pin,
                            'status' => $value->status,
                            'priority' => $value->priority,
                            'todayTask' => $todayTask,
                            'overDueTask' => $overDueTask,
                            'upcomingTask' => $upcomingTask,
                            'subTaskList' => $subTask,
                            'memberData' => $memberData,
                        ];
                        $upcomingTaskData[] = $upcoming_data;
                    }
                }
            }
            $response['pinTaskData'] = $pinTaskData;
            $response['todayTaskData'] = $todayTaskData;
            $response['overDueTaskData'] = $overDueTaskData;
            $response['upcomingTaskData'] = $upcomingTaskData;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Task list successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task data not found',
                    'data' => []
                ]);
            }
        }
        
        public function taskProjectList(Request $request){
            $companyId = $request->companyID;
            $userId = Auth::user()->id;
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            // if($ownerCheck){
                $project = Project::orderBy('id', 'DESC')->where('company_id', $companyId)->get();
            // } else {
            //     $project = Project::orderBy('id', 'DESC')->where('user_id', $userId)->orWhere('manager_id', $userId)->where('company_id', $companyId)->get();
            // }
            $allProjectData = [];
            foreach ($project as $key => $value) {
                $clientData = ProjectClient::find($value->client_id);
                $projectFavorite = ProjectFavorite::where('project_id', $value->id)->where('is_favorite', 1)->first();
                $startDate = date('d-m-Y',strtotime($value->start_date));
                $endDate = date('m/d/Y',strtotime($value->end_date));
                $lastUpDate = date('d F, Y',strtotime($value->updated_at));
                if($value->status == 0){
                    $status = 'Upcoming';
                }elseif ($value->status == 1){
                    $status = 'Today';
                }elseif($value->status == 2){
                    $status = 'OverDue';
                }else{
                    $status = 'Closed';
                }
                if($projectFavorite){
                    $is_favorite = 1;
                } else {
                    $is_favorite = 0;
                }
                
                // $members_id = explode(",", $value->members_id);
                // $memberData = [];
                // if($members_id){
                //     $userData = User::whereIn('id',$members_id)->get();
                //     foreach ($userData as $key => $val) {
                //         if($val->profile){
                //             $profile = asset('public/images/profilePhoto/'. $val->profile);
                //          } else {
                //              $profile = asset('public/images/user_avatar.png');
                //          }
                //         $m_data = [
                //             'id' => $val->id,
                //             'name' => $val->name,
                //             'profile' => $profile,
                //         ];
                //         $memberData[] = $m_data;
                //     }
                // }

                $checkList = CheckList::where('project_id', $value->id)->get();
                
                $memberData = [];
                // if($checkList){
                //     foreach ($checkList as $key => $val) {
                //         $userData = User::where('id',$val->assigne_id)->first();
                //         // dd($userData);
                //         // if($userData->profile){
                //         //     $profile = asset('public/images/profilePhoto/'. $userData->profile);
                //         //  } else {
                //         //      $profile = asset('public/images/user_avatar.png');
                //         //  }
                //         $m_data = [
                //             'id' => $userData->id,
                //             'name' => $userData->name,
                //             'profile' => asset('public/images/user_avatar.png'),
                //         ];
                //         // dd($m_data);
                //         $memberData[] = $m_data;
                //     }
                // }
                
                $clientData = ProjectClient::find($value->client_id);
                if($clientData){
                    $clientName = $clientData->name;
                } else {
                    $clientName = null;
                }

                $pro_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'description' => $value->description,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'clientName' => $clientName,
                    'status' => $status,
                    'lastUpDate' => $lastUpDate,
                    'is_favorite' => $is_favorite,
                    'total_cost' => $value->total_cost,
                    // 'membersData' => $memberData,
                ];
                $allProjectData[] = $pro_data;
                
            }

            $project = Project::where('company_id', $companyId)
                ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
                ->get();
            $AssigneeMeData = [];
            foreach ($project as $key => $value) {
                if(($value->user_id != $userId) && ($value->company_id == $companyId)){
                    $clientData = ProjectClient::find($value->client_id);
                    $startDate = date('Y-m-d',strtotime($value->start_date));
                    $endDate = date('m/d/Y',strtotime($value->end_date));
                    $lastUpDate = date('d F, Y',strtotime($value->updated_at));
                    $projectFavorite = ProjectFavorite::where('project_id', $value->id)->where('is_favorite', 1)->first();
                    if($value->status == 0){
                        $status = 'Upcoming';
                    }elseif ($value->status == 1){
                        $status = 'Today';
                    }elseif($value->status == 2){
                        $status = 'OverDue';
                    }else{
                        $status = 'Closed';
                    }
                    if($projectFavorite){
                        $is_favorite = 1;
                    } else {
                        $is_favorite = 0;
                    }
                    $clientData = ProjectClient::find($value->client_id);
                    if($clientData){
                        $clientName = $clientData->name;
                    } else {
                        $clientName = null;
                    }
                    $pro_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'description' => $value->description,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'clientName' => $clientName,
                        'status' => $status,
                        'lastUpDate' => $lastUpDate,
                        'is_favorite' => $is_favorite,
                    ];
                    $allProjectData[] = $pro_data;
                }
            }

            $teamData = [];
            $team = Team::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
            foreach ($team as $key => $value) {
                $ta_data = [
                    'teamId' => $value->id,
                ];
                $teamData[] = $ta_data;
            }
            $projectAssigneTeamData = [];
            if($teamData){
                foreach ($teamData as $key => $value) {
                    $teamId = $value['teamId'];
                    if($value['teamId']){
                        $projectAssigneTeamData = Project::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
                    }
                }
            }
            // dd($projectAssigneTeamData);
            $teamAssigneeMeData = [];
            foreach ($projectAssigneTeamData as $key => $value) {
                if(($value->user_id != $userId) && ($value->company_id == $companyId)){
                    
                    $startDate = date('Y-m-d',strtotime($value->start_date));
                    $endDate = date('Y-m-d',strtotime($value->end_date));
                    $lastUpDate = date('d F, Y',strtotime($value->updated_at));
                    $projectFavorite = ProjectFavorite::where('project_id', $value->id)->where('is_favorite', 1)->first();
                    if($value->status == 0){
                        $status = 'Upcoming';
                    }elseif ($value->status == 1){
                        $status = 'Today';
                    }elseif($value->status == 2){
                        $status = 'OverDue';
                    }else{
                        $status = 'Closed';
                    }
                    if($projectFavorite){
                        $is_favorite = 1;
                    } else {
                        $is_favorite = 0;
                    }
                    $clientData = ProjectClient::find($value->client_id);
                    if($clientData){
                        $clientName = $clientData->name;
                    } else {
                        $clientName = null;
                    }
                    $pro_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'description' => $value->description,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'clientName' => $clientName,
                        'status' => $status,
                        'lastUpDate' => $lastUpDate,
                        'is_favorite' => $is_favorite,
                    ];
                    $allProjectData[] = $pro_data;
                }
            }
            if($allProjectData){
                return response()->json([
                    'status' => true,
                    'message' => 'Task project list successfully',
                    'data' => $allProjectData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project data not found',
                    'data' => []
                ]);
            }
        }
        
        public function taskListAll(Request $request){
        $request->validate([
            'companyID' => 'required',
        ]);
        
        $companyId = $request->companyID;
        $userId = Auth::user()->id;
        
        // Fetch teams where the current user is a member
        $teams = Team::orderBy('id', 'DESC')
                     ->where('company_id', $companyId)
                     ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
                     ->pluck('id');
    
        // Fetch tasks assigned to teams where the user is a member
        $teamTaskIds = TaskAssigne::orderBy('id', 'DESC')
                                  ->whereIn('team_id', $teams)
                                  ->pluck('task_id');
    
        // Fetch tasks assigned directly to the user
        $userTaskIds = TaskAssigne::orderBy('id', 'DESC')
                                  ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
                                  ->pluck('task_id');
    
       $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if($ownerCheck){
                   $myTaskIds = Task::where('company_id', $companyId)
                         ->pluck('id');
            }else{
                   $myTaskIds = Task::where('user_id', $userId)
                         ->where('company_id', $companyId)
                         ->pluck('id');
            }
        // Fetch tasks assigned to the user
     
    
        // Fetch subtasks assigned to the user
        $subTaskIds = SubTask::orderBy('id', 'DESC')
                             ->whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')
                             ->pluck('task_id');
    
        // Combine all task IDs
        $taskIds = array_unique(array_merge(
            $teamTaskIds->toArray(),
            $userTaskIds->toArray(),
            $myTaskIds->toArray(),
            $subTaskIds->toArray()
        ));
    
        // Fetch tasks with project, client, and service information
        $tasks = Task::whereIn('id', $taskIds)
                     ->orderBy('id', 'DESC')
                     ->get();
    
        $allTask = [];
        foreach ($tasks as $task) {
            $starated_at = date('Y-m-d', strtotime($task->start_date));
            $allTask[] = [
                'task_id' => $task->id,
                'title' => $task->description,
                'starated_at' => $starated_at,
            ];
        }
    
        if($allTask){
            return response()->json([
                'status' => true,
                'message' => 'Task list successfully',
                'data' => $allTask
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Task data not found',
                'data' => []
            ]);
        }
    }

        
        public function userTaskList(Request $request){
            $request->validate([
                'userID' => 'required',
                'companyID' => 'required',
            ]);
            $userId = $request->userID;
            $user = User::where('id', $userId)->first();
            $companyId = $request->companyID;
            if($user){
                $teamIds = Team::where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('id')->toArray();
                $taskAssigneTeamData = TaskAssigne::whereIn('team_id', $teamIds)->pluck('task_id')->toArray();
                $taskAssigneMemberData = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                $taskMyData = Task::where('user_id', $userId)->where('company_id', $companyId)->pluck('id')->toArray();
                $subTaskAssigneMemberData = SubTask::whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->pluck('task_id')->toArray();
                $assignTask = array_merge($taskAssigneMemberData, $taskAssigneTeamData, $taskMyData, $subTaskAssigneMemberData);
                // dd($assignTask);
                $assignTask = array_unique($assignTask);
                
                
                $paymentDate = now()->toDateString();
                
                $response = [
                    'pinTaskData' => [],
                    'todayTaskData' => [],
                    'overDueTaskData' => [],
                    'upcomingTaskData' => [],
                    'completedTaskData' => [],
                ];    
                
                
                // Pin Task
            
            
                $pinTasks = Task::where('pin', 1)->where('completed', 0)->whereIn('id', $assignTask)->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])->get();
                foreach ($pinTasks as $task) {
                    // dd($task->assignees);
                    $projectName = optional($task->project)->name;
                    $clientName = optional($task->client)->name;
                    $serviceName = optional($task->service)->title;
                    $createdName = optional($task->user)->name;
                
                    // Determine task dates
                    $dueDate = date('d-m-Y',strtotime($task->due_date));
                    $startDate = date('d-m-Y',strtotime($task->start_date));
                    $created_at = date('d-m-Y',strtotime($task->created_at));
                
                    // Check task status
                    $statusName = optional($task->status)->status ?: 'Pending';
                
                    // Check task due date relative to current date
                    $carbonDate = Carbon::parse($dueDate);
                    $checkDate = $carbonDate->toDateString();
                    // $checkDate = $task->due_date->toDateString();
                    $todayTask = $checkDate === $paymentDate ? 1 : 0;
                    $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                    $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
                
                    // Process subtasks
                    $totalSubTask = $task->subtasks->count();
                
                    // Process assignees
                    $memberData = [];
                    $checkFilter = 0;
                    foreach ($task->assignees as $val) {
                        if($val->members_id){
                            $members_id = explode(",", $val->members_id);
                            $userData = User::whereIn('id',$members_id)->get();
                            foreach ($userData as $key => $v) {
                                if($v->profile){
                                    $profile = asset('public/images/profilePhoto/'. $v->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        } else {
                            $team_id = explode(",", $val->team_id);
                            $taskTeamData = Team::whereIn('id', $team_id)->get();
                            foreach ($taskTeamData as $key => $v) {
                                $team_members_id = explode(",", $v->members_id);
                                if($team_members_id){
                                    $userData = User::whereIn('id',$team_members_id)->get();
                                    foreach ($userData as $key => $v) {
                                        if($v->profile){
                                            $profile = asset('public/images/profilePhoto/'. $v->profile);
                                        } else {
                                            $profile = asset('public/images/user_avatar.png');
                                        }
                                        $pro_data = [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'profile' => $profile,
                                        ];
                                        $memberData[] = $pro_data;
                                    }
                                }
                                
                            }
                        }
                    }
                
                    // Prepare task data
                    $taskData = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'description' => $task->description,
                        'projectName' => $projectName,
                        'clientName' => $clientName,
                        'createdName' => $createdName,
                        'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                        'service' => $serviceName,
                        'pinTask' => $task->pin,
                        'completed' => $task->completed,
                        'status' => $statusName,
                        'statusId' => $task->status,
                        'priority' => $task->priority,
                        'todayTask' => $todayTask,
                        'overDueTask' => $overDueTask,
                        'upcomingTask' => $upcomingTask,
                        'subTaskList' => $task->subtasks,
                        'memberData' => $memberData,
                        'created_at' => $created_at,
                        'isSubTask' => $totalSubTask,
                    ];
                
                    // Categorize task based on due date and status
                    
                    $response['pinTaskData'][] = $taskData;
                        
                }
                
                // Completed Task List
                
                
                $startOfMonth = now()->startOfMonth()->toDateString();
                $endOfMonth = now()->endOfMonth()->toDateString();
                $completedTasks = Task::where('completed', 1)
                ->whereIn('id', $assignTask)
                ->whereDate('completed_date', '>=', $startOfMonth)
                ->whereDate('completed_date', '<=', $endOfMonth)
                ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
                ->get();
                foreach ($completedTasks as $task) {
                    // dd($task->assignees);
                    $projectName = optional($task->project)->name;
                    $clientName = optional($task->client)->name;
                    $serviceName = optional($task->service)->title;
                    $createdName = optional($task->user)->name;
                
                    // Determine task dates
                    $dueDate = date('d-m-Y',strtotime($task->due_date));
                    $startDate = date('d-m-Y',strtotime($task->start_date));
                    $created_at = date('d-m-Y',strtotime($task->created_at));
                
                    // Check task status
                    // $statusName = optional($task->status)->status ?: 'Pending';
                    
                    $status = CompanyStatus::where('id', $task->status)->first();
                    if($status){
                        $statusName = $status->status;
                    } else {
                        $statusName = 'Pending';
                    }
                
                    // Check task due date relative to current date
                    $carbonDate = Carbon::parse($dueDate);
                    $checkDate = $carbonDate->toDateString();
                    // $checkDate = $task->due_date->toDateString();
                    $todayTask = $checkDate === $paymentDate ? 1 : 0;
                    $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                    $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
                
                    // Process subtasks
                    $totalSubTask = $task->subtasks->count();
                
                    // Process assignees
                    $memberData = [];
                    $checkFilter = 0;
                    foreach ($task->assignees as $val) {
                        if($val->members_id){
                            $members_id = explode(",", $val->members_id);
                            $userData = User::whereIn('id',$members_id)->get();
                            foreach ($userData as $key => $v) {
                                if($v->profile){
                                    $profile = asset('public/images/profilePhoto/'. $v->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        } else {
                            $team_id = explode(",", $val->team_id);
                            $taskTeamData = Team::whereIn('id', $team_id)->get();
                            foreach ($taskTeamData as $key => $v) {
                                $team_members_id = explode(",", $v->members_id);
                                if($team_members_id){
                                    $userData = User::whereIn('id',$team_members_id)->get();
                                    foreach ($userData as $key => $v) {
                                        if($v->profile){
                                            $profile = asset('public/images/profilePhoto/'. $v->profile);
                                        } else {
                                            $profile = asset('public/images/user_avatar.png');
                                        }
                                        $pro_data = [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'profile' => $profile,
                                        ];
                                        $memberData[] = $pro_data;
                                    }
                                }
                                
                            }
                        }
                    }
                
                    // Prepare task data
                    $taskData = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'description' => $task->description,
                        'projectName' => $projectName,
                        'clientName' => $clientName,
                        'createdName' => $createdName,
                        'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                        'service' => $serviceName,
                        'pinTask' => $task->pin,
                        'completed' => $task->completed,
                        'status' => $statusName,
                        'statusId' => $task->status,
                        'priority' => $task->priority,
                        'todayTask' => $todayTask,
                        'overDueTask' => $overDueTask,
                        'upcomingTask' => $upcomingTask,
                        'subTaskList' => $task->subtasks,
                        'memberData' => $memberData,
                        'created_at' => $created_at,
                        'isSubTask' => $totalSubTask,
                    ];
                
                    // Categorize task based on due date and status
                    
                    $response['completedTaskData'][] = $taskData;
                        
                }
                
                
                // todayTask List
                
                $todayTasks = Task::where('pin', 0)
                ->where('completed', 0)
                ->whereIn('id', $assignTask)
                ->whereDate('due_date', $paymentDate)
                ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
                ->get();
                foreach ($todayTasks as $task) {
                    // dd($task->assignees);
                    $projectName = optional($task->project)->name;
                    $clientName = optional($task->client)->name;
                    $serviceName = optional($task->service)->title;
                    $createdName = optional($task->user)->name;
                
                    // Determine task dates
                    $dueDate = date('d-m-Y',strtotime($task->due_date));
                    $startDate = date('d-m-Y',strtotime($task->start_date));
                    $created_at = date('d-m-Y',strtotime($task->created_at));
                
                    // Check task status
                    $status = CompanyStatus::where('id', $task->status)->first();
                    if($status){
                        $statusName = $status->status;
                    } else {
                        $statusName = 'Pending';
                    }
                
                    // Check task due date relative to current date
                    $carbonDate = Carbon::parse($dueDate);
                    $checkDate = $carbonDate->toDateString();
                    // $checkDate = $task->due_date->toDateString();
                    $todayTask = $checkDate === $paymentDate ? 1 : 0;
                    $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                    $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
                
                    // Process subtasks
                    $totalSubTask = $task->subtasks->count();
                
                    // Process assignees
                    $memberData = [];
                    $checkFilter = 0;
                    foreach ($task->assignees as $val) {
                        if($val->members_id){
                            $members_id = explode(",", $val->members_id);
                            $userData = User::whereIn('id',$members_id)->get();
                            foreach ($userData as $key => $v) {
                                if($v->profile){
                                    $profile = asset('public/images/profilePhoto/'. $v->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        } else {
                            $team_id = explode(",", $val->team_id);
                            $taskTeamData = Team::whereIn('id', $team_id)->get();
                            foreach ($taskTeamData as $key => $v) {
                                $team_members_id = explode(",", $v->members_id);
                                if($team_members_id){
                                    $userData = User::whereIn('id',$team_members_id)->get();
                                    foreach ($userData as $key => $v) {
                                        if($v->profile){
                                            $profile = asset('public/images/profilePhoto/'. $v->profile);
                                        } else {
                                            $profile = asset('public/images/user_avatar.png');
                                        }
                                        $pro_data = [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'profile' => $profile,
                                        ];
                                        $memberData[] = $pro_data;
                                    }
                                }
                                
                            }
                        }
                    }
                
                    // Prepare task data
                    $taskData = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'description' => $task->description,
                        'projectName' => $projectName,
                        'clientName' => $clientName,
                        'createdName' => $createdName,
                        'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                        'service' => $serviceName,
                        'pinTask' => $task->pin,
                        'completed' => $task->completed,
                        'status' => $statusName,
                        'statusId' => $task->status,
                        'priority' => $task->priority,
                        'todayTask' => $todayTask,
                        'overDueTask' => $overDueTask,
                        'upcomingTask' => $upcomingTask,
                        'subTaskList' => $task->subtasks,
                        'memberData' => $memberData,
                        'created_at' => $created_at,
                        'isSubTask' => $totalSubTask,
                    ];
                
                    // Categorize task based on due date and status
                    
                    $response['todayTaskData'][] = $taskData;
                        
                }
            
            
                // overDueTaskData
                
                
                $overDueTasks = Task::where('pin', 0)
                ->where('completed', 0)
                ->whereIn('id', $assignTask)
                ->whereDate('due_date', '<', $paymentDate)
                ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
                ->get();
                foreach ($overDueTasks as $task) {
                    // dd($task->assignees);
                    $projectName = optional($task->project)->name;
                    $clientName = optional($task->client)->name;
                    $serviceName = optional($task->service)->title;
                    $createdName = optional($task->user)->name;
                
                    // Determine task dates
                    $dueDate = date('d-m-Y',strtotime($task->due_date));
                    $startDate = date('d-m-Y',strtotime($task->start_date));
                    $created_at = date('d-m-Y',strtotime($task->created_at));
                
                    // Check task status
                    $status = CompanyStatus::where('id', $task->status)->first();
                    if($status){
                        $statusName = $status->status;
                    } else {
                        $statusName = 'Pending';
                    }
                
                    // Check task due date relative to current date
                    $carbonDate = Carbon::parse($dueDate);
                    $checkDate = $carbonDate->toDateString();
                    // $checkDate = $task->due_date->toDateString();
                    $todayTask = $checkDate === $paymentDate ? 1 : 0;
                    $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                    $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
                
                    // Process subtasks
                    $totalSubTask = $task->subtasks->count();
                
                    // Process assignees
                    $memberData = [];
                    $checkFilter = 0;
                    foreach ($task->assignees as $val) {
                        if($val->members_id){
                            $members_id = explode(",", $val->members_id);
                            $userData = User::whereIn('id',$members_id)->get();
                            foreach ($userData as $key => $v) {
                                if($v->profile){
                                    $profile = asset('public/images/profilePhoto/'. $v->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        } else {
                            $team_id = explode(",", $val->team_id);
                            $taskTeamData = Team::whereIn('id', $team_id)->get();
                            foreach ($taskTeamData as $key => $v) {
                                $team_members_id = explode(",", $v->members_id);
                                if($team_members_id){
                                    $userData = User::whereIn('id',$team_members_id)->get();
                                    foreach ($userData as $key => $v) {
                                        if($v->profile){
                                            $profile = asset('public/images/profilePhoto/'. $v->profile);
                                        } else {
                                            $profile = asset('public/images/user_avatar.png');
                                        }
                                        $pro_data = [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'profile' => $profile,
                                        ];
                                        $memberData[] = $pro_data;
                                    }
                                }
                                
                            }
                        }
                    }
                
                    // Prepare task data
                    $taskData = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'description' => $task->description,
                        'projectName' => $projectName,
                        'clientName' => $clientName,
                        'createdName' => $createdName,
                        'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                        'service' => $serviceName,
                        'pinTask' => $task->pin,
                        'completed' => $task->completed,
                        'status' => $statusName,
                        'statusId' => $task->status,
                        'priority' => $task->priority,
                        'todayTask' => $todayTask,
                        'overDueTask' => $overDueTask,
                        'upcomingTask' => $upcomingTask,
                        'subTaskList' => $task->subtasks,
                        'memberData' => $memberData,
                        'created_at' => $created_at,
                        'isSubTask' => $totalSubTask,
                    ];
                
                    // Categorize task based on due date and status
                    
                    $response['overDueTaskData'][] = $taskData;
                        
                }
                
                
                // upcomingTaskData
                
                
                $upcomingTasks = Task::where('pin', 0)
                ->where('completed', 0)
                ->whereIn('id', $assignTask)
                ->whereDate('due_date', '>', $paymentDate)
                ->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])
                ->get();
                foreach ($upcomingTasks as $task) {
                    // dd($task->assignees);
                    $projectName = optional($task->project)->name;
                    $clientName = optional($task->client)->name;
                    $serviceName = optional($task->service)->title;
                    $createdName = optional($task->user)->name;
                
                    // Determine task dates
                    $dueDate = date('d-m-Y',strtotime($task->due_date));
                    $startDate = date('d-m-Y',strtotime($task->start_date));
                    $created_at = date('d-m-Y',strtotime($task->created_at));
                
                    // Check task status
                    $status = CompanyStatus::where('id', $task->status)->first();
                    if($status){
                        $statusName = $status->status;
                    } else {
                        $statusName = 'Pending';
                    }
                
                    // Check task due date relative to current date
                    $carbonDate = Carbon::parse($dueDate);
                    $checkDate = $carbonDate->toDateString();
                    // $checkDate = $task->due_date->toDateString();
                    $todayTask = $checkDate === $paymentDate ? 1 : 0;
                    $overDueTask = $checkDate < $paymentDate ? 1 : 0;
                    $upcomingTask = $checkDate > $paymentDate ? 1 : 0;
                
                    // Process subtasks
                    $totalSubTask = $task->subtasks->count();
                
                    // Process assignees
                    $memberData = [];
                    $checkFilter = 0;
                    foreach ($task->assignees as $val) {
                        if($val->members_id){
                            $members_id = explode(",", $val->members_id);
                            $userData = User::whereIn('id',$members_id)->get();
                            foreach ($userData as $key => $v) {
                                if($v->profile){
                                    $profile = asset('public/images/profilePhoto/'. $v->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $v->id,
                                    'name' => $v->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        } else {
                            $team_id = explode(",", $val->team_id);
                            $taskTeamData = Team::whereIn('id', $team_id)->get();
                            foreach ($taskTeamData as $key => $v) {
                                $team_members_id = explode(",", $v->members_id);
                                if($team_members_id){
                                    $userData = User::whereIn('id',$team_members_id)->get();
                                    foreach ($userData as $key => $v) {
                                        if($v->profile){
                                            $profile = asset('public/images/profilePhoto/'. $v->profile);
                                        } else {
                                            $profile = asset('public/images/user_avatar.png');
                                        }
                                        $pro_data = [
                                            'id' => $v->id,
                                            'name' => $v->name,
                                            'profile' => $profile,
                                        ];
                                        $memberData[] = $pro_data;
                                    }
                                }
                                
                            }
                        }
                    }
                
                    // Prepare task data
                    $taskData = [
                        'task_id' => $task->id,
                        'title' => $task->title,
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'description' => $task->description,
                        'projectName' => $projectName,
                        'clientName' => $clientName,
                        'createdName' => $createdName,
                        'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                        'service' => $serviceName,
                        'pinTask' => $task->pin,
                        'completed' => $task->completed,
                        'status' => $statusName,
                        'statusId' => $task->status,
                        'priority' => $task->priority,
                        'todayTask' => $todayTask,
                        'overDueTask' => $overDueTask,
                        'upcomingTask' => $upcomingTask,
                        'subTaskList' => $task->subtasks,
                        'memberData' => $memberData,
                        'created_at' => $created_at,
                        'isSubTask' => $totalSubTask,
                    ];
                
                    // Categorize task based on due date and status
                    
                    $response['upcomingTaskData'][] = $taskData;
                        
                }
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Task list successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User data not found',
                    'data' => []
                ]);
            }
        }
        
        public function taskDelete(Request $request){
            $request->validate([
                'taskID' => 'required',
            ]);
            $file = Task::where('id', $request->taskID)->first();
            if($file){
                $file->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Task deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task deleted unsuccessfully',
                ]);
            }
        }
        
        public function subTaskDelete(Request $request){
            $request->validate([
                'subTaskID' => 'required',
            ]);
            $file = SubTask::where('id', $request->subTaskID)->first();
            if($file){
                $file->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'subTask deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'subTask deleted unsuccessfully',
                ]);
            }
        }
        
        public function checkListDelete(Request $request){
            $request->validate([
                'checkID' => 'required',
            ]);
            $file = CheckList::where('id', $request->checkID)->first();
            if($file){
                $file->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Check list deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Check list deleted unsuccessfully',
                ]);
            }
        }
        
        public function taskReminder(Request $request){
            $request->validate([
                'taskID' => 'required',
                'reminderdate' => 'required',
                'priority' => 'required',
                'priority_based' => 'required',
                'selectedHour' => 'required',
                'selectedMinute' => 'required',
                'recipientsjson' => 'required',
                'notificationTypesjson' => 'required',
            ]);
            $userId = Auth::user()->id;
            $task = Task::where('id', $request->taskID)->first();
            if($task){
                $reminderdate = new DateTime($request->reminderdate);
                $date = $reminderdate->format('Y-m-d');
                $time = $request->selectedHour . ':' . $request->selectedMinute;
                $taskId = $request->taskID;
                $remind = [
                    'task_id' => $taskId, 
                    'user_id' => $userId, 
                    'date' => $date, 
                    'day_type' => $request->priority, 
                    'day_count' => $request->priority_based, 
                    'time' => $time, 
                    'recipients' => $request->recipientsjson, 
                    'notificationTypes' => $request->notificationTypesjson, 
                ];
                // dd($remind);
                $remind = TaskRemind::create($remind);
                
                if($remind){
                    return response()->json([
                        'status' => true,
                        'message' => 'Task reminder setup successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task reminder setup unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task not found',
                ]);
            }
        }
        
        public function getRemainingTotalCost(Request $request){
            $request->validate([
                'projectID' => 'required',
                'clinetID' => 'required',
            ]);
            $task = Task::where('project_id', $request->projectID)
                ->where('client_id', $request->clientID)
                ->latest()
                ->first();
            if($task){
                $reminderdate = new DateTime($request->reminderdate);
                $date = $reminderdate->format('Y-m-d');
                $time = $request->selectedHour . ':' . $request->selectedMinute;
                $taskId = $request->taskID;
                $remind = [
                    'task_id' => $taskId, 
                    'user_id' => $userId, 
                    'date' => $date, 
                    'day_type' => $request->priority, 
                    'day_count' => $request->priority_based, 
                    'time' => $time, 
                    'recipients' => $request->recipientsjson, 
                    'notificationTypes' => $request->notificationTypesjson, 
                ];
                // dd($remind);
                $remind = TaskRemind::create($remind);
                
                if($remind){
                    return response()->json([
                        'status' => true,
                        'message' => 'Task reminder setup successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task reminder setup unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Task not found',
                ]);
            }
        }
        
        public function taskCheckListCompleted(Request $request){
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
            $task = Task::where('id',$request->taskIds)->first();
            if($task){
                $binaryFile = $request->file('files');
                if($binaryFile){
                    if ($binaryFile->getSize() > 0) {
                        $binaryData = file_get_contents($binaryFile->getRealPath());
                        $fileNameWithoutExtension = pathinfo($binaryFile->getClientOriginalName(), PATHINFO_FILENAME);
                        $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $binaryFile->getClientOriginalExtension();
                        $path = public_path('images/userCheckList/') . $newFileName;
                        file_put_contents($path, $binaryData);
                        if($completed == 1){
                             $timerhour = $request->timerhour;
                              $timerminute = $request->timerminute;
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
                            }  else if ($timerhour == '00' && $timerminute == '00') {
                                $oldTime = $task->actual_time;
                                $newTime = $request->actualtime;
                                $totalSeconds = $this->timeToSeconds($oldTime) + $this->timeToSeconds($newTime);
                                $resultTime = $this->secondsToTime($totalSeconds);
                                $task->actual_time = $resultTime;
                                $task->save();
                            } 
                        } 
                        
                        $userCheck = [
                            'user_id' => $userId, 
                            'task_id' => $request->taskIds, 
                            'completed' => $completed, 
                            'checklist_id' => $request->checklistids, 
                            'user_hour' => $request->timerhour, 
                            'user_minute' => $request->timerminute, 
                            'completedNote' => $request->completedNote, 
                            'file' => $newFileName, 
                        ];
                        $userCheck = UserCheckList::create($userCheck);
                    }
                }else {
                    $timerhour = $request->timerhour;
                    $timerminute = $request->timerminute;
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
                        }  else if ($timerhour == '00' && $timerminute == '00') {
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
                    $userCheck = UserCheckList::create($userCheck);
                }
                
                if($completed == 1){
                    return response()->json([
                        'status' => true,
                        'message' => 'Check list completed successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => true,
                        'message' => 'Check list InCompleted successfully',
                    ]);
                }
                
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data note found',
                ]);
            }
        }
        
        public function taskCheckListInCompleted(Request $request){
            $request->validate([
                'checkListID' => 'required',
                'completed' => 'required',
            ]);
            $completed = 0;
            if($request->completed == 'true'){
                $completed = 1;
            } 
            $userId = Auth::user()->id;
            $task = UserCheckList::where('id',$request->checkListID)->where('user_id', $userId)->first();
            // dd($completed);
            if($task){
                $task->completed = $completed;
                $task->save();
                if($completed == 1){
                    return response()->json([
                        'status' => true,
                        'message' => 'Check list completed successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => true,
                        'message' => 'Check list InCompleted successfully',
                    ]);
                }
                
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Check list data note found',
                ]);
            }
        }
        
        public function checkListTrackTime(Request $request){
            $request->validate([
                'checkListID' => 'required',
                'actualtime' => 'required',
            ]);
            $userId = Auth::user()->id;
            $task = UserCheckList::where('id',$request->checkListID)->where('user_id', $userId)->where('completed', 0)->first();
            // dd($task);
            if($task){
                $oldTime = $task->actualtime;
                $newTime = $request->actualtime;
                $totalSeconds = $this->timeToSeconds($oldTime) + $this->timeToSeconds($newTime);
                $resultTime = $this->secondsToTime($totalSeconds);
                $task->actualtime = $resultTime;
                $task->save();
                if($task){
                    return response()->json([
                        'status' => 205,
                        'message' => 'Check list time updated successfully',
                    ]);
                } 
                
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Check list already completed',
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
        
        public function taskCheckListDelete(Request $request){
            $request->validate([
                'checkListID' => 'required',
            ]);
            $file = UserCheckList::where('id', $request->checkListID)->first();
            if($file){
                $file->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Check list deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Check list deleted unsuccessfully',
                ]);
            }
        }
        
        public function taskTypeList(Request $request){
            $companyID = $request->companyID;
            $types = TaskType::where('company_id', $companyID)->get();
            $typesData = [];
            foreach ($types as $key => $value) {
                $pro_data = [
                    'id' => $value->id,
                    'title' => $value->title,
                ];
                $typesData[] = $pro_data;
            }
            if($typesData){
                return response()->json([
                    'status' => true,
                    'message' => 'Tsak Type list successfully',
                    'data' => $typesData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Tsak Type data not found',
                    'data' => []
                ]);
            }
        }

        public function taskTypeGet(Request $request){
            $typeID = $request->typeID;
            $types = TaskType::where('id', $typeID)->first();
            $response = [
                'id' => $types->id,
                'title' => $types->title,
            ];
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Tsak Type get successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Tsak Type data not found',
                    'data' => []
                ]);
            }
        }

        public function taskTypeAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'companyId' => 'required',
                    'title' => 'required',
                ]);
                $note = [
                    'user_id' => $userId, 
                    'company_id' => $request->companyId, 
                    'title' => $request->title, 
                ];
                // dd($request->all());
                $note = TaskType::create($note);
                if($note){
                    return response()->json([
                        'status' => true,
                        'message' => 'Tsak Type added successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Tsak Type added unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function taskTypeEdit(Request $request){
            $request->validate([
                'serviceID' => 'required',
                'title' => 'required',
            ]);
            
            $service = TaskType::find($request->serviceID);
            $service->title = $request->title;
            $service->save();
            if($service){
                return response()->json([
                    'status' => true,
                    'message' => 'Tsak Type Edited successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Tsak Type Edited unsuccessfully',
                ]);
            }
        }

        public function taskTypeDelete(Request $request){
            $request->validate([
                'typeID' => 'required',
            ]);
            $service = TaskType::where('id', $request->typeID)->first();
            if($service){
                $service->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Tsak Type deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Tsak Type deleted unsuccessfully',
                ]);
            }
        }
    }