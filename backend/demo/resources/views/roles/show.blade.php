@extends('layouts.app')
@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Show Role</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">Show Role</li>
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
                            <div class="row">
                                <div class="col-sm-12 form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" value="{{ $role->name }}" disabled name="name" require placeholder="Enter Name">
                                    @if ($errors->has('name'))
                                        <span class="text-danger">{{ $errors->first('name') }}</span>
                                    @endif
                                </div>
                                <div class="col-sm-12 form-group">
                                    <label>Permission</label>
                                    @if(!empty($rolePermissions))
                                        @foreach($rolePermissions as $value)
                                            <div class="form-check">
                                                {{-- <input class="form-check-input" disabled name="permission[]" value="{{ $value->id }}" type="checkbox"> --}}
                                                <label class="form-check-label">{{ $value->name }}</label>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-sm-1">
                                    <div class="form-group">
                                        <a href="{{ route('roles.index') }}" class="form-control btn btn-primary">Back</a>
                                    </div>
                                </div>
                            </div>
                            
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


