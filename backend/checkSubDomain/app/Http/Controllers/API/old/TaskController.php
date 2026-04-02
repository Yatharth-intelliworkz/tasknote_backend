<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\Task;
    use App\Models\Project;
    use App\Models\Service;
    use App\Models\Attestment;
    use App\Models\TaskAssigne;
    use App\Models\SubTask;
    use App\Models\CompanyStatus;
    use App\Models\ProjectFavorite;
    use App\Models\CheckList;
    use App\Models\Note;
    use App\Models\Company;
    use App\Models\NoteShare;
    use App\Models\Team;
    use App\Models\UserRole;
    use App\Models\TaskComment;
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

    class TaskController extends Controller{
        
        public function index(Request $request){
            $request->validate([
                'companyID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = Auth::user()->id;
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if($ownerCheck){
                $ownerTaskListSet = [];
                $ownerTaskList = Task::orderBy('id', 'DESC')->where('company_id', $companyId)->get();
                foreach ($ownerTaskList as $key => $value) {
                    $ownerTaskListSet[] = $value->id;
                }
                $ta_data = [];
                $assignTask = array_merge($ownerTaskListSet);
            } else {
                $teamData = [];
                $team = Team::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
                foreach ($team as $key => $value) {
                    $ta_data = [
                        'teamId' => $value->id,
                    ];
                    $teamData[] = $ta_data;
                }
                $taskAssigneTeamData = [];
                $taskAssigneMemberData = [];
                $taskMyData = [];
                if($teamData){
                    foreach ($teamData as $key => $value) {
                        $teamId = $value['teamId'];
                        if($value['teamId']){
                            $taskAssigneTeam = TaskAssigne::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
                            foreach ($taskAssigneTeam as $key => $value) {
                                $taskAssigneTeamData[] =  $value->task_id;
                            }
                        }
                    }
                }
                $taskAssigneMember = TaskAssigne::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
                foreach ($taskAssigneMember as $key => $value) {
                    $taskAssigneMemberData[] = $value->task_id;
                }
                
                $myTask = Task::where('user_id', $userId)->where('company_id', $companyId)->get();
                foreach ($myTask as $key => $value) {
                    $taskMyData[] = $value->id;
                }
                
                $ta_data = [];
                
                $subTaskAssigneMemberData = [];
                $subTaskAssigneMember = SubTask::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->get();
                foreach ($subTaskAssigneMember as $key => $value) {
                    $subTaskAssigneMemberData[] = $value->task_id;
                }
                
                $assignTask = array_merge($taskAssigneTeamData,$taskAssigneMemberData, $taskMyData, $subTaskAssigneMemberData);
            }
            
            // dd($assignTask);
            $myTaskData = [];
            foreach (array_unique($assignTask) as $key => $value) {
                $taskList = Task::orderBy('id', 'DESC')->where('id', $value)->where('company_id', $companyId);
                // dd($taskList);
                if($request->rangetyped == 1){
                    if($request->duedate == true){
                        $rangeStartFormat = new DateTime($request->rangeStart);
                        $rangeEndFormat = new DateTime($request->rangeEnd);
                        
                        $startRangeFormet = $rangeStartFormat->format('Y-m-d');
                        $endRangeFormet = $rangeEndFormat->format('Y-m-d');
                        // dd($rangeEndFormat);
                        $taskList->whereDate('tasks.due_date', '>=', $startRangeFormet);
                        $taskList->whereDate('tasks.due_date', '<=', $endRangeFormet);
                    }

                    if($request->createddate == true){
                        $rangeStartFormat = new DateTime($request->rangeStart);
                        $rangeEndFormat = new DateTime($request->rangeEnd);

                        $startRangeFormet = $rangeStartFormat->format('Y-m-d');
                        $endRangeFormet = $rangeEndFormat->format('Y-m-d');

                        $taskList->whereDate('tasks.created_at', '>=', $startRangeFormet);
                        $taskList->whereDate('tasks.created_at', '<=', $endRangeFormet);
                    }

                    if($request->closeddate == true){
                        
                        $rangeStartFormat = new DateTime($request->rangeStart);
                        $rangeEndFormat = new DateTime($request->rangeEnd);

                        $startRangeFormet = $rangeStartFormat->format('Y-m-d');
                        $endRangeFormet = $rangeEndFormat->format('Y-m-d');
                        // dd($startRangeFormet);
                        // $taskList->where('tasks.completed', 1);
                        $taskList->whereDate('tasks.completed_date', '>=', $startRangeFormet);
                        $taskList->whereDate('tasks.completed_date', '<=', $endRangeFormet);
                    }
                } elseif($request->rangetyped == 2) {
                    if($request->duedate == true){
                        $beforDate = new DateTime($request->befordate);

                        $beforFormat = $beforDate->format('Y-m-d');

                        $taskList->whereDate('tasks.due_date', '<=', $beforFormat);
                    }
                    if($request->createddate == true){

                        $beforDate = new DateTime($request->befordate);

                        $beforFormat = $beforDate->format('Y-m-d');

                        $taskList->whereDate('tasks.created_at', '<=', $beforFormat);
                    }
                    if($request->closeddate == true){

                        $beforDate = new DateTime($request->befordate);

                        $beforFormat = $beforDate->format('Y-m-d');

                        $taskList->where('tasks.completed', 1);
                        $taskList->whereDate('tasks.completed_date', '<=', $beforFormat);
                    }
                
                } elseif($request->rangetyped == 3) {
                    if($request->duedate == true){
                        $onDate = new DateTime($request->ondate);

                        $onFormat = $onDate->format('Y-m-d');

                        $taskList->whereDate('tasks.due_date', $onFormat);
                    }
                    if($request->createddate == true){
                        $onDate = new DateTime($request->ondate);

                        $onFormat = $onDate->format('Y-m-d');

                        $taskList->whereDate('tasks.created_at', $onFormat);
                    }
                    if($request->closeddate == true){
                        $onDate = new DateTime($request->ondate);

                        $onFormat = $onDate->format('Y-m-d');

                        $taskList->where('tasks.completed', 1);
                        $taskList->whereDate('tasks.completed_date', $onFormat);
                    }
                } else {
                    if($request->duedate == true){
                        $afterDate = new DateTime($request->afterdate);

                        $afterFormat = $afterDate->format('Y-m-d');

                        $taskList->whereDate('tasks.due_date', '>=', $afterFormat);
                    }
                    if($request->createddate == true){
                        $afterDate = new DateTime($request->afterdate);

                        $afterFormat = $afterDate->format('Y-m-d');

                        $taskList->whereDate('tasks.created_at', '>=', $afterFormat);
                    }
                    if($request->closeddate == true){
                        $afterDate = new DateTime($request->afterdate);

                        $afterFormat = $afterDate->format('Y-m-d');

                        $taskList->where('tasks.completed', 1);
                        $taskList->whereDate('tasks.completed_date', '>=', $afterFormat);
                    }
                }
                if($request->statusID){
                    $statusId = $request->statusID;
                    $statusIds = explode(',', $statusId);
                    $taskList->whereIn('tasks.status', $statusIds);
                }
                $taskList = $taskList->get();
                $ta_data = [
                    'allTask' => $taskList,
                ];
                $myTaskData[] = $ta_data;
            }
            // dd($myTaskData);
            $upcomingTaskData = [];
            $overDueTaskData = [];
            $todayTaskData = [];
            $pinTaskData = [];
            $taskData = [];
            $completedTaskData = [];
            foreach ($myTaskData as $item) {
                $tasksCollection = $item['allTask'];
                foreach ($tasksCollection as $key => $value) {
                    $projectData = Project::find($value->project_id);
                    if($projectData){
                        $projectName = $projectData->name;
                    } else {
                        $projectName = null;
                    }
                    $clientData = User::find($value->client_id);
                    if($clientData){
                        $clientName = $clientData->name;
                    } else {
                        $clientName = null;
                    }
                    $serviceData = Service::find($value->service_id);
                    if($serviceData){
                        $serviceName = $serviceData->title;
                    } else {
                        $serviceName = null;
                    }
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
                    $dueDate = date('d-m-Y',strtotime($value->due_date));
                    $startDate = date('d-m-Y',strtotime($value->start_date));
                    $created_at = date('d-m-Y',strtotime($value->created_at));
                    $status = CompanyStatus::where('id', $value->status)->first();
                    if($status){
                        $statusName = $status->status;
                    } else {
                        $statusName = 'Pending';
                    }
                    $checkDueDate = date('Y-m-d',strtotime($value->due_date));
                    $paymentDate = date('Y-m-d');
                    $paymentDate=date('Y-m-d', strtotime($paymentDate));
                    $createdData = User::where('id', $value->user_id)->first();
                    if($createdData){
                        if($createdData->profile){
                            $c_profile = asset('public/images/profilePhoto/'. $createdData->profile);
                        } else {
                            $c_profile = null;
                        }
                        $createdName = $createdData->name;
                        $createdProfile = $c_profile;
                    } else {
                        $createdName = '';
                        $createdProfile = null;
                    }
                    $totalSubTask = 0;
                    $CheckCompletedTask = 0;
                    $subTaskCheck = SubTask::whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->get();
                    foreach ($subTaskCheck as $key => $subVal) {
                        if($subVal->task_id == $value->id){
                            $totalSubTask = 1;
                        }
                        if($subVal->completed == $value->completed){
                            $CheckCompletedTask = 1;
                        }
                    }
                    if($value->completed == 0){
                        if($value->pin == 1){
                            $pin_data = [
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
                                'statusId' => $value->status,
                                'priority' => $value->priority,
                                'todayTask' => $todayTask,
                                'overDueTask' => $overDueTask,
                                'upcomingTask' => $upcomingTask,
                                'subTaskList' => $subTask,
                                'memberData' => $memberData,
                                'created_at' => $created_at,
                                'isSubTask' => $totalSubTask,
                            ];
                            $pinTaskData[] = $pin_data;
                        } elseif ($checkDueDate == $paymentDate) {
                            $today_data = [
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
                                'statusId' => $value->status,
                                'priority' => $value->priority,
                                'todayTask' => $todayTask,
                                'overDueTask' => $overDueTask,
                                'upcomingTask' => $upcomingTask,
                                'subTaskList' => $subTask,
                                'memberData' => $memberData,
                                'created_at' => $created_at,
                                'isSubTask' => $totalSubTask,
                            ];
                            $todayTaskData[] = $today_data;
    
                        } elseif ($checkDueDate < $paymentDate) {
                            $overDue_data = [
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
                                'statusId' => $value->status,
                                'priority' => $value->priority,
                                'todayTask' => $todayTask,
                                'overDueTask' => $overDueTask,
                                'upcomingTask' => $upcomingTask,
                                'subTaskList' => $subTask,
                                'memberData' => $memberData,
                                'created_at' => $created_at,
                                'isSubTask' => $totalSubTask,
                            ];
                            $overDueTaskData[] = $overDue_data;
                            
                        } elseif ($checkDueDate > $paymentDate){
                            $upcoming_data = [
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
                                'statusId' => $value->status,
                                'priority' => $value->priority,
                                'todayTask' => $todayTask,
                                'overDueTask' => $overDueTask,
                                'upcomingTask' => $upcomingTask,
                                'subTaskList' => $subTask,
                                'memberData' => $memberData,
                                'created_at' => $created_at,
                                'isSubTask' => $totalSubTask,
                            ];
                            $upcomingTaskData[] = $upcoming_data;
                        }
                    } else {
                        $complet_data = [
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
                            'statusId' => $value->status,
                            'priority' => $value->priority,
                            'todayTask' => $todayTask,
                            'overDueTask' => $overDueTask,
                            'upcomingTask' => $upcomingTask,
                            'subTaskList' => $subTask,
                            'memberData' => $memberData,
                            'created_at' => $created_at,
                            'isSubTask' => $totalSubTask,
                        ];
                        $completedTaskData[] = $complet_data;
                    }
                }
            }
            $response['pinTaskData'] = $pinTaskData;
            $response['todayTaskData'] = $todayTaskData;
            $response['overDueTaskData'] = $overDueTaskData;
            $response['upcomingTaskData'] = $upcomingTaskData;
            $response['completedTaskData'] = $completedTaskData;
            
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

        public function taskAdd(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'title' => 'required',
                'description' => 'required',
                'start' => 'required',
                'due_date' => 'required',
                'project_id' => 'required',
                'service_id' => 'required',
                'status' => 'required',
                'priority' => 'required',
                'priority' => 'required',
            ]);
            if($request->start){
                $startfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->start));
                $startDateTime = new DateTime($startfromDate);
                $startDate = $startDateTime->format("Y-m-d H:i:s");
            }
            if($request->due_date){
                $duefromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->due_date));
                $dueDateTime = new DateTime($duefromDate);
                $dueDate = $dueDateTime->format("Y-m-d H:i:s");
            }
            
            $projectData = Project::where('id', $request->project_id)->first();
            if(isset($request->files) && !empty($request->files)){
                $task = [
                    'user_id' => $userId, 
                    'title' => $request->title, 
                    'description' => $request->description, 
                    'start_date' =>$startDate, 
                    'due_date' =>$dueDate, 
                    'project_id' => $request->project_id,
                    'client_id'=>$projectData->client_id,
                    'company_id'=>$projectData->company_id,
                    'service_id'=>$request->service_id,
                    'status'=>$request->status, 
                    'priority'=>$request->priority, 
                ];
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
                    'title' => $request->title, 
                    'description' => $request->description, 
                    'due_date' =>$dueDate,
                    'start_date' =>$startDate,
                    'project_id' => $request->project_id,
                    'client_id'=>$projectData->client_id,
                    'company_id'=>$projectData->company_id,
                    'service_id'=>$request->service_id,
                    'status'=>$request->status, 
                    'priority'=>$request->priority, 
                ];
                $task = Task::create($task);
            }
            $members_id = NULL;
            $team_id = NULL;
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
                } else if($request->team_id){
                    if($request->team_id){
                        $data = $request->team_id;
                        $itemIds = array_column($data, 'item_id');
                        $team_id = implode(",", $itemIds);
                    }
                }
            }
            $assigne = new TaskAssigne;
            $assigne->task_id = $task->id;
            $assigne->project_id = $request->project_id;
            $assigne->members_id = $members_id;
            $assigne->team_id = $team_id;
            $assigne->save();
            
            if($request->sub_task){
                if (isset($request->is_sender)) {
                    $subData = json_decode($request->sub_task, true);
                } else {
                    $subData = $request->sub_task;
                }
                $subMembers_id = null;
                $subTeam_id = null;
                // dd($subData);
                foreach ($subData as $key => $val) {
                    // dd($val['sub_members_id']);
                    if($val['sub_members_id']){
                        // $data = json_decode($val['sub_members_id'], true);
                        $data = $val['sub_members_id'];
                        $itemIds = array_column($data, 'item_id');
                        $subMembers_id = implode(",", $itemIds);
                    } else if($val['sub_team_id']){
                        // $data = json_decode($val['sub_team_id'], true);
                        $data = $val['sub_team_id'];
                        $itemIds = array_column($data, 'item_id');
                        $subTeam_id = implode(",", $itemIds);
                    }
                    if($val['sub_due_date']){
                        $subDuefromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $val['sub_due_date']));
                        $subDueDateTime = new DateTime($subDuefromDate);
                        $subDueDate = $subDueDateTime->format("Y-m-d H:i:s");
                    }
                    if(isset($request->is_sender)){
                        $status = $val['sub_status'];
                    } else {
                        $statusData = CompanyStatus::where('status', $val['sub_status'])->where('company_id', $projectData->company_id)->first();
                        $status = $statusData->id;
                    } 
                    $subTask = new SubTask;
                    $subTask->task_id = $task->id;
                    $subTask->user_id = $userId;
                    $subTask->assigne_id = $subMembers_id;
                    $subTask->team_id = $subTeam_id;
                    $subTask->title = $val['sub_title'];
                    $subTask->due_date = $subDueDate;
                    $subTask->status = $status;
                    $subTask->priority = $val['sub_priority'];
                    $subTask->save();
                }
            }

            if($request->checkList){
                if (isset($request->is_sender)) {
                    $checkData = json_decode($request->checkList, true);
                } else {
                    $checkData = $request->checkList;
                }
                foreach ($checkData as $key => $val) {
                    $checkTask = new CheckList;
                    $checkTask->task_id = $task->id;
                    $checkTask->title = $val['checkList_title'];
                    $checkTask->check_date = date('Y-m-d',strtotime($val['checkList_date']));
                    $checkTask->save();
                }
            }
            
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
                    $this->setupNotification($projectData->company_id, $task->id, $userId, $itemIds, $request->title);
                }
            } else {
                if($request->members_id){
                    $data = $request->members_id;
                    $itemIds = array_column($data, 'item_id');
                    $this->setupNotification($projectData->company_id, $task->id, $userId, $itemIds, $request->title);
                }
            }
            
            $myUserData = User::where('id', $userId)->first();
            if($myUserData){
                $taskData = Task::where('id', $task->id)->first();
                if($taskData->priority == 0){
                    $priority = 'Low';
                } else if($taskData->priority == 1) {
                    $priority = 'High';
                } else {
                    $priority = 'Medium';
                }
                $taskAssigneData = TaskAssigne::where('task_id', $task->id)->first();
                $names = '';
                if($taskAssigneData){
                    $members_id = explode(",", $taskAssigneData->members_id);
                    
                    if($members_id){
                        $assignUserData = User::whereIn('id',$members_id)->get();
                        $names = $assignUserData->pluck('name')->sort()->implode(', ');
                    }
                }
                if($taskAssigneData){
                    $members_id = explode(",", $taskAssigneData->members_id);
                    if($members_id){
                        $assignUserData = User::whereIn('id',$members_id)->get();
                        foreach ($assignUserData as $key => $val) {
                            $assignInfo = [
                                'taskId' => $task->id,
                                'mainName' => $val->name,
                                'email' => $val->email,
                                'created' => $myUserData->name, 
                                'taskName' => $taskData->title, 
                                'dueDate' => date('d M Y',strtotime($taskData->due_date)), 
                                'assignName' => $names, 
                                'taskPriority' => $priority, 
                                'cratedDate' => date('d M Y',strtotime($taskData->created_at)),
                            ];
                            // $data['thankyou'] = 'Thank you ' . $info['created'] . ' for New Task.';
                            Mail::send('mail.new_task_assign', ['info' => $assignInfo], function ($message) use ($assignInfo) {
                                $message->to($assignInfo['email'])->subject('Task Manager');
                            });
                        }
                    }
                }
                
                $mainInfo = [
                    'taskId' => $task->id,
                    'mainName' => $myUserData->name,
                    'email' => $myUserData->email,
                    'created' => $myUserData->name, 
                    'taskName' => $taskData->title, 
                    'dueDate' => date('d M Y',strtotime($taskData->due_date)), 
                    'assignName' => $names, 
                    'taskPriority' => $priority, 
                    'cratedDate' => date('d M Y',strtotime($taskData->created_at)),
                ];
                // $data['thankyou'] = 'Thank you ' . $info['created'] . ' for New Task.';
                Mail::send('mail.new_task_assign', ['info' => $mainInfo], function ($message) use ($mainInfo) {
                    $message->to($mainInfo['email'])->subject('Task Manager');
                });
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
                    'massage' => 'You Have Been shared as a Team Member By ' . $userName . ' on '  . $name, 
                ];
                $project =  Notification::create($project);
            }

            return true;
        }
        
        public function taskEdit(Request $request){
            // dd($request->all());
            $userId = Auth::user()->id;
            $request->validate([
                'taskID' => 'required',
                'title' => 'required',
                'description' => 'required',
                'start' => 'required',
                'due_date' => 'required',
                'project_id' => 'required',
                'service_id' => 'required',
                'status' => 'required',
                'priority' => 'required',
            ]);
            $taskID = $request->taskID;
            $taskData = Task::where('id', $taskID)->first();
            // dd($taskData);
            if($taskData){
                if($request->start){
                    $startfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->start));
                    $startDateTime = new DateTime($startfromDate);
                    $startDate = $startDateTime->format("Y-m-d");
                }
                if($request->due_date){
                    $duefromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->due_date));
                    $dueDateTime = new DateTime($duefromDate);
                    $dueDate = $dueDateTime->format("Y-m-d");
                }
                $projectData = Project::where('id', $request->project_id)->first();
                if(isset($request->files) && !empty($request->files)){
                    $taskData->title = $request->title;
                    $taskData->description = $request->description;
                    $taskData->start_date = $startDate;
                    $taskData->due_date = $dueDate;
                    $taskData->project_id = $request->project_id;
                    $taskData->client_id = $projectData->client_id;
                    $taskData->service_id = $request->service_id;
                    $taskData->status = $request->status;
                    $taskData->priority = $request->priority;
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
                    $taskData->title = $request->title;
                    $taskData->description = $request->description;
                    $taskData->due_date = $dueDate;
                    $taskData->start_date = $startDate;
                    $taskData->project_id = $request->project_id;
                    $taskData->client_id = $projectData->client_id;
                    $taskData->service_id = $request->service_id;
                    $taskData->status = $request->status;
                    $taskData->priority = $request->priority;
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
                    } else if($request->team_id){
                        if($request->team_id){
                            $data = $request->team_id;
                            $itemIds = array_column($data, 'item_id');
                            $team_id = implode(",", $itemIds);
                        }
                    }
                }
                TaskAssigne::where('task_id', $taskData->id)->delete();
                $assigne = new TaskAssigne;
                $assigne->task_id = $taskData->id;
                $assigne->project_id = $request->project_id;
                $assigne->members_id = $members_id;
                $assigne->team_id = $team_id;
                $assigne->save();
                
                if($request->sub_task){
                    if (isset($request->is_sender)) {
                        $subData = json_decode($request->sub_task, true);
                    } else {
                        $subData = $request->sub_task;
                    }
                    $subMembers_id = null;
                    $subTeam_id = null;
                    // SubTask::where('task_id', $taskData->id)->delete();
                    foreach ($subData as $key => $val) {
                        if($val['sub_members_id']){
                            $data = $val['sub_members_id'];
                            $itemIds = array_column($data, 'item_id');
                            $subMembers_id = implode(",", $itemIds);
                        } else if($val['sub_team_id']){
                            $data = $val['sub_team_id'];
                            $itemIds = array_column($data, 'item_id');
                            $subTeam_id = implode(",", $itemIds);
                        }
                        if($val['sub_due_date']){
                            $subDuefromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $val['sub_due_date']));
                            $subDueDateTime = new DateTime($subDuefromDate);
                            $subDueDate = $subDueDateTime->format("Y-m-d H:i:s");
                        }

                        if(isset($request->is_sender)){
                            $status = $val['sub_status'];
                        } else {
                            $statusData = CompanyStatus::where('status', $val['sub_status'])->where('company_id', $projectData->company_id)->first();
                            $status = $statusData->id;
                        } 
                        
                        $subTask = new SubTask;
                        $subTask->task_id = $taskData->id;
                        $subTask->user_id = $userId;
                        $subTask->assigne_id = $subMembers_id;
                        $subTask->team_id = $subTeam_id;
                        $subTask->title = $val['sub_title'];
                        $subTask->due_date = $subDueDate;
                        $subTask->status = $status;
                        $subTask->priority = $val['sub_priority'];
                        $subTask->save();
                    }
                }
    
                if($request->checkList){
                    if (isset($request->is_sender)) {
                        $checkData = json_decode($request->checkList, true);
                    } else {
                        $checkData = $request->checkList;
                    }
                    CheckList::where('task_id', $taskData->id)->delete();
                    foreach ($checkData as $key => $val) {
                        $checkTask = new CheckList;
                        $checkTask->task_id = $taskData->id;
                        $checkTask->title = $val['checkList_title'];
                        $checkTask->check_date = date('Y-m-d',strtotime($val['checkList_date']));
                        $checkTask->save();
                    }
                }
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
                Notification::create($cratedData);
                return response()->json([
                    'status' => true,
                    'message' => 'Status updated successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status updated unsuccessfully',
                ]);
            }
        }

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
            // dd($request->all());
            $request->validate([
                'taskID' => 'required',
                'completed' => 'required',
            ]);
            $completed = $request->completed;
            if($completed == 1){
                $task = Task::where('id',$request->taskID)->where('completed',0)->first();
                if($task){
                    $task->completed = $request->completed;
                    $task->completed_date = date('Y-m-d');
                    $task->save();
                    return response()->json([
                        'status' => true,
                        'message' => 'Task completed successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task note found',
                    ]);
                }
            } else {
                $task = Task::where('id',$request->taskID)->where('completed',1)->first();
                if($task){
                    $task->completed = $request->completed;
                    $task->completed_date = NULL;
                    $task->save();
                    return response()->json([
                        'status' => true,
                        'message' => 'Task incompleted successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task note found',
                    ]);
                }
            }
            
        }
        
        public function subTaskCompleted(Request $request){
            // dd($request->all());
            $request->validate([
                'subTaskID' => 'required',
                'completed' => 'required',
            ]);
            $task = SubTask::where('id',$request->subTaskID)->where('completed',0)->first();
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
                    'message' => 'Task already completed unsuccessfully',
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
                        $imageName = uniqid() . '.' . $extension;
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
                            $imageName = uniqid() . '.' . $extension;
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
                            $imageName = uniqid() . '.' . $extension;
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
            foreach ($checkUser as $key => $val) {
                $userName = User::where('id', $val->user_id)->first();
                if($val->is_comment == 'text'){
                    $comment = $val->comment;
                } else if($val->is_comment == 'file') {
                    $comment = asset('public/images/taskComment/'. $val->comment);
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
                $file = $request->file('files'); // Get the file from the 'files' bag
                $imageName = mt_rand(10000000000, 99999999999) . '.' . $file->extension();
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
            // dd($task);  
            if($task){
                $projectData = Project::find($task->project_id);
                $clientData = User::find($task->client_id);
                $serviceData = Service::find($task->service_id);
                $checkDate = date('Y-m-d',strtotime($task->due_date));
                $dueDate = date('m/d/Y',strtotime($task->due_date));
                $startDate = date('m/d/Y',strtotime($task->start_date));
                $subTask = SubTask::where('task_id', $task->id)->get();
                $tSubData = [];
                if($subTask){
                    foreach ($subTask as $key => $val) {
                       $assigne_id = explode(",", $val->assigne_id);
                        $team_id = explode(",", $val->team_id);
                        $status = CompanyStatus::where('id', $val->status)->first();
                        if($status){
                            $sName = $status->status;
                        } else {
                            $sName = '';
                        }
                        // dd($assigne_id);
                        $subData = [
                            'id' => $val->id,
                            'title' => $val->title,
                            'assigne_id' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$assigne_id)->get(),
                            'team_id' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$team_id)->get(),
                            'due_date' => $val->due_date,
                            'status' => $sName,
                            'priority' => $val->priority,
                            'pin' => $val->pin,
                            'completed' => $val->completed,
                        ];
                        $tSubData[] = $subData;
                    }
                }
                $checkList = CheckList::where('task_id', $task->id)->get();
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
                $response = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'start_date' => $startDate,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectData->name,
                    'project_id' => $task->project_id,
                    'clientName' => $clientName,
                    'client_id' => $task->client_id,
                    'serviceName' => $serviceData->title,
                    'service_id' => $task->service_id,
                    'taskMembers' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$members_id)->get(),
                    'taskTeam' => Team::select('id AS item_id', 'name AS item_text')->where('id',$team_id)->get(),
                    'status' => $task->status,
                    'priority' => $task->priority,
                    'pin' => $task->pin,
                    'subTask' => $sallData,
                    'checkList' => $checkList,
                    'files' => $allAtt,
                ];
                
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
                $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',1)->first();
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
                $response = [
                    'id' => $note->id,
                    'title' => $note->title,
                    'description' => $note->description,
                    'pin' => $note->pin,
                    'color' => $note->color,
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
                $note = [
                    'user_id' => $userId, 
                    'company_id' => $request->companyID, 
                    'title' => $request->title, 
                    'description' => $request->description, 
                    'color' => $rgbColor,
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
            
            $note = Note::find($request->noteID);
            $note->title = $request->title;
            $note->description = $request->description;
            // $note->color = $rgbColor;
            $note->save();
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
                    $clientData = User::find($value->client_id);
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
            if($ownerCheck){
                $project = Project::orderBy('id', 'DESC')->where('company_id', $companyId)->get();
            } else {
                $project = Project::orderBy('id', 'DESC')->where('user_id', $userId)->where('company_id', $companyId)->get();
            }
            $allProjectData = [];
            foreach ($project as $key => $value) {
                $clientData = User::find($value->client_id);
                $projectFavorite = ProjectFavorite::where('project_id', $value->id)->where('is_favorite', 1)->first();
                $startDate = date('d-m-Y',strtotime($value->start_date));
                $endDate = date('d-m-Y',strtotime($value->end_date));
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
                
                $members_id = explode(",", $value->members_id);
                $memberData = [];
                if($members_id){
                    $userData = User::whereIn('id',$members_id)->get();
                    foreach ($userData as $key => $val) {
                        if($val->profile){
                            $profile = asset('public/images/profilePhoto/'. $val->profile);
                         } else {
                             $profile = asset('public/images/user_avatar.png');
                         }
                        $m_data = [
                            'id' => $val->id,
                            'name' => $val->name,
                            'profile' => $profile,
                        ];
                        $memberData[] = $m_data;
                    }
                }

                $team_id = explode(",", $value->team_id);
                $teamData = [];
                if($team_id){
                    $team_data = Team::whereIn('id',$members_id)->get();
                    foreach ($team_data as $key => $val) {
                        if($val->profile){
                            $profile = asset('public/images/profilePhoto/'. $val->profile);
                         } else {
                             $profile = asset('public/images/user_avatar.png');
                         }
                        $m_data = [
                            'id' => $val->id,
                            'name' => $val->name,
                            'profile' => $profile,
                        ];
                        $teamData[] = $m_data;
                    }
                }
                
                $clientData = User::find($value->client_id);
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
                    'membersData' => $memberData,
                ];
                $allProjectData[] = $pro_data;
                
            }

            $project = Project::where('company_id', $companyId)
                ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
                ->get();
            $AssigneeMeData = [];
            foreach ($project as $key => $value) {
                if(($value->user_id != $userId) && ($value->company_id == $companyId)){
                    $clientData = User::find($value->client_id);
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
                    $clientData = User::find($value->client_id);
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
                    $clientData = User::find($value->client_id);
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
            $teamData = [];
            $team = Team::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
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
                        $taskAssigneTeam = TaskAssigne::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
                        foreach ($taskAssigneTeam as $key => $value) {
                            $taskAssigneTeamData[] =  $value->task_id;
                        }
                    }
                }
            }
            $taskAssigneMember = TaskAssigne::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
            foreach ($taskAssigneMember as $key => $value) {
                $taskAssigneMemberData[] = $value->task_id;
            }
            $taskMyData = [];
            $myTask = Task::where('user_id', $userId)->where('company_id', $companyId)->get();
            foreach ($myTask as $key => $value) {
                $taskMyData[] = $value->id;
            }
            $subTaskAssigneMemberData = [];
            $subTaskAssigneMember = SubTask::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $userId . ', assigne_id)')->get();
            foreach ($subTaskAssigneMember as $key => $value) {
                $subTaskAssigneMemberData[] = $value->task_id;
            }
            $ta_data = [];
            $assignTask = array_merge($taskAssigneTeamData,$taskAssigneMemberData, $taskMyData, $subTaskAssigneMemberData);
            $myTaskData = [];
            foreach (array_unique($assignTask) as $key => $value) {
                $taskList = Task::orderBy('id', 'DESC')->where('id', $value)->where('company_id', $companyId);
                $taskList = $taskList->get();
                $ta_data = [
                    'allTask' => $taskList,
                ];
                $myTaskData[] = $ta_data;
            }
            $allTask = [];
            foreach ($myTaskData as $item) {
                $tasksCollection = $item['allTask'];
                foreach ($tasksCollection as $key => $value) {
                    $projectData = Project::find($value->project_id);
                    if($projectData){
                        $projectName = $projectData->name;
                    } else {
                        $projectName = null;
                    }
                    $clientData = User::find($value->client_id);
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
                    $dueDate = date('Y-m-d',strtotime($value->due_date));
                    $created_at = date('Y-m-d',strtotime($value->created_at));
                    $all_data = [
                        'task_id' => $value->id,
                        'title' => $value->title,
                        'due_date' => $dueDate,
                        'created_at' => $created_at,
                    ];
                    $allTask[] = $all_data;
                    
                }
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
                $teamData = [];
                $team = Team::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
                foreach ($team as $key => $value) {
                    $ta_data = [
                        'teamId' => $value->id,
                    ];
                    $teamData[] = $ta_data;
                }
                $taskAssigneTeamData = [];
                $taskAssigneMemberData = [];
                $taskMyData = [];
                if($teamData){
                    foreach ($teamData as $key => $value) {
                        $teamId = $value['teamId'];
                        if($value['teamId']){
                            $taskAssigneTeam = TaskAssigne::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
                            foreach ($taskAssigneTeam as $key => $value) {
                                $taskAssigneTeamData[] =  $value->task_id;
                            }
                        }
                    }
                }
                $taskAssigneMember = TaskAssigne::orderBy('id', 'DESC')->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
                foreach ($taskAssigneMember as $key => $value) {
                    $taskAssigneMemberData[] = $value->task_id;
                }
                
                $myTask = Task::where('user_id', $userId)->where('company_id', $companyId)->get();
                foreach ($myTask as $key => $value) {
                    $taskMyData[] = $value->id;
                }
                
                $ta_data = [];
                $assignTask = array_merge($taskAssigneTeamData,$taskAssigneMemberData, $taskMyData);
                // dd($assignTask);
                $myTaskData = [];
                foreach (array_unique($assignTask) as $key => $value) {
                    $taskList = Task::orderBy('id', 'DESC')->where('id', $value)->where('company_id', $companyId);
                    // dd($taskList);
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
                // dd($myTaskData);
                $upcomingTaskData = [];
                $overDueTaskData = [];
                $todayTaskData = [];
                $pinTaskData = [];
                $taskData = [];
                $completedTaskData = [];
                foreach ($myTaskData as $item) {
                    $tasksCollection = $item['allTask'];
                    foreach ($tasksCollection as $key => $value) {
                        $projectData = Project::find($value->project_id);
                        if($projectData){
                            $projectName = $projectData->name;
                        } else {
                            $projectName = null;
                        }
                        $clientData = User::find($value->client_id);
                        if($clientData){
                            $clientName = $clientData->name;
                        } else {
                            $clientName = null;
                        }
                        $serviceData = Service::find($value->service_id);
                        if($serviceData){
                            $serviceName = $serviceData->title;
                        } else {
                            $serviceName = null;
                        }
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
                        $dueDate = date('d-m-Y',strtotime($value->due_date));
                        $startDate = date('d-m-Y',strtotime($value->start_date));
                        $created_at = date('d-m-Y',strtotime($value->created_at));
                        $status = CompanyStatus::where('id', $value->status)->first();
                        if($status){
                            $statusName = $status->status;
                        } else {
                            $statusName = 'Pending';
                        }
                        $checkDueDate = date('Y-m-d',strtotime($value->due_date));
                        $paymentDate = date('Y-m-d');
                        $paymentDate=date('Y-m-d', strtotime($paymentDate));
                        $createdData = User::where('id', $value->user_id)->first();
                        if($createdData){
                            $createdName = $createdData->name;
                        } else {
                            $createdName = '';
                        }
                        if($value->completed == 0){
                            if($value->pin == 1){
                                $pin_data = [
                                    'task_id' => $value->id,
                                    'title' => $value->title,
                                    'start_date' => $startDate,
                                    'due_date' => $dueDate,
                                    'description' => $value->description,
                                    'projectName' => $projectName,
                                    'clientName' => $clientName,
                                    'createdName' => $createdName,
                                    'service' => $serviceName,
                                    'pinTask' => $value->pin,
                                    'completed' => $value->completed,
                                    'status' => $statusName,
                                    'statusId' => $value->status,
                                    'priority' => $value->priority,
                                    'todayTask' => $todayTask,
                                    'overDueTask' => $overDueTask,
                                    'upcomingTask' => $upcomingTask,
                                    'subTaskList' => $subTask,
                                    'memberData' => $memberData,
                                    'created_at' => $created_at,
                                ];
                                $pinTaskData[] = $pin_data;
                            } elseif ($checkDueDate == $paymentDate) {
                                $today_data = [
                                    'task_id' => $value->id,
                                    'title' => $value->title,
                                    'start_date' => $startDate,
                                    'due_date' => $dueDate,
                                    'description' => $value->description,
                                    'projectName' => $projectName,
                                    'clientName' => $clientName,
                                    'createdName' => $createdName,
                                    'service' => $serviceName,
                                    'pinTask' => $value->pin,
                                    'completed' => $value->completed,
                                    'status' => $statusName,
                                    'statusId' => $value->status,
                                    'priority' => $value->priority,
                                    'todayTask' => $todayTask,
                                    'overDueTask' => $overDueTask,
                                    'upcomingTask' => $upcomingTask,
                                    'subTaskList' => $subTask,
                                    'memberData' => $memberData,
                                    'created_at' => $created_at,
                                ];
                                $todayTaskData[] = $today_data;
        
                            } elseif ($checkDueDate < $paymentDate) {
                                $overDue_data = [
                                    'task_id' => $value->id,
                                    'title' => $value->title,
                                    'start_date' => $startDate,
                                    'due_date' => $dueDate,
                                    'description' => $value->description,
                                    'projectName' => $projectName,
                                    'clientName' => $clientName,
                                    'createdName' => $createdName,
                                    'service' => $serviceName,
                                    'pinTask' => $value->pin,
                                    'completed' => $value->completed,
                                    'status' => $statusName,
                                    'statusId' => $value->status,
                                    'priority' => $value->priority,
                                    'todayTask' => $todayTask,
                                    'overDueTask' => $overDueTask,
                                    'upcomingTask' => $upcomingTask,
                                    'subTaskList' => $subTask,
                                    'memberData' => $memberData,
                                    'created_at' => $created_at,
                                ];
                                $overDueTaskData[] = $overDue_data;
                                
                            } elseif ($checkDueDate > $paymentDate){
                                $upcoming_data = [
                                    'task_id' => $value->id,
                                    'title' => $value->title,
                                    'start_date' => $startDate,
                                    'due_date' => $dueDate,
                                    'description' => $value->description,
                                    'projectName' => $projectName,
                                    'clientName' => $clientName,
                                    'createdName' => $createdName,
                                    'service' => $serviceName,
                                    'pinTask' => $value->pin,
                                    'completed' => $value->completed,
                                    'status' => $statusName,
                                    'statusId' => $value->status,
                                    'priority' => $value->priority,
                                    'todayTask' => $todayTask,
                                    'overDueTask' => $overDueTask,
                                    'upcomingTask' => $upcomingTask,
                                    'subTaskList' => $subTask,
                                    'memberData' => $memberData,
                                    'created_at' => $created_at,
                                ];
                                $upcomingTaskData[] = $upcoming_data;
                            }
                        } else {
                            $complet_data = [
                                'task_id' => $value->id,
                                'title' => $value->title,
                                'start_date' => $startDate,
                                'due_date' => $dueDate,
                                'description' => $value->description,
                                'projectName' => $projectName,
                                'clientName' => $clientName,
                                'createdName' => $createdName,
                                'service' => $serviceName,
                                'pinTask' => $value->pin,
                                'completed' => $value->completed,
                                'status' => $statusName,
                                'statusId' => $value->status,
                                'priority' => $value->priority,
                                'todayTask' => $todayTask,
                                'overDueTask' => $overDueTask,
                                'upcomingTask' => $upcomingTask,
                                'subTaskList' => $subTask,
                                'memberData' => $memberData,
                                'created_at' => $created_at,
                            ];
                            $completedTaskData[] = $complet_data;
                        }
                    }
                }
                $response['pinTaskData'] = $pinTaskData;
                $response['todayTaskData'] = $todayTaskData;
                $response['overDueTaskData'] = $overDueTaskData;
                $response['upcomingTaskData'] = $upcomingTaskData;
                $response['completedTaskData'] = $completedTaskData;
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
    }