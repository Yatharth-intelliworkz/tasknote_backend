<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use App\Models\Task;
    use App\Models\Project;
    use App\Models\Service;
    use App\Models\Attestment;
    use App\Models\TaskAssigne;
    use App\Models\SubTask;
    use App\Models\CheckList;
    use App\Models\User;
    use App\Models\Team;
    use DB;
    use Auth;

    class TaskController extends Controller {

        function __construct(){
            $this->middleware('permission:task-list|task-create|task-edit|task-delete', ['only' => ['index','show']]);
            $this->middleware('permission:task-create', ['only' => ['create','store']]);
            $this->middleware('permission:task-edit', ['only' => ['edit','update']]);
            $this->middleware('permission:task-delete', ['only' => ['destroy']]);
        }

        public function index(Request $request){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $task = Task::orderBy('id','DESC')->get();
            } else {
                $task = Task::where('user_id', Auth::id())->orderBy('id','DESC')->get();
            }
            $task = Task::orderBy('id','DESC')->get();
            return view('task.index',compact('task'));
        }

        public function create(){
            $user = User::get();
            $project = Project::get();
            $service = Service::get();
            return view('task.create', compact('user', 'project', 'service'));
        }

    
        public function store(Request $request){
            // dd($request->all());
            $validatedData = $request->validate(
                [
                    'title' => 'required',
                    'description' => 'required',
                    'due_date' => 'required',
                    'project_id' => 'required',
                    'service_id' => 'required',
                ],
                [
                    'title.required' => 'Please enter a Title.',
                    'description.required' => 'Please enter Description.',
                    'due_date.required' => 'Please select Date.',
                    'project_id.required' => 'Please select Project Name.',
                    'service_id.required' => 'Please select Service.',
                ]
            );

            $projectData = Project::where('id', $request->project_id)->first();
            if(isset($request->project_file) && !empty($request->project_file)){
                $task = [
                    'user_id' => Auth::id(), 
                    'title' => $request->title, 
                    'description' => $request->description, 
                    'due_date' =>date('Y-m-d',strtotime($request->due_date)), 
                    'project_id' => $request->project_id,
                    'client_id'=>$projectData->client_id,
                    'service_id'=>$request->service_id,
                    'status'=>$request->status, 
                    'priority'=>$request->priority, 
                ];
                $task = Task::create($task);
                if ($request->hasFile('project_file')) {
                    $sheets = $request->file('project_file');
                    $upload_files = [];
                    foreach ($sheets as $sheet) {
                        $filename = $sheet->getClientOriginalName();
                        $extension = $sheet->getClientOriginalExtension();
                        $filename = time() . '.' . $extension;
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
            }else{
                $task = [
                    'user_id' => Auth::id(),
                    'title' => $request->title, 
                    'description' => $request->description, 
                    'due_date' =>date('Y-m-d',strtotime($request->due_date)), 
                    'project_id' => $request->project_id,
                    'client_id'=>$projectData->client_id,
                    'service_id'=>$request->service_id,
                    'status'=>$request->status, 
                    'priority'=>$request->priority, 
                ];
                $task = Task::create($task);
            }
            if($request->members_id){
                $members_id = implode(",", $request->members_id);
                $membersId = [];    
                $teamId = [];    
                foreach($request->members_id as $val){
                    $done = explode("-", $val);
                    if($done[0] == 'members'){
                        $membersId[] =  $done[1];
                    } elseif($done[0] == 'team'){
                        $teamId[] =  $done[1];
                    }
                }
                $members = '';
                $team = '';
                if($membersId){
                    $members = implode(",", $membersId);
                }
                if($teamId){
                    $team = implode(",", $teamId);
                }
                $assigne = new TaskAssigne;
                $assigne->task_id = $task->id;
                $assigne->project_id = $request->project_id;
                $assigne->members_id = $members;
                $assigne->team_id = $team;
                $assigne->save();
            }

            if($request->sub_title){
                $subDetails = [];
                $subTitles = $request->sub_title;
                $subDueDate = $request->sub_due_date;
                $subType = $request->sub_type;
                for ($i = 0; $i < count($subTitles); $i++) {
                    $subDetail = [
                        'sub_title' => $subTitles[$i],
                        'sub_due_date' => $subDueDate[$i],
                        'sub_type' => $subType[$i]
                    ];
                    $subDetails[] = $subDetail;
                }
                foreach ($subDetails as $key => $val) {
                    $subTask = new SubTask;
                    $subTask->task_id = $task->id;
                    $subTask->assigne_id = $assigne->id;
                    $subTask->title = $val['sub_title'];
                    $subTask->due_date = date('Y-m-d',strtotime($val['sub_due_date']));
                    $subTask->type = $val['sub_type'];
                    $subTask->save();
                }
            }

            if($request->checkList_title){
                $checkDetails = [];
                $checkTitles = $request->checkList_title;
                $checkDate = $request->checkList_date;
                $subType = $request->sub_type;
                for ($i = 0; $i < count($checkTitles); $i++) {
                    $checkDetail = [
                        'check_title' => $checkTitles[$i],
                        'check_date' => $checkDate[$i]
                    ];
                    $checkDetails[] = $checkDetail;
                }
                foreach ($checkDetails as $key => $val) {
                    $checkTask = new CheckList;
                    $checkTask->task_id = $task->id;
                    $checkTask->title = $val['check_title'];
                    $checkTask->check_date = date('Y-m-d',strtotime($val['check_date']));
                    $checkTask->save();
                }
            }
            return redirect()->route('task.index')->with('success','Task created successfully');
        }

        // public function show($id){
        //     $role = Role::find($id);
        //     return view('task.show',compact('role','rolePermissions'));
        // }

        public function edit($id){
            $taskData = Task::where('id', $id)->first();
            $user = User::get();
            $project = Project::get();
            $subTask = SubTask::where('task_id', $id)->get();
            $checkList = CheckList::where('task_id', $id)->get();
            $service = Service::get();
            $taskAssigne = TaskAssigne::where('task_id', $id)->first();
            $projectData = Project::where('id', $taskData->project_id)->first();
            $members = explode(',', $projectData->members_id);
            $manager = explode(',', $projectData->manager_id);
            
            $allData = array_merge($members,$manager);
            
            $membersData = [];
            foreach ($allData as $key => $val) {
                $membersData[] = User::where('id', $val)->first();
            }
            $team = Team::where('project_id', $projectData->id)->get();
            $attestment = Attestment::where('type_id', $id)->where('type', 'tasks')->get();
            return view('task.edit', compact('taskData', 'user', 'project', 'subTask', 'membersData', 'team', 'taskAssigne', 'attestment', 'service', 'checkList'));
        }

        public function update(Request $request, $id){
            $validatedData = $request->validate(
                [
                    'title' => 'required',
                    'description' => 'required',
                    'due_date' => 'required',
                    'project_id' => 'required',
                    'service_id' => 'required',
                    'members_id' => 'required',
                ],
                [
                    'title.required' => 'Please enter a Title.',
                    'description.required' => 'Please enter Description.',
                    'due_date.required' => 'Please select Date.',
                    'project_id.required' => 'Please select Project Name.',
                    'service_id.required' => 'Please select Service.',
                    'members_id.required' => 'Please select Members Name.',
                ]
            );
            $projectData = Project::where('id', $request->project_id)->first();
            if(isset($request->project_file) && !empty($request->project_file)){
                $task = Task::where('id', $id)->first();
                $task->title = $request->title;
                $task->description = $request->description;
                $task->due_date = date('Y-m-d',strtotime($request->due_date));
                $task->project_id = $request->project_id;
                $task->client_id = $projectData->client_id;
                $task->service_id = $request->service_id;
                $task->status = $request->status;
                $task->priority = $request->priority;
                $task->save();
                if ($request->hasFile('project_file')) {
                    $sheets = $request->file('project_file');
                    $upload_files = [];
                    foreach ($sheets as $sheet) {
                        $filename = $sheet->getClientOriginalName();
                        $extension = $sheet->getClientOriginalExtension();
                        $filename = time() . '.' . $extension;
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
            }else{
                $task = Task::where('id', $id)->first();
                $task->title = $request->title;
                $task->description = $request->description;
                $task->due_date = date('Y-m-d',strtotime($request->due_date));
                $task->project_id = $request->project_id;
                $task->client_id = $projectData->client_id;
                $task->service_id = $request->service_id;
                $task->status = $request->status;
                $task->priority = $request->priority;
                $task->save();
            }
            if($request->members_id){
                $members_id = implode(",", $request->members_id);
                $membersId = [];    
                $teamId = [];    
                foreach($request->members_id as $val){
                    $done = explode("-", $val);
                    if($done[0] == 'members'){
                        $membersId[] =  $done[1];
                    } elseif($done[0] == 'team'){
                        $teamId[] =  $done[1];
                    }
                }
                $members = '';
                $team = '';
                if($membersId){
                    $members = implode(",", $membersId);
                }
                if($teamId){
                    $team = implode(",", $teamId);
                }
                $assigne = new TaskAssigne;
                $assigne->task_id = $task->id;
                $assigne->project_id = $request->project_id;
                $assigne->members_id = $members;
                $assigne->team_id = $team;
                $assigne->save();
            }
    
            if($request->sub_title){
                $subDetails = [];
                $subTitles = $request->sub_title;
                $subDueDate = $request->sub_due_date;
                $subType = $request->sub_type;
                for ($i = 0; $i < count($subTitles); $i++) {
                    $subDetail = [
                        'sub_title' => $subTitles[$i],
                        'sub_due_date' => $subDueDate[$i],
                        'sub_type' => $subType[$i]
                    ];
                    $subDetails[] = $subDetail;
                }
                foreach ($subDetails as $key => $val) {
                    $subTask = new SubTask;
                    $subTask->task_id = $task->id;
                    $subTask->assigne_id = $assigne->id;
                    $subTask->title = $val['sub_title'];
                    $subTask->due_date = date('Y-m-d',strtotime($val['sub_due_date']));
                    $subTask->type = $val['sub_type'];
                    $subTask->save();
                }
            }
    
            if($request->up_sub_id){
                $upSubDetails = [];
                $upSubId = $request->up_sub_id;
                $upSubTitles = $request->up_sub_title;
                $upSubDueDate = $request->up_sub_due_date;
                $upSubType = $request->up_sub_type;
                for ($i = 0; $i < count($upSubId); $i++) {
                    $upSubDetail = [
                        'sub_id' => $upSubId[$i],
                        'sub_title' => $upSubTitles[$i],
                        'sub_due_date' => $upSubDueDate[$i],
                        'sub_type' => $upSubType[$i]
                    ];
                    $upSubDetails[] = $upSubDetail;
                }
                foreach ($upSubDetails as $key => $val) {
                    $upSubTask = SubTask::where('id', $val['sub_id'])->first();
                    $upSubTask->task_id = $task->id;
                    $upSubTask->assigne_id = $assigne->id;
                    $upSubTask->title = $val['sub_title'];
                    $upSubTask->due_date = date('Y-m-d',strtotime($val['sub_due_date']));
                    $upSubTask->type = $val['sub_type'];
                    $upSubTask->save();
                }
            }

            if($request->checkList_title){
                $checkDetails = [];
                $checkTitles = $request->checkList_title;
                $checkDate = $request->checkList_date;
                for ($i = 0; $i < count($checkTitles); $i++) {
                    $checkDetail = [
                        'check_title' => $checkTitles[$i],
                        'check_date' => $checkDate[$i],
                    ];
                    $checkDetails[] = $checkDetail;
                }
                foreach ($checkDetails as $key => $val) {
                    $checkTask = new CheckList;
                    $checkTask->task_id = $task->id;
                    $checkTask->title = $val['check_title'];
                    $checkTask->check_date = date('Y-m-d',strtotime($val['check_date']));
                    $checkTask->save();
                }
            }

            if($request->up_check_id){
                $upCheckDetails = [];
                $upCheckId = $request->up_check_id;
                $upCheckTitles = $request->up_check_title;
                $upCheckDate = $request->up_check_date;
                for ($i = 0; $i < count($upCheckId); $i++) {
                    $upCheckDetail = [
                        'check_id' => $upCheckId[$i],
                        'check_title' => $upCheckTitles[$i],
                        'check_date' => $upCheckDate[$i],
                    ];
                    $upCheckDetails[] = $upCheckDetail;
                }
                foreach ($upCheckDetails as $key => $val) {
                    $upCheckTask = CheckList::where('id', $val['check_id'])->first();
                    $upCheckTask->task_id = $task->id;
                    $upCheckTask->title = $val['check_title'];
                    $upCheckTask->check_date = date('Y-m-d',strtotime($val['check_date']));
                    $upCheckTask->save();
                }
            }
            return redirect()->route('task.index')->with('success','Task updated successfully');
        }

        public function destroy($id){
            $task = Task::where('id', $id)->first();
            $task->delete();

            $taskAssigne = TaskAssigne::where('task_id', $id)->delete();
            
            $subTask = SubTask::where('task_id', $id)->delete();
            
            $checkList = CheckList::where('task_id', $id)->delete();
            
            return redirect()->route('task.index')->with('success','Task deleted successfully');
        }

        public function getassigneData($id){
            $project = Project::where('id', $id)->first();
            $members = explode(',', $project->members_id);
            $manager = explode(',', $project->manager_id);
            
            $allData = array_merge($members,$manager);
            
            $membersData = [];
            foreach ($allData as $key => $val) {
                $membersData[] = User::where('id', $val)->first();
            }
            $getData['membersData'] = $membersData;
            $getteam['team'] = Team::where('project_id', $id)->get();
            // $team = Team::where('project_id', $id)->get();
            return response()->json([$getData, $getteam]);
            // return view('task.getassigne', compact('project', 'membersData', 'team'));
        }

        public function removeImg($id){
            $attestment = Attestment::where('id', $id)->delete();
            if($attestment){
                return 1;
            } else {
                return 0;
            }
        }

        public function removeSubTask($id){
            $attestment = SubTask::where('id', $id)->delete();
            if($attestment){
                return 1;
            } else {
                return 0;
            }
        }
        public function removeCheckList($id){
            $attestment = CheckList::where('id', $id)->delete();
            if($attestment){
                return 1;
            } else {
                return 0;
            }
        }

        /*
        // SubTask
        */

        public function subTask(Request $request){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $subTask = SubTask::orderBy('id','DESC')->get();
            } else {
                $subTask = SubTask::where('user_id', Auth::id())->orderBy('id','DESC')->get();
            }
            return view('sub_task.index',compact('subTask'));
        }

        public function subTaskCreate(){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $task = Task::get();
            } else {
                $task = Task::where('user_id', Auth::id())->get();
            }
            $user = User::get();
            
            $service = Service::get();
            return view('sub_task.create', compact('user', 'task', 'service'));
        }

        public function getSubMembersData($id){
            $taskAssigne = TaskAssigne::where('task_id', $id)->first();
            $members = explode(',', $taskAssigne->members_id);
            $membersList = [];
            foreach($members as $val){
                $membersData = User::where('id', $val)->first();
                $membersList[] = $membersData;
            }
            return view('sub_task.getmembers', compact('membersList'));
        }

    }