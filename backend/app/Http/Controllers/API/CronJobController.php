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
    use App\Models\UserCheckList;
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
        
            // Daily
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 0)->get();
            // dd($taskData);
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $todayDate = date('Y-m-d');
                    $createdDate = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime($createdDate . ' +1 day'));
                    if ($checkDate == $todayDate){
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
                            'target_time' => $val->target_time,
                            'responsible_person' => $val->responsible_person,
                            'remainingtotalCost' => $val->remainingtotalCost,
                            'type_id' => $val->type_id,
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
                        
                        $checkListData = UserCheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checklist = new UserCheckList();
                                $checklist->project_id = $checkVal->project_id;
                                $checklist->task_id = $task->id;
                                $checklist->client_id = $checkVal->client_id;
                                $checklist->task_type_id = $checkVal->task_type_id;
                                $checklist->user_id = $checkVal->user_id;  // Access each item's data properly
                                $checklist->checklist_name = $checkVal->checklist_name;
                                $checklist->checklist_remark = $checkVal->checklist_remark;
                                
                                // Handle checklist_document as a boolean or string
                                $checklist->checklist_document = $checkVal->checklist_document;
                        
                                $checklist->checklist_time_hour = $checkVal->checklist_time_hour;
                                $checklist->checklist_time_minute = $checkVal->checklist_time_minute;
                                $checklist->checklist_responsible_person = $checkVal->checklist_responsible_person;
                                $checklist->save();
                            }
                        }
                    }
                    
                }
            }
            
            // weekly
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 1)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $todayDate = date('Y-m-d');
                    $createdDate = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime($createdDate . ' +1 week'));
                    if ($checkDate == $todayDate){
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
                            'recurring_time'=>1, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                            'responsible_person' => $val->responsible_person,
                            'remainingtotalCost' => $val->remainingtotalCost,
                            'type_id' => $val->type_id,
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
                        
                        $checkListData = UserCheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checklist = new UserCheckList();
                                $checklist->project_id = $checkVal->project_id;
                                $checklist->task_id = $task->id;
                                $checklist->client_id = $checkVal->client_id;
                                $checklist->task_type_id = $checkVal->task_type_id;
                                $checklist->user_id = $checkVal->user_id;  // Access each item's data properly
                                $checklist->checklist_name = $checkVal->checklist_name;
                                $checklist->checklist_remark = $checkVal->checklist_remark;
                                
                                // Handle checklist_document as a boolean or string
                                $checklist->checklist_document = $checkVal->checklist_document;
                        
                                $checklist->checklist_time_hour = $checkVal->checklist_time_hour;
                                $checklist->checklist_time_minute = $checkVal->checklist_time_minute;
                                $checklist->checklist_responsible_person = $checkVal->checklist_responsible_person;
                                $checklist->save();
                            }
                        }
                    }
                }
            }
            
            // monthly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 2)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $todayDate = date('Y-m-d');
                    $createdDate = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime($createdDate . ' +1 month'));
                    if ($checkDate == $todayDate){
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
                            'recurring_time'=>2, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                            'responsible_person' => $val->responsible_person,
                            'remainingtotalCost' => $val->remainingtotalCost,
                            'type_id' => $val->type_id,
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
                        
                        $checkListData = UserCheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checklist = new UserCheckList();
                                $checklist->project_id = $checkVal->project_id;
                                $checklist->task_id = $task->id;
                                $checklist->client_id = $checkVal->client_id;
                                $checklist->task_type_id = $checkVal->task_type_id;
                                $checklist->user_id = $checkVal->user_id;  // Access each item's data properly
                                $checklist->checklist_name = $checkVal->checklist_name;
                                $checklist->checklist_remark = $checkVal->checklist_remark;
                                
                                // Handle checklist_document as a boolean or string
                                $checklist->checklist_document = $checkVal->checklist_document;
                        
                                $checklist->checklist_time_hour = $checkVal->checklist_time_hour;
                                $checklist->checklist_time_minute = $checkVal->checklist_time_minute;
                                $checklist->checklist_responsible_person = $checkVal->checklist_responsible_person;
                                $checklist->save();
                            }
                        }
                    }
                }
            }
            
            // quaterly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 3)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $todayDate = date('Y-m-d');
                    $createdDate = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime($createdDate . ' +3 month'));
                    if ($checkDate == $todayDate){
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
                            'recurring_time'=>3, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time,
                            'responsible_person' => $val->responsible_person,
                            'remainingtotalCost' => $val->remainingtotalCost,
                            'type_id' => $val->type_id,
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
                        
                        $checkListData = UserCheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checklist = new UserCheckList();
                                $checklist->project_id = $checkVal->project_id;
                                $checklist->task_id = $task->id;
                                $checklist->client_id = $checkVal->client_id;
                                $checklist->task_type_id = $checkVal->task_type_id;
                                $checklist->user_id = $checkVal->user_id;  // Access each item's data properly
                                $checklist->checklist_name = $checkVal->checklist_name;
                                $checklist->checklist_remark = $checkVal->checklist_remark;
                                
                                // Handle checklist_document as a boolean or string
                                $checklist->checklist_document = $checkVal->checklist_document;
                        
                                $checklist->checklist_time_hour = $checkVal->checklist_time_hour;
                                $checklist->checklist_time_minute = $checkVal->checklist_time_minute;
                                $checklist->checklist_responsible_person = $checkVal->checklist_responsible_person;
                                $checklist->save();
                            }
                        }
                    }
                }
            }
            
            // half yearly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 4)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $todayDate = date('Y-m-d');
                    $createdDate = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime($createdDate . ' +6 month'));
                    if ($checkDate == $todayDate){
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
                            'recurring_time'=>4, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time, 
                            'responsible_person' => $val->responsible_person,
                            'remainingtotalCost' => $val->remainingtotalCost,
                            'type_id' => $val->type_id,
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
                        
                        $checkListData = UserCheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checklist = new UserCheckList();
                                $checklist->project_id = $checkVal->project_id;
                                $checklist->task_id = $task->id;
                                $checklist->client_id = $checkVal->client_id;
                                $checklist->task_type_id = $checkVal->task_type_id;
                                $checklist->user_id = $checkVal->user_id;  // Access each item's data properly
                                $checklist->checklist_name = $checkVal->checklist_name;
                                $checklist->checklist_remark = $checkVal->checklist_remark;
                                
                                // Handle checklist_document as a boolean or string
                                $checklist->checklist_document = $checkVal->checklist_document;
                        
                                $checklist->checklist_time_hour = $checkVal->checklist_time_hour;
                                $checklist->checklist_time_minute = $checkVal->checklist_time_minute;
                                $checklist->checklist_responsible_person = $checkVal->checklist_responsible_person;
                                $checklist->save();
                            }
                        }
                    }
                }
            }
            
            // yearly
            
            $taskData = Task::where('is_recurring', 1)->where('recurring_time', 5)->get();
            if($taskData){
                foreach ($taskData as $key => $val) {
                    $todayDate = date('Y-m-d');
                    $createdDate = date('Y-m-d',strtotime($val->created_at));
                    $checkDate = date('Y-m-d', strtotime($createdDate . ' +1 year'));
                    if ($checkDate == $todayDate){
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
                            'recurring_time'=>5, 
                            'follow_id'=>$val->follow_id, 
                            'target_time'=>$val->target_time,
                            'responsible_person' => $val->responsible_person,
                            'remainingtotalCost' => $val->remainingtotalCost,
                            'type_id' => $val->type_id,
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
                        
                        $checkListData = UserCheckList::where('task_id', $val->id)->get();
                        if($checkListData){
                            foreach ($checkListData as $key => $checkVal) {
                                $checklist = new UserCheckList();
                                $checklist->project_id = $checkVal->project_id;
                                $checklist->task_id = $task->id;
                                $checklist->client_id = $checkVal->client_id;
                                $checklist->task_type_id = $checkVal->task_type_id;
                                $checklist->user_id = $checkVal->user_id;  // Access each item's data properly
                                $checklist->checklist_name = $checkVal->checklist_name;
                                $checklist->checklist_remark = $checkVal->checklist_remark;
                                
                                // Handle checklist_document as a boolean or string
                                $checklist->checklist_document = $checkVal->checklist_document;
                        
                                $checklist->checklist_time_hour = $checkVal->checklist_time_hour;
                                $checklist->checklist_time_minute = $checkVal->checklist_time_minute;
                                $checklist->checklist_responsible_person = $checkVal->checklist_responsible_person;
                                $checklist->save();
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