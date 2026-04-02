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
                    <h1 class="m-0">Create New Project</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('project.index') }}">Projects</a></li>
                        <li class="breadcrumb-item active">Create New Project</li>
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
                            <form method="post" enctype="multipart/form-data" action="{{ route('project.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-12 form-group">
                                        <label>Project Name</label>
                                        <input type="text" class="form-control" name="name" require placeholder="Enter Project Name">
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>
                                   
                                    <div class="col-sm-12 form-group">
                                        <label>Description</label>
                                        <textarea id="description" name="description" class="form-control" placeholder="Enter Description"></textarea>
                                        @if ($errors->has('description'))
                                            <span class="text-danger">{{ $errors->first('description') }}</span>
                                        @endif
                                    </div>
                                    <div class="col-sm-12 form-group">
                                        <label>Start Date & End Date</label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">
                                                    <i class="far fa-calendar-alt"></i>
                                                </span>
                                            </div>
                                            <input type="text" name="start_end_date" class="form-control float-right" id="reservation">
                                        </div>
                                        @if ($errors->has('start_end_date'))
                                            <span class="text-danger">{{ $errors->first('start_end_date') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Client</label>
                                            <select class="select2" name="client_id" id="client_id" data-placeholder="Select a Client" style="width: 100%;">
                                                <option value="">Select a Client</option>
                                                @foreach($clientUser as $val)
                                                    <option value="{{ $val->id }}">{{ $val->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('client_id'))
                                                <span class="text-danger">{{ $errors->first('client_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <!-- textarea -->
                                        <div class="form-group">
                                            <label>File</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" name="project_file" id="project_file">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Memebers</label>
                                            <select class="select2" multiple="multiple" name="members_id[]" id="members_id" data-placeholder="Select a Memebers" style="width: 100%;">
                                            @foreach($membersUser as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                            </select>
                                            @if ($errors->has('members_id'))
                                                <span class="text-danger">{{ $errors->first('members_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Project Manager</label>
                                            <select class="select2" multiple="multiple" name="manager_id[]" id="manager_id" data-placeholder="Select a Project Manager" style="width: 100%;">
                                            @foreach($teamLeader as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                            @endforeach
                                            </select>
                                            @if ($errors->has('manager_id'))
                                                <span class="text-danger">{{ $errors->first('manager_id') }}</span>
                                            @endif
                                        </div>
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
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script>
    $(function () {
        $('.select2').select2();
        $('#reservation').daterangepicker();
    });
</script>


