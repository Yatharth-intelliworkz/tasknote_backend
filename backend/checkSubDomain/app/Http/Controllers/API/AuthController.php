<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\Company;
    use App\Models\ClientAttestment;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use DB;

    class AuthController extends Controller{
        public function __construct(){
            $this->middleware('auth:api', ['except' => ['login', 'register']]);
        }

        public function login(Request $request){
            $credentials = $request->validate([
                'email' => 'required|string|email',
                'password' => 'required|string',
            ]);
            $password = Hash::make($request->password);
            $user = User::where('email', $request->email)->first();
            if($user){
                if(Hash::check($request->password , $user->password )){
                    foreach ($user->getRoleNames() as $key => $value) {
                        // dd($value);
                        if(empty($value == 'superAdmin') && empty($value == 'admin')){
                            // dd($value);
                            $credentials = $request->only('email', 'password');
                            if (auth()->attempt($credentials)) {
                                $user = Auth::user();
                                $user['token'] = $user->createToken('Laravelia')->accessToken;
                                if($user){
                                    $role = Role::where('name', $user->getRoleNames()[0])->first();
                                    $permission = Permission::get();
                                    $pData = [];
                                    $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$role->id)
                                        ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
                                        ->all();
                                    foreach ($permission as $key => $value) {
                                        $setRole = in_array($value->id, $rolePermissions) ? '1' : '0';
                                        $pro_data = [
                                            'id' => $value->id,
                                            'name' => $value->name,
                                            'is_permissions' => $setRole,
                                        ];
                                        $pData[] = $pro_data;
                                    }
                                    $company = Company::where('user_id', $user->id)->first();
                                    if($company){
                                        $c_id = $company->id;
                                        // $userUpdate = User::where('email', $request->email)->first();
                                        // $userUpdate->company_id = $c_id;
                                        // $userUpdate->save();
                                    } else {
                                        $c_id = 0;
                                    }
                                    $response = [
                                        'id' => $user->id,
                                        'name' => $user->name,
                                        'email' => $user->email,
                                        'email_verified_at' => $user->email_verified_at,
                                        'phone_no' => $user->phone_no,
                                        'created_by' => $user->created_by,
                                        'check_password' => $user->check_password,
                                        'token' => $user->token,
                                        'company_id' => $user->company_id,
                                        'roleName' => $user->getRoleNames()[0],
                                        'permission' => $pData,
                                    ];
                                    
                                }
                                return response()->json([
                                    'status' => true,
                                    'message' => 'Login successfully',
                                    'data' => $response
                                ], 200);
                            }
                        } else {
                            return response()->json([
                                'status' => false,
                                'message' => 'Invalid credentials'
                            ], 402);
                        }
                    }
                }
            }
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 402);
        }

        public function register(Request $request){
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'phone_no' => 'required|string|max:255',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no' => $request->phone_no,
                'password' => Hash::make($request->password),
            ]);
            $user->assignRole('10');
            $userData = User::where('id', $user->id)->first();
            if($userData){
                return response()->json([
                    'status' => true,
                    'message' => 'User created successfully',
                    'data' => $userData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User created unsuccessfully',
                    'data' => []
                ]);
            }
        }

        public function logout(){
            Auth::user()->tokens()->delete();
            return response()->json([
                'status' => true,
                'message' => 'Successfully logged out',
            ]);
        }
        
        public function clientList(Request $request){
            $companyID = $request->companyID;
            $client = User::orderBy('id', 'DESC')->where('company_id', $companyID)->whereHas(
                'roles', function($q){
                    $q->where('name', 'client');
                }
            )->get();
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
                    'address' => $value->address,
                    'company_name' => $value->company_name,
                    'profile' => $profile,
                    'gender' => $value->gender,
                ];
                $clientData[] = $pro_data;
            }
            if($clientData){
                return response()->json([
                    'status' => true,
                    'message' => 'Client list successfully',
                    'data' => $clientData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Client data not found',
                    'data' => []
                ]);
            }
        }
        
        public function clientAdd(Request $request){
            $request->validate([
                'companyId' => 'required',
                'name' => 'required|string',
                'email' => 'required|string|email|max:255',
                'phone_no' => 'required',
                'gender' => 'required',
                'address' => 'required',
                'company_name' => 'required',
            ]);
            if($request->email){
                $checkname = User::where('email', '!=' , $request->email)->first();
                if($checkname){
                    return response()->json([
                        'status' => false,
                        'code' => 210,
                        'message' => 'client email alrady exist',
                    ]);
                }
            }
            $password = $this->getRandomString();
            if($request->hasFile('files') && $request->file('files')->isValid()){
                if($request->hasFile('profile') && $request->file('profile')->isValid()){
                    $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                    $request->profile->move(public_path('images/profilePhoto'), $imageName);
                    $user = [
                        'company_id' => $request->companyId,
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone_no' => $request->phone_no,
                        'gender' => $request->gender,
                        'address' => $request->address,
                        'company_name' => $request->company_name,
                        'password' => Hash::make($password),
                        'profile'=>$imageName,
                    ];
                } else {
                    $user = [
                        'company_id' => $request->companyId,
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone_no' => $request->phone_no,
                        'gender' => $request->gender,
                        'address' => $request->address,
                        'company_name' => $request->company_name,
                        'password' => Hash::make($password),
                    ];
                }
                $user = User::create($user);
                $role = Role::firstOrCreate(['name' => 'client', 'id' => 5]);
                $user->assignRole($role);
                if ($request->hasFile('files')) {
                    $sheets = $request->file('files');
                    $upload_files = [];
                    foreach ($sheets as $sheet) {
                        $filename = $sheet->getClientOriginalName();
                        $extension = $sheet->getClientOriginalExtension();
                        $filename = mt_rand(10000000000,99999999999) . '.' . $extension;
                        $path = public_path('images/clientAttestment');
                        if ($sheet->move($path, $filename)) {
                            $image = new ClientAttestment;
                            $image->file = $filename;
                            $image->client_id = $user->id;
                            $image->type = 'client';
                            $image->save();
                        }
                    }
                }
            }else {
                if($request->hasFile('profile') && $request->file('profile')->isValid()){
                    $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                    $request->profile->move(public_path('images/profilePhoto'), $imageName);
                    $user = [
                        'company_id' => $request->companyId,
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone_no' => $request->phone_no,
                        'gender' => $request->gender,
                        'address' => $request->address,
                        'company_name' => $request->company_name,
                        'password' => Hash::make($password),
                        'profile'=>$imageName,
                    ];
                } else {
                    $user = [
                        'company_id' => $request->companyId,
                        'name' => $request->name,
                        'email' => $request->email,
                        'phone_no' => $request->phone_no,
                        'gender' => $request->gender,
                        'address' => $request->address,
                        'company_name' => $request->company_name,
                        'password' => Hash::make($password),
                    ];
                }
                $user = User::create($user);
                $role = Role::firstOrCreate(['name' => 'client', 'id' => 5]);
                $user->assignRole($role);
            }
            $userData = User::where('id', $user->id)->first();
            if($userData){
                return response()->json([
                    'status' => true,
                    'message' => 'Client added successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Client added unsuccessfully',
                ]);
            }
            
        }
        
        public function clientGet(Request $request){
            $request->validate([
                'clientId' => 'required',
            ]);
            $userData = User::find($request->clientId);
            if($userData){
                if($userData->profile){
                    $profile = asset('public/images/profilePhoto/'. $userData->profile); 
                } else {
                    $profile = asset('public/images/user_avatar.png');
                }
                $response = [
                    'id' => $userData->id,
                    'name' => $userData->name,
                    'email' => $userData->email,
                    'phone_no' => $userData->phone_no,
                    'company_name' => $userData->company_name,
                    'address' => $userData->address,
                    'profile' => $profile,
                    'gender' => $userData->gender,
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Client get successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Client data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Client data not found',
                    'data' => []
                ]);
            }
        }
        
        public function clientEdit(Request $request){
            // dd($request->all());
            $request->validate([
                'clientId' => 'required',
            ]);
            $userData = User::find($request->clientId);
            
            if($userData){
                if($request->hasFile('files') && $request->file('files')->isValid()){
                    if($request->hasFile('profile') && $request->file('profile')->isValid()){
                        $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                        $request->profile->move(public_path('images/profilePhoto'), $imageName);
                        $userData->name = $request->name;
                        $userData->email = $request->email;
                        $userData->phone_no = $request->phone_no;
                        $userData->gender = $request->gender;
                        $userData->address = $request->address;
                        $userData->company_name = $request->company_name;
                        $userData->profile = $imageName;
                        $userData->save();   
                    } else {
                        $userData->name = $request->name;
                        $userData->email = $request->email;
                        $userData->phone_no = $request->phone_no;
                        $userData->gender = $request->gender;
                        $userData->address = $request->address;
                        $userData->company_name = $request->company_name;
                        $userData->save(); 
                    }
                    if ($request->hasFile('files')) {
                        $sheets = $request->file('files');
                        $upload_files = [];
                        foreach ($sheets as $sheet) {
                            $filename = $sheet->getClientOriginalName();
                            $extension = $sheet->getClientOriginalExtension();
                            $filename = mt_rand(10000000000,99999999999) . '.' . $extension;
                            $path = public_path('images/clientAttestment');
                            if ($sheet->move($path, $filename)) {
                                $image = new ClientAttestment;
                                $image->file = $filename;
                                $image->client_id = $userData->id;
                                $image->type = 'client';
                                $image->save();
                            }
                        }
                    }
                }else {
                    if($request->hasFile('profile') && $request->file('profile')->isValid()){
                        $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                        $request->profile->move(public_path('images/profilePhoto'), $imageName);
                        $userData->name = $request->name;
                        $userData->email = $request->email;
                        $userData->phone_no = $request->phone_no;
                        $userData->gender = $request->gender;
                        $userData->address = $request->address;
                        $userData->company_name = $request->company_name;
                        $userData->profile = $imageName;
                        $userData->save(); 
                    } else {
                        $userData->name = $request->name;
                        $userData->email = $request->email;
                        $userData->phone_no = $request->phone_no;
                        $userData->gender = $request->gender;
                        $userData->address = $request->address;
                        $userData->company_name = $request->company_name;
                        $userData->save(); 
                    }
                }
                return response()->json([
                    'status' => true,
                    'message' => 'Client edited successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Client edited unsuccessfully',
                ]);
            }
            
        }
        
        public function clientDelete(Request $request){
            $request->validate([
                'clientId' => 'required',
            ]);
            $userData = User::find($request->clientId);
            if($userData){
                $userData->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Client deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Client data not found',
                ]);
            }
        }

        function getRandomString($n = 10) {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $randomString = '';
         
            for ($i = 0; $i < $n; $i++) {
                $index = rand(0, strlen($characters) - 1);
                $randomString .= $characters[$index];
            }
         
            return $randomString;
        }
    }
