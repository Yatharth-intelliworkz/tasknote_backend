<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use App\Http\Controllers\Controller;
    use Spatie\Permission\Models\Role;
    use Spatie\Permission\Models\Permission;
    use App\Models\Note;
    use DB;
    use Auth;

    class NoteController extends Controller {

        function __construct(){
            $this->middleware('permission:note-list|note-create|note-edit|note-delete', ['only' => ['index','show']]);
            $this->middleware('permission:note-create', ['only' => ['create','store']]);
            $this->middleware('permission:note-edit', ['only' => ['edit','update']]);
            $this->middleware('permission:note-delete', ['only' => ['destroy']]);
        }

        public function index(Request $request){
            if(Auth::user()->assignRole()->roles[0]['name'] == 'superAdmin'){
                $note = Note::orderBy('id','DESC')->get();
            } else {
                $note = Note::where('user_id', Auth::id())->orderBy('id','DESC')->get();
            }
            
            return view('note.index',compact('note'));
        }

        public function create(){
            return view('note.create');
        }

    
        public function store(Request $request){
            // dd($request->all());
            $validatedData = $request->validate(
                [
                    'title' => 'required',
                    'description' => 'required',
                ],
                [
                    'title.required' => 'Please enter a Title.',
                    'description.required' => 'Please enter Description.',
                ]
            );
            $note = [
                'user_id' => Auth::id(), 
                'title' => $request->title, 
                'description' => $request->description, 
            ];
            Note::create($note);
            
            return redirect()->route('note.index')
                            ->with('success','Note created successfully');
        }

        public function show($id){
            $role = Role::find($id);
            $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
                ->where("role_has_permissions.role_id",$id)
                ->get();
            return view('service.show',compact('role','rolePermissions'));
        }

        public function edit($id){
            $note = Note::find($id);
            return view('note.edit',compact('note'));
        }

        public function update(Request $request, $id){
            $validatedData = $request->validate(
                [
                    'title' => 'required',
                    'description' => 'required',
                ],
                [
                    'title.required' => 'Please enter a Title.',
                    'description.required' => 'Please enter Description.',
                ]
            );
            $note = Note::find($id);
            $note->user_id = Auth::id();
            $note->title = $request->title;
            $note->description = $request->description;
            $note->save();
            
            return redirect()->route('note.index')->with('success','Note updated successfully');
        }

        public function destroy($id){
            $company = Note::where('id', $id)->first();
            $company->delete();
            return redirect()->route('note.index')->with('success','Note deleted successfully');
        }

    }