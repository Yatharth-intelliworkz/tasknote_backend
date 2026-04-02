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
                    <h1 class="m-0">Create New Task</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('task.index') }}">Tasks</a></li>
                        <li class="breadcrumb-item active">Create New Task</li>
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
                            <form method="post" enctype="multipart/form-data" action="{{ route('task.store') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-sm-12 form-group">
                                        <label>Task Title</label>
                                        <input type="text" class="form-control" name="title" require placeholder="Enter Task Title">
                                        @if ($errors->has('title'))
                                            <span class="text-danger">{{ $errors->first('title') }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="col-sm-12 form-group">
                                        <label>Description</label>
                                        <textarea id="description" name="description" class="form-control" placeholder="Enter Description"></textarea>
                                        @if ($errors->has('description'))
                                            <span class="text-danger">{{ $errors->first('description') }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Due Date:</label>
                                            <div class="input-group date" id="reservationdate" data-target-input="nearest">
                                                <input type="text" name="due_date" class="form-control datetimepicker-input" data-target="#reservationdate"/>
                                                <div class="input-group-append" data-target="#reservationdate" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                            @if ($errors->has('due_date'))
                                                <span class="text-danger">{{ $errors->first('due_date') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Project</label>
                                            <select class="select2" name="project_id" onchange="getAssigneData(this.value)" id="project_id" data-placeholder="Select a Project" style="width: 100%;">
                                                <option value="">Select a Project</option>
                                                @foreach($project as $val)
                                                <option value="{{ $val->id }}">{{ $val->name }}</option>
                                                @endforeach 
                                            </select>
                                            @if ($errors->has('project_id'))
                                                <span class="text-danger">{{ $errors->first('project_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="showAssigneData">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Assignee Members</label>
                                            {{-- {{dd($show)}}; --}}
                                            <select class="membersDatas setMembersClass" multiple="multiple" name="members_id[]" id="members_id" data-placeholder="Select a Memebers" style="width: 100%;">
                                                {{-- @if($team->count() > 0)
                                                    <optgroup label="Team" id="teamGroup">
                                                    @foreach($team as $val)
                                                        <option value="team-{{ $val->id }}">{{ $val->name }}</option>
                                                    @endforeach
                                                    </optgroup>
                                                @endif
                                                <optgroup label="Members" id="membersGroup">
                                                @foreach($membersData as $val)
                                                    <option value="members-{{ $val->id }}">{{ $val->name }}</option>
                                                @endforeach
                                                </optgroup> --}}
                                            </select>
                                            @if ($errors->has('members_id'))
                                                <span class="text-danger">{{ $errors->first('members_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Service</label>
                                            <select class="select2" name="service_id" id="service_id" data-placeholder="Select a Service" style="width: 100%;">
                                                <option value="">Select a Service</option>
                                                @foreach($service as $val)
                                                <option value="{{ $val->id }}">{{ $val->title }}</option>
                                                @endforeach 
                                            </select>
                                            @if ($errors->has('service_id'))
                                                <span class="text-danger">{{ $errors->first('service_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                          <label>Select Task Status</label>
                                            <select class="select2" name="status" id="status" data-placeholder="Select a Task Status" style="width: 100%;">
                                                <option value="0">Pedding</option>
                                                <option value="1">Other</option>
                                                <option value="2">Complete</option>
                                                <option value="3">Rejected</option>
                                            </select>
                                            @if ($errors->has('status'))
                                                <span class="text-danger">{{ $errors->first('status') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                          <label>Select Task Priority</label>
                                            <select class="select2" name="priority" id="priority" data-placeholder="Select a Task Priority" style="width: 100%;">
                                                <option value="0">Low</option>
                                                <option value="1">High</option>
                                                <option value="2">Medium</option>
                                            </select>
                                            @if ($errors->has('status'))
                                                <span class="text-danger">{{ $errors->first('status') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Upload Documents</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input" multiple name="project_file[]" id="project_file">
                                                <label class="custom-file-label" for="customFile">Choose file</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <hr>
                                        <div class="col-sm-6">
                                            <h5 class="m-0">Create Sub Task</h5>
                                        </div>
                                        <br>
                                    </div>
                                </div>
                                <div class="row clonedata">
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Sub Task Title</label>
                                            <input type="text" class="form-control" name="sub_title[]" require placeholder="Enter Task Title">
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label>Sub Task Due Date</label>
                                            <div class="input-group date" id="sub_reservationdate" data-target-input="nearest">
                                                <input type="text" name="sub_due_date[]" class="form-control datetimepicker-input" data-target="#sub_reservationdate"/>
                                                <div class="input-group-append" data-target="#sub_reservationdate" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                 </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label>Sub Members</label>
                                            <select class="subMembersClass membersDatas" multiple="multiple" name="sub_members_id[]" id="sub_members_id" data-placeholder="Select a Memebers" style="width: 100%;">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <div class="form-group">
                                            <label>Sub Task Type</label>
                                            <select class="select2" name="sub_type[]" id="sub_type" data-placeholder="Select a Sub Task Type" style="width: 100%;">
                                                <option value="0">Pedding</option>
                                                <option value="1">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <a class="btn btn-primary mt-4 btn-rounded btn-icon float-right addfield"><i class="fa fa-plus"></i></a>
                                    </div>
                                </div>
                                <div class="addmorefield"><br></div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <hr>
                                        <div class="col-sm-6">
                                            <h5 class="m-0">Create CheckList</h5>
                                        </div>
                                        <br>
                                    </div>
                                </div>
                                <div class="row clonedata">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>CheckList Title</label>
                                            <input type="text" class="form-control" name="checkList_title[]" require placeholder="Enter CheckList Title">
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>CheckList Date</label>
                                            <div class="input-group date" id="check_reservationdate" data-target-input="nearest">
                                                <input type="text" name="checkList_date[]" class="form-control datetimepicker-input" data-target="#check_reservationdate"/>
                                                <div class="input-group-append" data-target="#check_reservationdate" data-toggle="datetimepicker">
                                                    <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-2">
                                        <a class="btn btn-primary mt-4 btn-rounded btn-icon float-right addfield_checklist"><i class="fa fa-plus"></i></a>
                                    </div>
                                </div>
                                <div class="addmore_checklist"><br></div>
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
    $(document).ready(function() {
        var wrapper = $(".addmorefield");
        var add_button = $(".addfield");
        var max_fields = 10;
        var x = 1;

        $(add_button).click(function(e) {
            e.preventDefault();
            if (x < max_fields) {
                x++;
                var row = '<div class="row">';
                row += '<div class="col-sm-4">';
                row += '<div class="form-group">';
                row += '<label>Sub Task Title</label>';
                row += '<input type="text" class="form-control" name="sub_title[]" require placeholder="Enter Task Title">';
                row += '</div>';
                row += '</div>';
                
                
                row += '<div class="col-sm-2">';
                row += '<div class="form-group">';
                row += '<label>Sub Task Due Date</label>';
                row += '<div class="input-group date" id="'+x+'_sub_reservationdate" data-target-input="nearest">';
                row += '<input type="text" name="sub_due_date[]" class="form-control datetimepicker-input" data-target="#'+x+'_sub_reservationdate"/>';
                row += '<div class="input-group-append" data-target="#'+x+'_sub_reservationdate" data-toggle="datetimepicker">';
                row += '<div class="input-group-text"><i class="fa fa-calendar"></i></div>';
                row += '</div>';
                row += '</div>';
                row += '</div>';
                row += '</div>';
                
                row += '<div class="col-sm-2">';
                row += '<div class="form-group">';
                row += '<label>Sub Members</label>';
                row += '<select class="subMembersClass membersDatas" multiple="multiple"  name="sub_type[]" id="'+x+'_sub_type" data-placeholder="Select a Sub Task Type" style="width: 100%;">';
                row += '<option value="0">Pedding</option>';
                row += '<option value="1">Other</option>';
                row += '</select>';
                row += '</div>';
                row += '</div>';

                row += '<div class="col-sm-2">';
                row += '<div class="form-group">';
                row += '<label>Sub Task Type</label>';
                row += '<select class="select2"  name="sub_type[]" id="'+x+'_sub_type" data-placeholder="Select a Sub Task Type" style="width: 100%;">';
                row += '<option value="0">Pedding</option>';
                row += '<option value="1">Other</option>';
                row += '</select>';
                row += '</div>';
                row += '</div>';
                
                
                row += '<div class="col-sm-2">';
                row += '<a href="#" class="btn mt-4 btn-danger float-right removefield"><i class="fa fa-trash"></i></a>';
                row += '</div>';
                
                $(wrapper).append(row);
                $('#'+x+'_sub_reservationdate').datetimepicker({
                    format: 'L'
                });
                $('#'+x+'_sub_type').select2();
            }
        });

        $(wrapper).on("click", ".removefield", function(e) {
            e.preventDefault();
            $(this).closest('.row').remove();
            x--;
        });
    });

    $(document).ready(function() {
        var wrapper = $(".addmore_checklist");
        var add_button = $(".addfield_checklist");
        var max_fields = 10;
        var x = 1;

        $(add_button).click(function(e) {
            e.preventDefault();
            if (x < max_fields) {
                x++;
                var row = '<div class="row">';
                row += '<div class="col-sm-6">';
                row += '<div class="form-group">';
                row += '<label>CheckList Title</label>';
                row += '<input type="text" class="form-control" name="checkList_title[]" require placeholder="Enter CheckList Title">';
                row += '</div>';
                row += '</div>';
                
                
                row += '<div class="col-sm-4">';
                row += '<div class="form-group">';
                row += '<label>Sub CheckList Date</label>';
                row += '<div class="input-group date" id="'+x+'_check_reservationdate" data-target-input="nearest">';
                row += '<input type="text" name="checkList_date[]" class="form-control datetimepicker-input" data-target="#'+x+'_check_reservationdate"/>';
                row += '<div class="input-group-append" data-target="#'+x+'_check_reservationdate" data-toggle="datetimepicker">';
                row += '<div class="input-group-text"><i class="fa fa-calendar"></i></div>';
                row += '</div>';
                row += '</div>';
                row += '</div>';
                row += '</div>';
                
                
                row += '<div class="col-sm-2">';
                row += '<a href="#" class="btn mt-4 btn-danger float-right remove_checklist"><i class="fa fa-trash"></i></a>';
                row += '</div>';
                
                $(wrapper).append(row);
                $('#'+x+'_check_reservationdate').datetimepicker({
                    format: 'L'
                });
            }
        });

        $(wrapper).on("click", ".remove_checklist", function(e) {
            e.preventDefault();
            $(this).closest('.row').remove();
            x--;
        });
    });

    $(document).on('click', '.removemore', function () {
        $(this).parent().parent().remove();
    });
    $(function () {
        $('#reservationdate').datetimepicker({
            format: 'L'
        });
        $('#sub_reservationdate').datetimepicker({
            format: 'L'
        });
        $('#check_reservationdate').datetimepicker({
            format: 'L'
        });
        $('.select2').select2();
        $('.subMembersClass').select2();
        $('.setMembersClass').select2();
        
        $('#reservation').daterangepicker();
    });
    function getAssigneData(id) {
        var id =id;
        $(".membersDatas").html('');
        $.ajax({
            type:"get",
            // url:"tasks/getassigneData/"+id,
            url: 'getassigneData/' + id,
            dataType: 'json',
            success : function(results) {
                $('.membersDatas').html('<option value="">-- Select Members --</option>');
                if(results[1].team.length > 0){
                    $(".membersDatas").append('<optgroup label="Team" id="teamGroup">');
                    $.each(results[1].team, function (key, value) {
                        $(".membersDatas").append('<option value="team-' + value
                            .id + '">' + value.name + '</option>');
                        });
                    $(".membersDatas").append('</optgroup>');
                }
                $(".membersDatas").append('<optgroup label="Members" id="membersGroup">');
                $.each(results[0].membersData, function (key, value) {
                    $(".membersDatas").append('<option value="members-' + value
                        .id + '">' + value.name + '</option>');
                });
                $(".membersDatas").append('</optgroup>');
            // $('#showAssigneData').html(results);
            }
        });
    }

    $(document).ready(function () {
        $('.membersDatas').change(function() {
            if ($('#teamGroup option[value]').is(':selected')) {
                $('#teamGroup').prop('disabled', false);
                $('#membersGroup').prop('disabled', true);
            } else if ($('#membersGroup option[value]').is(':selected')) {
                $('#teamGroup').prop('disabled', true);
                $('#membersGroup').prop('disabled', false);
            }
        });
    });
  </script>


