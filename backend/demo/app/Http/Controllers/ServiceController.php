<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use App\Models\Service;
    use DB;
    use Auth;

    class ServiceController extends Controller {

        function __construct(){
            $this->middleware('permission:service-list|service-create|service-edit|service-delete', ['only' => ['index','show']]);
            $this->middleware('permission:service-create', ['only' => ['create','store']]);
            $this->middleware('permission:service-edit', ['only' => ['edit','update']]);
            $this->middleware('permission:service-delete', ['only' => ['destroy']]);
        }

        public function index(Request $request){
            $service = Service::orderBy('id','DESC')->get();
            return view('service.index',compact('service'));
        }

        public function create(){
            return view('service.create');
        }

    
        public function store(Request $request){
            $this->validate($request, [
                'title' => 'required',
            ]);

            $service = Service::create(['title' => $request->input('title')]);
            return redirect()->route('service.index')
                            ->with('success','Service created successfully');
        }

        public function show($id){
            $role = Role::find($id);
            $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
                ->where("role_has_permissions.role_id",$id)
                ->get();
            return view('service.show',compact('role','rolePermissions'));
        }

        public function edit($id){
            $service = Service::find($id);
            return view('service.edit',compact('service'));
        }

        public function update(Request $request, $id){
            $this->validate($request, [
                'title' => 'required',
            ]);
            $service = Service::find($id);
            $service->title = $request->input('title');
            $service->save();
            return redirect()->route('service.index')->with('success','Service updated successfully');
        }

        public function destroy($id){
            $service = Service::where('id', $id)->first();
            $service->delete();
            return redirect()->route('service.index')->with('success','Service deleted successfully');
        }

}