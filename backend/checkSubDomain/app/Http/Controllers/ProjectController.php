<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use App\Models\Project;
    use App\Models\User;
    use App\Models\Team;
    use DB;
    use Auth;

    class ProjectController extends Controller {
        function __construct(){
            $this->middleware('permission:project-list|project-create|project-edit|project-delete', ['only' => ['index','show']]);
            $this->middleware('permission:project-create', ['only' => ['create','store']]);
            $this->middleware('permission:project-edit', ['only' => ['edit','update']]);
            $this->middleware('permission:project-delete', ['only' => ['destroy']]);
        }

        public function index(Request $request){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $project = Project::orderBy('id','DESC')->get();
            } else {
                $project = Project::orderBy('id','DESC')
                ->orwhereRaw('FIND_IN_SET(' . Auth::id() . ', members_id)')
                ->orwhereRaw('FIND_IN_SET(' . Auth::id() . ', manager_id)')
                ->get();
            }
            
            return view('project.index',compact('project'));
        }

        public function create(){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $clientUser = User::whereHas(
                    'roles', function($q){
                        $q->where('name', 'client');
                    }
                )->get();
                $teamLeader = User::whereHas(
                    'roles', function($q){
                        $q->where('name', 'teamLeader');
                    }
                )->get();
                $membersUser = User::whereHas(
                    'roles', function($q){
                        $q->where('position', '5');
                    }
                )->get();
            } else {
                $clientUser = User::where('created_by', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->where('name', 'client');
                    }
                )->get();
                $teamLeader = User::where('created_by', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->where('name', 'teamLeader');
                    }
                )->get();
                $membersUser = User::where('created_by', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->where('position', '5');
                    }
                )->get();
            }
            return view('project.create', compact('clientUser', 'teamLeader', 'membersUser'));
        }

    
        public function store(Request $request){
            $this->validate($request, [
                'name' => 'required',
                'description' => 'required',
                'start_end_date' => 'required',
                'client_id' => 'required',
                'manager_id' => 'required',
                'members_id' => 'required',
            ]);

            if(isset($request->project_file) && !empty($request->project_file)){
                $imageName = time().'.'.$request->project_file->extension();  
                $request->project_file->move(public_path('images/project'), $imageName);
                $allDate = explode("-", $request->start_end_date);
                $members_id = implode(",", $request->members_id);
                $manager_id = implode(",", $request->manager_id);
                $project = [
                    'user_id' => Auth::id(), 
                    'name' => $request->name, 
                    'description' => $request->description, 
                    'start_date' =>date('Y-m-d',strtotime($allDate[0])), 
                    'end_date' => date('Y-m-d',strtotime($allDate[1])), 
                    'client_id' => $request->client_id,
                    'file'=>$imageName,
                    'members_id'=>$members_id.','.Auth::id(),
                    'manager_id'=>$manager_id, 
                    'status'=>0, 
                ];
                Project::create($project);
            }else{
                $allDate = explode("-", $request->start_end_date);
                $members_id = implode(",", $request->members_id);
                $manager_id = implode(",", $request->manager_id);
                $project = [
                    'user_id' => Auth::id(), 
                    'name' => $request->name, 
                    'description' => $request->description, 
                    'start_date' =>date('Y-m-d',strtotime($allDate[0])), 
                    'end_date' => date('Y-m-d',strtotime($allDate[1])), 
                    'client_id' => $request->client_id,
                    'members_id'=>$members_id.','.Auth::id(),
                    'manager_id'=>$manager_id, 
                    'status'=>0, 
                ];
                Project::create($project);
            }
            return redirect()->route('project.index')
                            ->with('success','Project created successfully');
        }

        public function show($id){
            $role = Role::find($id);
            return view('project.show',compact('role','rolePermissions'));
        }

        public function edit($id){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $clientUser = User::whereHas(
                    'roles', function($q){
                        $q->where('name', 'client');
                    }
                )->get();
                $teamLeader = User::whereHas(
                    'roles', function($q){
                        $q->where('name', 'teamLeader');
                    }
                )->get();
                $membersUser = User::whereHas(
                    'roles', function($q){
                        $q->where('position', '4');
                    }
                )->get();
            } else {
                $clientUser = User::where('created_by', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->where('name', 'client');
                    }
                )->get();
                $teamLeader = User::where('created_by', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->where('name', 'teamLeader');
                    }
                )->get();
                $membersUser = User::where('created_by', Auth::id())->whereHas(
                    'roles', function($q){
                        $q->where('position', '4');
                    }
                )->get();
            }
            $project = Project::find($id);
            return view('project.edit',compact('project', 'clientUser', 'teamLeader', 'membersUser'));
        }

        public function update(Request $request, $id){
            $this->validate($request, [
                'name' => 'required',
                'description' => 'required',
                'start_end_date' => 'required',
                'client_id' => 'required',
                'manager_id' => 'required',
                'members_id' => 'required',
            ]);

            if(isset($request->project_file) && !empty($request->project_file)){
                $imageName = time().'.'.$request->project_file->extension();  
                $request->project_file->move(public_path('images/project'), $imageName);
                $allDate = explode("-", $request->start_end_date);
                $members_id = implode(",", $request->members_id);
                $manager_id = implode(",", $request->manager_id);

                $project = Project::find($id);
                $project->user_id = Auth::id();
                $project->name = $request->input('name');
                $project->description = $request->input('description');
                $project->start_date = date('Y-m-d',strtotime($allDate[0]));
                $project->end_date = date('Y-m-d',strtotime($allDate[1]));
                $project->client_id = $request->input('client_id');
                $project->file = $imageName;
                $project->members_id = $members_id.','.Auth::id();
                $project->manager_id = $manager_id;
                $project->save();
            }else{
                $allDate = explode("-", $request->start_end_date);
                $members_id = implode(",", $request->members_id);
                $manager_id = implode(",", $request->manager_id);

                $project = Project::find($id);
                $project->user_id = Auth::id();
                $project->name = $request->input('name');
                $project->description = $request->input('description');
                $project->start_date = date('Y-m-d',strtotime($allDate[0]));
                $project->end_date = date('Y-m-d',strtotime($allDate[1]));
                $project->client_id = $request->input('client_id');
                $project->members_id = $members_id.','.Auth::id();
                $project->manager_id = $manager_id;
                $project->save();
            }
            return redirect()->route('project.index')->with('success','Project updated successfully');
        }

        public function destroy($id){
            $service = Service::where('id', $id)->first();
            $service->delete();
            return redirect()->route('project.index')->with('success','Project deleted successfully');
        }


        public function teamList(){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $team = Team::get();
            } else {
                $team = Team::where('created_by', Auth::id())->get();
            }
            return view('team.index',compact('team'));
        }

        public function teamCreate(){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $user = User::get();
                $project = Project::orderBy('id','DESC')->get();
            } else {
                $project = Project::orderBy('id','DESC')
                ->orwhereRaw('FIND_IN_SET(' . Auth::id() . ', members_id)')
                ->orwhereRaw('FIND_IN_SET(' . Auth::id() . ', manager_id)')
                ->get();
            }
            return view('team.create',compact('user', 'project'));
        }

        public function getMembersData($id){
            
            $project = Project::where('id', $id)->first();
            // dd($project);
            $members = explode(',', $project->members_id);
            $membersList = [];
            foreach($members as $val){
                $membersData = User::where('id', $val)->first();
                $membersList[] = $membersData;
            }
            // $manager = explode(',', $project->manager_id);
            // $managerList = [];
            // foreach($manager as $val){
            //     $managerData = User::where('id', $val)->first();
            //     $managerList[] = $managerData;
            // }
            return view('team.getmembers', compact('project', 'membersList'));
        }

        public function teamStore(Request $request){
            $this->validate($request, [
                'name' => 'required',
                'project_id' => 'required',
                'members_id' => 'required',
            ]);
            // dd($request->all());
            $members_id = implode(",", $request->members_id);
            $team = [
                'created_by' => Auth::id(), 
                'name' => $request->name, 
                'project_id' => $request->project_id, 
                'members_id'=>$members_id,
            ];
            Team::create($team);
            return redirect()->route('team')
                            ->with('success','Team created successfully');
        }

        public function teamEdit($id){
            $team = Team::find($id);
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $project = Project::orderBy('id','DESC')->get();
            } else {
                $project = Project::orderBy('id','DESC')
                ->orwhereRaw('FIND_IN_SET(' . Auth::id() . ', members_id)')
                ->orwhereRaw('FIND_IN_SET(' . Auth::id() . ', manager_id)')
                ->get();
            }
            $projectData = Project::where('id', $team->project_id)->first();
            $members = explode(',', $projectData->members_id);
            $membersList = [];
            foreach($members as $val){
                $membersData = User::where('id', $val)->first();
                $membersList[] = $membersData;
            }
            return view('team.edit',compact('team', 'project', 'membersList'));
        }

        public function teamUpdate(Request $request, $id){
            $this->validate($request, [
                'name' => 'required',
                'project_id' => 'required',
                'members_id' => 'required',
            ]);
            $upTeam = Team::find($id);
            $upTeam->members_id = '';
            $upTeam->save();
            // dd($request->all());
            $members_id = implode(",", $request->members_id);
            
            $team = Team::find($id);
            $team->created_by = Auth::id();
            $team->name = $request->input('name');
            $team->project_id = $request->input('project_id');
            $team->members_id = $members_id;
            $team->save();
            
            return redirect()->route('team')->with('success','Team updated successfully');
        }

}