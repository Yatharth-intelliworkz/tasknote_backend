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
                    <h1 class="m-0">Edit Team</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('team') }}">Teams</a></li>
                        <li class="breadcrumb-item active">Edit Team</li>
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
                            <form method="post" enctype="multipart/form-data" action="{{ route('teamUpdate',$team->id) }}">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-6 form-group">
                                        <label>Team Name</label>
                                        <input type="text" class="form-control" name="name" value="{{ $team->name }}" require placeholder="Enter Team Name">
                                        @if ($errors->has('name'))
                                            <span class="text-danger">{{ $errors->first('name') }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Project</label>
                                            <select class="select2" name="project_id" id="project_id" onchange="getAssigneData(this.value)" data-placeholder="Select a Project" style="width: 100%;">
                                                <option value="">Select a project</option>
                                                @foreach($project as $val)
                                                <option value="{{ $val->id }}" @if($team->project_id == $val->id) selected="selected" @endif>{{ $val->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('project_id'))
                                                <span class="text-danger">{{ $errors->first('project_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="showAssigneData"></div>
                                <div class="row" id="hideAssigneData">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Assignee Members</label>
                                            <select class="select2" multiple="multiple" name="members_id[]" id="members_id_old" data-placeholder="Select a Memebers" style="width: 100%;">
                                                @php $members_ids = explode(",", $team->members_id); @endphp
                                                @foreach($membersList as $val)
                                                    <option value="{{ $val->id }}" {{ in_array($val->id, $members_ids) ? 'selected="selected"' : '' }}>{{ $val->name }}</option>
                                                @endforeach
                                            </select>
                                            @if ($errors->has('members_id'))
                                                <span class="text-danger">{{ $errors->first('members_id') }}</span>
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
    function getAssigneData(id) {
        var id =id;
        $.ajax({
            type:"get",
            url: 'getMembersData/' + id,
            success : function(results) {
                $('#hideAssigneData').hide();
                $('#showAssigneData').html(results);
                $('#members_id_old').val('');
            }
        });
    }
</script>


