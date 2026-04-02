@extends('layouts.app')
<style>
    .select2-container .select2-selection--single{
      height: 38px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow{
      height: 36 !important;
    }
    .select2-container .select2-selection--multiple{
      min-height: 38px !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice{
      background-color: #007bff !important;
      border-color: #006fe6 !important;
      color: #fff !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{
      color: rgba(255,255,255,.7) !important;
    }
  
</style>
@section('content')
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Create New User</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item active">Create New User</li>
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
                            <form method="post" enctype="multipart/form-data" action="{{ route('users.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-12 form-group">
                                        <label>Name</label>
                                        <input type="text" class="form-control" name="name" require placeholder="Enter Name">
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>
                                    <div class="col-sm-12 form-group">
                                        <label>Email</label>
                                        <input type="text" class="form-control" name="email" require placeholder="Enter Email">
                                        @if ($errors->has('email'))
                                            <span class="text-danger">{{ $errors->first('email') }}</span>
                                        @endif
                                    </div>
                                    <div class="col-sm-12 form-group">
                                        <label>Password</label>
                                        <input type="password" class="form-control" name="password" require placeholder="Enter Password">
                                        @if ($errors->has('password'))
                                            <span class="text-danger">{{ $errors->first('password') }}</span>
                                        @endif
                                    </div>
                                    <div class="col-sm-12 form-group">
                                        <label>Confirm Password</label>
                                        <input type="password" class="form-control" name="confirm-password" require placeholder="Enter Confirm Password">
                                        @if ($errors->has('confirm-password'))
                                            <span class="text-danger">{{ $errors->first('confirm-password') }}</span>
                                        @endif
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Is Active</label>
                                            <select class="select2" name="is_active" id="is_active" data-placeholder="Select a Status" style="width: 100%;">
                                                <option value="0">Active</option>
                                                <option value="1">In Active</option>
                                            </select>
                                            @if ($errors->has('is_active'))
                                                <span class="text-danger">{{ $errors->first('is_active') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <!--<div class="col-sm-6">-->
                                    <!--    <div class="form-group">-->
                                    <!--        <label>Select Role</label>-->
                                    <!--        <select class="select2" name="roles" id="roles" data-placeholder="Select a Role" style="width: 100%;">-->
                                    <!--            <option value="">Select a Role</option>-->
                                    <!--            @foreach($roles as $val)-->
                                    <!--                @if(Auth::user()->assignRole()->roles[0]['name'] != $val->name && $val->name != 'SuperAdmin')-->
                                    <!--                    <option value="{{ $val->id }}">{{ $val->name }}</option>-->
                                    <!--                @endif-->
                                    <!--            @endforeach -->
                                    <!--        </select>-->
                                    <!--        @if ($errors->has('roles'))-->
                                    <!--            <span class="text-danger">{{ $errors->first('roles') }}</span>-->
                                    <!--        @endif-->
                                    <!--    </div>-->
                                    <!--</div>-->
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script>
    $(function () {
        $('.select2').select2();
    });
</script>


