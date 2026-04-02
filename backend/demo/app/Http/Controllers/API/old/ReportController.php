<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\Task;
    use App\Models\Project;
    use App\Models\SubTask;
    use App\Models\TaskAssigne;
    use App\Models\CompanyStatus;
    use App\Models\Company;
    use App\Models\Team;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use DB;
    use DateTime;

    class ReportController extends Controller{
        
        public function userReport(Request $request){
            $request->validate([
                'companyID' => 'required',
                'userID' => 'required',
                'statusID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = $request->userID;
            $statusId = $request->statusID;
            $userIds = explode(',', $userId);
            $statusIds = explode(',', $statusId);
            $userStatusReport = [];
            foreach ($userIds as $key => $uId) {
                $statusData = [];
                foreach ($statusIds as $key => $sId) {
                    $status = CompanyStatus::where('id', $sId)->first();
                    // $totalTask = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    //     ->where('tasks.company_id', $companyId)
                    //     ->where('tasks.status', $sId)
                    //     ->whereNull('task_assignes.deleted_at')
                    //     ->whereRaw('FIND_IN_SET(' . $uId . ', task_assignes.members_id)')
                    //     ->count();
                    
                    $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                    ->where(function($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                              ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                    
                    $ex_s_data = [
                        'status' => $status->status,
                        'colorCode' => $status->code,
                    ];
                    $ex_statusData[] = $ex_s_data;
                    
                    $s_data = [
                        'status' => $status->status,
                        'colorCode' => $status->code,
                        'totalTask' => $totalTask,
                    ];
                    $statusData[] = $s_data;
                }
                $user = User::where('id', $uId)->first();
                $a_data = [
                    'name' => $user->name,
                    // 'statusList' => $ex_statusData,
                    'allData' => $statusData,
                ];
                $userStatusReport[] = $a_data;
            }


            $userTsakReport = [];
            foreach ($userIds as $key => $uId) {
                // $totalUserTask = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                //         ->where('tasks.company_id', $companyId)
                //         ->whereNull('task_assignes.deleted_at')
                //         ->whereRaw('FIND_IN_SET(' . $uId . ', task_assignes.members_id)')
                //         ->get();
                $totalUserTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->where(function($query) use ($uId) {
                    $query->where('tasks.user_id', $uId)
                        ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                })
                ->whereNull('task_assignes.deleted_at')
                ->get();
                $taskList = [];
                foreach ($totalUserTask as $key => $tData) {
                    $created_at = date('d-m-Y',strtotime($tData->created_at));
                    $dueDate = date('Y-m-d',strtotime($tData->due_date));
                    $t_data = [
                        'task_id' => $tData->id,
                        'title' => $tData->title,
                        'created_at' => $created_at,
                        'created_at' => $dueDate,
                        'priority' => $tData->priority,
                    ];
                    $taskList[] = $t_data;
                }
                $statusData = [];
                foreach ($statusIds as $key => $sId) {
                    $status = CompanyStatus::where('id', $sId)->first();
                    $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.status', $sId)
                        ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                        
                    $s_data = [
                        'status' => $status->status,
                        'totalTask' => $totalTask,
                    ];
                    $statusData[] = $s_data;
                }
                $totalLowPriorityTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.priority', 0)
                        ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                $totalHighPriorityTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.priority', 1)
                        ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                $totalMediumPriorityTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.priority', 2)
                        ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                $user = User::where('id', $uId)->first();
                $a_data = [
                    'name' => $user->name,
                    'totalUserTask' => count($totalUserTask),
                    'totalLowPriorityTask' => $totalLowPriorityTask,
                    'totalHighPriorityTask' => $totalHighPriorityTask,
                    'totalMediumPriorityTask' => $totalMediumPriorityTask,
                    'statusData' => $statusData,
                    'taskList' => $taskList,
                ];
                $userTsakReport[] = $a_data;
            }
            // dd($userTsakReport);
            $response['userStatusReport'] = $userStatusReport;
            $response['userTsakReport'] = $userTsakReport;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'User report successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User report not found',
                    'data' => []
                ]);
            }
        }

        public function projectReport(Request $request)
        {
            $request->validate([
                'companyID' => 'required',
            ]);
            $userId = Auth::user()->id;
            $companyId = $request->companyID;
            $projectData = [];
            $myProject = Project::orderBy('id', 'DESC')->where('user_id', $userId)->where('company_id', $companyId)->get();
            foreach ($myProject as $key => $value) {
                $ta_data = [
                    'projectId' => $value->id,
                ];
                $projectData[] = $ta_data;
            }
            $assignProject = Project::where('company_id', $companyId)
            ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
            ->get();
            foreach ($assignProject as $key => $value) {
                if(($value->user_id != $userId) && ($value->company_id == $companyId)){
                    $ta_data = [
                        'projectId' => $value->id,
                    ];
                    $projectData[] = $ta_data;
                }
            }
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if($ownerCheck){
                $projectGetData = Project::where('company_id',$companyId)->get();
            } else {
                $projectGetData = Project::whereIn('id',$projectData)->get();
            }
            $totalTask = 0; 
            $totalTaskCompleted = 0; 
            $totalTaskIncompleted = 0; 
            $proList = [];
            foreach($projectGetData as $val){
                $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
                if($ownerCheck){
                    $totalTask += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.project_id', $val->id)
                        ->where('tasks.company_id', $companyId)
                        ->whereNull('task_assignes.deleted_at')
                        ->count();

                    $totalTaskCompleted += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.project_id', $val->id)
                        ->where('tasks.completed', '1')
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
    
                    $totalTaskIncompleted += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.project_id', $val->id)
                        ->where('tasks.completed', '0')
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                } else {
                    $totalTask += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.project_id', $val->id)
                        ->where('tasks.company_id', $companyId)
                        ->where(function($query) use ($userId) {
                            $query->where('tasks.user_id', $userId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();

                    $totalTaskCompleted += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.project_id', $val->id)
                            ->where('tasks.completed', '1')
                            ->where(function($query) use ($userId) {
                                $query->where('tasks.user_id', $userId)
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                            })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
    
                    $totalTaskIncompleted += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.project_id', $val->id)
                        ->where('tasks.completed', '0')
                        ->where(function($query) use ($userId) {
                            $query->where('tasks.user_id', $userId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                }
                



                $startDate = date('Y-m-d',strtotime($val->start_date));
                $endDate = date('Y-m-d',strtotime($val->end_date));
                $lastUpDate = date('d F, Y',strtotime($val->updated_at));
                $clientData = User::find($val->client_id);
                $cratedData = User::where('id',$val->user_id)->first();
                if($cratedData){
                    $createdName = $cratedData->name;
                } else {
                    $createdName = '';
                }
                $taskCompleted = Task::where('project_id', $val->id)->where('completed', '1')->count();
                $taskIncompleted = Task::where('project_id', $val->id)->where('completed', '0')->count();
                $pro_data = [
                    'id' => $val->id,
                    'name' => $val->name,
                    'createdName' => $createdName,
                    'description' => $val->description,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'clientName' => $clientData->name,
                    'lastUpDate' => $lastUpDate,
                    'taskCompleted' => $taskCompleted,
                    'taskIncompleted' => $taskIncompleted,
                ];
                $proList[] = $pro_data;
            }
            // dd($userTsakReport);
            $response['totalProject'] = count($projectData);
            $response['totalTask'] = $totalTask;
            $response['totalTaskCompleted'] = $totalTaskCompleted;
            $response['totalTaskIncompleted'] = $totalTaskIncompleted;
            $response['projectList'] = $proList;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Project report successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project report not found',
                    'data' => []
                ]);
            }
        }

        public function statusReport(Request $request) {
            $request->validate([
                'companyID' => 'required',
                'userID' => 'required',
                'statusID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = $request->userID;
            $statusId = $request->statusID;
            $userIds = explode(',', $userId);
            $statusIds = explode(',', $statusId);
            $userStatusReport = [];
            foreach ($userIds as $key => $uId) {
                $statusData = [];
                foreach ($statusIds as $key => $sId) {
                    $status = CompanyStatus::where('id', $sId)->first();
                    $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.status', $sId)
                        ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                    
                    $s_data = [
                        'status' => $status->status,
                        'colorCode' => $status->code,
                        'totalTask' => $totalTask,
                    ];
                    $statusData[] = $s_data;
                }
                $user = User::where('id', $uId)->first();
                $a_data = [
                    'name' => $user->name,
                    'allData' => $statusData,
                ];
                $userStatusReport[] = $a_data;
            }

            
            
            foreach ($userIds as $key => $uId) {
                $statusTsakReport = [];
                foreach ($statusIds as $key => $sId) {
                    $status = CompanyStatus::where('id', $sId)->first();
                    $totalStatusTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.status', $sId)
                        // ->where(function($query) use ($uId) {
                        //     $query->where('tasks.user_id', $uId)
                        //           ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        // })
                        ->whereNull('task_assignes.deleted_at')
                        ->get();
                    
                        // dd($totalStatusTask);
                    $taskList = [];
                    foreach ($totalStatusTask as $key => $tData) {
                        $created_at = date('d-m-Y',strtotime($tData->created_at));
                        $dueDate = date('Y-m-d',strtotime($tData->due_date));
                        $t_data = [
                            'task_id' => $tData->id,
                            'company_id' => $tData->company_id,
                            'status_id' => $tData->status,
                            'title' => $tData->title,
                            'status' => $status->status,
                            'created_at' => $created_at,
                            'created_at' => $dueDate,
                            'priority' => $tData->priority,
                        ];
                        $taskList[] = $t_data;
                    }
                    $a_data = [
                        'status' => $status->status,
                        'taskList' => $taskList,
                    ];
                    $statusTsakReport[] = $a_data;
                }
            }
            // dd($userTsakReport);
            $response['userStatusReport'] = $userStatusReport;
            $response['statusTsakReport'] = $statusTsakReport;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Status report successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User report not found',
                    'data' => []
                ]);
            }
        }

        public function userPerformanceReport(Request $request)
        {
            $request->validate([
                'companyID' => 'required',
                'userID' => 'required',
            ]);
            $companyId = $request->companyID;
            $userId = $request->userID;
            $userIds = explode(',', $userId);
            $userStatusReport = [];
            foreach ($userIds as $key => $uId) {
                $performanceOnTrack = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    ->where('tasks.completed', 0)
                    ->where('tasks.company_id', $companyId)
                    ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceBeforeTime = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.completed', 1)
                    ->whereDate('tasks.due_date', '>', date('Y-m-d'))
                    ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceDelayed = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.completed', 0)
                    ->whereDate('tasks.due_date', '<', date('Y-m-d'))
                    ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                
                $user = User::where('id', $uId)->first();
                $a_data = [
                    'name' => $user->name,
                    'performanceOnTrack' => $performanceOnTrack,
                    'performanceBeforeTime' => $performanceBeforeTime,
                    'performanceDelayed' => $performanceDelayed,
                ];
                $userStatusReport[] = $a_data;
            }

            // dd($userStatusReport);
            $userTsakReport = [];
            foreach ($userIds as $key => $uId) {
                $totalUserTask = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->get();
                $taskList = [];
                foreach ($totalUserTask as $key => $tData) {
                    $created_at = date('d-m-Y',strtotime($tData->created_at));
                    $dueDate = date('Y-m-d',strtotime($tData->due_date));
                    $t_data = [
                        'task_id' => $tData->id,
                        'title' => $tData->title,
                        'created_at' => $created_at,
                        'created_at' => $dueDate,
                        'status' => CompanyStatus::where('id', $tData->status)->first(),
                        'priority' => $tData->priority,
                    ];
                    $taskList[] = $t_data;
                }
                
                $performanceOnTrack = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.completed', 0)
                    ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceBeforeTime = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.completed', 1)
                    ->whereDate('tasks.due_date', '>', date('Y-m-d'))
                    ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceDelayed = Task::join("task_assignes","task_assignes.task_id","=","tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.completed', 0)
                    ->whereDate('tasks.due_date', '<', date('Y-m-d'))
                    ->where(function($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                  ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                $user = User::where('id', $uId)->first();
                $a_data = [
                    'name' => $user->name,
                    'totalUserTask' => count($totalUserTask),
                    'performanceOnTrack' => $performanceOnTrack,
                    'performanceBeforeTime' => $performanceBeforeTime,
                    'performanceDelayed' => $performanceDelayed,
                    'taskList' => $taskList,
                ];
                $userTsakReport[] = $a_data;
            }
            // dd($userTsakReport);
            $response['userStatusReport'] = $userStatusReport;
            $response['userTsakReport'] = $userTsakReport;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'User report successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User report not found',
                    'data' => []
                ]);
            }
        }

    // public function userReport(Request $request)
    // {
    //     $request->validate([
    //         'companyID' => 'required',
    //         'userID' => 'required',
    //         'statusID' => 'required',
    //     ]);
    //     $userID = $request->get('userID');
    //     $users = explode(',', $userID);
    
    //     $companyID = $request->get('companyID');
    //     $statusId = $request->get('statusId');
    //     $status = explode(',', $statusId);
    //     $userDatas = [];
    //     $tasksAssignedDatas = [];
    //     $completedcounts = [];
    //     $uncompletedcounts = [];
    //     $withstatuses = [];
    
    //     foreach ($users as $user) {
    //         $userData = DB::table('users')
    //             ->where('id', $user)
    //             ->where('company_id', $companyID)
    //             ->first();
    //         $userDatas[] = $userData;
    //     }
    
    //     foreach ($userDatas as $userDat) {
    //         $taskassignees = DB::table('task_assignes')
    //             ->join('tasks', 'tasks.id', 'task_assignes.task_id')
    //             ->where('task_assignes.members_id', $userDat->id)
    //             ->whereNull('task_assignes.deleted_at')
    //             ->whereNull('tasks.deleted_at')
    //             ->get();
    
    //         if ($taskassignees->isNotEmpty()) {
    //             $tasksAssignedDatas[] = $taskassignees;
    //         }
    //     }
    
    //     foreach ($tasksAssignedDatas as $tasksAssignedDatass) {
    //         $userStatuses = [];
    //         foreach ($status as $statuses) {
    //             $withstatus = $tasksAssignedDatass->where('status', $statuses)->values();
    //             if ($withstatus->isNotEmpty()) {
    //                 $userStatuses[] = $withstatus;
    //             }
    //         }
    //         $withstatuses[] = $userStatuses;
    //     }
    
    //     $completedcount = collect($withstatuses)->flatten()->where('completed', '1')->count();
    //     $uncompletedcount = collect($withstatuses)->flatten()->where('completed', '0')->count();
    //     $completedcounts[] = $completedcount;
    //     $uncompletedcounts[] = $uncompletedcount;
    
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'User Report successfully',
    //         'userData' => $userDatas,
    //         'tasksAssignedDatas' => $withstatuses,
    //         'completedcounts' => $completedcounts,
    //         'uncompletedcounts' => $uncompletedcounts
    //     ]);
    // }
    
    // public function projectreport(Request $request){
    //     $companyID = $request->get('companyID');
    //     $projectData = DB::table('projects')
    //                     ->where('company_id', $companyID)
    //                     ->get();
    //     $projectDatacount = $projectData->count();
        
    //     $tasksData = [];
    //     $totalTaskCount = 0; 
    //     $totalTaskCountcompleted = 0; 
    //     $totalTaskCountuncompleted = 0; 
    
    //     foreach($projectData as $projectDatas){
    //         $tasksData[$projectDatas->id] = DB::table('tasks')->where('project_id', $projectDatas->id)->get();
            
            
    //         $totalTaskCountcompleted += $tasksData[$projectDatas->id]->where('completed', '1')->count();
    //         $totalTaskCountuncompleted += $tasksData[$projectDatas->id]->where('completed', '0')->count();
    //         $totalTaskCount += $tasksData[$projectDatas->id]->count();
    //     }
    
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Project Report successfully',
    //         'tasksData' => $tasksData,
    //         'projectDatacount' => $projectDatacount,
    //         'totalTaskCount' => $totalTaskCount, 
    //         'totalTaskCountcompleted' => $totalTaskCountcompleted, 
    //         'totalTaskCountuncompleted' => $totalTaskCountuncompleted, 
    //     ]);
    // }
    
    // public function statusreport(Request $request) {
    //     $userID = $request->get('userID');
    //     $users = explode(',', $userID);
    
    //     $companyID = $request->get('companyID');
    //     $statusId = $request->get('statusId');
    //     $status = explode(',', $statusId);
    
    //     $userDatas = [];
    //     $withstatuses = [];
    
    //     foreach ($users as $user) {
    //         $userData = DB::table('users')
    //             ->where('id', $user)
    //             ->where('company_id', $companyID)
    //             ->first();
    
    //         $userDatas[] = $userData;
    //     }
    
    //     foreach ($userDatas as $userDat) {
    //         $taskassignees = DB::table('task_assignes')
    //             ->join('tasks', 'tasks.id', 'task_assignes.task_id')
    //             ->where('task_assignes.members_id', $userDat->id)
    //             ->whereNull('task_assignes.deleted_at')
    //             ->whereNull('tasks.deleted_at')
    //             ->get();
    
    //         if ($taskassignees->isNotEmpty()) {
    //             $tasksAssignedDatas[] = $taskassignees;
    //         }
    //     }
    //     dd($tasksAssignedDatas);
        
    //     foreach ($tasksAssignedDatas as $tasksAssignedDatass) {
    //     $userStatuses = [];
    //     $userStatusCounts = [];
    
    //     foreach ($status as $statuses) {
    //         $withstatus = $tasksAssignedDatass->where('status', $statuses)->values();
    //             $userStatusCounts[$statuses] = $withstatus->count();
    
    //         if ($withstatus->isNotEmpty()) {
    //             $userStatuses[$statuses] = $withstatus;
    //         } else {
    //             $userStatuses[$statuses] = [];
    //             $userStatusCounts[$statuses] = 0;
    //         }
    //     }
    
    //     $counts[] = $userStatusCounts;
    //     $withstatuses[] = $userStatuses;
    // }
    
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Status Report successfully',
    //         'userData' => $userDatas,
    //         'statusData' => $withstatuses,
    //         'counts' => $counts,
    //     ]);
    // }

}