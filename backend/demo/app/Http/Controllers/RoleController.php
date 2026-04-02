<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use DB;
    use Auth;

    class RoleController extends Controller {

        function __construct(){
            $this->middleware('permission:role-list|role-create|role-edit|role-delete', ['only' => ['index','store']]);
            $this->middleware('permission:role-create', ['only' => ['create','store']]);
            $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
            $this->middleware('permission:role-delete', ['only' => ['destroy']]);
        }

        public function index(Request $request){
            // $roleName = Auth::user()->assignRole()->roles[0]['name'];
            // if($roleName = 'superAdmin'){
            //     $roles = Role::orderBy('id','DESC')->get();
            // }
            $roles = Role::orderBy('id','DESC')->get();
            // dd(Auth::user()->assignRole()->roles[0]['name']);
            return view('roles.index',compact('roles'));
        }

        public function create(){
            $permission = Permission::get();
            return view('roles.create',compact('permission'));
        }

    
        public function store(Request $request){
            $this->validate($request, [
                'name' => 'required|unique:roles,name',
                'permission' => 'required',
            ]);
            $roleData = [
                'name' => $request->input('name'), 
                'position' => 5, 
            ];
            $role = Role::create($roleData);
            $role->syncPermissions($request->input('permission'));
            return redirect()->route('roles.index')
                            ->with('success','Role created successfully');
        }

        public function show($id){
            $role = Role::find($id);
            $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
                ->where("role_has_permissions.role_id",$id)
                ->get();
            return view('roles.show',compact('role','rolePermissions'));
        }

        public function edit($id){
            $role = Role::where('id', $id)->first();
            $permission = Permission::get();
            $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
                ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
                ->all();
            return view('roles.edit',compact('role','permission','rolePermissions'));
        }

        public function update(Request $request, $id){
            // dd($request->all());
            $this->validate($request, [
                'name' => 'required',
                'permission' => 'required',
            ]);
            $role = Role::find($id);
            $role->name = $request->input('name');
            $role->save();
            // dd($request->input('permission'));
            $role->syncPermissions($request->input('permission'));
            return redirect()->route('roles.index')->with('success','Role updated successfully');
        }

        public function destroy($id){
            $client = Role::where('id', $id)->where('fixed_role', 0)->first();
            $client->delete();
            return redirect()->route('roles.index')->with('success','Role deleted successfully');
        }

    }