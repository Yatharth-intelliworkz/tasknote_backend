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
    use App\Models\TaskAssigne;
    use App\Models\Task;
    use App\Models\TaskComment;
    use App\Models\SubTask;
    use App\Models\CheckList;
    use App\Models\Note;
    use App\Models\NoteShare;
    use App\Models\CompanyStatus;
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
    use Illuminate\Support\Facades\Mail;
// use Illuminate\Support\Facades\Log;

    class CronJobController extends Controller{
        public function index(){
        //     Log::info('The scheduled function has been called.');
        // echo 'Function called';
            // Daily
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 0)->get();
            // dd($taskData);
            if($taskData){
                foreach ($taskData as $key => $val) {
                    
                    $taskUpdate = Task::where('id', $val->id)->first();
                    $taskUpdate->is_recurring = 0;
                    $taskUpdate->recurring_time = NULL;
                    $taskUpdate->save();
                    
                    
                    $statusData = CompanyStatus::where('company_id', $val->company_id)->where('status', 'New Task')->first();
                    
                    $projectData = Project::where('id', $val->project_id)->first();
                    if($projectData){
                        dd($projectData);
                            
                    }
                    dd($taskData);
                    $task = [
                        'user_id' => $val->user_id, 
                        'title' => $val->title, 
                        'description' => $val->description, 
                        'start_date' =>date('Y-m-d H:i:s'), 
                        'due_date' =>date('Y-m-d H:i:s', strtotime('+1 day')), 
                        'periodic_date' =>date('Y-m-d H:i:s'), 
                        'project_id' => $val->project_id,
                        'client_id'=>$val->client_id,
                        'company_id'=>$val->company_id,
                        'service_id'=>$val->service_id,
                        'status'=>$statusData->id, 
                        'priority'=>$val->priority, 
                        'is_recurring'=>1, 
                        'recurring_time'=>0, 
                        'follow_id'=>$val->follow_id, 
                        'target_time'=>$val->target_time, 
                    ];
                    // dd($task);
                    $task = Task::create($task);
                    
                    $assigneData = TaskAssigne::where('task_id', $val->id)->first();
                    if($assigneData){
                        $assigne = new TaskAssigne;
                        $assigne->task_id = $task->id;
                        $assigne->project_id = $assigneData->project_id;
                        $assigne->members_id = $assigneData->members_id;
                        $assigne->team_id = $assigneData->team_id;
                        $assigne->save();
                    }
                    
                    $subTaskData = SubTask::where('task_id', $val->id)->get();
                    if($subTaskData){
                        foreach ($subTaskData as $key => $subVal) {
                            $subTask = new SubTask;
                            $subTask->task_id = $task->id;
                            $subTask->user_id = $subVal->user_id;
                            $subTask->assigne_id = $subVal->assigne_id;
                            $subTask->team_id = $subVal->team_id;
                            $subTask->title = $subVal->title;
                            $subTask->due_date = date('Y-m-d H:i:s', strtotime('+1 day'));
                            $subTask->status = $statusData->id;
                            $subTask->priority = $subVal->priority;
                            $subTask->save();
                        }
                    }
                    
                    
                    $checkListData = CheckList::where('task_id', $val->id)->get();
                    if($checkListData){
                        foreach ($checkListData as $key => $checkVal) {
                            $checkTask = new CheckList;
                            $checkTask->task_id = $task->id;
                            $checkTask->title = $checkVal->title;
                            $checkTask->check_date = date('Y-m-d H:i:s', strtotime('+1 day'));
                            $checkTask->save();
                        }
                    }
                }
                
                if($assigneData){
                    $members_id = explode(",", $assigneData->members_id);
                    if($members_id){
                        $assignUserData = User::whereIn('id',$members_id)->get();
                        $names = $assignUserData->pluck('name')->sort()->implode(', ');
                        
                        if($val->priority == 0){
                            $priority = 'Low';
                        } else if($val->priority == 1) {
                            $priority = 'High';
                        } else {
                            $priority = 'Medium';
                        }
                        
                        $myUserData = User::where('id', $val->user_id)->first();
                        $assignUserData = User::whereIn('id',$members_id)->get();
                        $emailAddresses = $assignUserData->pluck('email')->toArray();
                        $assignInfo = [
                            'taskId' => $task->id,
                            'email' => $emailAddresses,
                            'created' => $myUserData->name, 
                            'taskName' => $val->title, 
                            'dueDate' => date('d M Y',strtotime($val->due_date)), 
                            'assignName' => $names, 
                            'taskPriority' => $priority, 
                            'cratedDate' => date('d M Y',strtotime($val->created_at)),
                            'ownerEmail' => $myUserData->email, 
                        ];
                        Mail::send('mail.new_task_assign', ['info' => $assignInfo], function ($message) use ($assignInfo) {
                            $message->to($assignInfo['email'])->subject('New Task Assign in Task Note');
                            $message->cc($assignInfo['ownerEmail']);
                        });
                    }
                }
                // dd($taskData);
            }
            
            // weekly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 1)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $created_at = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime('+1 week'));
                    if($created_at == $checkDate){
                        $taskUpdate = Task::where('id', $val->id)->first();
                        $taskUpdate->is_recurring = 0;
                        $taskUpdate->recurring_time = NULL;
                        $taskUpdate->save();
                        
                        
                        $statusData = CompanyStatus::where('company_id', $val->company_id)->where('status', 'New Task')->first();
                        
                        $task = [
                            'user_id' => $val->user_id, 
                            'title' => $val->title, 
                            'description' => $val->description, 
                            'start_date' =>date('Y-m-d H:i:s'), 
                            'due_date' =>date('Y-m-d H:i:s', strtotime('+1 week')), 
                            'periodic_date' =>date('Y-m-d H:i:s'), 
                            'project_id' => $val->project_id,
                            'client_id'=>$val->client_id,
                            'company_id'=>$val->company_id,
                            'service_id'=>$val->service_id,
                            'status'=>$statusData->id, 
                            'priority'=>$val->priority, 
                            'is_recurring'=>1, 
                            'recurring_time'=>0, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                        ];
                        // dd($task);
                        $task = Task::create($task);
                        
                        $assigneData = TaskAssigne::where('task_id', $val->id)->first();
                        if($assigneData){
                            $assigne = new TaskAssigne;
                            $assigne->task_id = $task->id;
                            $assigne->project_id = $assigneData->project_id;
                            $assigne->members_id = $assigneData->members_id;
                            $assigne->team_id = $assigneData->team_id;
                            $assigne->save();
                        }
                        
                        $subTaskData = SubTask::where('task_id', $val->id)->get();
                        if($subTaskData){
                            foreach ($subTaskData as $key => $subVal) {
                                $subTask = new SubTask;
                                $subTask->task_id = $task->id;
                                $subTask->user_id = $subVal->user_id;
                                $subTask->assigne_id = $subVal->assigne_id;
                                $subTask->team_id = $subVal->team_id;
                                $subTask->title = $subVal->title;
                                $subTask->due_date = date('Y-m-d H:i:s', strtotime('+1 week'));
                                $subTask->status = $statusData->id;
                                $subTask->priority = $subVal->priority;
                                $subTask->save();
                            }
                        }
                        
                        
                        $checkListData = CheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checkTask = new CheckList;
                                $checkTask->task_id = $task->id;
                                $checkTask->title = $checkVal->title;
                                $checkTask->check_date = date('Y-m-d H:i:s', strtotime('+1 week'));
                                $checkTask->save();
                            }
                        }
                    }
                }
            }
            
            // monthly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 2)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $created_at = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime('+1 month'));
                    // dd($checkDate);
                    if($created_at == $checkDate){
                        $taskUpdate = Task::where('id', $val->id)->first();
                        $taskUpdate->is_recurring = 0;
                        $taskUpdate->recurring_time = NULL;
                        $taskUpdate->save();
                        
                        
                        $statusData = CompanyStatus::where('company_id', $val->company_id)->where('status', 'New Task')->first();
                        
                        $task = [
                            'user_id' => $val->user_id, 
                            'title' => $val->title, 
                            'description' => $val->description, 
                            'start_date' =>date('Y-m-d H:i:s'), 
                            'due_date' =>date('Y-m-d H:i:s', strtotime('+1 month')), 
                            'periodic_date' =>date('Y-m-d H:i:s'), 
                            'project_id' => $val->project_id,
                            'client_id'=>$val->client_id,
                            'company_id'=>$val->company_id,
                            'service_id'=>$val->service_id,
                            'status'=>$statusData->id, 
                            'priority'=>$val->priority, 
                            'is_recurring'=>1, 
                            'recurring_time'=>0, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                        ];
                        // dd($task);
                        $task = Task::create($task);
                        
                        $assigneData = TaskAssigne::where('task_id', $val->id)->first();
                        if($assigneData){
                            $assigne = new TaskAssigne;
                            $assigne->task_id = $task->id;
                            $assigne->project_id = $assigneData->project_id;
                            $assigne->members_id = $assigneData->members_id;
                            $assigne->team_id = $assigneData->team_id;
                            $assigne->save();
                        }
                        
                        $subTaskData = SubTask::where('task_id', $val->id)->get();
                        if($subTaskData){
                            foreach ($subTaskData as $key => $subVal) {
                                $subTask = new SubTask;
                                $subTask->task_id = $task->id;
                                $subTask->user_id = $subVal->user_id;
                                $subTask->assigne_id = $subVal->assigne_id;
                                $subTask->team_id = $subVal->team_id;
                                $subTask->title = $subVal->title;
                                $subTask->due_date = date('Y-m-d H:i:s', strtotime('+1 month'));
                                $subTask->status = $statusData->id;
                                $subTask->priority = $subVal->priority;
                                $subTask->save();
                            }
                        }
                        
                        
                        $checkListData = CheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checkTask = new CheckList;
                                $checkTask->task_id = $task->id;
                                $checkTask->title = $checkVal->title;
                                $checkTask->check_date = date('Y-m-d H:i:s', strtotime('+1 month'));
                                $checkTask->save();
                            }
                        }
                    }
                }
            }
            
            // quaterly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 3)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $created_at = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime('+3 month'));
                    // dd($checkDate);
                    if($created_at == $checkDate){
                        $taskUpdate = Task::where('id', $val->id)->first();
                        $taskUpdate->is_recurring = 0;
                        $taskUpdate->recurring_time = NULL;
                        $taskUpdate->save();
                        
                        
                        $statusData = CompanyStatus::where('company_id', $val->company_id)->where('status', 'New Task')->first();
                        
                        $task = [
                            'user_id' => $val->user_id, 
                            'title' => $val->title, 
                            'description' => $val->description, 
                            'start_date' =>date('Y-m-d H:i:s'), 
                            'due_date' =>date('Y-m-d H:i:s', strtotime('+3 month')), 
                            'periodic_date' =>date('Y-m-d H:i:s'), 
                            'project_id' => $val->project_id,
                            'client_id'=>$val->client_id,
                            'company_id'=>$val->company_id,
                            'service_id'=>$val->service_id,
                            'status'=>$statusData->id, 
                            'priority'=>$val->priority, 
                            'is_recurring'=>1, 
                            'recurring_time'=>0, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                        ];
                        // dd($task);
                        $task = Task::create($task);
                        
                        $assigneData = TaskAssigne::where('task_id', $val->id)->first();
                        if($assigneData){
                            $assigne = new TaskAssigne;
                            $assigne->task_id = $task->id;
                            $assigne->project_id = $assigneData->project_id;
                            $assigne->members_id = $assigneData->members_id;
                            $assigne->team_id = $assigneData->team_id;
                            $assigne->save();
                        }
                        
                        $subTaskData = SubTask::where('task_id', $val->id)->get();
                        if($subTaskData){
                            foreach ($subTaskData as $key => $subVal) {
                                $subTask = new SubTask;
                                $subTask->task_id = $task->id;
                                $subTask->user_id = $subVal->user_id;
                                $subTask->assigne_id = $subVal->assigne_id;
                                $subTask->team_id = $subVal->team_id;
                                $subTask->title = $subVal->title;
                                $subTask->due_date = date('Y-m-d H:i:s', strtotime('+3 month'));
                                $subTask->status = $statusData->id;
                                $subTask->priority = $subVal->priority;
                                $subTask->save();
                            }
                        }
                        
                        
                        $checkListData = CheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checkTask = new CheckList;
                                $checkTask->task_id = $task->id;
                                $checkTask->title = $checkVal->title;
                                $checkTask->check_date = date('Y-m-d H:i:s', strtotime('+3 month'));
                                $checkTask->save();
                            }
                        }
                    }
                }
            }
            
            // half yearly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 4)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $created_at = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime('+6 month'));
                    dd($checkDate);
                    if($created_at == $checkDate){
                        $taskUpdate = Task::where('id', $val->id)->first();
                        $taskUpdate->is_recurring = 0;
                        $taskUpdate->recurring_time = NULL;
                        $taskUpdate->save();
                        
                        
                        $statusData = CompanyStatus::where('company_id', $val->company_id)->where('status', 'New Task')->first();
                        
                        $task = [
                            'user_id' => $val->user_id, 
                            'title' => $val->title, 
                            'description' => $val->description, 
                            'start_date' =>date('Y-m-d H:i:s'), 
                            'due_date' =>date('Y-m-d H:i:s', strtotime('+6 month')), 
                            'periodic_date' =>date('Y-m-d H:i:s'), 
                            'project_id' => $val->project_id,
                            'client_id'=>$val->client_id,
                            'company_id'=>$val->company_id,
                            'service_id'=>$val->service_id,
                            'status'=>$statusData->id, 
                            'priority'=>$val->priority, 
                            'is_recurring'=>1, 
                            'recurring_time'=>0, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                        ];
                        // dd($task);
                        $task = Task::create($task);
                        
                        $assigneData = TaskAssigne::where('task_id', $val->id)->first();
                        if($assigneData){
                            $assigne = new TaskAssigne;
                            $assigne->task_id = $task->id;
                            $assigne->project_id = $assigneData->project_id;
                            $assigne->members_id = $assigneData->members_id;
                            $assigne->team_id = $assigneData->team_id;
                            $assigne->save();
                        }
                        
                        $subTaskData = SubTask::where('task_id', $val->id)->get();
                        if($subTaskData){
                            foreach ($subTaskData as $key => $subVal) {
                                $subTask = new SubTask;
                                $subTask->task_id = $task->id;
                                $subTask->user_id = $subVal->user_id;
                                $subTask->assigne_id = $subVal->assigne_id;
                                $subTask->team_id = $subVal->team_id;
                                $subTask->title = $subVal->title;
                                $subTask->due_date = date('Y-m-d H:i:s', strtotime('+6 month'));
                                $subTask->status = $statusData->id;
                                $subTask->priority = $subVal->priority;
                                $subTask->save();
                            }
                        }
                        
                        
                        $checkListData = CheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checkTask = new CheckList;
                                $checkTask->task_id = $task->id;
                                $checkTask->title = $checkVal->title;
                                $checkTask->check_date = date('Y-m-d H:i:s', strtotime('+6 month'));
                                $checkTask->save();
                            }
                        }
                    }
                }
            }
            
            // yearly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 5)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $created_at = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime('+1 year'));
                    // dd($checkDate);
                    if($created_at == $checkDate){
                        $taskUpdate = Task::where('id', $val->id)->first();
                        $taskUpdate->is_recurring = 0;
                        $taskUpdate->recurring_time = NULL;
                        $taskUpdate->save();
                        
                        
                        $statusData = CompanyStatus::where('company_id', $val->company_id)->where('status', 'New Task')->first();
                        
                        $task = [
                            'user_id' => $val->user_id, 
                            'title' => $val->title, 
                            'description' => $val->description, 
                            'start_date' =>date('Y-m-d H:i:s'), 
                            'due_date' =>date('Y-m-d H:i:s', strtotime('+1 year')), 
                            'periodic_date' =>date('Y-m-d H:i:s'), 
                            'project_id' => $val->project_id,
                            'client_id'=>$val->client_id,
                            'company_id'=>$val->company_id,
                            'service_id'=>$val->service_id,
                            'status'=>$statusData->id, 
                            'priority'=>$val->priority, 
                            'is_recurring'=>1, 
                            'recurring_time'=>0, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                        ];
                        // dd($task);
                        $task = Task::create($task);
                        
                        $assigneData = TaskAssigne::where('task_id', $val->id)->first();
                        if($assigneData){
                            $assigne = new TaskAssigne;
                            $assigne->task_id = $task->id;
                            $assigne->project_id = $assigneData->project_id;
                            $assigne->members_id = $assigneData->members_id;
                            $assigne->team_id = $assigneData->team_id;
                            $assigne->save();
                        }
                        
                        $subTaskData = SubTask::where('task_id', $val->id)->get();
                        if($subTaskData){
                            foreach ($subTaskData as $key => $subVal) {
                                $subTask = new SubTask;
                                $subTask->task_id = $task->id;
                                $subTask->user_id = $subVal->user_id;
                                $subTask->assigne_id = $subVal->assigne_id;
                                $subTask->team_id = $subVal->team_id;
                                $subTask->title = $subVal->title;
                                $subTask->due_date = date('Y-m-d H:i:s', strtotime('+1 year'));
                                $subTask->status = $statusData->id;
                                $subTask->priority = $subVal->priority;
                                $subTask->save();
                            }
                        }
                        
                        
                        $checkListData = CheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checkTask = new CheckList;
                                $checkTask->task_id = $task->id;
                                $checkTask->title = $checkVal->title;
                                $checkTask->check_date = date('Y-m-d H:i:s', strtotime('+1 year'));
                                $checkTask->save();
                            }
                        }
                    }
                }
            }
        }
        
        public function taskMailSend(){
            $taskData = Task::where('is_send', 0)->get();
            foreach ($taskData as $key => $task) {
                $task->is_send = 1;
                $task->save();
                if($task->priority == 0){
                    $priority = 'Low';
                } else if($task->priority == 1) {
                    $priority = 'High';
                } else {
                    $priority = 'Medium';
                }
                $myUserData = User::where('id', $task->user_id)->first();
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
                        $emailAddresses = $assignUserData->pluck('email')->toArray();
                        
                        $assignInfo = [
                            'taskId' => $task->id,
                            'email' => $emailAddresses,
                            'created' => $myUserData->name, 
                            'taskName' => $task->title, 
                            'dueDate' => date('d M Y',strtotime($task->due_date)), 
                            'assignName' => $names, 
                            'taskPriority' => $priority, 
                            'cratedDate' => date('d M Y',strtotime($task->created_at)),
                            'ownerEmail' => $myUserData->email, 
                        ];
                        // dd($assignInfo);
                        Mail::send('mail.new_task_assign', ['info' => $assignInfo], function ($message) use ($assignInfo) {
                            $message->to($assignInfo['email'])->subject('New Task Assign in Task Note');
                            // $message->cc($assignInfo['ownerEmail']);
                        });
                    }
                }
                $taskData = Task::where('id', $task->id)->first();
                if($taskData->follow_id){
                    $followUserData = User::where('id',$taskData->follow_id)->first();
                    if($followUserData){
                        $mainInfo = [
                            'taskId' => $task->id,
                            'email' => $followUserData->email,
                            'created' => $myUserData->name, 
                            'taskName' => $taskData->title, 
                            'dueDate' => date('d M Y',strtotime($taskData->due_date)), 
                            'assignName' => $names, 
                            'taskPriority' => $priority, 
                            'cratedDate' => date('d M Y',strtotime($taskData->created_at)),
                            'ownerEmail' => $myUserData->email, 
                        ];
                        Mail::send('mail.new_task_assign', ['info' => $mainInfo], function ($message) use ($mainInfo) {
                            $message->to($mainInfo['email'])->subject('New Task by following');
                        });
                    }
                }
            }
        }
    }