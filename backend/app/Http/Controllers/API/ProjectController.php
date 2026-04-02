<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\UserFavorite;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use App\Models\Project;
    use App\Models\ProjectFavorite;
    use App\Models\Service;
    use App\Models\TaskType;
    use App\Models\TaskAssigne;
    use App\Models\ProjectTypeList;
    use App\Models\ProjectCheckList;
    use App\Models\Task;
    use App\Models\TaskComment;
    use App\Models\SubTask;
    use App\Models\UserCheckList;
    use App\Models\ProjectClient;
    use App\Models\Note;
    use App\Models\NoteShare;
    use App\Models\CompanyStatus;
    use App\Models\CheckList;
    use App\Models\Team;
    use App\Models\Company;
    use App\Models\Notification;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use App\Models\UserRole;
    use App\Models\UserModel;
    use App\Models\UserPermission;
    use DateTime; 
    use Carbon\Carbon;
    use DB;

    class ProjectController extends Controller{
        
        public function index(Request $request){
            $companyId = $request->companyID;
            $userId = Auth::user()->id;
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if($ownerCheck){
                $project = Project::where('company_id', $companyId)->orderBy('name', 'ASC')->get();
                // $project = Project::select('projects.*', 'project_favorites.is_favorite')
                //         ->leftJoin("project_favorites","project_favorites.project_id","=","projects.id")
                //         ->where('projects.company_id', $companyId)
                //         ->orderBy('project_favorites.is_favorite', 'DESC')
                //         ->whereNull('project_favorites.deleted_at')
                //         ->groupBy('projects.id', 'project_favorites.is_favorite')
                //         ->get();
            } else {
                $project = Project::select('projects.*', 'project_favorites.is_favorite')
                        ->leftJoin("project_favorites","project_favorites.project_id","=","projects.id")
                        ->where('projects.company_id', $companyId)
                        ->where(function($query) use ($userId) {
                            $query->where('projects.user_id', $userId)
                                  ->orWhere('projects.manager_id', $userId);
                            })
                        ->orderBy('project_favorites.is_favorite', 'DESC')
                        ->whereNull('project_favorites.deleted_at')
                        ->groupBy('projects.id', 'project_favorites.is_favorite')
                        ->get();
            //     // $project = Project::orderBy('id', 'DESC')->where('user_id', $userId)->where('company_id', $companyId)->get();
                // $project = Project::select('projects.*')
                // ->where('projects.company_id', $companyId)
                // ->where(function($query) use ($userId) {
                //     $query->where('projects.user_id', $userId)
                //           ->orWhere('projects.manager_id', $userId);
                // })
                // ->get();
            }
            // code change by jeet for listing on 16-8-24 start
            //   if($ownerCheck){
            //     // $project = Project::orderBy('id', 'DESC')->where('company_id', $companyId)->get();
            //     $project = Project::select('projects.*', 'project_favorites.is_favorite')
            //                 ->leftJoin("project_favorites", "project_favorites.project_id", "=", "projects.id")
            //                 ->leftJoin("task_assignes", "task_assignes.project_id", "=", "projects.id")
            //                 ->where(function ($query) use ($userId) {
            //                     $query->whereRaw('FIND_IN_SET(' . $userId . ', task_assignes.members_id)')
            //                           ->orWhere('projects.user_id', $userId); // Include projects you created
            //                 })
            //                 ->where('projects.company_id', $companyId)
            //                 ->orderBy('project_favorites.is_favorite', 'DESC')
            //                 ->whereNull('project_favorites.deleted_at')
            //                 ->groupBy('projects.id', 'project_favorites.is_favorite')
            //                 ->get();

            // } else {
            //     // $project = Project::orderBy('id', 'DESC')->where('user_id', $userId)->where('company_id', $companyId)->get();
            //     $project = Project::select('projects.*', 'project_favorites.is_favorite')
            //                 ->leftJoin("project_favorites", "project_favorites.project_id", "=", "projects.id")
            //                 ->leftJoin("task_assignes", "task_assignes.project_id", "=", "projects.id")
            //                 ->where(function ($query) use ($userId) {
            //                     $query->whereRaw('FIND_IN_SET(' . $userId . ', task_assignes.members_id)')
            //                           ->orWhere('projects.user_id', $userId) // Include projects you created
            //                           ->orWhere('projects.manager_id', $userId); // Include projects where you are the manager
            //                 })
            //                 ->where('projects.company_id', $companyId)
            //                 ->orderBy('project_favorites.is_favorite', 'DESC')
            //                 ->whereNull('project_favorites.deleted_at')
            //                 ->groupBy('projects.id', 'project_favorites.is_favorite')
            //                 ->get();
            // }
            // code change by jeet for listing on 16-8-24 end
            $CreatedByMeData = [];
            foreach ($project as $key => $value) {
                $totalTaskList = Task::where('project_id', $value->id)->get();
                $completTaskList = Task::where('completed', 1)->where('project_id', $value->id)->count();
                
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
                $cratedData = User::where('id',$value->user_id)->first();
                if($cratedData){
                    $createdName = $cratedData->name;
                } else {
                    $createdName = '';
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
                    'createdName' => $createdName,
                    'description' => $value->description,
                    'service_id' => $value->service_id,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'clientName' => $clientName,
                    'status' => $status,
                    'lastUpDate' => $lastUpDate,
                    'is_favorite' => $is_favorite,
                    'totalTask' => count($totalTaskList),
                    'completTask' => $completTaskList,
                    'total_cost' => $value->total_cost,
                ];
                $CreatedByMeData[] = $pro_data;
                
            }

            // $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            // if($ownerCheck){
            //     // $project = Project::orderBy('id', 'DESC')->where('company_id', $companyId)->get();
            //     $project = Project::select('projects.*', 'project_favorites.is_favorite')
            //             ->leftJoin("project_favorites","project_favorites.project_id","=","projects.id")
            //             ->where('projects.company_id', $companyId)
            //             ->orderBy('project_favorites.is_favorite', 'DESC')
            //             ->groupBy('projects.id', 'project_favorites.is_favorite')
            //             ->whereNull('project_favorites.deleted_at')
            //             ->get();
            // } else {
            //     $project = Project::where('company_id', $companyId)
            //     ->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')
            //     ->get();
            //     // $project = Project::select('projects.*', 'project_favorites.is_favorite')
            //     //         ->leftJoin("project_favorites","project_favorites.project_id","=","projects.id")
            //     //         ->where('projects.company_id', $companyId)
            //     //         ->whereRaw('FIND_IN_SET(' . $userId . ', projects.members_id)')
            //     //         ->orderBy('project_favorites.is_favorite', 'DESC')
            //     //         ->whereNull('project_favorites.deleted_at')
            //     //         ->get();
            // }
            // $AssigneeMeData = [];
            // foreach ($project as $key => $value) {
            //     if(($value->user_id != $userId) && ($value->company_id == $companyId)){
            //         $totalTaskList = Task::where('project_id', $value->id)->get();
            //         $completTaskList = Task::where('completed', 1)->where('project_id', $value->id)->count();
            //         $clientData = ProjectClient::find($value->client_id);
            //         $startDate = date('Y-m-d',strtotime($value->start_date));
            //         $endDate = date('Y-m-d',strtotime($value->end_date));
            //         $lastUpDate = date('d F, Y',strtotime($value->updated_at));
            //         $projectFavorite = ProjectFavorite::where('project_id', $value->id)->where('is_favorite', 1)->first();
            //         if($value->status == 0){
            //             $status = 'Upcoming';
            //         }elseif ($value->status == 1){
            //             $status = 'Today';
            //         }elseif($value->status == 2){
            //             $status = 'OverDue';
            //         }else{
            //             $status = 'Closed';
            //         }
            //         if($projectFavorite){
            //             $is_favorite = 1;
            //         } else {
            //             $is_favorite = 0;
            //         }
                    
            //         $cratedData = User::where('id',$value->user_id)->first();
            //         if($cratedData){
            //             $createdName = $cratedData->name;
            //         } else {
            //             $createdName = '';
            //         }
            //         $clientData = ProjectClient::find($value->client_id);
            //         if($clientData){
            //             $clientName = $clientData->name;
            //         } else {
            //             $clientName = null;
            //         }
            //         $pro_data = [
            //             'id' => $value->id,
            //             'name' => $value->name,
            //             'createdName' => $createdName,
            //             'description' => $value->description,
            //             'service_id' => $value->service_id,
            //             'startDate' => $startDate,
            //             'endDate' => $endDate,
            //             'clientName' => $clientName,
            //             'status' => $status,
            //             'lastUpDate' => $lastUpDate,
            //             'is_favorite' => $is_favorite,
            //             'totalTask' => count($totalTaskList),
            //             'completTask' => $completTaskList,
            //             'total_cost' => $value->total_cost,
            //         ];
            //         $AssigneeMeData[] = $pro_data;
            //     }
            // }

            // $teamData = [];
            // $team = Team::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
            // foreach ($team as $key => $value) {
            //     $ta_data = [
            //         'teamId' => $value->id,
            //     ];
            //     $teamData[] = $ta_data;
            // }
            // $projectAssigneTeamData = [];
            // if($teamData){
            //     foreach ($teamData as $key => $value) {
            //         $teamId = $value['teamId'];
            //         if($value['teamId']){
            //             $projectAssigneTeamData = Project::orderBy('id', 'DESC')->where('company_id', $companyId)->whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
            //         }
            //     }
            // }
            // // dd($projectAssigneTeamData);
            // $teamAssigneeMeData = [];
            // foreach ($projectAssigneTeamData as $key => $value) {
            //     if(($value->user_id != $userId) && ($value->company_id == $companyId)){
            //         $totalTaskList = Task::where('project_id', $value->id)->where('user_id', $userId)->count();
            //         $completTaskList = Task::where('completed', 1)->where('project_id', $value->id)->where('user_id', $userId)->count();
            //         $clientData = ProjectClient::find($value->client_id);
            //         $startDate = date('Y-m-d',strtotime($value->start_date));
            //         $endDate = date('Y-m-d',strtotime($value->end_date));
            //         $lastUpDate = date('d F, Y',strtotime($value->updated_at));
            //         $projectFavorite = ProjectFavorite::where('project_id', $value->id)->where('is_favorite', 1)->first();
            //         if($value->status == 0){
            //             $status = 'Upcoming';
            //         }elseif ($value->status == 1){
            //             $status = 'Today';
            //         }elseif($value->status == 2){
            //             $status = 'OverDue';
            //         }else{
            //             $status = 'Closed';
            //         }
            //         if($projectFavorite){
            //             $is_favorite = 1;
            //         } else {
            //             $is_favorite = 0;
            //         }
            //         $cratedData = User::where('id',$value->user_id)->first();
            //         if($cratedData){
            //             $createdName = $cratedData->name;
            //         } else {
            //             $createdName = '';
            //         }
            //         $pro_data = [
            //             'id' => $value->id,
            //             'name' => $value->name,
            //             'createdName' => $createdName,
            //             'description' => $value->description,
            //             'service_id' => $value->service_id,
            //             'startDate' => $startDate,
            //             'endDate' => $endDate,
            //             'clientName' => $clientData->name,
            //             'status' => $status,
            //             'lastUpDate' => $lastUpDate,
            //             'is_favorite' => $is_favorite,
            //             'totalTask' => $totalTaskList,
            //             'completTask' => $completTaskList,
            //         ];
            //         $teamAssigneeMeData[] = $pro_data;
            //     }
            // }
            // dd($teamAssigneeMeData);
            $response['CreatedByMe'] = $CreatedByMeData;
            // $response['AssigneeMe'] = $AssigneeMeData;
            // $response['TeamAssigneeMe'] = $teamAssigneeMeData;
            
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
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Project list successfully',
                    'add' => $add,
                    'edit' => $edit,
                    'delete' => $delete,
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project data not found',
                    'data' => []
                ]);
            }
        }
        
        public function teamAndMembersList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            $team = Team::where('company_id', $companyID)->get();
            // dd($team);
            $teamData = [];
            foreach ($team as $key => $value) {
                $members_id = explode(",", $value->members_id);
                $pro_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                ];
                $teamData[] = $pro_data;
            }


            $member = User::where('company_id', $companyID)->whereHas(
                'roles', function($q){
                    $q->where('name', 'user');
                }
            )->get();
            $memberData = [];
            $comapnyData = Company::where('id', $companyID)->first();
            $userData = User::where('id', $comapnyData->user_id)->first();
            if($userData){
                $c_data = [
                    'id' => $userData->id,
                    'name' => $userData->name,
                ];
                $memberData[] = $c_data;
            }
            foreach ($member as $key => $value) {
                $m_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                ];
                $memberData[] = $m_data;
            }
            $response['teamData'] = $teamData;
            $response['memberData'] = $memberData;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Team & member list successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Team & member data not found',
                    'data' => []
                ]);
            }
        }
        
        public function membersGet(Request $request){
            $userId = Auth::user()->id;
            $user = User::find($userId);
            $membersUser = User::where('created_by', $user->created_by)->whereHas(
                'roles', function($q){
                    $q->where('position', '5');
                }
            )->get();
            $membersData = [];
            foreach ($membersUser as $key => $value) {
                $m_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                ];
                $membersData[] = $m_data;
            }
            if($membersData){
                return response()->json([
                    'status' => true,
                    'message' => 'Members list successfully',
                    'data' => $membersData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Members data not found',
                    'data' => []
                ]);
            }
        }

        public function managerGet(Request $request){
            $userId = Auth::user()->id;
            $user = User::find($userId);
            $teamLeader = User::where('created_by', $user->created_by)->whereHas(
                'roles', function($q){
                    $q->where('name', 'teamLeader');
                }
            )->get();
            // dd($teamLeader);
            $managerData = [];
            foreach ($teamLeader as $key => $value) {
                $m_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                ];
                $managerData[] = $m_data;
            }
            //dd($managerData);
            if($managerData){
                return response()->json([
                    'status' => true,
                    'message' => 'Manager list successfully',
                    'data' => $managerData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Manager data not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectGet(){
            $id = request('projectID');
            $project = Project::where('id', $id)->first();
            if($project){
                $typeListData = [];
                $checkListData = [];
                $manager_id = explode(",", $project->manager_id);
                $typeList = ProjectTypeList::where('project_id', $project->id)->get();
                if($typeList){
                    foreach ($typeList as $key => $val) {
                        $t_data = [
                            'id' => $val->id,
                            'taskTypeId' => $val->taskTypeId,
                            'remark' => $val->remark,
                            'hours' => $val->hours,
                            'tasktype' => $val->tasktypeTitle,
                            'cost' => $val->cost,
                            'tasktypeDescription' => $val->tasktypeDescription,
                        ];
                        $typeListData[] = $t_data;
                    }
                }
                $checkList = ProjectCheckList::where('project_id', $project->id)->get();
                if($checkList){
                    foreach ($checkList as $key => $val) {
                        $c_data = [
                            'id' => $val->id,
                            'taskTypeId' => $val->taskTypeId,
                            'tasktypechecklist' => $val->tasktypechecklist,
                            'tasktyperemark' => $val->tasktyperemark,
                            'is_document' => $val->is_document,
                        ];
                        $checkListData[] = $c_data;
                    }
                }
                $response = [
                    'id' => $project->id,
                    'name' => $project->name,
                    'description' => $project->description,
                    'total_cost' => $project->total_cost,
                    'start_date' => date('d-m-Y',strtotime($project->start_date)),
                    'end_date' => date('d-m-Y',strtotime($project->end_date)),
                    'membersName' => $project->members_id != 'null' ? $project->members_id : '',
                    'managerName' => User::select('id AS item_id', 'name AS item_text')->whereIn('id',$manager_id)->get(),
                    'service_id' => $project->service_id,
                    'typeListData' => $typeListData,
                    'checkListData' => $checkListData,
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Project get successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Project data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project data not found',
                    'data' => null
                ]);
            }
        }

        public function projectAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'name' => 'required|string',
                    'description' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'manager_id' => 'required',
                    'companyId' => 'required',
                ]);
                $members_id = '';
                $team_id = '';
                $manager_id = '';
                $client_id = '0';
                if($request->name){
                    $checkname = Project::where('company_id', $request->companyId)->where('name', $request->name)->first();
                    if($checkname){
                        return response()->json([
                            'status' => false,
                            'code' => 210,
                            'message' => 'Project name alrady exist',
                        ]); 
                    }
                }
                if (isset($request->is_sender)) {
                    if($request->managers_id){
                        $data = json_decode($request->managers_id, true);
                        $itemIds = array_column($data, 'item_id');
                        $manager_id = implode(",", $itemIds);
                    }
                } else {
                    if($request->managers_id){
                        $data = json_decode($request->managers_id, true);
                        // $itemIds = array_column($data, 'item_id');
                        $manager_id = implode(",", $request->managers_id); 
                    }
                }
                // dd($request->all());
                $startDate = '';
                $endDate = '';
                if($request->start_date){
                    $startfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->start_date));
                    $startDateTime = new DateTime($startfromDate);
                    $startDate = $startDateTime->format("Y-m-d H:i:s");
                }
                if($request->end_date){
                    $endfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->end_date));
                    $endDateTime = new DateTime($endfromDate);
                    $endDate = $endDateTime->format("Y-m-d H:i:s");
                }
               
                $project = [
                    'name' => $request->name,
                    'user_id' => $userId, 
                    'company_id' => $request->companyId, 
                    'description' => $request->description, 
                    'total_cost' => $request->total_cost, 
                    'service_id' => $request->servic_id, 
                    'start_date' => $startDate, 
                    'end_date' => $endDate, 
                    'members_id' => $request->members_id, 
                    'manager_id' => $manager_id,
                    'status' => $request->status ? : '0', 
                ];
                // dd($project);
                $project =  Project::create($project);
                if($project){
                    if($request->taskType){
                        if (isset($request->is_sender)) {
                            $taskTypeData = json_decode($request->taskType, true);
                        } else {
                            // $taskTypeData = $request->taskType;
                            $taskTypeData = json_decode($request->taskType, true);
                        }
                        foreach ($taskTypeData as $key => $val) {
                            if($val['hours']){
                                $hours = $val['hours'];
                            } else {
                                $hours =  0;
                            }
                            
                            $typeList = new ProjectTypeList;
                            $typeList->project_id = $project->id;
                            $typeList->taskTypeId = $val['taskTypeId'];
                            $typeList->remark = $val['remark'];
                            $typeList->tasktypeTitle = $val['tasktype'];
                            $typeList->tasktypeDescription = $val['taskdescription'];
                            $typeList->cost = $val['cost'];
                            $typeList->hours = $hours;
                            $typeList->save();
                        }
                    }
                    
                    if($request->checkList){
                        if (isset($request->is_sender)) {
                            $checkListData = json_decode($request->checkList, true);
                        } else {
                            // $checkListData = $request->checkList;
                            $checkListData = json_decode($request->checkList, true);
                        }
                        foreach ($checkListData as $key => $vals) {
                            $checkList = new ProjectCheckList;
                            $checkList->project_id = $project->id;
                            $checkList->taskTypeId = $vals['tasktypeIdck'];
                            $checkList->tasktypechecklist = $vals['tasktypechecklist'];
                            $checkList->tasktyperemark = $vals['tasktyperemark'];
                            $checkList->is_document = $vals['is_document'];
                            $checkList->save();
                        }
                    }
                    
                    // if($request->members_id || $request->manager_id){
                    //     $data = json_decode($request->members_id, true);
                    //     $itemIds = array_column($data, 'item_id');
                    //     $this->setupNotification($request->companyId, $project->id, $userId, $itemIds, $request->name);
                    // }
                    
                    return response()->json([
                        'status' => true,
                        'message' => 'Project added successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Project added unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function projectEdit(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'projectID' => 'required',
                    'name' => 'required|string',
                    'description' => 'required',
                    'start_date' => 'required',
                    'end_date' => 'required',
                    'manager_id' => 'required',
                ]);
                $manager_id = '';
                $service = Project::find($request->projectID);
                if($request->name){
                    $checkname = Project::where('company_id', $service->company_id)->where('id', '!=' , $request->projectID)->where('name', $request->name)->first();
                    if($checkname){
                        return response()->json([
                            'status' => false,
                            'code' => 210,
                            'message' => 'Project name alrady exist',
                        ]); 
                    }
                }
                if($request->managers_id){
                    $data = json_decode($request->managers_id, true);
                    $itemIds = array_column($data, 'item_id');
                    $manager_id = implode(",", $itemIds);
                }
                
                $startDate = '';
                $endDate = '';
                if($request->start_date){
                    $startfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->start_date));
                    $startDateTime = new DateTime($startfromDate);
                    $startDate = $startDateTime->format("Y-m-d H:i:s");
                }
                if($request->end_date){
                    $endfromDate = trim(preg_replace('/\s*\([^)]*\)/', '', $request->end_date));
                    $endDateTime = new DateTime($endfromDate);
                    $endDate = $endDateTime->format("Y-m-d H:i:s");
                }
                $project = Project::find($request->projectID);
                $project->name = $request->name;
                $project->description = $request->description;
                $project->service_id = $request->servic_id;
                $project->total_cost = $request->total_cost;
                $project->start_date = $startDate;
                $project->end_date = $endDate;
                $project->members_id = $request->members_id;
                $project->manager_id = $manager_id;
                $project->save();
                if($project){
                    if (!empty($request->taskType)) {
                        $taskTypeData = json_decode($request->taskType, true);
                    
                        if (!empty($taskTypeData) && is_array($taskTypeData)) {
                            $oldTypeList = ProjectTypeList::where('project_id', $project->id)->get();
                            $oldTypeCount = count($oldTypeList);
                            $newTypeCount = count($taskTypeData);
                    
                            // Collect old type IDs to compare later for deletion
                            $oldTypeIds = $oldTypeList->pluck('id')->toArray();
                            $updatedTypeIds = [];
                    
                            // Update existing ProjectTypeList entries or create new ones
                            foreach ($taskTypeData as $val) {
                                $hours = isset($val['hours']) ? $val['hours'] : 0;
                    
                                // Use project_id and taskTypeId to find or create a new entry
                                $typeList = ProjectTypeList::firstOrNew([
                                    'project_id' => $project->id,
                                    'taskTypeId' => $val['taskTypeId']
                                ]);
                    
                                // Update attributes
                                $typeList->remark = $val['remark'];
                                $typeList->tasktypeTitle = $val['tasktype'];
                                $typeList->tasktypeDescription = $val['tasktypeDescription'];
                                $typeList->cost = $val['cost'];
                                $typeList->hours = $hours;
                                $typeList->save();
                    
                                // Collect updated type IDs
                                $updatedTypeIds[] = $typeList->id;
                            }
                    
                            // Delete extra old types if new type count is less
                            if ($oldTypeCount > $newTypeCount) {
                                ProjectTypeList::where('project_id', $project->id)
                                    ->whereNotIn('id', $updatedTypeIds)
                                    ->delete();
                            }
                    
                            // If there are more new types, the above loop already handles adding them.
                        }
                    }


                    
                    if(!empty($request->checkList)){
                        if (isset($request->is_sender)) {
                            $checkListData = json_decode($request->checkList, true);
                        } else {
                            // $checkListData = $request->checkList;
                            $checkListData = json_decode($request->checkList, true);
                        }
                        if(!empty($checkListData) && is_array($checkListData)){
                            $existingCheckList = ProjectCheckList::where('project_id', $project->id)->get();
                            $existingIds = $existingCheckList->pluck('id')->toArray();
                            $newIds = [];
                            
                            foreach ($checkListData as $key => $vals) {
                                if (isset($vals['id']) && in_array($vals['id'], $existingIds)) {
                                    $checkList = ProjectCheckList::find($vals['id']);
                                    $newIds[] = $vals['id']; // Add this ID to the list of new IDs
                                } else {
                                    // dd($project->id);
                                    $checkList = new ProjectCheckList;
                                    $checkList->project_id = $project->id;
                                }
                        
                                $checkList->taskTypeId = $vals['taskTypeId'] ?? null;
                                $checkList->tasktypechecklist = $vals['tasktypechecklist'] ?? '';
                                $checkList->tasktyperemark = $vals['tasktyperemark'] ?? '';
                                $checkList->is_document = $vals['is_document'] ?? 'No Document Required';
                                $checkList->save();
                                
                                if (!isset($vals['id'])) {
                                    $newIds[] = $checkList->id;
                                }
                            }
                        
                            $idsToDelete = array_diff($existingIds, $newIds);
                            ProjectCheckList::whereIn('id', $idsToDelete)->delete();
                        } else {
                            ProjectCheckList::where('project_id', $project->id)->delete();
                        }
                        
                    }
                    return response()->json([
                        'status' => true,
                        'message' => 'Project edited successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Project edited unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function projectDelete(Request $request){
            $request->validate([
                'projectID' => 'required',
            ]);
            $project = Project::where('id', $request->projectID)->first();
            if($project){
                $project->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Project deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project deleted unsuccessfully',
                ]);
            }
        }
        
        public function projectFavorite(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'projectID' => 'required',
                    'is_favorite' => 'required',
                ]);
                $project = ProjectFavorite::where('project_id', $request->projectID)->where('user_id', $userId)->first();
                if($project){
                    $project->is_favorite = $request->is_favorite;
                    $project->save();
                    return response()->json([
                        'status' => true,
                        'message' => 'Project favorite change successfully',
                    ]);
                } else {
                    $project = [
                        'user_id' => $userId,
                        'project_id' => $request->projectID, 
                        'is_favorite' => $request->is_favorite, 
                    ];
                    $project =  projectFavorite::create($project);
                    return response()->json([
                        'status' => true,
                        'message' => 'Project favorite change successfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function companyList(Request $request){
            $company = Company::orderBy('id', 'DESC')->where('user_id', Auth::user()->id)->get();
            $userId = Auth::user()->id;
            $companyData = [];
            $userData = User::where('id', $userId)->first();
            // dd($userData);
            if($userData){
                $companyOne = Company::orderBy('id', 'DESC')->where('id', $userData->company_id)->first();
                if($companyOne){
                    $member = User::where('company_id', $companyOne->id)->whereHas(
                        'roles', function($q){
                            $q->where('name', 'user');
                        }
                    )->count();
                    if($companyOne->logo){
                        $profile = asset('public/images/company/'. $companyOne->logo);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    $pro1_data = [
                        'id' => $companyOne->id,
                        'name' => $companyOne->name,
                        'email_id' => $companyOne->email_id,
                        'phone_no' => $companyOne->phone_no,
                        'address' => $companyOne->address,
                        'description' => $companyOne->description,
                        'created_at' => date('d M Y',strtotime($companyOne->created_at)),
                        'project' => Project::orderBy('id', 'DESC')->where('company_id', $companyOne->id)->count(),
                        'task' => Task::orderBy('id', 'DESC')->where('company_id', $companyOne->id)->count(),
                        'members' => $member,
                        'logo' => $profile,
                    ];
                    $companyData[] = $pro1_data;
                }
            }
            foreach ($company as $key => $value) {
                $member = User::where('company_id', $value->id)->whereHas(
                    'roles', function($q){
                        $q->where('name', 'user');
                    }
                )->count();
                if($value->logo){
                    $profile = asset('public/images/company/'. $value->logo);
                } else {
                    $profile = asset('public/images/user_avatar.png');
                }
                if($companyOne->id != $value->id){
                    $pro_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'email_id' => $value->email_id,
                        'phone_no' => $value->phone_no,
                        'address' => $value->address,
                        'description' => $value->description,
                        'created_at' => date('d M Y',strtotime($value->created_at)),
                        'project' => Project::orderBy('id', 'DESC')->where('company_id', $value->id)->count() ,
                        'task' => Task::orderBy('id', 'DESC')->where('company_id', $value->id)->count(),
                        'members' => $member,
                        'logo' => $profile,
                    ];
                    $companyData[] = $pro_data;
                }
            }
            if($companyData){
                return response()->json([
                    'status' => true,
                    'message' => 'Company list successfully',
                    'data' => $companyData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => []
                ]);
            }
        }
        
        public function companyFounderList(Request $request){
            $company = Company::orderBy('id', 'DESC')->where('user_id', Auth::user()->id)->get();
            $userId = Auth::user()->id;
            $founderData = [];
            $companyData = [];
            $userData = User::where('id', $userId)->first();
            $founderOne = Company::orderBy('id', 'DESC')->where('user_id', $userId)->get();
            if($founderOne){
                foreach ($founderOne as $key => $val) {
                    $member = User::where('company_id', $val->id)->whereHas(
                        'roles', function($q){
                            $q->where('name', 'user');
                        }
                    )->count();
                    if($val->logo){
                        $profile = asset('public/images/company/'. $val->logo);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    $pro1_data = [
                        'id' => $val->id,
                        'name' => $val->name,
                        'email_id' => $val->email_id,
                        'phone_no' => $val->phone_no,
                        'address' => $val->address,
                        'description' => $val->description,
                        'created_at' => date('d M Y',strtotime($val->created_at)),
                        'project' => Project::orderBy('id', 'DESC')->where('company_id', $val->id)->count(),
                        'task' => Task::orderBy('id', 'DESC')->where('company_id', $val->id)->count(),
                        'members' => $member,
                        'logo' => $profile,
                    ];
                    $founderData[] = $pro1_data;
                }
            }
            if($userData){
                $sharedCompany = Company::orderBy('id', 'DESC')->where('user_id', '!=' ,$userId)->where('id', $userData->company_id)->get();
                foreach ($sharedCompany as $key => $value) {
                    $member = User::where('company_id', $value->id)->whereHas(
                        'roles', function($q){
                            $q->where('name', 'user');
                        }
                    )->count();
                    if($value->logo){
                        $profile = asset('public/images/company/'. $value->logo);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    // if($companyOne->id != $value->id){
                        $pro_data = [
                            'id' => $value->id,
                            'name' => $value->name,
                            'email_id' => $value->email_id,
                            'phone_no' => $value->phone_no,
                            'address' => $value->address,
                            'description' => $value->description,
                            'created_at' => date('d M Y',strtotime($value->created_at)),
                            'project' => Project::orderBy('id', 'DESC')->where('company_id', $value->id)->count() ,
                            'task' => Task::orderBy('id', 'DESC')->where('company_id', $value->id)->count(),
                            'members' => $member,
                            'logo' => $profile,
                        ];
                        $companyData[] = $pro_data;
                    // }
                }
            }
            $response['founderCompany'] = $founderData;
            $response['sharedCompany'] = $companyData;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Company list successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => []
                ]);
            }
        }

        public function companyGet(){
            $id = request('companyID');
            $company = Company::where('id', $id)->first();
            if($company){
                $response = [
                    'id' => $company->id,
                    'name' => $company->name,
                    'email_id' => $company->email_id,
                    'phone_no' => $company->phone_no,
                    'address' => $company->address,
                    'description' => $company->description,
                    'logo' => asset('images/company/'. $company->logo),
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Company data successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Company data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => []
                ]);
            }
        }

        public function companyAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'name' => 'required|string',
                    'email_id' => 'required|string|email',
                    'phone_no' => 'required',
                    'address' => 'required',
                    // 'logo' => 'required',
                    'description' => 'required',
                ]);
                // if(isset($request->logo) && !empty($request->logo)){
                if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
                    $imageName = mt_rand(10000000000,99999999999).'.'.$request->logo->extension();  
                    $request->logo->move(public_path('images/company'), $imageName);
                    $company = [
                        'name' => $request->name,
                        'user_id' => $userId, 
                        'email_id' => $request->email_id, 
                        'phone_no' => $request->phone_no, 
                        'address' => $request->address,
                        'description' => $request->description, 
                        'logo'=>$imageName,
                    ];
                    $company = Company::create($company);
                }else {
                    $company = [
                        'name' => $request->name, 
                        'user_id' => $userId,
                        'email_id' => $request->email_id, 
                        'phone_no' => $request->phone_no, 
                        'address' => $request->address,
                        'description' => $request->description, 
                    ];
                    $company =  Company::create($company);
                }
                if($company){
                    $processColor = '#78D8CE';
                    list($sR, $sG, $sB) = sscanf($processColor, "#%02x%02x%02x");
                    $sA = '0.2';
                    $processRgbColor = 'rgba('.$sR. ', ' .$sG. ', ' .$sB. ', ' . $sA .')';
                    
                    $closedColor = '#8BD878';
                    list($cR, $cG, $cB) = sscanf($closedColor, "#%02x%02x%02x");
                    $cA = '0.2';
                    $closedRgbColor = 'rgba('.$cR. ', ' .$cG. ', ' .$cB. ', ' . $cA .')';

                    $pendingColor = '#FF9292';
                    list($pR, $pG, $pB) = sscanf($pendingColor, "#%02x%02x%02x");
                    $pA = '0.2';
                    $pendingRgbColor = 'rgba('.$pR. ', ' .$pG. ', ' .$pB. ', ' . $pA .')';


                    $rejectedColor = '#CBA1F5';
                    list($rR, $rG, $rB) = sscanf($rejectedColor, "#%02x%02x%02x");
                    $rA = '0.2';
                    $rejectedRgbColor = 'rgba('.$rR. ', ' .$rG. ', ' .$rB. ', ' . $rA .')';


                    $underColor = '#FFDB77';
                    list($uR, $uG, $uB) = sscanf($underColor, "#%02x%02x%02x");
                    $uA = '0.2';
                    $underRgbColor = 'rgba('.$uR. ', ' .$uG. ', ' .$uB. ', ' . $uA .')';

                    $onHoldColor = '#70D8FF';
                    list($oR, $oG, $oB) = sscanf($onHoldColor, "#%02x%02x%02x");
                    $oA = '0.2';
                    $onHoldRgbColor = 'rgba('.$oR. ', ' .$oG. ', ' .$oB. ', ' . $oA .')';
                    
                    $newColor = '#EC0707';
                    list($oR, $oG, $oB) = sscanf($newColor, "#%02x%02x%02x");
                    $oA = '0.2';
                    $newRgbColor = 'rgba('.$oR. ', ' .$oG. ', ' .$oB. ', ' . $oA .')';

                    

                    $statusData = [
                        [
                            "company_id" => $company->id,
                            "status" => "Process", 
                            "code" => $processRgbColor
                        ], 
                        [ 
                            "company_id" => $company->id, 
                            "status" => "Closed", 
                            "code" => $closedRgbColor
                        ],
                        [ 
                            "company_id" => $company->id, 
                            "status" => "Pending", 
                            "code" => $pendingRgbColor
                        ],
                        [
                            "company_id" => $company->id, 
                            "status" => "Rejected", 
                            "code" => $rejectedRgbColor
                        ],
                        [
                            "company_id" => $company->id, 
                            "status" => "In Review", 
                            "code" => $underRgbColor
                        ],
                        [
                            "company_id" => $company->id, 
                            "status" => "On-hold", 
                            "code" => $onHoldRgbColor
                        ],
                        [
                            "company_id" => $company->id, 
                            "status" => "New Task", 
                            "code" => $newRgbColor
                        ]
                    ];
                    foreach ($statusData as $key => $val) {
                        $status = [
                            'company_id' => $val['company_id'], 
                            'status' => $val['status'], 
                            'code' => $val['code'], 
                        ];
                        $status = CompanyStatus::create($status);
                    }
                    return response()->json([
                        'status' => true,
                        'message' => 'Company added successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Company added unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function companyEdit(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'companyID' => 'required',
            ]);
            $company = Company::find($request->companyID);
            if($request->hasFile('logo') && $request->file('logo')->isValid()){
                $imageName = mt_rand(10000000000,99999999999).'.'.$request->logo->extension();  
                $request->logo->move(public_path('images/company'), $imageName);
                $company = Company::find($request->companyID);
                $company->name = $request->name;
                $company->user_id = Auth::id();
                $company->phone_no = $request->phone_no;
                $company->address = $request->address;
                $company->description = $request->description;
                $company->logo = $imageName;
                $company->save();
            }else {
                $company = Company::find($request->companyID);
                $company->name = $request->name;
                $company->user_id = Auth::id();
                $company->phone_no = $request->phone_no;
                $company->address = $request->address;
                $company->description = $request->description;
                $company->save();
            }
            if($company){
                return response()->json([
                    'status' => true,
                    'message' => 'Company edited successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company edited unsuccessfully',
                ]);
            }
        }

        public function companyDelete(Request $request){
            $request->validate([
                'companyID' => 'required',
            ]);
            $company = Company::where('id', $request->companyID)->first();
            if($company){
                $company->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Company deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company deleted unsuccessfully',
                ]);
            }
        }
        
        public function companyDetails(){
            $companyID = request('companyID');
            $company = Company::where('id', $companyID)->first();
            if($company){

                // Member

                $member = User::where('company_id', $companyID)->whereHas(
                    'roles', function($q){
                        $q->where('name', 'user');
                    }
                )->take(5)->get();
                $memberData = [];
                $comapnyData = Company::where('id', $companyID)->first();
                $userData = User::where('id', $comapnyData->user_id)->first();
                if($userData){
                    if($userData->profile){
                        $profile = asset('public/images/profilePhoto/'. $userData->profile);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    if($userData->reportingTo){
                        $reportingTo = User::where('id', $userData->reportingTo)->first();
                        if($reportingTo){
                            $toName = $reportingTo->name;
                        } else {
                            $toName = null;
                        }
                        
                    } else {
                        $toName = null;
                        $reportingTo = null;
                    }
                    if($userData->assignRole){
                        $assignRole = UserRole::where('id', $userData->assignRole)->first();
                        if($assignRole){
                            $assignName = $assignRole->name;
                        } else {
                            $assignName = null;
                        }
                        
                    } else {
                        $assignName = null;
                        $assignRole = null;
                    }
                    $c_data = [
                        'id' => $userData->id,
                        'name' => $userData->name,
                        'email' => $userData->email,
                        'phone_no' => $userData->phone_no,
                        'designation' => $userData->designation,
                        'reportingTo' => $toName,
                        'assignRole' => $assignName,
                        'gender' => $userData->gender,
                        'dob' => date('d-m-Y',strtotime($userData->dob)),
                        'profile' => $profile,
                    ];
                    $memberData[] = $c_data;
                }
                foreach ($member as $key => $value) {
                    if($value->id != $userData->id){
                        if($value->profile){
                            $profile = asset('public/images/profilePhoto/'. $value->profile);
                        } else {
                            $profile = asset('public/images/user_avatar.png');
                        }
                        if($value->reportingTo){
                            $reportingTo = User::where('id', $value->reportingTo)->first();
                            if($reportingTo){
                                $toName = $reportingTo->name;
                            } else {
                                $toName = null;
                            }
                            
                        } else {
                            $toName = null;
                            $reportingTo = null;
                        }
                        if($value->assignRole){
                            $assignRole = UserRole::where('id', $value->assignRole)->first();
                            if($assignRole){
                                $assignName = $assignRole->name;
                            } else {
                                $assignName = null;
                            }
                        } else {
                            $assignName = null;
                            $assignRole = null;
                        }
                        $m_data = [
                            'id' => $value->id,
                            'name' => $value->name,
                            'email' => $value->email,
                            'phone_no' => $value->phone_no,
                            'designation' => $value->designation,
                            'reportingTo' => $toName,
                            'assignRole' => $assignName,
                            'gender' => $value->gender,
                            'dob' => date('d-m-Y',strtotime($value->dob)),
                            'profile' => $profile,
                        ];
                        $memberData[] = $m_data;
                    }
                }

                // Role
                
                $role = UserRole::where('company_id', $companyID)->get();
                $roleData = [];
                foreach ($role as $key => $value) {
                    $pro_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'permission' => UserPermission::where('user_role_id', $value->id)->get(),
                    ];
                    $roleData[] = $pro_data;
                }

                // status

                $status = CompanyStatus::where('company_id', $companyID)->get();
                $statusData = [];
                foreach ($status as $key => $value) {
                    $pro_data = [
                        'id' => $value->id,
                        'status' => $value->status,
                        'code' => $value->code,
                        'is_action' => $value->is_action,
                    ];
                    $statusData[] = $pro_data;
                }

                // client

                $client = ProjectClient::where('company_id', $companyID)
                    ->latest() // Orders by created_at in descending order by default
                    ->take(6) // Limits the number of results to 4
                    ->get();
                $clientData = [];
                foreach ($client as $key => $value) {
                    if($value->profile){
                        $profile = asset('public/images/profilePhoto/'. $value->profile); 
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    $pro_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'email' => $value->email,
                        'phone_no' => $value->phone_no,
                        'company_name' => $value->company_name,
                        'profile' => $profile,
                        'gender' => $value->gender,
                    ];
                    $clientData[] = $pro_data;
                }

                // services

                $service = Service::where('company_id', $companyID)->get();
                $serviceData = [];
                foreach ($service as $key => $value) {
                    $pro_data = [
                        'id' => $value->id,
                        'title' => $value->title,
                    ];
                    $serviceData[] = $pro_data;
                }
                
                
                // team

                $team = Team::where('company_id', $companyID)->get();
                $teamData = [];
                foreach ($team as $key => $value) {
                    $members_id = explode(",", $value->members_id);
                    $words = explode(" ", $value->name);
                    $acronym = "";
                    foreach ($words as $key => $w) {
                        if($key <= 1){
                            $acronym .= mb_substr($w, 0, 1);
                        }
                    
                    }
                    $pro_data = [
                        'id' => $value->id,
                        'title' => $value->name,
                        'totalMember' => User::select('id', 'name')->whereIn('id',$members_id)->count(),
                        'setWords' => strtoupper($acronym),
                    ];
                    $teamData[] = $pro_data;
                }

                $response = [
                    'members' => $memberData,
                    'roles' => $roleData,
                    'status' => $statusData,
                    'clients' => $clientData,
                    'services' => $serviceData,
                    'team' => $teamData,
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Company Details successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Company data not found',
                        'data' => null
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => null
                ]);
            }
        }
        
        public function comapnyChange(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'companyID' => 'required',
            ]);
            $user = User::where('id', $userId)->first();
            if($user){
                // $user->company_id = $request->companyID;
                // $user->save();
                return response()->json([
                    'status' => true,
                    'message' => 'Company change successfully',
                    'companyID' => $request->companyID,
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function serviceList(Request $request){
            $companyID = $request->companyID;
            $service = Service::where('company_id', $companyID)->get();
            $serviceData = [];
            foreach ($service as $key => $value) {
                $pro_data = [
                    'id' => $value->id,
                    'title' => $value->title,
                ];
                $serviceData[] = $pro_data;
            }
            if($serviceData){
                return response()->json([
                    'status' => true,
                    'message' => 'Service list successfully',
                    'data' => $serviceData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Service data not found',
                    'data' => []
                ]);
            }
        }

        public function serviceGet(Request $request){
            $serviceID = $request->serviceID;
            $service = Service::where('id', $serviceID)->first();
            $response = [
                'id' => $service->id,
                'title' => $service->title,
            ];
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Service get successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Service data not found',
                    'data' => []
                ]);
            }
        }

        public function serviceAdd(Request $request){
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
                $note = Service::create($note);
                if($note){
                    return response()->json([
                        'status' => true,
                        'message' => 'Service added successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Service added unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function serviceEdit(Request $request){
            $request->validate([
                'serviceID' => 'required',
                'title' => 'required',
            ]);
            
            $service = Service::find($request->serviceID);
            $service->title = $request->title;
            $service->save();
            if($service){
                return response()->json([
                    'status' => true,
                    'message' => 'Service Edited successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Service Edited unsuccessfully',
                ]);
            }
        }

        public function serviceDelete(Request $request){
            $request->validate([
                'serviceID' => 'required',
            ]);
            $service = Service::where('id', $request->serviceID)->first();
            if($service){
                $service->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Service deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Service deleted unsuccessfully',
                ]);
            }
        }
        
        public function statusList(Request $request){
            $companyID = $request->companyID;
            $service = CompanyStatus::where('company_id', $companyID)->where('is_active', 0)->get();
            $serviceData = [];
            foreach ($service as $key => $value) {
                $pro_data = [
                    'id' => $value->id,
                    'status' => $value->status,
                    'code' => $value->code,
                    'is_action' => $value->is_action,
                    'is_active' => $value->is_active,
                ];
                $serviceData[] = $pro_data;
            }
            if($serviceData){
                return response()->json([
                    'status' => true,
                    'message' => 'Status list successfully',
                    'data' => $serviceData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status data not found',
                    'data' => []
                ]);
            }
        }
        
        public function mainStatusList(Request $request){
            $companyID = $request->companyID;
            $service = CompanyStatus::where('company_id', $companyID)->get();
            $serviceData = [];
            foreach ($service as $key => $value) {
                $pro_data = [
                    'id' => $value->id,
                    'status' => $value->status,
                    'code' => $value->code,
                    'is_action' => $value->is_action,
                    'is_active' => $value->is_active,
                ];
                $serviceData[] = $pro_data;
            }
            if($serviceData){
                return response()->json([
                    'status' => true,
                    'message' => 'Status list successfully',
                    'data' => $serviceData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status data not found',
                    'data' => []
                ]);
            }
        }

        public function statusGet(Request $request){
            $statusId = $request->statusId;
            $status = CompanyStatus::where('id', $statusId)->first();
            $response = [
                'id' => $status->id,
                'status' => $status->status,
                'code' => $status->code,
            ];
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Status get successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status data not found',
                    'data' => []
                ]);
            }
        }

        public function statusAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'companyId' => 'required',
                    'status' => 'required|string',
                    'code' => 'required',
                ]);
                $companyId = $request->companyId;
                $checkStatus = CompanyStatus::where('company_id', $companyId)->where('status', $request->status)->first();
                if($checkStatus){
                    return response()->json([
                        'status' => false,
                        'message' => 'Status already exists',
                    ]);
                } else {
                    $color = $request->code;
                    list($R, $G, $B) = sscanf($color, "#%02x%02x%02x");
                    $A = '0.2';
                    $rgbColor = 'rgba('.$R. ', ' .$G. ', ' .$B. ', ' . $A .')';
                    $note = [
                        'status' => $request->status, 
                        'company_id' => $request->companyId, 
                        'code' => $rgbColor, 
                        'is_action' => 1, 
                    ];
                    // dd($request->all());
                    $note = CompanyStatus::create($note);
                    if($note){
                        return response()->json([
                            'status' => true,
                            'message' => 'Status added successfully',
                        ]);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Status added unsuccessfully',
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function statusEdit(Request $request){
            $request->validate([
                'statusId' => 'required',
                'status' => 'required|string',
                'code' => 'required',
            ]);
            $service = CompanyStatus::find($request->statusId);
            $checkStatus = CompanyStatus::where('company_id', $service->company_id)->where('id', '!=' , $request->statusId)->where('status', $request->status)->first();
            // dd($checkStatus);
            if($checkStatus){
                return response()->json([
                    'status' => false,
                    'message' => 'Status already exists',
                ]);
            } else {
                $color = $request->code;
                list($R, $G, $B) = sscanf($color, "#%02x%02x%02x");
                $A = '0.2';
                $rgbColor = 'rgba('.$R. ', ' .$G. ', ' .$B. ', ' . $A .')';
                
                $service->status = $request->status;
                $service->code = $rgbColor;
                $service->is_active = 0;
                $service->save();
                if($service){
                    return response()->json([
                        'status' => true,
                        'message' => 'Status Edited successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Status Edited unsuccessfully',
                    ]);
                }
            }
            
        }
        
        public function statusActive(Request $request){
            $request->validate([
                'statusId' => 'required',
                'isActive' => 'required',
            ]);
            $status = CompanyStatus::where('id', $request->statusId)->first();
            if($status){
                $status->is_active = $request->isActive;
                $status->save();
                if($request->isActive == 1){
                    return response()->json([
                        'status' => true,
                        'message' => 'Status inActive successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => true,
                        'message' => 'Status active successfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status active unsuccessfully',
                ]);
            }
        }

        public function statusDelete(Request $request){
            $request->validate([
                'statusId' => 'required',
            ]);
            $status = CompanyStatus::where('id', $request->statusId)->first();
            if($status){
                $status->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Status deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Status deleted unsuccessfully',
                ]);
            }
        }
        
        // team
        
        public function teamList(Request $request){
            $companyID = $request->companyID;
            if (isset($request->userID)) {
                $team = Team::where('company_id', $companyID)->whereRaw('FIND_IN_SET(' . $request->userID . ', members_id)')->get();
            } else {
                $team = Team::where('company_id', $companyID)->get();
            }
            // dd($team);
            $teamData = [];
            foreach ($team as $key => $value) {
                if($value->project_id){
                    $project = Project::where('id', $value->project_id)->first();
                    if($project){
                        $pName = $project->name;
                    } else {
                        $pName = null;
                    }
                } else {
                    $pName = null;
                }
                $members_id = explode(",", $value->members_id);
                if($members_id){
                    $memberData = [];
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
                $pro_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'projectName' => $pName,
                    'members_id' => $memberData,
                ];
                $teamData[] = $pro_data;
            }
            if($teamData){
                return response()->json([
                    'status' => true,
                    'message' => 'Team list successfully',
                    'data' => $teamData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Team data not found',
                    'data' => []
                ]);
            }
        }
        
        public function teamGet(Request $request){
            $teamId = $request->teamId;
            $team = Team::where('id', $teamId)->first();
            if($team){
                $members_id = explode(",", $team->members_id);
                $response = [
                    'id' => $team->id,
                    'name' => $team->name,
                    'project_id' => $team->project_id,
                    'members_id' => User::select('id', 'name')->whereIn('id',$members_id)->get(),
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Team get successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Team data not found',
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
        
        public function getTeamMember(Request $request){
            $teamId = $request->teamId;
            $team = Team::where('id', $teamId)->first();
            if($team){
                $members_id = explode(",", $team->members_id);
                $userData = User::select('id', 'name')->whereIn('id',$members_id)->get();
                foreach ($userData as $key => $value) {
                    $words = explode(" ", $value->name);
                    $acronym = "";
                    foreach ($words as $key => $w) {
                        if($key <= 1){
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
                
                if($memberData){
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

        public function teamAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'companyId' => 'required',
                    'name' => 'required|string',
                    'members_id' => 'required',
                    'projectId' => 'required',
                ]);
                $companyId = $request->companyId;
                $checkTeam = Team::where('company_id', $companyId)->where('name', $request->name)->first();
                if($checkTeam){
                    return response()->json([
                        'status' => false,
                        'message' => 'Team already exists',
                    ]);
                } else {
                    $team = [
                        'name' => $request->name, 
                        'company_id' => $request->companyId,
                        'project_id' => $request->projectId, 
                        // 'members_id' => trim($request->members_id), 
                        'members_id' => str_replace(' ','',$request->members_id), 
                    ];
                    // dd($team);
                    $team = Team::create($team);
                    if($team){
                        $this->setupNotificationByTeam($companyId, $team->id, $userId, $request->members_id, $request->name);
                        return response()->json([
                            'status' => true,
                            'message' => 'Team added successfully',
                        ]);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Team added unsuccessfully',
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function teamEdit(Request $request){
            $request->validate([
                'teamId' => 'required',
                'name' => 'required|string',
                'members_id' => 'required',
                'projectId' => 'required',
            ]);
            $team = Team::find($request->teamId);
            $checkTeam = Team::where('company_id', $team->company_id)->where('id', '!=' , $request->teamId)->where('name', $request->name)->first();
            if($checkTeam){
                return response()->json([
                    'status' => false,
                    'message' => 'Team already exists',
                ]);
            } else {
                $team->project_id = $request->projectId;
                $team->name = $request->name;
                $team->members_id = $request->members_id;
                $team->save();
                if($team){
                    return response()->json([
                        'status' => true,
                        'message' => 'Team Edited successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Team Edited unsuccessfully',
                    ]);
                }
            }
        }
        
        public function teamDelete(Request $request){
            $request->validate([
                'teamId' => 'required',
            ]);
            $team = Team::where('id', $request->teamId)->first();
            if($team){
                $team->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Team deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Team deleted unsuccessfully',
                ]);
            }
        }
        
        public function dashboard(){
            $userId = Auth::user()->id;
            $companyID = request('companyID');
            $company = Company::where('id', $companyID)->first();
            if($company){
                $teamData = [];
                $totalAllTasks = 0;
                $totalAssignedToMeTasks = 0;
                $totalDueTodayTasks = 0;
                $totalPastDueTasks = 0;
                $totalNewTasks = 0;
                $totalClosedTasks = 0;

                $totalLowPriorityTasks = 0;
                $totalMediumPriorityTasks = 0;
                $totalHighPriorityTasks = 0;
                
                $statistics = [];
                $allTask = [];
                $taskAssigneMemberData = TaskAssigne::whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                $myTaskData= [];
                $teamIds = Team::where('company_id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('id')->toArray();
                $taskAssigneTeamData = TaskAssigne::whereIn('team_id', $teamIds)->pluck('task_id')->toArray();
                $assignTask = array_merge($taskAssigneTeamData,$taskAssigneMemberData);
                rsort($assignTask);
                // dd($assignTask);
                $assignTask = array_unique($assignTask);
                foreach ($assignTask as $key => $value) {
                    // if($key < 5){
                        $taskList = Task::orderBy('id', 'DESC')->where('id', $value)->where('company_id', $companyID);
                        $taskList = $taskList->get();
                        $ta_data = [
                            'allTask' => $taskList,
                        ];
                        $myTaskData[] = $ta_data;
                    // }
                }
                // dd($myTaskData);
                $allTask = [];
                foreach ($myTaskData as $key2 => $item) {
                    $tasksCollection = $item['allTask'];
                        foreach ($tasksCollection as $key1 => $value) {
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
                                'memberData' => $memberData,
                                'due_date' => $dueDate,
                                'created_at' => $created_at,
                            ];
                            $allTask[] = $all_data;
                            
                        }
                    
                }
                
                
                // project list

                $projectGetData = [];
                $ownerCheck = Company::where('id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
                if($ownerCheck){
                    $projectData = Project::orderBy('id', 'DESC')->where('company_id', $companyID)->limit(5)->get();
                } else {
                    $projectData = Project::where('company_id', $companyID)
                        ->where(function($query) use ($userId) {
                            $query->where('user_id', $userId)
                                  ->orWhereRaw('FIND_IN_SET(?, members_id)', [$userId]);
                        })
                        ->orderBy('id', 'DESC')
                        ->limit(5)
                        ->get();
                }
                if($projectData){
                    foreach ($projectData as $key => $PVal) {
                        $totalTaskList = Task::where('project_id', $PVal->id)->where('user_id', $userId)->with(['assignees'])->get();
                        $completTaskList = Task::where('completed', 1)->where('project_id', $PVal->id)->where('user_id', $userId)->count();
                        
                        $startDate = date('d-m-Y',strtotime($PVal->start_date));
                        $endDate = date('d-m-Y',strtotime($PVal->end_date));
                        $lastUpDate = date('d F, Y',strtotime($PVal->updated_at));
                        $projectFavorite = ProjectFavorite::where('project_id', $PVal->id)->where('is_favorite', 1)->first();
                        if($PVal->status == 0){
                            $status = 'Upcoming';
                        }elseif ($PVal->status == 1){
                            $status = 'Today';
                        }elseif($PVal->status == 2){
                            $status = 'OverDue';
                        }else{
                            $status = 'Closed';
                        }
                        if($projectFavorite){
                            $is_favorite = 1;
                        } else {
                            $is_favorite = 0;
                        }
                        $cratedData = User::where('id',$PVal->user_id)->first();
                        if($cratedData){
                            $createdName = $cratedData->name;
                        } else {
                            $createdName = '';
                        }
                        $clientData = ProjectClient::find($PVal->client_id);
                        if($clientData){
                            $clientName = $clientData->name;
                        } else {
                            $clientName = null;
                        }
                        $taskCost = 0;
                        $setData = 0;
                        // dd($totalTaskList);
                        if ($totalTaskList) {
                            foreach ($totalTaskList as $key => $task) {
                                $checkList = UserCheckList::where('task_id', $task->id)->get();
                                foreach ($checkList as $val) {
                                    if($val->user_id){
                                        $userData = User::where('id',$val->user_id)->first();
                                        if($userData->hour_per_cost){
                                            $userHourCost = $userData->hour_per_cost;
                                            $userHour = $val->user_hour;
                                            $countCost = $userHourCost * $userHour;
                                            // $set_data = [
                                            //     'totalUseCost' => $countCost,
                                            // ];
                                            $setData += $countCost;
                                        }
                                    }
                                }
                            }
                        }
                        // dd($setData);
                        $totalHourCost = 0;
                        if (is_numeric($PVal->total_cost)) {
                            $totalHourCost = $PVal->total_cost - $setData;
                        }
                        
                        $pro_data = [
                            'id' => $PVal->id,
                            'name' => $PVal->name,
                            'createdName' => $createdName,
                            'description' => $PVal->description,
                            'projectCost' => $PVal->total_cost,
                            'startDate' => $startDate,
                            'endDate' => $endDate,
                            'clientName' => $clientName,
                            'status' => $status,
                            'lastUpDate' => $lastUpDate,
                            'is_favorite' => $is_favorite,
                            'totalTask' => count($totalTaskList),
                            'completTask' => $completTaskList,
                            'useByHourCost' => $setData,
                            'totalHourCost' => $totalHourCost,
                        ];
                        $projectGetData[] = $pro_data;
                    }
                }                
                
                
                
                // note list
                if($ownerCheck){
                    $sahrenote = NoteShare::get(); 
                } else {
                    $sahrenote = NoteShare::where('user_id', $userId)->get(); 
                }
                // dd($sahrenote);
                $shareData = [];
                foreach ($sahrenote as $key => $value) {
                    $paymentDate = now()->toDateString();
                    $sNote = Note::where('id', $value->note_id)->where('company_id', $companyID)->first();
                    if ($sNote) {
                        if ($sNote->is_expiry == 1 && $sNote->expiry_date >= $paymentDate) {
                            $share_data = [
                                'id' => $sNote->id,
                                'title' => $sNote->title,
                                'description' => $sNote->description,
                                'pin' => $sNote->pin,
                                'edited' => $value->edited,
                                'deleted' => $value->deleted,
                                'created_at' => date('d-m-Y', strtotime($value->created_at)),
                            ];
                            $shareData[] = $share_data;
                        } elseif ($sNote->is_expiry == 0) {
                            $share_data = [
                                'id' => $sNote->id,
                                'title' => $sNote->title,
                                'description' => $sNote->description,
                                'pin' => $sNote->pin,
                                'edited' => $value->edited,
                                'deleted' => $value->deleted,
                                'created_at' => date('d-m-Y', strtotime($value->created_at)),
                            ];
                            $shareData[] = $share_data;
                        }
                    }
                }
                
                // Task commnet list
                $commnetData = [];
                if($ownerCheck){
                    $latestCommentedTasksIds = TaskComment::select('task_id')
                        ->orderBy('task_id', 'DESC')
                        ->distinct('task_id')
                        ->take(3)
                        ->pluck('task_id');
                    // dd($latestCommentedTasksIds);
                    $tskCommentList = Task::whereIn('id', $latestCommentedTasksIds)
                          ->where('company_id', $companyID)
                          ->get();
    
                    foreach ($tskCommentList as $key => $value) {
                        $commnetList = TaskComment::select('comment')->where('task_id', $value->id)->orderBy('id', 'desc')->take(3)->get();
                        $comment = [
                            'id' => $value->id,
                            'title' => $value->description,
                            'commnetList' => $commnetList,
                        ];
                        $commnetData[] = $comment;
                    }
                } else{
                    $latestCommentedTasksIds = TaskComment::select('task_id')
                        ->orderBy('task_id', 'DESC')
                        ->where('user_id', $userId)
                        ->distinct('task_id')
                        ->take(3)
                        ->pluck('task_id');
                    // dd($latestCommentedTasksIds);
                    $tskCommentList = Task::whereIn('id', $latestCommentedTasksIds)
                          ->where('company_id', $companyID)
                          ->get();
    
                    foreach ($tskCommentList as $key => $value) {
                        $commnetList = TaskComment::select('comment')->where('task_id', $value->id)->where('user_id', $userId)->orderBy('id', 'desc')->take(3)->get();
                        $comment = [
                            'id' => $value->id,
                            'title' => $value->description,
                            'commnetList' => $commnetList,
                        ];
                        $commnetData[] = $comment;
                    }
                }
                // dd($commnetData);
                $totalCounts = [
                    'totalAllTasks' => $totalAllTasks,
                    'totalClosedTasks' => $totalClosedTasks,
                    'totalNewTasks' => $totalNewTasks,
                    'totalPastDueTasks' => $totalPastDueTasks,
                    'totalAssignedToMeTasks' => $totalAssignedToMeTasks,
                    'totalDueTodayTasks' => $totalDueTodayTasks,

                    'totalLowPriorityTasks' => $totalLowPriorityTasks,
                    'totalMediumPriorityTasks' => $totalMediumPriorityTasks,
                    'totalHighPriorityTasks' => $totalHighPriorityTasks,

                    'statisticsData' => $statistics,
                    
                    'taskList' => $allTask,
                    
                    'projectList' => $projectGetData,
                    
                    'noteList' => $shareData,
                    
                    'latestCommentsList' => $commnetData,
                ];
                
                
                $userData = User::where('id',$userId)->first();

                $projectAdd = 1;
                if($userData){
                    $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',1)->first();
                    if($userModel){
                        $projectAdd = $userModel->add;
                    }
                }

                $taskAdd = 1;
                if($userData){
                    $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',2)->first();
                    if($userModel){
                        $taskAdd = $userModel->add;
                    }
                }

                $discussionAdd = 1;
                if($userData){
                    $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',3)->first();
                    if($userModel){
                        $discussionAdd = $userModel->add;
                    }
                }

                $noteAdd = 1;
                if($userData){
                    $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',4)->first();
                    if($userModel){
                        $noteAdd = $userModel->add;
                    }
                }
                
                $organizationAdd = 1;
                if($userData){
                    $userModel = UserPermission::where('user_role_id',$userData->assignRole)->where('user_model_id',5)->first();
                    if($userModel){
                        $organizationAdd = $userModel->add;
                    }
                }
                $permissions = [
                        'projectAdd' => $projectAdd,
                        'taskAdd' => $taskAdd,
                        'discussionAdd' => $discussionAdd,
                        'noteAdd' => $noteAdd,
                        'organizationAdd' => $organizationAdd,
                        ];

                
                if($totalCounts){
                    return response()->json([
                        'status' => true,
                        'message' => 'Dashboard data successfully',
                        'data' => $totalCounts,
                        'permissions' => $permissions,
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Dashboard data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => []
                ]);
            }
        }
        
        public function dashboardCount(){
            $userId = Auth::user()->id;
            $companyID = request('companyID');
            $company = Company::where('id', $companyID)->first();
            if($company){
                $teamData = [];
                $totalAllTasks = 0;
                $totalAssignedToMeTasks = 0;
                $totalDueTodayTasks = 0;
                $totalPastDueTasks = 0;
                $totalNewTasks = 0;
                $totalClosedTasks = 0;

                $totalLowPriorityTasks = 0;
                $totalMediumPriorityTasks = 0;
                $totalHighPriorityTasks = 0;
                
                $ownerCheck = Company::where('id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
                if($ownerCheck){
                    $ownerTaskListSet = [];
                    $ownerTaskList = Task::orderBy('id', 'DESC')->where('company_id', $companyID)->get();
                    foreach ($ownerTaskList as $key => $value) {
                        $ownerTaskListSet[] = $value->id;
                    }
                    $ta_data = [];
                    $assignTask = array_merge($ownerTaskListSet);
                } else {
                    $teamData = [];
                    $team = Team::orderBy('id', 'DESC')->where('company_id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->get();
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
                    
                    $myTask = Task::where('user_id', $userId)->where('company_id', $companyID)->get();
                    foreach ($myTask as $key => $value) {
                        $taskMyData[] = $value->id;
                    }
    
                    $assignTask = array_merge($taskAssigneTeamData,$taskAssigneMemberData, $taskMyData);
                }
                // dd($assignTask);
                $statistics = [];
                $tasksCount = Task::whereIn('id', array_unique($assignTask))
                ->where('company_id', $companyID)
                ->get();
                 $totalAllTasks = count($tasksCount);
                 
                $totalClosedTasks = $tasksCount->where('completed', 1)->count();
                $totalPastDueTasks = $tasksCount->where('completed', 0)->where('due_date', '<', date('Y-m-d'))->count();
                // $totalNewTasks = $tasksCount->where('created_at', Carbon::today())->count();
                $totalAssignedToMeTasks = $tasksCount->where('user_id', '!=', $userId)->count();
                $totalDueTodayTasks = $tasksCount->filter(function ($task) {
                    return Carbon::parse($task->due_date)->toDateString() === now()->toDateString();
                })->count();
                $totalNewTasks = $tasksCount->filter(function ($task) {
                    return Carbon::parse($task->created_at)->toDateString() === now()->toDateString();
                })->count();
                $totalLowPriorityTasks = $tasksCount->where('priority', 0)->count();
                $totalMediumPriorityTasks = $tasksCount->where('priority', 2)->count();
                $totalHighPriorityTasks = $tasksCount->where('priority', 1)->count();
                // foreach ($tasksCount->take(5) as $task) {
                //     $totalNewTasks++; // Or apply other specific logic here
                // }
                $totalAssignedToMeTasks = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyID)
                    ->whereRaw('FIND_IN_SET(?, task_assignes.members_id)', $userId)
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                
                
                
                $ownerCheck = Company::where('id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
                if($ownerCheck){
                    $year = date('Y');
                    $months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
                    foreach ($months as $key => $val) {
                        $taskCompleted = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->whereYear('tasks.start_date', '=', $year)
                            ->whereMonth('tasks.start_date', $key)
                            ->where('tasks.company_id', $companyID)
                            ->where('tasks.completed', 1)
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $taskIncompleted = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->whereYear('tasks.start_date', '=', $year)
                            ->whereMonth('tasks.start_date', $key)
                            ->where('tasks.company_id', $companyID)
                            ->where('tasks.completed', 0)
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $pro_data = [
                            'name' => $val,
                            'completed' => $taskCompleted,
                            'incompleted' => $taskIncompleted,
                        ];
                        $statistics[] = $pro_data;
                    }
                } else {
                    $year = date('Y');
                    $months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
                    foreach ($months as $key => $val) {
                        $taskCompleted = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->whereYear('tasks.start_date', '=', $year)
                            ->whereMonth('tasks.start_date', $key)
                            ->where('tasks.company_id', $companyID)
                            ->where(function($query) use ($userId) {
                                $query->where('tasks.user_id', $userId)
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                            })
                            ->where('tasks.completed', 1)
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $taskIncompleted = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->whereYear('tasks.start_date', '=', $year)
                            ->whereMonth('tasks.start_date', $key)
                            ->where('tasks.company_id', $companyID)
                            ->where(function($query) use ($userId) {
                                $query->where('tasks.user_id', $userId)
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                            })
                            ->where('tasks.completed', 0)
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $pro_data = [
                            'name' => $val,
                            'completed' => $taskCompleted,
                            'incompleted' => $taskIncompleted,
                        ];
                        $statistics[] = $pro_data;
                    }
                }
                
                $totalCounts = [
                    'totalAllTasks' => $totalAllTasks,
                    'totalClosedTasks' => $totalClosedTasks,
                    'totalNewTasks' => $totalNewTasks,
                    'totalPastDueTasks' => $totalPastDueTasks,
                    'totalAssignedToMeTasks' => $totalAssignedToMeTasks,
                    'totalDueTodayTasks' => $totalDueTodayTasks,

                    'totalLowPriorityTasks' => $totalLowPriorityTasks,
                    'totalMediumPriorityTasks' => $totalMediumPriorityTasks,
                    'totalHighPriorityTasks' => $totalHighPriorityTasks,
                    
                    'statisticsData' => $statistics,
                ];
                if($totalCounts){
                    return response()->json([
                        'status' => true,
                        'message' => 'Dashboard data successfully',
                        'data' => $totalCounts
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Dashboard data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => []
                ]);
            }
        }
        
        
        public function dashboardUserByCount(){
            $userId = Auth::user()->id;
            $companyId = request('companyID');
            $company = Company::where('id', $companyId)->first();
            if($company){
                
                // Partner List
                
                $partnerAllData = [];
                $partnerData = User::where('user_type',3)->where('company_id',$companyId)->get();
                if($partnerData){
                    foreach ($partnerData as $key => $value) {
                        $uId = $value->id;
                        $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.company_id', $companyId)
                            ->where(function ($query) use ($uId) {
                                $query->whereRaw('FIND_IN_SET(?, tasks.responsible_person)', [$uId])
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                            })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $closeTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.company_id', $companyId)
                            ->where('tasks.completed', 1)
                            ->where(function ($query) use ($uId) {
                                $query->whereRaw('FIND_IN_SET(?, tasks.responsible_person)', [$uId])
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                            })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        
                        $ta_data = [
                            'id' => $uId,
                            'name' => $value->name,
                            'allTaskCount' => $totalTask,
                            'closeTaskCount' => $closeTask,
                        ];
                        $partnerAllData[] = $ta_data;
                    }
                }
                
                // Employee List
                
                $employeeAllData = [];
                $employeeData = User::where('user_type',4)->where('company_id',$companyId)->get();
                if($employeeData){
                    foreach ($employeeData as $key => $value) {
                        $uId = $value->id;
                        $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.company_id', $companyId)
                            ->where(function ($query) use ($uId) {
                                $query->whereRaw('FIND_IN_SET(?, tasks.responsible_person)', [$uId])
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                            })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $closeTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.company_id', $companyId)
                            ->where('tasks.completed', 1)
                            ->where(function ($query) use ($uId) {
                                $query->whereRaw('FIND_IN_SET(?, tasks.responsible_person)', [$uId])
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                            })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        
                        $ta_data = [
                            'id' => $uId,
                            'name' => $value->name,
                            'allTaskCount' => $totalTask,
                            'closeTaskCount' => $closeTask,
                        ];
                        $employeeAllData[] = $ta_data;
                    }
                }
                
                // Manager List
                
                $managerAllData = [];
                $managerData = User::where('user_type',2)->where('company_id',$companyId)->get();
                if($managerData){
                    foreach ($managerData as $key => $value) {
                        $uId = $value->id;
                        $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.company_id', $companyId)
                            ->where(function ($query) use ($uId) {
                                $query->whereRaw('FIND_IN_SET(?, tasks.responsible_person)', [$uId])
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                                })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $closeTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                            ->where('tasks.company_id', $companyId)
                            ->where('tasks.completed', 1)
                            ->where(function ($query) use ($uId) {
                                $query->whereRaw('FIND_IN_SET(?, tasks.responsible_person)', [$uId])
                                      ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                            })
                            ->whereNull('task_assignes.deleted_at')
                            ->count();
                        $u_data = [
                            'id' => $uId,
                            'name' => $value->name,
                            'allTaskCount' => $totalTask,
                            'closeTaskCount' => $closeTask,
                        ];
                        $managerAllData[] = $u_data;
                    }
                }
                
                
                $totalCounts = [
                    'totalPartnerData' => $partnerAllData,
                    'totalManagerData' => $managerAllData,
                    'totalEmployeeData' => $employeeAllData,
                ];
                
                if($totalCounts){
                    return response()->json([
                        'status' => true,
                        'message' => 'Dashboard data successfully',
                        'data' => $totalCounts,
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Dashboard data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Company data not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectDetail(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'projectID' => 'required',
            ]);
            $projectID = $request->projectID;
            $project = Project::where('id', $projectID)->first();
            $tasaDatas = [];
            $paymentDate = now()->toDateString();
            if($project){
                $project->manager_id = User::select('id AS item_id', 'name AS item_text')
                         ->where('id', $project->manager_id)
                         ->get();
                $project->start_date = date('d-m-Y',strtotime($project->start_date));
                $project->end_date = date('d-m-Y',strtotime($project->end_date));
                
                $typeListData = [];
                $checkListData = [];
                $typeList = ProjectTypeList::where('project_id', $project->id)->get();
                if($typeList){
                    foreach ($typeList as $key => $val) {
                        $t_data = [
                            'id' => $val->id,
                            'taskTypeId' => $val->taskTypeId,
                            'remark' => $val->remark,
                            'hours' => $val->hours,
                            'tasktype' => $val->tasktypeTitle,
                            'cost' => $val->cost,
                            'tasktypeDescription' => $val->tasktypeDescription,
                        ];
                        $typeListData[] = $t_data;
                    }
                }
                $checkList = ProjectCheckList::where('project_id', $project->id)->get();
                if($checkList){
                    foreach ($checkList as $key => $val) {
                        $c_data = [
                            'id' => $val->id,
                            'taskTypeId' => $val->taskTypeId,
                            'tasktypechecklist' => $val->tasktypechecklist,
                            'tasktyperemark' => $val->tasktyperemark,
                            'is_document' => $val->is_document,
                        ];
                        $checkListData[] = $c_data;
                    }
                }
                
                $companyID = $project->company_id;
                $taskList = Task::orderBy('id', 'DESC')->where('project_id', $project->id)->get();
                foreach ($taskList as $task) {
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
                    $checkData = [];
                    $checkFilter = 0;
                    $checkUserList = DB::table('user_check_lists')
                    ->select('user_id', DB::raw('GROUP_CONCAT(id) as checklist_ids'))
                    ->where('task_id', $task->id)
                    ->whereNull('deleted_at')
                    ->groupBy('user_id')
                    ->get();
                    
                    if($checkUserList){
                        foreach ($checkUserList as $key => $check) {
                            $checkUser = User::where('id', $check->user_id)->first();
                            if( $checkUser){
                                if($checkUser->profile){
                                    $profile = asset('public/images/profilePhoto/'. $checkUser->profile);
                                } else {
                                    $profile = asset('public/images/user_avatar.png');
                                }
                                $pro_data = [
                                    'id' => $checkUser->id,
                                    'name' => $checkUser->name,
                                    'profile' => $profile,
                                ];
                                $memberData[] = $pro_data;
                            }
                        }
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
                                'track_time' => $check->actualtime,
                                'completed' => $check->completed,
                                'completedNote' => $check->completedNote,
                            ];
                            $allCheck[] = $check_data;
                    }
                    $taskData = [
                        'task_id' => $task->id,
                        'start_date' => $startDate,
                        'due_date' => $dueDate,
                        'description' => $task->description,
                        'projectName' => $projectName,
                        'clientName' => $clientName,
                        'createdName' => $createdName,
                        'createdProfile' => $task->user->profile ? asset('public/images/profilePhoto/'. $task->user->profile) : null,
                        'pinTask' => $task->pin,
                        'completed' => $task->completed,
                        'status' => $statusName,
                        'statusId' => $task->status,
                        'priority' => $task->priority,
                        'actual_time' => $task->actual_time,
                        'todayTask' => $todayTask,
                        'overDueTask' => $overDueTask,
                        'upcomingTask' => $upcomingTask,
                        'memberData' => $memberData,
                        'created_at' => $created_at,
                        'checkList' => $allCheck,
                        'remainingtotalCost' => intval($task->remainingtotalCost),
                    ];
                    $tasaDatas[] = $taskData;
                }
                $response['taskList'] = $tasaDatas;
                $response['projectName'] = $project->name;
                $response['projectDtail'] = $project;
                $response['typeListData'] = $typeListData;
                $response['checkListData'] = $checkListData;
                $response['updated'] = date('d-m-Y', strtotime($project->updated_at));
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Project detail successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Project detail not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Priject data not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectTaskList(Request $request){
            $userId = Auth::user()->id;
            $request->validate([
                'projectID' => 'required',
            ]);
            $myTaskData = [];
            $taskAssigneTeamData = [];
            $taskAssigneMemberData = [];
            $teamData = [];
            $taskMyData = [];
            $projectID = $request->projectID;
            $project = Project::where('id', $projectID)->first();
            if($project){
                $companyId = $project->company_id;
                $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
                
                $assignTask = [];
                
                if($ownerCheck){
                    $assignTask = Task::where('company_id', $companyId)->where('project_id', $project->id)->pluck('id')->toArray();
                } else {
                    $taskAssigneMemberData = TaskAssigne::where('project_id', $project->id)->whereRaw('FIND_IN_SET(' . $userId . ', members_id)')->pluck('task_id')->toArray();
                    
                    $taskMyData = Task::where('project_id', $project->id)->where('user_id', $userId)->where('company_id', $companyId)->pluck('id')->toArray();
                    
                    $assignTask = array_merge($taskAssigneMemberData, $taskMyData);
                }
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
                    
                    $response['pinTaskData'][] = $taskData;
                        
                }
                
                // Completed Task List
                
                
                $startOfMonth = now()->startOfMonth()->toDateString();
                $endOfMonth = now()->endOfMonth()->toDateString();
                $completedTasks = Task::where('completed', 1)
                ->whereIn('id', $assignTask)
                // ->whereDate('completed_date', '>=', $startOfMonth)
                // ->whereDate('completed_date', '<=', $endOfMonth)
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
                        'message' => 'Project task list successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Task list not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Priject data not found',
                    'data' => []
                ]);
            }
        }
        
        function setupNotificationByTeam($companyId, $lastId, $formId, $toId, $name){
            $userName = '';
            $user = User::where('id', $formId)->first();
            if($user){
                $userName = $user->name;
            }
            $cratedData = [
                'company_id' => $companyId,
                'project_id' => NULL,
                'task_id' => NULL, 
                'note_id' => NULL, 
                'team_id' => $lastId, 
                'form_id' => $formId, 
                'to_id' => NULL, 
                'massage' => 'Team Created By ' . $userName . ' Named ' . $name, 
            ];
            Notification::create($cratedData);
            $teamMember_id = explode(",", $toId);
            foreach ($teamMember_id as $key => $val) {
                $notification = [
                    'company_id' => $companyId,
                    'project_id' => NULL,
                    'task_id' => NULL, 
                    'note_id' => NULL, 
                    'team_id' => $lastId, 
                    'form_id' => $formId, 
                    'to_id' => $val, 
                    'massage' => 'Team has been shared to you by ' . $userName . ' on '  . $name, 
                    // 'massage' => 'You Have Been Added as a Team Member By ' . $userName . ' on '  . $name, 
                ];
                $notification =  Notification::create($notification);
            }

            return true;
        }
        
        function setupNotification($companyId, $lastId, $formId, $toId, $name){
            $userName = '';
            $user = User::where('id', $formId)->first();
            if($user){
                $userName = $user->name;
            }
            $cratedData = [
                'company_id' => $companyId,
                'project_id' => $lastId,
                'task_id' => NULL, 
                'note_id' => NULL, 
                'team_id' => NULL, 
                'form_id' => $formId, 
                'to_id' => NULL, 
                'massage' => 'Project Created By ' . $userName . ' Named ' . $name, 
            ];
            Notification::create($cratedData);

            foreach ($toId as $key => $val) {
                $project = [
                    'company_id' => $companyId,
                    'project_id' => $lastId,
                    'task_id' => NULL, 
                    'note_id' => NULL, 
                    'team_id' => NULL, 
                    'form_id' => $formId, 
                    'to_id' => $val, 
                    'massage' => 'You Have Been Added as a Team Member By ' . $userName . ' on '  . $name, 
                ];
                $project =  Notification::create($project);
            }

            return true;
        }

        public function notificationList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            $ownerCheck = Company::where('id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
            if($ownerCheck){
                $form = Notification::where('to_id', null)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            } else {
                $form = Notification::where('form_id', $userId)->where('to_id', null)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            }
            
            $allData = [];
            foreach ($form as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $form_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $allData[] = $form_data;
            }
            if($ownerCheck){
                $to = Notification::where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            } else {
                $to = Notification::where('to_id', $userId)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            }
            
            
            // dd($sahrenote);
            foreach ($to as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $to_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $allData[] = $to_data;
            }

            if($allData){
                return response()->json([
                    'status' => true,
                    'message' => 'Notification list successfully',
                    'count' => count($allData),
                    'data' => $allData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notification data not found',
                    'data' => []
                ]);
            }
        }
        
        public function phoneNotificationList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            $ownerCheck = Company::where('id', $companyID)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->exists();
            if($ownerCheck){
                $form = Notification::where('to_id', null)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            } else {
                $form = Notification::where('form_id', $userId)->where('to_id', null)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            }
            
            $allData = [];
            foreach ($form as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $form_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $allData[] = $form_data;
            }
            if($ownerCheck){
                $to = Notification::where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            } else {
                $to = Notification::where('to_id', $userId)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            }
            
            
            // dd($sahrenote);
            foreach ($to as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $to_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $allData[] = $to_data;
            }
            
            
            // commnetList
            
            
            $commnetData = [];
            if($ownerCheck){
                $latestCommentedTasksIds = TaskComment::select('task_id')
                    ->orderBy('task_id', 'DESC')
                    ->distinct('task_id')
                    ->take(3)
                    ->pluck('task_id');
                // dd($latestCommentedTasksIds);
                $tskCommentList = Task::whereIn('id', $latestCommentedTasksIds)
                      ->where('company_id', $companyID)
                      ->get();

                foreach ($tskCommentList as $key => $value) {
                    $commnetList = TaskComment::select('comment')->where('task_id', $value->id)->orderBy('id', 'desc')->take(3)->get();
                    $comment = [
                        'id' => $value->id,
                        'title' => $value->description,
                        'commnetList' => $commnetList,
                    ];
                    $commnetData[] = $comment;
                }
            } else{
                $latestCommentedTasksIds = TaskComment::select('task_id')
                    ->orderBy('task_id', 'DESC')
                    ->where('user_id', $userId)
                    ->distinct('task_id')
                    ->take(3)
                    ->pluck('task_id');
                // dd($latestCommentedTasksIds);
                $tskCommentList = Task::whereIn('id', $latestCommentedTasksIds)
                      ->where('company_id', $companyID)
                      ->get();

                foreach ($tskCommentList as $key => $value) {
                    $commnetList = TaskComment::select('comment')->where('task_id', $value->id)->where('user_id', $userId)->orderBy('id', 'desc')->take(3)->get();
                    $comment = [
                        'id' => $value->id,
                        'title' => $value->description,
                        'commnetList' => $commnetList,
                    ];
                    $commnetData[] = $comment;
                }
            }
            
            $response = [
                'notificationData' => $allData,
                'commnetData' => $commnetData,
            ];

            if($allData){
                return response()->json([
                    'status' => true,
                    'message' => 'Notification list successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notification data not found',
                    'data' => []
                ]);
            }
        }

        public function notificationView(Request $request){
            $request->validate([
                'notificationId' => 'required',
            ]);
            
            $notification = Notification::find($request->notificationId);
            $notification->is_read = 1;
            $notification->save();
            if($notification){
                return response()->json([
                    'status' => true,
                    'message' => 'Notification read successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notification read unsuccessfully',
                ]);
            }
        }
        
        public function notificationAllList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            $unredForm = Notification::where('form_id', $userId)->where('to_id', null)->where('company_id', $companyID)->where('is_read', 0)->orderBy('id', 'DESC')->get();
            $unredData = [];
            foreach ($unredForm as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $form_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $unredData[] = $form_data;
            }

            $unredTo = Notification::where('to_id', $userId)->where('company_id', $companyID)->where('is_read', 0)->get();
            foreach ($unredTo as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $to_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $unredData[] = $to_data;
            }


            $readForm = Notification::where('form_id', $userId)->where('to_id', null)->where('company_id', $companyID)->where('is_read', 1)->orderBy('id', 'DESC')->get();
            $readData = [];
            foreach ($readForm as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $form_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $readData[] = $form_data;
            }

            $readTo = Notification::where('to_id', $userId)->where('company_id', $companyID)->where('is_read', 1)->get();
            foreach ($readTo as $key => $value) {
                $is_setModule = '';
                $common_id = 0;
                if($value->project_id){
                    $common_id = $value->project_id;
                    $is_setModule = 'project';
                } else if($value->task_id) {
                    $common_id = $value->task_id;
                    $is_setModule = 'task';
                } else if($value->note_id) {
                    $common_id = $value->note_id;
                    $is_setModule = 'note';
                } else if($value->team_id) {
                    $common_id = $value->team_id;
                    $is_setModule = 'team';
                }              
                $to_data = [
                    'id' => $value->id,
                    'common_id' => $common_id,
                    'massage' => $value->massage,
                    'is_setModule' => $is_setModule,
                    'date' => date('l d M y', strtotime($value->created_at)),
                    'time' => date('h:i A', strtotime($value->created_at)),
                ];
                $readData[] = $to_data;
            }
            
            // foreach ($allData as $key => $value) {
            //     $notification = Notification::find($value['id']);
            //     $notification->is_read = 1;
            //     $notification->save();
            // }
            $response['unRead'] = $unredData;
            $response['read'] = $readData;
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Notification list successfully',
                    'unReadCount' => count($unredData),
                    'readCount' => count($readData),
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Notification data not found',
                    'unReadCount' => 0,
                    'readCount' => 0,
                    'data' => []
                ]);
            }
        }
        
        public function notificationViewAll(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            Notification::where('form_id', $userId)
                ->where('company_id', $companyID)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
            
            Notification::where('to_id', $userId)
                ->where('company_id', $companyID)
                ->where('is_read', 0)
                ->update(['is_read' => 1]);
                
            return response()->json([
                'status' => true,
                'message' => 'View all successfully',
            ]);
        }
        
        public function notificationDeleteAll(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;
            Notification::where('form_id', $userId)
                ->where('company_id', $companyID)
                ->where('is_read', 1)
                ->delete();
            
            // Delete notifications where 'to_id' matches
            Notification::where('to_id', $userId)
                ->where('company_id', $companyID)
                ->where('is_read', 1)
                ->delete();
                
                
            return response()->json([
                'status' => true,
                'message' => 'Deleted all successfully',
            ]);
        }
        
        public function projectMembersList(Request $request){
            $projectID = $request->projectID;
            $projectData = Project::where('id', $projectID)->first();
            if($projectData){
                $memberData = [];
                $userData = User::where('id', $projectData->user_id)->first();
                if($userData){
                    if($userData->profile){
                        $profile = asset('public/images/profilePhoto/'. $userData->profile);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    if($userData->reportingTo){
                        $reportingTo = User::where('id', $userData->reportingTo)->first();
                        $toName = $reportingTo->name;
                        $toId = $reportingTo->id;
                    } else {
                        $toName = null;
                        $reportingTo = null;
                        $toId = null;
                    }
                    if($userData->assignRole){
                        $assignRole = UserRole::where('id', $userData->assignRole)->first();
                        $assignName = $assignRole->name;
                        $assignId = $assignRole->id;
                    } else {
                        $assignName = null;
                        $assignRole = null;
                        $assignId = null;
                    }
                    $userFavorite = UserFavorite::where('favorite_id', $userData->id)->where('is_favorite', 1)->first();
                    if($userFavorite){
                        $is_favorite = 1;
                    } else {
                        $is_favorite = 0;
                    }
                    $c_data = [
                        'id' => $userData->id,
                        'name' => $userData->name,
                        'email' => $userData->email,
                        'phone_no' => $userData->phone_no,
                        'designation' => $userData->designation,
                        'reportingTo' => $toName,
                        'assignRole' => $assignName,
                        'gender' => $userData->gender,
                        'dob' => date('d-m-Y',strtotime($userData->dob)),
                        'profile' => $profile,
                        'roleId' => $assignId,
                        'reportingId' => $toId,
                        'is_favorite' => $is_favorite,
                        'hour_per_cost' => $userData->hour_per_cost,
                    ];
                    $memberData[] = $c_data;
                }
                $membersId = explode(",", $projectData->members_id);
                $asignMember = User::whereIn('id',$membersId)->get();
                if($asignMember){
                    foreach ($asignMember as $key => $value) {
                        if($value->id != $userData->id){
                            if($value->profile){
                                $profile = asset('public/images/profilePhoto/'. $value->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            if($value->reportingTo){
                                $reportingTo = User::where('id', $value->reportingTo)->first();
                                $toName = $reportingTo->name;
                                $toId = $reportingTo->id;
                            } else {
                                $toName = null;
                                $reportingTo = null;
                            }
                            if($value->assignRole){
                                $assignRole = UserRole::where('id', $value->assignRole)->first();
                                if($assignRole){
                                    $assignName = $assignRole->name;
                                    $assignId = $assignRole->id;
                                } else {
                                    $assignName = null;
                                    $assignId = null;
                                }
                            } else {
                                $assignName = null;
                            }
                            $userFavorite = UserFavorite::where('favorite_id', $value->id)->where('is_favorite', 1)->first();
                            if($userFavorite){
                                $is_favorite = 1;
                            } else {
                                $is_favorite = 0;
                            }
                            $m_data = [
                                'id' => $value->id,
                                'name' => $value->name,
                                'email' => $value->email,
                                'phone_no' => $value->phone_no,
                                'designation' => $value->designation,
                                'reportingTo' => $toName,
                                'assignRole' => $assignName,
                                'gender' => $value->gender,
                                'dob' => date('d-m-Y',strtotime($value->dob)),
                                'profile' => $profile,
                                'roleId' => $assignId,
                                'reportingId' => $toId,
                                'is_favorite' => $is_favorite,
                                'hour_per_cost' => $value->hour_per_cost,
                            ];
                            $memberData[] = $m_data;
                        }
                    }
                }

                $managerId = explode(",", $projectData->manager_id);
                $asignManager = User::whereIn('id',$managerId)->get();
                if($asignManager){
                    foreach ($asignManager as $key => $value) {
                        if($value->id != $userData->id){
                            if($value->profile){
                                $profile = asset('public/images/profilePhoto/'. $value->profile);
                            } else {
                                $profile = asset('public/images/user_avatar.png');
                            }
                            if($value->reportingTo){
                                $reportingTo = User::where('id', $value->reportingTo)->first();
                                $toName = $reportingTo->name;
                                $toId = $reportingTo->id;
                            } else {
                                $toName = null;
                                $reportingTo = null;
                            }
                            if($value->assignRole){
                                $assignRole = UserRole::where('id', $value->assignRole)->first();
                                if($assignRole){
                                    $assignName = $assignRole->name;
                                    $assignId = $assignRole->id;
                                } else {
                                    $assignName = null;
                                    $assignId = null;
                                }
                            } else {
                                $assignName = null;
                            }
                            $userFavorite = UserFavorite::where('favorite_id', $value->id)->where('is_favorite', 1)->first();
                            if($userFavorite){
                                $is_favorite = 1;
                            } else {
                                $is_favorite = 0;
                            }
                            $m_data = [
                                'id' => $value->id,
                                'name' => $value->name,
                                'email' => $value->email,
                                'phone_no' => $value->phone_no,
                                'designation' => $value->designation,
                                'reportingTo' => $toName,
                                'assignRole' => $assignName,
                                'gender' => $value->gender,
                                'dob' => date('d-m-Y',strtotime($value->dob)),
                                'profile' => $profile,
                                'roleId' => $assignId,
                                'reportingId' => $toId,
                                'is_favorite' => $is_favorite,
                                'hour_per_cost' => $value->hour_per_cost,
                            ];
                            $memberData[] = $m_data;
                        }
                    }
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
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectAssigneList(Request $request){
            $projectID = $request->projectID;
            $projectData = Project::where('id', $projectID)->first();
            if($projectData){
                $checkList = CheckList::where('project_id', $projectID)->get();
                
                $memberData = [];
                if($checkList){
                    foreach ($checkList as $key => $val) {
                        $userData = User::where('id',$val->assigne_id)->first();
                        if($userData->profile){
                        $profile = asset('public/images/profilePhoto/'. $userData->profile);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    if($userData->reportingTo){
                        $reportingTo = User::where('id', $userData->reportingTo)->first();
                        $toName = $reportingTo->name;
                        $toId = $reportingTo->id;
                    } else {
                        $toName = null;
                        $reportingTo = null;
                        $toId = null;
                    }
                    if($userData->assignRole){
                        $assignRole = UserRole::where('id', $userData->assignRole)->first();
                        $assignName = $assignRole->name;
                        $assignId = $assignRole->id;
                    } else {
                        $assignName = null;
                        $assignRole = null;
                        $assignId = null;
                    }
                    $userFavorite = UserFavorite::where('favorite_id', $userData->id)->where('is_favorite', 1)->first();
                    if($userFavorite){
                        $is_favorite = 1;
                    } else {
                        $is_favorite = 0;
                    }
                    $m_data = [
                        'id' => $userData->id,
                        'name' => $userData->name,
                        'email' => $userData->email,
                        'phone_no' => $userData->phone_no,
                        'designation' => $userData->designation,
                        'reportingTo' => $toName,
                        'assignRole' => $assignName,
                        'gender' => $userData->gender,
                        'dob' => date('d-m-Y',strtotime($userData->dob)),
                        'profile' => $profile,
                        'roleId' => $assignId,
                        'reportingId' => $toId,
                        'is_favorite' => $is_favorite,
                        'hour_per_cost' => $userData->hour_per_cost,
                    ];
                        // dd($m_data);
                        $memberData[] = $m_data;
                    }
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
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => []
                ]);
            }
        }
        
        public function referenceByList(Request $request){
            $companyID = $request->companyID;
            $members = User::where('company_id', $companyID)
                ->orderBy('id', 'DESC')
                ->whereHas('roles', function($q) {
                    $q->where('name', 'user');
                })
                ->get()
                ->map(function ($item) {
                    $item->type = 'member';
                    return $item;
                });
        
            // Fetch clients and add type
            $clients = ProjectClient::where('company_id', $companyID)
                ->orderBy('id', 'DESC')
                ->get()
                ->map(function ($item) {
                    $item->type = 'client';
                    return $item;
                });
            $combined = $members->merge($clients);
            // dd($combined);
            if($combined){
                $referenceData = [];
                foreach ($combined as $key => $value) {
                    $pro_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'type' => $value->type,
                        
                    ];
                    $referenceData[] = $pro_data;
                }
                if($referenceData){
                    return response()->json([
                        'status' => true,
                        'message' => 'Reference list successfully',
                        'data' => $referenceData
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Reference data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Reference not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectByTaskTypeList(Request $request){
            $projectID = $request->projectID;
            $projectData = Project::where('id', $projectID)->first();
            if($projectData){
                $checkList = ProjectTypeList::where('project_id', $projectID)->get();
                $memberData = [];
                if($checkList){
                    foreach ($checkList as $key => $val) {
                        $m_data = [
                            'id' => $val->taskTypeId,
                            'taskTypeId' => $val->taskTypeId,
                            'remark' => $val->remark,
                            'hours' => $val->hours,
                            'tasktype' => $val->tasktypeTitle,
                            'cost' => $val->cost,
                        ];
                        $memberData[] = $m_data;
                    }
                }
                if($memberData){
                    return response()->json([
                        'status' => true,
                        'message' => 'Type list successfully',
                        'data' => $memberData
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Type List data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectByTypeCheckList(Request $request){
            $projectID = $request->projectID;
            $typeID = $request->typeID;
            $projectData = Project::where('id', $projectID)->first();
            if($projectData){
                $checkList = ProjectCheckList::where('project_id', $projectID)->where('taskTypeId', $typeID)->get();
                $memberData = [];
                if($checkList){
                    foreach ($checkList as $key => $val) {
                        $m_data = [
                            'id' => $val->id,
                            'taskTypeId' => $val->taskTypeId,
                            'tasktypechecklist' => $val->tasktypechecklist,
                            'tasktyperemark' => $val->tasktyperemark,
                            'is_document' => $val->is_document,
                        ];
                        $memberData[] = $m_data;
                    }
                }
                if($memberData){
                    return response()->json([
                        'status' => true,
                        'message' => 'Type check list successfully',
                        'data' => $memberData
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Type check List data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectByDescription(Request $request){
            $projectID = $request->projectID;
            $typeID = $request->typeID;
            $projectData = Project::where('id', $projectID)->first();
            if($projectData){
                $checkList = ProjectTypeList::where('project_id', $projectID)->where('taskTypeId', $typeID)->first();
                if($checkList){
                        $m_data = [
                            'id' => $checkList->id,
                            'tasktypeDescription' => $checkList->tasktypeDescription
                        ];
                    }
                if($m_data){
                    return response()->json([
                        'status' => true,
                        'message' => 'Description list successfully',
                        'data' => $m_data
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Description List data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => []
                ]);
            }
        }
        
        public function projectByTaskDetail(Request $request){
            $projectID = $request->projectID;
            $clientID = $request->clientID;            
            $projectData = Project::where('id', $projectID)->first();
            if($projectData){
                $taskData = Task::where('project_id', $projectID)->where('client_id', $clientID)->get();
                if($taskData){
                    $taskList = [];
                    foreach ($taskData as $key => $val) {
                        $typeList = ProjectTypeList::where('project_id', $val->project_id)->where('taskTypeId', $val->type_id)->get();
                        $typeData = [];
                        if($typeList){
                            foreach ($typeList as $key => $tVal) {
                                $checkList = ProjectCheckList::where('project_id', $projectID)->where('taskTypeId', $tVal->taskTypeId)->get();
                                $checkData = [];
                                if($checkList){
                                    foreach ($checkList as $key => $cVal) {
                                        $getDocument = UserCheckList::where('checklist_id', $cVal->id)->first();
                                        if($getDocument){
                                            $files = asset('public/images/userCheckList/'. $getDocument->file);
                                        } else {
                                            $files = 'No File';
                                        }
                                        $c_data = [
                                            'id' => $cVal->id,
                                            'taskTypeId' => $cVal->taskTypeId,
                                            'tasktypechecklist' => $cVal->tasktypechecklist,
                                            'tasktyperemark' => $cVal->tasktyperemark,
                                            'is_document' => $cVal->is_document,
                                            'files' => $files,
                                        ];
                                        $checkData[] = $c_data;
                                    }
                                    
                                }
                                $t_data = [
                                    'id' => $tVal->id,
                                    'taskTypeId' => $tVal->taskTypeId,
                                    'remark' => $tVal->remark,
                                    'hours' => $tVal->hours,
                                    'tasktypeTitle' => $tVal->tasktypeTitle,
                                    'cost' => $tVal->cost,
                                    'actual_time' => $val->actual_time,
                                    'checkData' => $checkData,
                                ];
                                $typeData[] = $t_data;
                            }
                        }
                        $m_data = [
                            'id' => $val->id,
                            'description' => $val->description,
                            'typeData' => $typeData,
                        ];
                        $taskList[] = $m_data;
                    }
                    if($taskList){
                        return response()->json([
                            'status' => true,
                            'message' => 'Task List successfully',
                            'data' => $taskList
                        ]);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Task List data not found',
                            'data' => []
                        ]);
                    }
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Project not found',
                    'data' => []
                ]);
            }
        }
        
        
        public function importProject(Request $request){
            $request->validate([
                'companyid' => 'required',
            ]);
            if($request->datas){
                $data = $request->datas;
                // $data = json_decode($request->datas, true);
                foreach ($data as $val) {
                    // $gender = 0;
                    // if(strtolower($val['gender'] = 'Female')){
                    //     $gender = 1;
                    // } 
                    $is_active = 1;
                    if(strtolower($val['is_active'] = 'Active')){
                        $is_active = 0;
                    } 
                    $reID = 2;
                    if($val['refernce_by']){
                        $referanceData = User::where('name', $val['refernce_by'])->first();
                        if($referanceData){
                            $reID = $referanceData->id;
                        }
                    }
                    $user = [
                        'company_id' => $request->companyid,
                        'name' => $val['Name'],
                        'email' => $val['email'],
                        'phone_no' => $val['phone_no'],
                        'address' => $val['Address'],
                        'company_name' => $val['Company'],
                        'is_active' => $val['is_active'],
                        'client_code' => $val['client_code'],
                        'type' => $val['type'],
                        'pan_no' => $val['pan_no'],
                        'gst_no' => $val['gst_no'],
                        'contact_person_name' => $val['contact_person_name'],
                        'refernce_by' => $reID,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                        'deleted_at' => NULL,
                    ];
                    $user = ProjectClient::create($user);
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Client import successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Client data not found',
                ]);
            }
        }
    }
