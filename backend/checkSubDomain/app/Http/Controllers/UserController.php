<?php

    namespace App\Http\Controllers;
    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use App\Models\User;
    use Spatie\Permission\Models\Role;
    use DB;
    use Hash;
    use Illuminate\Support\Arr;
    use Auth;

    

class UserController extends Controller{

    function __construct(){
        $this->middleware('permission:user-list|user-create|user-edit|user-delete', ['only' => ['index','store']]);
        $this->middleware('permission:user-create', ['only' => ['create','store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
 
    public function index(Request $request){
        // dd(Auth::user()->assignRole()->roles[0]['name']);
        if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
            $data = User::orderBy('id','DESC')->get();
        } else {
            $data = User::where('created_by', Auth::id())->orderBy('id','DESC')->get();
        }
        
        return view('users.index',compact('data'));
    }

    public function create(){
        $roles = Role::all();
        return view('users.create',compact('roles'));
    }


    public function store(Request $request){
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|same:confirm-password',
            'roles' => 'required'
        ]);

        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $random = $this->generateRandomString();
        $check = base64_encode($random);
        // dd($check);
        // dd(base64_decode($check));
        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = $input['password'];
        $user->check_password = $request->password;
        $user->created_by = Auth::id();
        $user->save();
        // $user = User::create($input);
        $user->assignRole($request->input('roles'));
        return redirect()->route('users.index')
                        ->with('success','User created successfully');
    }


    public function show($id)
    {
        if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
            $user = User::where('id', $id)->first();
        } else {
            $user = User::where('id', $id)->where('created_by', Auth::id())->first();
        }
        return view('users.show',compact('user'));
    }

    public function edit($id){
        if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
            $user = User::where('id', $id)->first();
        } else {
            $user = User::where('id', $id)->where('created_by', Auth::id())->first();
        }
        $roles = Role::all();
        $userRole = $user->roles->first();
        return view('users.edit',compact('user','roles','userRole'));
    }

    public function update(Request $request, $id){
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'same:confirm-password',
            'roles' => 'required'
        ]);

        $input = $request->all();
        if(!empty($input['password'])){ 
            $input['password'] = Hash::make($input['password']);
        }else{
            $input = Arr::except($input,array('password'));    
        }
        $input['created_by'] = Auth::id();
        // dd($input);
        $user = User::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();
        $user->assignRole($request->input('roles'));
        return redirect()->route('users.index')
                        ->with('success','User updated successfully');
    }

    
    public function destroy($id){
        User::find($id)->delete();
        return redirect()->route('users.index')
                        ->with('success','User deleted successfully');
    }

    public function ajaxView($id){
        $users = User::where('id', $id)->first();
        return view('users.ajaxView', compact('users'));
    }

    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }

}