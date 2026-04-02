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
                    <h1 class="m-0">Create New Sub Task</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('subTask') }}">Sub Tasks</a></li>
                        <li class="breadcrumb-item active">Create New Sub Task</li>
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
                            <form method="post" enctype="multipart/form-data" action="{{ route('teamStore') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6 form-group">
                                        <label>Sub Task Title</label>
                                        <input type="text" class="form-control" name="title" require placeholder="Enter Sub Task Title">
                                        @if ($errors->has('title'))
                                            <span class="text-danger">{{ $errors->first('title') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Task</label>
                                            <select class="select2" name="task_id" id="task_id" onchange="getAssigneData(this.value)" data-placeholder="Select a Task" style="width: 100%;">
                                                <option value="">Select a Task</option>
                                                @foreach($task as $val)
                                                <option value="{{ $val->id }}">{{ $val->title }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('task_id'))
                                                <span class="text-danger">{{ $errors->first('task_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="showAssigneData"></div>
                                {{-- <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Memebers</label>
                                            <select class="select2" multiple="multiple" name="members_id[]" id="members_id" data-placeholder="Select a Memebers" style="width: 100%;">
                                            @foreach($user as $val)
                                                @if($val->id != Auth::id())
                                                    <option value="{{ $val->id }}">{{ $val->name }}</option>
                                                @endif
                                            @endforeach
                                            </select>
                                            @if ($errors->has('members_id'))
                                                <span class="text-danger">{{ $errors->first('members_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div> --}}
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
    function getAssigneData(id) {
        var id =id;
        $.ajax({
            type:"get",
            url: 'getSubMembersData/' + id,
            success : function(results) {
                $('#showAssigneData').html(results);
            }
        });
    }
</script>


