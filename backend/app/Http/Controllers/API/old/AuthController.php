<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\CustomerSetup;
    use App\Models\Company;
    use App\Models\UserType;
    use App\Models\ClientAttestment;
    use App\Models\ProjectClient;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Mail;
    use DB;

    class AuthController extends Controller{
        public function __construct(){
            $this->middleware('auth:api', ['except' => ['login', 'register', 'forgotPassword', 'checkCustomer']]);
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
                                    if (isset($request->is_sender)) {
                                        $userUpdate = User::where('email', $request->email)->first();
                                        $userUpdate->fcm_token = $request->fcm_token;
                                        $userUpdate->save();
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
                                    $userTypes = '';
                                    $UserType = UserType::where('id', $user->user_type)->first();
                                    if($UserType){
                                        $userTypes = $UserType->title;
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
                                        'user_type' => $userTypes,
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
                'email' => 'required|string|email',
                'password' => 'required|string|min:6',
                'phone_no' => 'required|string|max:255',
            ]);
            $checkEmail = User::where('email', $request->email)->first();
            if($checkEmail){
                return response()->json([
                    'status' => false,
                    'message' => 'Email already exists',
                ]);
            }
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone_no' => $request->phone_no,
                'password' => Hash::make($request->password),
            ]);
            $user->assignRole('10');
            $userData = User::where('id', $user->id)->first();
            if($userData){
                $email = $user->email;
                    $passwordData = [
                'name' => $user->name,
                'email' => $user->email,
                'phone_no' => $user->phone_no,
                ];
             Mail::send('mail.Registeration', ['info' => $passwordData], function ($message) use ($passwordData, $email) {
                $message->to($email)->subject('Task Note');
            });
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
            $client = ProjectClient::where('company_id', $companyID)->orderBy('id', 'DESC')->get();
            $clientData = [];
            foreach ($client as $key => $value) {
                if($value->profile){
                    $profile = asset('public/images/profilePhoto/'. $value->profile); 
                } else {
                    $profile = asset('public/images/user_avatar.png');
                }
                $is_active = 'Active';
                if($value->is_active == 1){
                    $is_active = 'InActive';
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
                    'refernce_by' => $value->refernce_by,
                    'is_active' => $is_active,
                    'client_code' => $value->client_code,
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
                $checkname = ProjectClient::where('email', $request->email)->first();
                if($checkname){
                    return response()->json([
                        'status' => false,
                        'code' => 210,
                        'message' => 'client email alrady exist',
                    ]);
                }
            }
            $is_active = 0;
                if ($request->is_active == 1) {
                    $is_active = 1;
                } 
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
                        'refernce_by' => $request->refernce_by,
                        'profile'=>$imageName,
                        'is_active' => $is_active, 
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
                        'refernce_by' => $request->refernce_by,
                        'is_active' => $is_active, 
                    ];
                }
                $user = ProjectClient::create($user);
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
                        'refernce_by' => $request->reference_by,
                        'profile'=>$imageName,
                        'is_active' => $is_active, 
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
                        'refernce_by' => $request->reference_by,
                        'is_active' => $is_active, 
                    ];
                }
                $user = ProjectClient::create($user);
            }
            $userData = ProjectClient::where('id', $user->id)->first();
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
            $userData = ProjectClient::find($request->clientId);
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
                    'refernce_by' => $userData->refernce_by,
                    'is_active' => $userData->is_active,
                    'client_code' => $userData->client_code,
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
            $userData = ProjectClient::find($request->clientId);
            
            if($userData){
                $is_active = 0;
                if ($request->is_active == 1) {
                    $is_active = 1;
                } 
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
                        $userData->refernce_by = $request->reference_by;
                        $userData->profile = $imageName;
                        $userData->is_active = $is_active;
                        $userData->client_code = $request->client_code;
                        
                        $userData->save();   
                    } else {
                        $userData->name = $request->name;
                        $userData->email = $request->email;
                        $userData->phone_no = $request->phone_no;
                        $userData->gender = $request->gender;
                        $userData->address = $request->address;
                        $userData->company_name = $request->company_name;
                        $userData->refernce_by = $request->reference_by;
                        $userData->is_active = $is_active;
                        $userData->client_code = $request->client_code;
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
                        $userData->refernce_by = $request->reference_by;
                        $userData->is_active = $is_active;
                        $userData->client_code = $request->client_code;
                        $userData->profile = $imageName;
                        $userData->save(); 
                    } else {
                        $userData->name = $request->name;
                        $userData->email = $request->email;
                        $userData->phone_no = $request->phone_no;
                        $userData->gender = $request->gender;
                        $userData->address = $request->address;
                        $userData->company_name = $request->company_name;
                        $userData->refernce_by = $request->reference_by;
                        $userData->client_code = $request->client_code;
                        $userData->is_active = $is_active;
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
            $userData = ProjectClient::find($request->clientId);
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
        
        public function forgotPassword(Request $request){
            $request->validate([
                'email' => 'required',
            ]);
            $email = $request->email;
            $user = User::where('email', $email)->first();
            if($user){
                $password = $this->getRandomString();
                // dd($password);
                $user->password = Hash::make($password);
                $user->save();
                $emailData = [
                    'userName' => $user->name,
                    'email' => $user->email,
                    'password' => $password,
                ];
                Mail::send('mail.forgot_password', ['info' => $emailData], function ($message) use ($emailData) {
                    $message->to($emailData['email'])->subject('Task Manager Password Reset Request');
                });
                if($user){
                    return response()->json([
                        'status' => true,
                        'message' => 'Your mail send successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Your mail send unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not current',
                ]);
            }
        }
        
        public function checkCustomer(Request $request){
            // dd($request->all());
            $request->validate([
                'appCode' => 'required',
            ]);
            $customerData = CustomerSetup::where('app_code', $request->appCode)->where('is_setup', 0)->first();
            if($customerData){
                $response = [
                    'name' => $customerData->name,
                    'url' => $customerData->url,
                    'puser_id' => $customerData->puser_id,
                    'app_code' => $customerData->app_code,
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Customer get successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Customer data not found',
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
        
        public function importclinet(Request $request){
            $request->validate([
                'companyid' => 'required',
            ]);
            if($request->datas){
                $data = $request->datas;
                // $data = json_decode($request->datas, true);
                foreach ($data as $val) {
                    $gender = 0;
                    if(strtolower($val['gender'] = 'Female')){
                        $gender = 1;
                    } 
                    $user = [
                        'company_id' => $request->companyid,
                        'name' => $val['Name'],
                        'email' => $val['email'],
                        'phone_no' => $val['phone_no'],
                        'gender' => $gender,
                        'address' => $val['Address'],
                        'company_name' => $val['Company'],
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
