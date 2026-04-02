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
use App\Models\Service;
use App\Models\ProjectClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use DB;
use DateTime;
use Carbon\Carbon;

class ReportController extends Controller
{

    public function userReport(Request $request)
    {
        $request->validate([
            'companyID' => 'required',
            'userID' => 'required',
            'statusID' => 'required',
        ]);
        
        $startDate = null;
        if($request->start){
            $start = new DateTime($request->start);
            $startDate = $start->format('Y-m-d');
    
            $due_date = new DateTime($request->due_date);
            $dueDateSend = $due_date->format('Y-m-d');
        }
        
        
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
                if($startDate){
                    $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                } else {
                    $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                    
                }
                
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
            if($startDate){
                $totalUserTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->whereDate('tasks.due_date', '>=', $startDate)
                ->whereDate('tasks.due_date', '<=', $dueDateSend)
                ->where(function ($query) use ($uId) {
                    $query->where('tasks.user_id', $uId)
                        ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                })
                ->whereNull('task_assignes.deleted_at')
                ->get();
            } else {
                $totalUserTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->where(function ($query) use ($uId) {
                    $query->where('tasks.user_id', $uId)
                        ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                })
                ->whereNull('task_assignes.deleted_at')
                ->get();
            }
            $taskList = [];
            foreach ($totalUserTask as $key => $tData) {
                $created_at = date('d-m-Y', strtotime($tData->created_at));
                $dueDate = date('d-m-Y', strtotime($tData->due_date));
                $statusName = CompanyStatus::where('id', $tData->status)->first();
                $serviceName = Service::where('id', $tData->service_id)->first();
                $clientData = ProjectClient::where('id', $tData->client_id)->first();
                if($clientData){
                    $clientName = $clientData->name;
                } else {
                    $clientName = null;
                }
                $projectData = Project::where('id', $tData->project_id)->first();
                if($projectData){
                    $projectName = $projectData->name;
                } else {
                    $projectName = null;
                }
                $creatorName = User::where('id', $tData->user_id)->first();
                $progress = '';
                if ($tData->completed_date != null) {
                    $completedDate = date('d-m-Y', strtotime($tData->completed_date));

                    if ($completedDate != null && $dueDate != null) {
                        if ($completedDate < $dueDate) {
                            $progress = "Before time";
                        } elseif ($completedDate > $dueDate) {
                            $progress = "Delay";
                        } elseif ($completedDate == $dueDate) {
                            $progress = "On Time";
                        }
                    }
                } else {
                    $completedDate = '';
                    $progress = '';
                }


                $t_data = [
                    'task_id' => $tData->id,
                    'title' => $tData->title,
                    'created_at' => $created_at,
                    'dueDate' => $dueDate,
                    'priority' => $tData->priority,
                    'status' => $statusName->status,
                    'service' => NULL,
                    'client' => $clientName,
                    'project' => $projectName,
                    'creator' => $creatorName->name,
                    'completedDate' => $completedDate,
                    'progress' => $progress,
                ];
                $taskList[] = $t_data;
            }
            $statusData = [];
            foreach ($statusIds as $key => $sId) {
                $status = CompanyStatus::where('id', $sId)->first();
                if($startDate){
                    // dd($dueDateSend);
                    $statusTotalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.status', $sId)
                        ->whereDate('tasks.due_date', '>=', $startDate)
                        ->whereDate('tasks.due_date', '<=', $dueDateSend)
                        ->where(function ($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                        // dd($totalTask);
                } else {
                    $statusTotalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                        ->where('tasks.company_id', $companyId)
                        ->where('tasks.status', $sId)
                        ->where(function ($query) use ($uId) {
                            $query->where('tasks.user_id', $uId)
                                ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                        })
                        ->whereNull('task_assignes.deleted_at')
                        ->count();
                        
                }

                $s_data = [
                    'status' => $status->status,
                    'totalTask' => $statusTotalTask,
                ];
                $statusData[] = $s_data;
            }
            $totalLowPriorityTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->where('tasks.priority', 0)
                ->where(function ($query) use ($uId) {
                    $query->where('tasks.user_id', $uId)
                        ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                })
                ->whereNull('task_assignes.deleted_at')
                ->count();
            $totalHighPriorityTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->where('tasks.priority', 1)
                ->where(function ($query) use ($uId) {
                    $query->where('tasks.user_id', $uId)
                        ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                })
                ->whereNull('task_assignes.deleted_at')
                ->count();
            $totalMediumPriorityTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->where('tasks.priority', 2)
                ->where(function ($query) use ($uId) {
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
        if ($response) {
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
            if (($value->user_id != $userId) && ($value->company_id == $companyId)) {
                $ta_data = [
                    'projectId' => $value->id,
                ];
                $projectData[] = $ta_data;
            }
        }
        $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
        if ($ownerCheck) {
            $projectGetData = Project::where('company_id', $companyId)->get();
        } else {
            $projectGetData = Project::whereIn('id', $projectData)->get();
        }
        $totalTask = 0;
        $totalTaskCompleted = 0;
        $totalTaskIncompleted = 0;
        $proList = [];
        foreach ($projectGetData as $val) {
            $ownerCheck = Company::where('id', $companyId)->whereRaw('FIND_IN_SET(' . $userId . ', user_id)')->first();
            if ($ownerCheck) {
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
                    ->where(function ($query) use ($userId) {
                        $query->where('tasks.user_id', $userId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $totalTaskCompleted += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.project_id', $val->id)
                    ->where('tasks.completed', '1')
                    ->where(function ($query) use ($userId) {
                        $query->where('tasks.user_id', $userId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $totalTaskIncompleted += Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.project_id', $val->id)
                    ->where('tasks.completed', '0')
                    ->where(function ($query) use ($userId) {
                        $query->where('tasks.user_id', $userId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$userId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
            }




            $startDate = date('Y-m-d', strtotime($val->start_date));
            $endDate = date('Y-m-d', strtotime($val->end_date));
            $lastUpDate = date('d F, Y', strtotime($val->updated_at));
            $clientData = ProjectClient::find($val->client_id);
            if($clientData){
                $clientName = $clientData->name;
            } else {
                $clientName = null;
            }
            $cratedData = User::where('id', $val->user_id)->first();
            if ($cratedData) {
                $createdName = $cratedData->name;
            } else {
                $createdName = '';
            }
            $proTotalTask = Task::where('project_id', $val->id)->count();
            $taskCompleted = Task::where('project_id', $val->id)->where('completed', '1')->count();
            $taskIncompleted = Task::where('project_id', $val->id)->where('completed', '0')->count();
            $pro_data = [
                'id' => $val->id,
                'name' => $val->name,
                'createdName' => $createdName,
                'description' => $val->description,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'clientName' => $clientName,
                'lastUpDate' => $lastUpDate,
                'taskCompleted' => $taskCompleted,
                'projectTotalTask' => $proTotalTask,
                'taskIncompleted' => $taskIncompleted,
            ];
            $proList[] = $pro_data;
        }
        $response['totalProject'] = count($projectData);
        $response['totalTask'] = $totalTask;
        $response['totalTaskCompleted'] = $totalTaskCompleted;
        $response['totalTaskIncompleted'] = $totalTaskIncompleted;
        $response['projectList'] = $proList;
        if ($response) {
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

    public function statusReport(Request $request)
    {
        $request->validate([
            'companyID' => 'required',
            'userID' => 'required',
            'statusID' => 'required',
        ]);
        
        $startDate = null;
        if($request->start){
            $start = new DateTime($request->start);
            $startDate = $start->format('Y-m-d');
    
            $due_date = new DateTime($request->due_date);
            $dueDateSend = $due_date->format('Y-m-d');
        }
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
                
                if($startDate){
                     $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                } else {
                $totalTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
                }

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
                
                if($startDate){
                $totalStatusTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                        ->whereDate('tasks.due_date', '>=', $startDate)
                        ->whereDate('tasks.due_date', '<=', $dueDateSend)
                        ->whereNull('task_assignes.deleted_at')
                        ->get();
                } else {
                $totalStatusTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.status', $sId)
                    ->whereNull('task_assignes.deleted_at')
                    ->get();
                }

                // dd($totalStatusTask);
                $taskList = [];
                foreach ($totalStatusTask as $key => $tData) {
                    $created_at = date('d-m-Y', strtotime($tData->created_at));
                    $dueDate = date('d-m-Y', strtotime($tData->due_date));
                    $statusName = CompanyStatus::where('id', $tData->status)->first();
                    $serviceName = Service::where('id', $tData->service_id)->first();
                    $clientData = ProjectClient::where('id', $tData->client_id)->first();
                    if($clientData){
                        $clientName = $clientData->name;
                    } else {
                        $clientName = null;
                    }
                    $projectData = Project::where('id', $tData->project_id)->first();
                    if($projectData){
                        $projectName = $projectData->name;
                    } else {
                        $projectName = null;
                    }
                    $creatorName = User::where('id', $tData->user_id)->first();
                    $progress = '';
                    if ($tData->completed_date != null) {
                        $completedDate = date('d-m-Y', strtotime($tData->completed_date));

                        if ($completedDate != null && $dueDate != null) {
                            if ($completedDate < $dueDate) {
                                $progress = "Before time";
                            } elseif ($completedDate > $dueDate) {
                                $progress = "Delay";
                            } elseif ($completedDate == $dueDate) {
                                $progress = "On Time";
                            }
                        }
                    } else {
                        $completedDate = '';
                        $progress = '';
                    }
                    $t_data = [
                        'task_id' => $tData->id,
                        'company_id' => $tData->company_id,
                        'status_id' => $tData->status,
                        'title' => $tData->title,
                        'status' => $status->status,
                        'created_at' => $created_at,
                        'priority' => $tData->priority,
                        'dueDate' => $dueDate,
                        'service' => $serviceName->title,
                        'client' => $clientName,
                        'project' => $projectName,
                        'creator' => $creatorName->name,
                        'completedDate' => $completedDate,
                        'progress' => $progress,
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
        if ($response) {
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
            'projectID' => 'required',
        ]);

        $startDate = null;
        if($request->start){
            $start = new DateTime($request->start);
            $startDate = $start->format('Y-m-d');
    
            $due_date = new DateTime($request->due_date);
            $dueDateSend = $due_date->format('Y-m-d');
        }

        $companyId = $request->companyID;
        $projectID = $request->projectID;
        $userId = $request->userID;
        $userIds = explode(',', $userId);
        $userStatusReport = [];
        foreach ($userIds as $key => $uId) {
            if($startDate){
                $performanceOnTrack = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.completed', 0)
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceBeforeTime = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '>', 'completed_date')
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceDelayed = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '<', 'completed_date')
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
            } else {
                $performanceOnTrack = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.completed', 0)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.company_id', $companyId)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceBeforeTime = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '>', 'completed_date')
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceDelayed = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '<', 'completed_date')
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
            }

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
            if($startDate){
                $totalUserTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->get();
            } else {
                $totalUserTask = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->get();
            }
            $taskList = [];
            foreach ($totalUserTask as $key => $tData) {
                $created_at = date('d-m-Y', strtotime($tData->created_at));
                $dueDate = date('d-m-Y', strtotime($tData->due_date));
                $statusName = CompanyStatus::where('id', $tData->status)->first();
                $serviceName = Service::where('id', $tData->service_id)->first();
                $clientData = ProjectClient::where('id', $tData->client_id)->first();
                if($clientData){
                    $clientName = $clientData->name;
                } else {
                    $clientName = null;
                }
                $projectData = Project::where('id', $tData->project_id)->first();
                if($projectData){
                    $projectName = $projectData->name;
                } else {
                    $projectName = null;
                }
                $creatorName = User::where('id', $tData->user_id)->first();
                $progress = '';
                if ($tData->completed_date != null) {
                    $completedDate = date('d-m-Y', strtotime($tData->completed_date));
                    if ($completedDate != null && $dueDate != null) {
                        if ($completedDate < $dueDate) {
                            $progress = "Before time";
                        } elseif ($completedDate > $dueDate) {
                            $progress = "Delay";
                        }
                    }
                } else {
                    $completedDate = '';
                    $progress = '';
                }
                $t_data = [
                    'task_id' => $tData->task_id,
                    'title' => $tData->title,
                    'created_at' => $created_at,
                    'status' => CompanyStatus::where('id', $tData->status)->first(),
                    'priority' => $tData->priority,
                    'dueDate' => $dueDate,
                    'service' => $serviceName->title,
                    'client' => $clientName,
                    'project' => $projectName,
                    'creator' => $creatorName->name,
                    'completedDate' => $completedDate,
                    'progress' => $progress,
                ];
                $taskList[] = $t_data;
            }
            if($startDate){
                $performanceOnTrack = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 0)
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceBeforeTime = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '>', 'completed_date')
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceDelayed = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '<', 'completed_date')
                    ->whereDate('tasks.due_date', '>=', $startDate)
                    ->whereDate('tasks.due_date', '<=', $dueDateSend)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
            } else {
                $performanceOnTrack = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 0)
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceBeforeTime = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '>', 'completed_date')
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();

                $performanceDelayed = Task::join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                    ->where('tasks.company_id', $companyId)
                    ->where('tasks.project_id', $projectID)
                    ->where('tasks.completed', 1)
                    ->whereColumn('due_date', '<', 'completed_date')
                    ->where(function ($query) use ($uId) {
                        $query->where('tasks.user_id', $uId)
                            ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
                    })
                    ->whereNull('task_assignes.deleted_at')
                    ->count();
            }
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
        if ($response) {
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
    
    function millisecondsToTimeString($milliseconds) {
        // dd($milliseconds);
        if($milliseconds != null){
            $hours = floor($milliseconds / 3600000);
            $milliseconds -= $hours * 3600000;
            $minutes = floor($milliseconds / 60000);
            $milliseconds -= $minutes * 60000;
            $seconds = floor($milliseconds / 1000);
            $milliseconds -= $seconds * 1000;
            return sprintf("%02d:%02d:%02d.%03d", $hours, $minutes, $seconds, $milliseconds);
        }
        
    }

    public function timeSheetReport(Request $request)
    {
        $request->validate([
            'companyID' => 'required',
            'userID' => 'required',
        ]);
        
        $startDate = null;
        if($request->start){
            $start = new DateTime($request->start);
            $startDate = $start->format('Y-m-d');
    
            $due_date = new DateTime($request->due_date);
            $dueDateSend = $due_date->format('Y-m-d');
        }
        
        
        $companyId = $request->companyID;
        $userId = $request->userID;
        $userIds = explode(',', $userId);
        $timeReport = [];
        $totalTargetTime = 0;
        $totalActualTime = 0;
        foreach ($userIds as $key => $uId) {
            if($startDate){
                $totalTask = Task::select('tasks.id', 'tasks.title', 'tasks.start_date', 'tasks.due_date', 'tasks.target_time', 'tasks.actual_time', 'task_assignes.members_id')
                ->join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                ->where('tasks.company_id', $companyId)
                ->whereDate('tasks.due_date', '>=', $startDate)
                ->whereDate('tasks.due_date', '<=', $dueDateSend)
                ->whereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]) 
                ->whereNull('task_assignes.deleted_at')
                    ->get();
            } else {
                $totalTask = Task::select('tasks.id', 'tasks.title', 'tasks.start_date', 'tasks.due_date', 'tasks.target_time', 'tasks.actual_time', 'task_assignes.members_id')
                  ->join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
                  ->where('tasks.company_id', $companyId)
                  ->whereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]) 
                  ->whereNull('task_assignes.deleted_at')
                  ->get();
            }
            // if($startDate){
            //     $totalTask = Task::select('tasks.id', 'tasks.title', 'tasks.start_date', 'tasks.due_date', 'tasks.target_time', 'tasks.actual_time', 'task_assignes.members_id')
            //     ->join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
            //     ->where('tasks.company_id', $companyId)
            //     ->whereDate('tasks.due_date', '>=', $startDate)
            //     ->whereDate('tasks.due_date', '<=', $dueDateSend)
            //     ->where(function ($query) use ($uId) {
            //         $query->where('tasks.user_id', $uId)
            //             ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
            //     })
            //     ->whereNull('task_assignes.deleted_at')
            //         ->get();
            // } else {
            //     $totalTask = Task::select('tasks.id', 'tasks.title', 'tasks.start_date', 'tasks.due_date', 'tasks.target_time', 'tasks.actual_time', 'task_assignes.members_id')
            //       ->join("task_assignes", "task_assignes.task_id", "=", "tasks.id")
            //       ->where('tasks.company_id', $companyId)
            //       ->where(function ($query) use ($uId) {
            //           $query->where('tasks.user_id', $uId)
            //                 ->orWhereRaw('FIND_IN_SET(?, task_assignes.members_id)', [$uId]);
            //       })
            //       ->whereNull('task_assignes.deleted_at')
            //       ->get();
            // }
            $taskList = [];
            $totalTargetTime = 0;
            $totalActualTime = 0;
            // dd($totalTask);
            foreach ($totalTask as $key => $tData) {
                $ids = explode(",", $tData->members_id);
                $assignName = DB::table('users')->whereIn('id', $ids)->pluck('name')->implode(',');
                $t_data = [
                    'task_id' => $tData->id,
                    'title' => $tData->title,
                    'startDate' => $tData->start_date,
                    'dueDate' => $tData->due_date,
                    'assignName' => $assignName,
                    'target_time' => $tData->target_time,
                    'actual_time' => $tData->actual_time,
                ];
                $taTime = $this->timeToSeconds($tData->target_time);
                $acTime = $this->timeToSeconds($tData->actual_time);
                // dd($acTime);
                
                
            }
            $totalTargetTime += $taTime;
            $totalActualTime += $acTime;
            $taskList[] = $t_data;
            
        }
        $taHours = $this->secondsToHours($totalTargetTime);
        $acHours = $this->secondsToHours($totalActualTime);
        // dd($taHours);
        // if($totalActualTime > 0){
            // dd($totalActualTime);
            $totalVariance = round($totalTargetTime) - round($totalActualTime);
            if ($totalVariance >= 0) {
                $flag = 'green';
            } else {
                $totalVariance = substr($totalVariance, 1);
                $totalVariance = (int)$totalVariance;
                $flag = 'red';
            }
        // } else {
        //     $totalVariance = $totalTargetTime - $totalActualTime;
        //     $totalVariance = substr($totalVariance, 1);
        //     $totalVariance = (int)$totalVariance;
        //     $flag = 'red';
        // }
        
        // dd('done');
        if($taskList){
            return response()->json([
                'status' => true,
                'message' => 'Time Sheet data successfully',
                'totalTargetTime' => $taHours,
                'totalActualTime' => $acHours,
                'totalVariance' => $this->secondsToHours($totalVariance),
                'flag' => $flag,
                'data' => $taskList
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Time Sheet data not found',
                'data' => []
            ]);
        }
    }
    
    function timeToSeconds($time) {
        if($time != null){
            $parts = explode(':', $time);
            return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
        }
        
    }
    function secondsToHours($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        return sprintf("%d:%02d", $hours, $minutes);
    }
    
    public function projectByTaskList(Request $request){
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
        $assignTask = [];
        if($project){
            $companyId = $project->company_id;
            $assignTask = Task::where('company_id', $companyId)->where('project_id', $project->id)->pluck('id')->toArray();
        
            $assignTask = array_unique($assignTask);
                    
            $paymentDate = now()->toDateString();
            
            $taskList = Task::whereIn('id', $assignTask)->with(['project', 'client', 'service', 'status', 'assignedUser', 'subtasks', 'assignees'])->get();
            foreach ($taskList as $task) {
                // dd($task->assignees);
                $projectName = optional($task->project)->name;
                $clientName = optional($task->client)->name;
                $serviceName = optional($task->service)->title;
                $createdName = optional($task->user)->name;
            
                // Determine task dates
                $dueDate = date('d-m-Y',strtotime($task->due_date));
                $startDate = date('d-m-Y',strtotime($task->start_date));
                $created_at = date('d-m-Y',strtotime($task->created_at));
                if($task->completed == 1){
                    $completedDate = date('d-m-Y',strtotime($task->completed_date));
                } else {
                    $completedDate = '-';
                }
                
            
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
            
                if($task->priority == 0){
                    $priority = 'low';
                } else if($task->priority == 1){
                    $priority = 'high';
                } else {
                    $priority = 'medium';
                }
            
                
                $taskData = [
                    'task_id' => $task->id,
                    'title' => $task->title,
                    'due_date' => $dueDate,
                    'description' => $task->description,
                    'projectName' => $projectName,
                    'clientName' => $clientName,
                    'service' => $serviceName,
                    'completedDate' => $completedDate,
                    'status' => $statusName,
                    'priority' => $priority,
                    'created_at' => $created_at,
                ];
            
                // Categorize task based on due date and status
                
                $response[] = $taskData;
                    
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
                'message' => 'Task list not found',
                'data' => []
            ]);
        }
    }

}
