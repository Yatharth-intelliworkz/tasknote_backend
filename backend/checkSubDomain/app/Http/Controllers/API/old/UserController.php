<?php

    namespace App\Http\Controllers\API;

    use App\Http\Controllers\Controller;
    use Illuminate\Http\Request;
    use App\Models\User;
    use App\Models\UserRole;
    use App\Models\UserModel;
    use App\Models\UserPermission;
    use App\Models\Company;
    use App\Models\TaskAssigne;
    use App\Models\Task;
    use App\Models\Team;
    use App\Models\UserFavorite;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Hash;
    

    class UserController extends Controller{
        
        public function index(Request $request){
            $userId = Auth::user()->id;
            $user = User::where('id', $userId)->first();
            // dd($user);
            if($user){
                if($user->profile){
                    $file = asset('public/images/profilePhoto/'. $user->profile);
                } else{
                    $file = null;
                }
                $response = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone_no' => $user->phone_no,
                    'company_name' => $user->company_name,
                    'gender' => $user->gender,
                    'address' => $user->address,
                    'gst_no' => $user->gst_no,
                    'profile' => $file,
                ];
                return response()->json([
                    'status' => true,
                    'message' => 'Profile data successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Profile data not found',
                ]);
            }
        }
        
        public function updateProfile(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'name' => 'required',
                    'phone_no' => 'required',
                ]);
                // dd($request->all());
                $user = User::find($userId);
                if($request->hasFile('profile') && $request->file('profile')->isValid()){
                    $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                    $request->profile->move(public_path('images/profilePhoto'), $imageName);
                    $user->name = $request->name;
                    $user->phone_no = $request->phone_no;
                    $user->profile = $imageName;
                    $user->save();
                } else {
                    $user = User::find($userId);
                    $user->name = $request->name;
                    $user->phone_no = $request->phone_no;
                    $user->save();
                }
                // if($request->file('profile')){
                //     $binaryFile = $request->file('profile');
                //     if ($binaryFile->getSize() > 0) {
                //         $binaryData = file_get_contents($binaryFile->getRealPath());
                //         $fileNameWithoutExtension = pathinfo($binaryFile->getClientOriginalName(), PATHINFO_FILENAME);
                //         $newFileName = $fileNameWithoutExtension . '_' . time() . '.' . $binaryFile->getClientOriginalExtension();
                //         $path = public_path('images/profilePhoto/') . $newFileName;
                //         file_put_contents($path, $binaryData);
                //         $user = User::find($userId);
                //         $user->name = $request->name;
                //         $user->phone_no = $request->phone_no;
                //         // $user->gender = $request->gender;
                //         $user->profile = $newFileName;
                //         $user->save();
                //     } 
                // } else {
                //         $user = User::find($userId);
                //         $user->name = $request->name;
                //         $user->phone_no = $request->phone_no;
                //         // $user->gender = $request->gender;
                //         $user->save();
                //     }
                if($user){
                    return response()->json([
                        'status' => true,
                        'message' => 'Update profile successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Update profile unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }

        public function updateBilling(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $user = User::find($userId);
                $user->phone_no = $request->phone_no;
                $user->company_name = $request->company_name;
                $user->address = $request->address;
                $user->gst_no = $request->gst_no;
                $user->save();
                
                $user = User::find($userId);
                if($user->files){
                    $file = asset('images/profile/'. $user->files);
                } else{
                    $file = null;
                }
                if($user){
                    return response()->json([
                        'status' => true,
                        'message' => 'Update billing successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Update billing unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function changePassword(Request $request){
            $userId = Auth::user()->id;
            $credentials = $request->validate([
                'oldPassword' => 'required|string|min:6',
                'password' => 'required|string|min:6',
            ]);
            $user = User::find($userId);
            if($user){
                if (!Hash::check($request->oldPassword, $user->password)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Old password not match',
                    ]);
                }
                $user = User::find($userId);
                $user->password = $request->password;
                $user->save();
                if($user){
                    return response()->json([
                        'status' => true,
                        'message' => 'Change Password successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Change Password unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        public function getUserList(Request $request){
            $userId = Auth::user()->id;
            $user = User::where('id', $userId)->first();
            $userData = User::orderBy('id', 'DESC')->where('created_by', $user->created_by)->get();
            $membersUser = User::orderBy('id', 'DESC')->where('created_by', $user->created_by)->whereHas(
                'roles', function($q){
                    $q->where('position', '5')->orWhere('name', 'teamLeader');
                }
            )->get();
            // dd($membersUser);
            $getData = [];
            foreach ($membersUser as $key => $value) {
                $m_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                ];
                $getData[] = $m_data;
            }
            if($getData){
                return response()->json([
                    'status' => true,
                    'message' => 'get users list successfully',
                    'data' => $getData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User data not found',
                ]);
            }
        }
        
        // user Roles

        public function roleList(Request $request){
            $companyID = $request->companyID;
            $role = UserRole::where('company_id', $companyID)->get();
            // dd($role);
            $roleData = [];
            foreach ($role as $key => $value) {
                $pro_data = [
                    'id' => $value->id,
                    'name' => $value->name,
                    'permission' => UserPermission::where('user_role_id', $value->id)->get(),
                ];
                $roleData[] = $pro_data;
            }
            if($roleData){
                return response()->json([
                    'status' => true,
                    'message' => 'Role list successfully',
                    'data' => $roleData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Role data not found',
                    'data' => []
                ]);
            }
        }
        
        public function roleGet(Request $request){
            $roleId = $request->roleId;
            $role = UserRole::where('id', $roleId)->first();
            $response = [
                'id' => $role->id,
                'name' => $role->name,
                'permission' => UserPermission::where('user_role_id', $role->id)->get(),
            ];
            if($response){
                return response()->json([
                    'status' => true,
                    'message' => 'Role get successfully',
                    'data' => $response
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Role data not found',
                    'data' => []
                ]);
            }
        }
        
        public function roleAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                
                $request->validate([
                    'companyId' => 'required',
                    'permission' => 'required',
                    'name' => 'required|string',
                ]);
                // dd($request->permission);
                $companyId = $request->companyId;
                $checkStatus = UserRole::where('company_id', $companyId)->where('name', $request->name)->first();
                if($checkStatus){
                    return response()->json([
                        'status' => false,
                        'message' => 'Role already exists',
                    ]);
                } else {
                    $role = new UserRole;
                    $role->company_id = $companyId;
                    $role->name = $request->name;
                    $role->save();
                    if (isset($request->is_sender)) {
                        $permissionData = json_decode($request->permission, true);
                    } else {
                        $permissionData = $request->permission;
                    }
                    foreach ($permissionData as $key => $value) {
                        $setRole = new UserPermission;
                        $setRole->user_role_id = $role->id;
                        $setRole->user_model_id = $key;
                        $setRole->save();
                        foreach ($value as $key => $val) {
                            $setPer = UserPermission::find($setRole->id);
                            $setPer->add = $val['add'];
                            $setPer->edit = $val['edit'];
                            $setPer->delete = $val['delete'];
                            $setPer->save();
                        }
                    }
                    if($role){
                        return response()->json([
                            'status' => true,
                            'message' => 'Role added successfully',
                        ]);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Role added unsuccessfully',
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function roleEdit(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'roleId' => 'required',
                    'permission' => 'required',
                    'name' => 'required|string',
                ]);
                $role = UserRole::find($request->roleId);
                $checkRole = UserRole::where('company_id', $role->company_id)->where('id', '!=' , $request->roleId)->where('name', $request->name)->first();
                if($checkRole){
                    return response()->json([
                        'status' => false,
                        'message' => 'Role already exists',
                    ]);
                } else {
                    $role->name = $request->name;
                    $role->save();
                    if (isset($request->is_sender)) {
                        $permissionData = json_decode($request->permission, true);
                    } else {
                        $permissionData = $request->permission;
                    }
                    UserPermission::where('user_role_id', $role->id)->delete();
                    foreach ($permissionData as $key => $value) {
                        $setRole = new UserPermission;
                        $setRole->user_role_id = $role->id;
                        $setRole->user_model_id = $key;
                        $setRole->save();
                        foreach ($value as $key => $val) {
                            $setPer = UserPermission::find($setRole->id);
                            $setPer->add = $val['add'];
                            $setPer->edit = $val['edit'];
                            $setPer->delete = $val['delete'];
                            $setPer->save();
                        }
                    }
                    if($role){
                        return response()->json([
                            'status' => true,
                            'message' => 'Role edited successfully',
                        ]);
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => 'Role edited unsuccessfully',
                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function roleDelete(Request $request){
            $request->validate([
                'roleId' => 'required',
            ]);
            $userRoleCheck = User::where('assignRole', $request->roleId)->first();
            if($userRoleCheck){
                return response()->json([
                    'status' => false,
                    'message' => 'This role is in used',
                ]);
            }
            $role = UserRole::where('id', $request->roleId)->first();
            if($role){
                UserPermission::where('user_role_id', $role->id)->delete();
                $role->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Role deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Role deleted unsuccessfully',
                ]);
            }
        }
        
        public function comapnyMemberList(Request $request){
            $companyID = $request->companyID;
            $member = User::where('company_id', $companyID)->whereHas(
                'roles', function($q){
                    $q->where('name', 'user');
                }
            )->get();
            $memberData = [];
            $comapnyData = Company::where('id', $companyID)->first();
            $userData = User::where('id', $comapnyData->user_id)->first();
            if($userData){
                if($userData->profile){
                    $profile = asset('public/images/profilePhoto/'. $userData->profile);
                } else {
                    $profile = asset('public/images/user_avatar.png');
                }
                if($userData->reportingTo){
                    $reportingTo = User::where('id', $userData->reportingTo)->first();
                    $toName = $reportingTo->name;
                    $toId = $reportingTo->id;
                } else {
                    $toName = null;
                    $reportingTo = null;
                    $toId = null;
                }
                if($userData->assignRole){
                    $assignRole = UserRole::where('id', $userData->assignRole)->first();
                    $assignName = $assignRole->name;
                    $assignId = $assignRole->id;
                } else {
                    $assignName = null;
                    $assignRole = null;
                    $assignId = null;
                }
                $userFavorite = UserFavorite::where('favorite_id', $userData->id)->where('is_favorite', 1)->first();
                if($userFavorite){
                    $is_favorite = 1;
                } else {
                    $is_favorite = 0;
                }
                $c_data = [
                    'id' => $userData->id,
                    'name' => $userData->name,
                    'email' => $userData->email,
                    'phone_no' => $userData->phone_no,
                    'designation' => $userData->designation,
                    'reportingTo' => $toName,
                    'assignRole' => $assignName,
                    'gender' => $userData->gender,
                    'dob' => date('d-m-Y',strtotime($userData->dob)),
                    'profile' => $profile,
                    'roleId' => $assignId,
                    'reportingId' => $toId,
                    'is_favorite' => $is_favorite,
                ];
                $memberData[] = $c_data;
            }
            // dd();
            foreach ($member as $key => $value) {
                if($value->id != $userData->id){
                    if($value->profile){
                        $profile = asset('public/images/profilePhoto/'. $value->profile);
                    } else {
                        $profile = asset('public/images/user_avatar.png');
                    }
                    if($value->reportingTo){
                        $reportingTo = User::where('id', $value->reportingTo)->first();
                        $toName = $reportingTo->name;
                        $toId = $reportingTo->id;
                    } else {
                        $toName = null;
                        $reportingTo = null;
                    }
                    if($value->assignRole){
                        $assignRole = UserRole::where('id', $value->assignRole)->first();
                        if($assignRole){
                            $assignName = $assignRole->name;
                            $assignId = $assignRole->id;
                        } else {
                            $assignName = null;
                            $assignId = null;
                        }
                    } else {
                        $assignName = null;
                    }
                    $userFavorite = UserFavorite::where('favorite_id', $value->id)->where('is_favorite', 1)->first();
                    if($userFavorite){
                        $is_favorite = 1;
                    } else {
                        $is_favorite = 0;
                    }
                    $m_data = [
                        'id' => $value->id,
                        'name' => $value->name,
                        'email' => $value->email,
                        'phone_no' => $value->phone_no,
                        'designation' => $value->designation,
                        'reportingTo' => $toName,
                        'assignRole' => $assignName,
                        'gender' => $value->gender,
                        'dob' => date('d-m-Y',strtotime($value->dob)),
                        'profile' => $profile,
                        'roleId' => $assignId,
                        'reportingId' => $toId,
                        'is_favorite' => $is_favorite,
                    ];
                    $memberData[] = $m_data;
                }
            }
            if($memberData){
                return response()->json([
                    'status' => true,
                    'message' => 'Member list successfully',
                    'data' => $memberData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Data not found',
                    'data' => []
                ]);
            }
        }
        
        public function comapnyMemberAdd(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'name' => 'required|string',
                    'email_id' => 'required|string|email',
                    'phone_no' => 'required',
                    'designation' => 'required',
                    'reportingTo' => 'required',
                    'gender' => 'required',
                    'dob' => 'required',
                    'companyID' => 'required',
                ]);
                $checkEmail = User::where('email', $request->email_id)->first();
                if($checkEmail){
                    return response()->json([
                        'status' => false,
                        'message' => 'Email already exists',
                    ]);
                }
                $assignRole = 0;
                $is_admin = 0;
                if ($request->admin_rights == 0) {
                    $assignRole = $request->assignRole;
                } else {
                    $is_admin = $request->admin_rights;
                }
                $password = $this->getRandomString();
                if($request->hasFile('profile') && $request->file('profile')->isValid()){
                    $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                    $request->profile->move(public_path('images/profilePhoto'), $imageName);
                    $member = [ 
                        'company_id' => $request->companyID,
                        'name' => $request->name,
                        'email' => $request->email_id, 
                        'phone_no' => $request->phone_no, 
                        'designation' => $request->designation,
                        'reportingTo' => $request->reportingTo, 
                        'assignRole' => $assignRole, 
                        'is_admin' => $is_admin, 
                        'gender' => $request->gender, 
                        'password' => Hash::make($password),
                        'dob' => date('Y-m-d',strtotime($request->dob)), 
                        'profile'=>$imageName,
                        'created_by'=>$userId,
                    ];
                }else {
                    $member = [
                        'company_id' => $request->companyID,
                        'name' => $request->name,
                        'email' => $request->email_id, 
                        'phone_no' => $request->phone_no, 
                        'designation' => $request->designation,
                        'reportingTo' => $request->reportingTo, 
                        'assignRole' => $assignRole, 
                        'is_admin' => $is_admin, 
                        'gender' => $request->gender,
                        'password' => Hash::make($password), 
                        'dob' => date('Y-m-d',strtotime($request->dob)),
                        'created_by'=>$userId,
                    ];
                }
                $member = User::create($member);
                $role = Role::firstOrCreate(['name' => 'user', 'id' => 2]);
                $member->assignRole($role);
                if($member){
                    return response()->json([
                        'status' => true,
                        'message' => 'Member added successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Member added unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function comapnyMemberGet(Request $request){
            $member = User::find($request->memberId);
            if($member){
                if($member->profile){
                    $profile = asset('public/images/profilePhoto/'. $member->profile);
                } else {
                    $profile = asset('public/images/user_avatar.png');
                }
                $response = [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email_id' => $member->email,
                    'phone_no' => $member->phone_no,
                    'designation' => $member->designation,
                    'reportingTo' => $member->reportingTo,
                    'assignRole' => $member->assignRole,
                    'gender' => $member->gender,
                    'dob' => date('d-m-Y',strtotime($member->dob)),
                    'profile' => $profile,
                ];
                if($response){
                    return response()->json([
                        'status' => true,
                        'message' => 'Member data successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Member data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Member data not found',
                    'data' => []
                ]);
            }
        }
        
        public function comapnyMemberEdit(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'name' => 'required|string',
                    'phone_no' => 'required',
                    'designation' => 'required',
                    'reportingTo' => 'required',
                    'gender' => 'required',
                    'dob' => 'required',
                    'memberId' => 'required',
                ]);
                $assignRole = 0;
                $is_admin = 0;
                if ($request->admin_rights == 0) {
                    $assignRole = $request->assignRole;
                } else {
                    $is_admin = $request->admin_rights;
                }
                $member = User::find($request->memberId);
                if($request->hasFile('profile') && $request->file('profile')->isValid()){
                    $imageName = mt_rand(10000000000,99999999999).'.'.$request->profile->extension();  
                    $request->profile->move(public_path('images/profilePhoto'), $imageName);
                    $member->name = $request->name;
                    $member->phone_no = $request->phone_no;
                    $member->designation = $request->designation;
                    $member->reportingTo = $request->reportingTo;
                    $member->assignRole = $assignRole;
                    $member->is_admin = $is_admin;
                    $member->gender = $request->gender;
                    $member->dob = date('Y-m-d',strtotime($request->dob));
                    $member->profile = $imageName;
                    $member->save();
                }else {
                    $member->name = $request->name;
                    $member->phone_no = $request->phone_no;
                    $member->designation = $request->designation;
                    $member->reportingTo = $request->reportingTo;
                    $member->assignRole = $assignRole;
                    $member->is_admin = $is_admin;
                    $member->gender = $request->gender;
                    $member->dob = date('Y-m-d',strtotime($request->dob));
                    $member->save();
                }
                if($member){
                    return response()->json([
                        'status' => true,
                        'message' => 'Member edited successfully',
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Member edited unsuccessfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function comapnyMemberDelete(Request $request){
            $request->validate([
                'memberId' => 'required',
            ]);
            $member = User::where('id', $request->memberId)->first();
            if($member){
                $member->delete();
                return response()->json([
                    'status' => true,
                    'message' => 'Member deleted successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Member deleted unsuccessfully',
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
        
        public function userDetails(Request $request){
            $request->validate([
                'userID' => 'required',
                'companyID' => 'required',
            ]);
            $userID = $request->userID;
            $user = User::where('id', $userID)->first();
            $companyID = $request->companyID;
            if($user){
                $myTaskData = [];
                $teamData = [];
                $taskAssigneTeamData = [];
                $totalAllTasks = 0;
                $totalCompletedTasks = 0;
                $totalIncompleteTasks = 0;
                $totalOverdueTasks = 0;
                $totalLowPriorityTasks = 0;
                $totalMediumPriorityTasks = 0;
                $totalHighPriorityTasks = 0;
                $totalPerformanceOnTrack = 0;
                $totalPerformanceBeforeTime = 0;
                $totalPerformanceDelayed = 0;
                $totalIncompletedProcessTasks = 0;
                $totalIncompletedPendingTasks = 0;
                $taskAssigneMemberData = [];
                $team = Team::whereRaw('FIND_IN_SET(' . $userID . ', members_id)')->get();
                foreach ($team as $key => $value) {
                    $ta_data = [
                        'teamId' => $value->id,
                    ];
                    $teamData[] = $ta_data;
                }
                $taskAssigneTeamData = [];
                $taskAssigneMemberData = [];
                $taskMyData = [];
                if($teamData){
                    foreach ($teamData as $key => $value) {
                        $teamId = $value['teamId'];
                        if($value['teamId']){
                            $taskAssigneTeam = TaskAssigne::whereRaw('FIND_IN_SET(' . $teamId . ', team_id)')->get();
                            foreach ($taskAssigneTeam as $key => $value) {
                                $taskAssigneTeamData[] =  $value->task_id;
                            }
                        }
                    }
                }
                $taskAssigneMember = TaskAssigne::whereRaw('FIND_IN_SET(' . $userID . ', members_id)')->get();
                foreach ($taskAssigneMember as $key => $value) {
                    $taskAssigneMemberData[] = $value->task_id;
                }
                $myTask = Task::where('user_id', $userID)->where('company_id', $companyID)->get();
                foreach ($myTask as $key => $value) {
                    $taskMyData[] = $value->id;
                }
                $assignTask = array_merge($taskAssigneTeamData, $taskAssigneMemberData, $taskMyData);
                // dd($assignTask);
                foreach (array_unique($assignTask) as $key => $value) {
                    $ta_data = [
                        'allTask' => Task::where('id', $value)->where('company_id', $companyID)->count(),
                        'completedTasks' => Task::where('id', $value)->where('completed', 1)->where('company_id', $companyID)->count(),
                        'incompleteTasks' => Task::where('id', $value)->where('completed', 0)->where('company_id', $companyID)->count(),
                        'overdueTasks' => Task::where('id', $value)->whereDate('due_date' , '>' , date("Y-m-d"))->where('company_id', $companyID)->count(),
                        'lowPriorityTasks' => Task::where('id', $value)->where('priority', 0)->where('company_id', $companyID)->count(),
                        'mediumPriorityTasks' => Task::where('id', $value)->where('priority', 2)->where('company_id', $companyID)->count(),
                        'highPriorityTasks' => Task::where('id', $value)->where('priority', 1)->where('company_id', $companyID)->count(),
                        'performanceOnTrack' => Task::where('id', $value)->where('completed', 0)->where('company_id', $companyID)->count(),
                        'performanceBeforeTime' => Task::where('id', $value)->where('completed', 1)->whereColumn('due_date', '>', 'completed_date')->where('company_id', $companyID)->count(),
                        'performanceDelayed' => Task::where('id', $value)->where('completed', 1)->whereColumn('due_date', '<', 'completed_date')->where('company_id', $companyID)->count(),
                        'incompletedProcessTasks' => Task::where('id', $value)->where('completed', 1)->where('company_id', $companyID)->count(),
                        'incompletedPendingTasks' => Task::where('id', $value)->where('completed', 0)->where('company_id', $companyID)->count(),
                    ];
                    // $ta_data = [
                    //     'allTask' => Task::where('id', $value)->count(),
                    //     'completedTasks' => Task::where('id', $value)->where('status', 2)->count(),
                    //     'incompleteTasks' => Task::where('id', $value)->where('status', 0)->count(),
                    //     'overdueTasks' => Task::where('id', $value)->where('status', 1)->count(),
                    //     'lowPriorityTasks' => Task::where('id', $value)->where('priority', 0)->count(),
                    //     'mediumPriorityTasks' => Task::where('id', $value)->where('priority', 1)->count(),
                    //     'highPriorityTasks' => Task::where('id', $value)->where('priority', 2)->count(),
                    //     'performanceOnTrack' => Task::where('id', $value)->where('status', 2)->whereDate('due_date', date('Y-m-d'))->count(),
                    //     'performanceBeforeTime' => Task::where('id', $value)->where('status', 2)->whereDate('due_date', '<', date('Y-m-d'))->count(),
                    //     'performanceDelayed' => Task::where('id', $value)->where('status', 2)->whereDate('due_date', '>', date('Y-m-d'))->count(),
                    //     'incompletedProcessTasks' => Task::where('id', $value)->where('status', 1)->whereDate('due_date', '>', date('Y-m-d'))->count(),
                    //     'incompletedPendingTasks' => Task::where('id', $value)->where('status', 0)->whereDate('due_date', '>', date('Y-m-d'))->count(),
                    // ];
                    $totalAllTasks += $ta_data['allTask'];
                    $totalCompletedTasks += $ta_data['completedTasks'];
                    $totalIncompleteTasks += $ta_data['incompleteTasks'];
                    $totalOverdueTasks += $ta_data['overdueTasks'];
                    $totalLowPriorityTasks += $ta_data['lowPriorityTasks'];
                    $totalMediumPriorityTasks += $ta_data['mediumPriorityTasks'];
                    $totalHighPriorityTasks += $ta_data['highPriorityTasks'];
                    $totalPerformanceOnTrack += $ta_data['performanceOnTrack'];
                    $totalPerformanceBeforeTime += $ta_data['performanceBeforeTime'];
                    $totalPerformanceDelayed += $ta_data['performanceDelayed'];
                    $totalIncompletedProcessTasks += $ta_data['incompletedProcessTasks'];
                    $totalIncompletedPendingTasks += $ta_data['incompletedPendingTasks'];

                    $myTaskData[] = $ta_data;
                }
                $totalCounts = [
                    'totalAllTasks' => $totalAllTasks,
                    'totalCompletedTasks' => $totalCompletedTasks,
                    'totalIncompleteTasks' => $totalIncompleteTasks,
                    'totalOverdueTasks' => $totalOverdueTasks,
                    'totalLowPriorityTasks' => $totalLowPriorityTasks,
                    'totalMediumPriorityTasks' => $totalMediumPriorityTasks,
                    'totalHighPriorityTasks' => $totalHighPriorityTasks,
                    'totalPerformanceOnTrack' => $totalPerformanceOnTrack,
                    'totalPerformanceBeforeTime' => $totalPerformanceBeforeTime,
                    'totalPerformanceDelayed' => $totalPerformanceDelayed,
                    'totalIncompletedProcessTasks' => $totalIncompletedProcessTasks,
                    'totalIncompletedPendingTasks' => $totalIncompletedPendingTasks,
                ];
                
                if($totalCounts){
                    return response()->json([
                        'status' => true,
                        'message' => 'User Details successfully',
                        'data' => $totalCounts
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'User data not found',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User data not found',
                    'data' => []
                ]);
            }
        }
        
        public function userFavorite(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'favoriteID' => 'required',
                    'is_favorite' => 'required',
                ]);
                $user = UserFavorite::where('favorite_id', $request->favoriteID)->where('user_id', $userId)->first();
                if($user){
                    $user->is_favorite = $request->is_favorite;
                    $user->save();
                    return response()->json([
                        'status' => true,
                        'message' => 'User favorite change successfully',
                    ]);
                } else {
                    $user = [
                        'user_id' => $userId,
                        'favorite_id' => $request->favoriteID, 
                        'is_favorite' => $request->is_favorite, 
                    ];
                    $user =  UserFavorite::create($user);
                    return response()->json([
                        'status' => true,
                        'message' => 'User favorite change successfully',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function userMemberList(Request $request){
            $companyID = $request->companyID;
            $userId = Auth::user()->id;    
            $member = User::select('users.*')
                ->Join("companies","companies.id","=","users.company_id")
                ->where('users.company_id', $companyID)->whereHas(
                    'roles', function($q){
                        $q->where('name', 'user');
                    }
                )->get();
            // dd($userId);
            $memberData = [];
            $comapnyData = Company::where('id', $companyID)->first();
            $userData = User::where('id', $comapnyData->user_id)->first();
            foreach ($member as $key => $value) {
                $assignName = null;
                $assignId = null;
                $toName = null;
                $reportingTo = null;
                $toId = null;
                // if($value->id != $userData->id){
                if($value->profile){
                    $profile = asset('public/images/profilePhoto/'. $value->profile);
                } else {
                    $profile = asset('public/images/user_avatar.png');
                }
                if($value->reportingTo){
                    $reportingTo = User::where('id', $value->reportingTo)->first();
                    $toName = $reportingTo->name;
                    $toId = $reportingTo->id;
                } 
                if($value->assignRole){
                    $assignRole = UserRole::where('id', $value->assignRole)->first();
                    if($assignRole){
                        $assignName = $assignRole->name;
                        $assignId = $assignRole->id;
                    }   
                } else {
                    $assignName = null;
                }
                $userFavorite = UserFavorite::where('favorite_id', $value->id)->where('user_id', $userId)->where('is_favorite', 1)->first();
                if($userFavorite){
                    $is_favorite = 1;
                } else {
                    $is_favorite = 0;
                }
                 $m_data = [
                     'id' => $value->id,
                     'name' => $value->name,
                     'email' => $value->email,
                     'phone_no' => $value->phone_no,
                     'designation' => $value->designation,
                     'reportingTo' => $toName,
                     'assignRole' => $assignName,
                     'gender' => $value->gender,
                     'dob' => date('d-m-Y',strtotime($value->dob)),
                     'profile' => $profile,
                     'roleId' => $assignId,
                     'reportingId' => $toId,
                     'is_favorite' => $is_favorite,
                 ];
                 $memberData[] = $m_data;
                // }
                usort($memberData, function ($a, $b) {
                    return $b['is_favorite'] <=> $a['is_favorite'];
                });
            }
        
            if($memberData){
                return response()->json([
                    'status' => true,
                    'message' => 'Member list successfully',
                    'data' => $memberData
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Member data not found',
                    'data' => []
                ]);
            }
        }
        
        public function getShortName(Request $request){
            $userId = Auth::user()->id;
            if($userId){
                $request->validate([
                    'userID' => 'required',
                ]);
                $user = User::where('id', $request->userID)->first();
                if($user){
                    $words = explode(" ", $user->name);
                    $acronym = "";
                    foreach ($words as $key => $w) {
                        if($key <= 1){
                            $acronym .= mb_substr($w, 0, 1);
                        }
                    }
                    $response = [
                        'name' => $user->name,
                        'setWords' => strtoupper($acronym),
                    ];
                    return response()->json([
                        'status' => true,
                        'message' => 'User name successfully',
                        'data' => $response
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'User not found',
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ]);
            }
        }
        
        public function profileremove(Request $request) {
            $userId = Auth::user()->id;
            $user = User::find($userId);
        
            if ($user) {
                $data = User::where('id', $userId)->update(['profile' => null]);
        
                return response()->json([
                    'status' => true,
                    'message' => 'Profile Photo Removed successfully',
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Profile Photo not found',
                ]);
            }
        }
    }
