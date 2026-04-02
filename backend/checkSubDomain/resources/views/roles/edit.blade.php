@extends('layouts.app')
@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">Edit Role</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                            <li class="breadcrumb-item active">Edit Role</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <section class="content">
            <div class="container-fluid">
                <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                        {{-- <form method="post" enctype="multipart/form-data" action="#"> --}}
                            <form method="post" enctype="multipart/form-data" action="{{ route('roles.update',$role->id) }}">
                                @csrf
                                @method('PATCH')
                                <div class="row">
                                    <div class="col-sm-12 form-group">
                                        @if ($role->fixed_role == 1)
                                            <h3><b>Role Name : </b>{{ $role->name }}</h3>
                                            <input type="hidden" class="form-control" name="name" value="{{ $role->name }}">
                                        @else
                                            <label>Role Name</label>
                                            <input type="text" class="form-control" name="name" value="{{ $role->name }}">
                                            @if ($errors->has('name'))
                                                <span class="text-danger">{{ $errors->first('name') }}</span>
                                            @endif
                                        @endif
                                        
                                    </div>
                                    <div class="col-sm-12 form-group">
                                        <label>Permission</label>
                                        @foreach($permission as $value)
                                            <div class="form-check">
                                                <input class="form-check-input" name="permission[]" {{ in_array($value->id, $rolePermissions) ? 'checked="checked"' : '' }} value="{{ $value->id }}" type="checkbox">
                                                <label class="form-check-label">{{ $value->name }}</label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-1">
                                        <div class="form-group">
                                            <button class="form-control btn btn-primary">Save</button>
                                        </div>
                                    </div>
                                </div>
                                
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            <!-- /.container-fluid -->
    </section>
        <!-- /.content -->
</div>
  <!-- /.content-wrapper -->
  
@endsection 


