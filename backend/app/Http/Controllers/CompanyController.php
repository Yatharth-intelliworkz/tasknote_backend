<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use App\Models\Company;
    use DB;
    use Auth;

    class CompanyController extends Controller {

        function __construct(){
            $this->middleware('permission:company-list|company-create|company-edit|company-delete', ['only' => ['index','show']]);
            $this->middleware('permission:company-create', ['only' => ['create','store']]);
            $this->middleware('permission:company-edit', ['only' => ['edit','update']]);
            $this->middleware('permission:company-delete', ['only' => ['destroy']]);
        }

        public function index(Request $request){
            $company = Company::orderBy('id','DESC')->get();
            return view('company.index',compact('company'));
        }

        public function create(){
            return view('company.create');
        }

    
        public function store(Request $request){
            // dd($request->all());
            $validatedData = $request->validate(
                [
                    'name' => 'required',
                    'email_id' => 'required|unique:companies,email_id',
                    'phone_no' => 'required',
                    'address' => 'required',
                    'description' => 'required',
                ],
                [
                    'name.required' => 'Please enter a Name.',
                    'email_id.required' => 'Please enter Email.',
                    'phone_no.required' => 'Please enter Phone No.',
                    'address.required' => 'Please enter Address.',
                    'description.required' => 'Please enter Description.',
                ]
            );
            if(isset($request->logo) && !empty($request->logo)){
                $imageName = time().'.'.$request->logo->extension();  
                $request->logo->move(public_path('images/company'), $imageName);
                $company = [
                    'name' => $request->name,
                    'user_id' => Auth::id(), 
                    'email_id' => $request->email_id, 
                    'phone_no' => $request->phone_no, 
                    'address' => $request->address,
                    'description' => $request->description, 
                    'logo'=>$imageName,
                ];
                Company::create($company);
            }else {
                $company = [
                    'name' => $request->name, 
                    'user_id' => Auth::id(),
                    'email_id' => $request->email_id, 
                    'phone_no' => $request->phone_no, 
                    'address' => $request->address,
                    'description' => $request->description, 
                ];
                Company::create($company);
            }
            return redirect()->route('company.index')
                            ->with('success','Company created successfully');
        }

        public function show($id){
            $role = Role::find($id);
            $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
                ->where("role_has_permissions.role_id",$id)
                ->get();
            return view('service.show',compact('role','rolePermissions'));
        }

        public function edit($id){
            $company = Company::find($id);
            return view('company.edit',compact('company'));
        }

        public function update(Request $request, $id){
            $validatedData = $request->validate(
                [
                    'name' => 'required',
                    'phone_no' => 'required',
                    'address' => 'required',
                    'description' => 'required',
                ],
                [
                    'name.required' => 'Please enter a Name.',
                    'phone_no.required' => 'Please enter Phone No.',
                    'address.required' => 'Please enter Address.',
                    'description.required' => 'Please enter Description.',
                ]
            );
            if(isset($request->logo) && !empty($request->logo)){
                $imageName = time().'.'.$request->logo->extension();  
                $request->logo->move(public_path('images/company'), $imageName);
                $company = Company::find($id);
                $company->name = $request->name;
                $company->user_id = Auth::id();
                $company->phone_no = $request->phone_no;
                $company->address = $request->address;
                $company->description = $request->description;
                $company->logo = $imageName;
                $company->save();
            }else {
                $company = Company::find($id);
                $company->name = $request->name;
                $company->user_id = Auth::id();
                $company->phone_no = $request->phone_no;
                $company->address = $request->address;
                $company->description = $request->description;
                $company->save();
            }
            return redirect()->route('company.index')->with('success','Company updated successfully');
        }

        public function destroy($id){
            $company = Company::where('id', $id)->first();
            $company->delete();
            return redirect()->route('company.index')->with('success','Company deleted successfully');
        }

}