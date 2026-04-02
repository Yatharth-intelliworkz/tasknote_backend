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
                    <h1 class="m-0">Edit Task</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('task.index') }}">Tasks</a></li>
                        <li class="breadcrumb-item active">Edit Task</li>
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
                            <form method="post" enctype="multipart/form-data" action="{{ route('task.update',$taskData->id) }}">
                                @csrf
                                @method('PATCH')
                                <div class="row">
                                    <div class="col-sm-12 form-group">
                                        <label>Task Title</label>
                                        <input type="text" class="form-control" name="title" value="{{ $taskData->title }}" require placeholder="Enter Task Title">
                                        @if ($errors->has('title'))
                                            <span class="text-danger">{{ $errors->first('title') }}</span>
                                        @endif
                                    </div>
                                   
                                    <div class="col-sm-12 form-group">
                                        <label>Description</label>
                                        <textarea id="description" name="description" class="form-control" placeholder="Enter Description">{{ $taskData->description }}</textarea>
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
                                                <input type="text" name="due_date" value="{{ date("m-d-Y", strtotime($taskData->due_date)) }}" class="form-control datetimepicker-input" data-target="#reservationdate"/>
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
                                                <option value="{{ $val->id }}" @if($taskData->project_id == $val->id) selected="selected" @endif>{{ $val->name }}</option>
                                                @endforeach 
                                            </select>
                                            @if ($errors->has('project_id'))
                                                <span class="text-danger">{{ $errors->first('project_id') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <div class="form-group">
                                            <label>Select Assignee Members</label>
                                        
                                            @php $members_ids = explode(",", $taskAssigne->members_id); @endphp
                                            @php $team_ids = explode(",", $taskAssigne->team_id); @endphp
                                            
                                            @php
                                                // dd(count($team_ids));
                                                $teamGroupDisabled = count($team_ids) > 1;
                                                $membersGroupDisabled = count($members_ids) > 1;
                                            @endphp
                                            <select class="select2" multiple="multiple" name="members_id[]" id="members_id" data-placeholder="Select Members" style="width: 100%;">
                                                @if($team->count() > 0)
                                                    <optgroup label="Team" id="teamGroup" {{ $membersGroupDisabled ? 'disabled' : '' }}>
                                                        @foreach($team as $val)
                                                            <option value="team-{{ $val->id }}" {{ in_array($val->id, $team_ids) ? 'selected="selected"' : '' }}>{{ $val->name }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endif
                                        
                                                <optgroup label="Members" id="membersGroup" {{ $teamGroupDisabled ? 'disabled' : '' }}>
                                                    @foreach($membersData as $val)
                                                        <option value="members-{{ $val->id }}" {{ in_array($val->id, $members_ids) ? 'selected="selected"' : '' }}>{{ $val->name }}</option>
                                                    @endforeach
                                                </optgroup>
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
                                                    <option value="{{ $val->id }}" @if($taskData->service_id == $val->id) selected="selected" @endif>{{ $val->title }}</option>
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
                                                <option value="0" @if($taskData->type == '0') selected="selected" @endif>Pedding</option>
                                                <option value="1" @if($taskData->type == '1') selected="selected" @endif>Other</option>
                                                <option value="2" @if($taskData->type == '2') selected="selected" @endif>Complete</option>
                                                <option value="3" @if($taskData->type == '3') selected="selected" @endif>Rejected</option>
                                            </select>
                                            @if ($errors->has('type'))
                                                <span class="text-danger">{{ $errors->first('type') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <div class="form-group">
                                            <label>Select Task Priority</label>
                                            <select class="select2" name="priority" id="priority" data-placeholder="Select a Task Priority" style="width: 100%;">
                                                <option value="0" @if($taskData->type == '0') selected="selected" @endif>Low</option>
                                                <option value="1" @if($taskData->type == '1') selected="selected" @endif>High</option>
                                                <option value="2" @if($taskData->type == '2') selected="selected" @endif>Medium</option>
                                            </select>
                                            @if ($errors->has('type'))
                                                <span class="text-danger">{{ $errors->first('type') }}</span>
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
                                    <div class="col-sm-2">
                                        <a class="btn mt-4 btn-primary" data-toggle="modal" data-target=".bd-example-modal-lg">Documents Files</a>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <hr>
                                        <div class="col-sm-6">
                                            <h5 class="m-0">Create Sub Task</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <a class="btn btn-primary mt-4 btn-rounded float-right btn-icon addfield"><i class="fa fa-plus"></i></a>
                                    </div>
                                </div>
                                @foreach($subTask as $key => $val)
                                    <input type="hidden" id="up_sub_id" name="up_sub_id[]" value="{{ $val->id }}">
                                    <div class="row" id="{{ $val->id }}_removeSubTask">
                                        <div class="col-sm-4">
                                        <div class="form-group">
                                            <label>Sub Task Title</label>
                                            <input type="text" class="form-control" value="{{ $val->title }}" name="up_sub_title[]" require placeholder="Enter Task Title">
                                        </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label>Sub Task Due Date</label>
                                                <div class="input-group date" id="{{ $key }}_edit_sub_reservationdate" data-target-input="nearest">
                                                    <input type="text" name="up_sub_due_date[]" value="{{ date("m-d-Y", strtotime($val->due_date)) }}" class="form-control datetimepicker-input" data-target="#{{ $key }}_edit_sub_reservationdate"/>
                                                    <div class="input-group-append" data-target="#{{ $key }}_edit_sub_reservationdate" data-toggle="datetimepicker">
                                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <div class="form-group">
                                                <label>Sub Task Type</label>
                                                <select class="select2" name="up_sub_type[]" data-placeholder="Select a Sub Task Type" style="width: 100%;">
                                                    <option value="0" @if($val->type == '0') selected="selected" @endif>Pedding</option>
                                                    <option value="1" @if($val->type == '1') selected="selected" @endif>Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <a onclick="removeSubTask({{ $val->id }})" class="btn mt-4 btn-danger"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </div>
                                    <script>
                                        $(function () {
                                            $('#{{ $key }}_edit_sub_reservationdate').datetimepicker({
                                                format: 'L'
                                            });
                                        });
                                    </script>
                                @endforeach
                                <div class="addmorefield"><br></div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <hr>
                                        <div class="col-sm-6">
                                            <h5 class="m-0">Create CheckList</h5>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <a class="btn btn-primary mt-4 btn-rounded float-right btn-icon addfield_checklist"><i class="fa fa-plus"></i></a>
                                    </div>
                                </div>
                                @foreach($checkList as $key => $val)
                                    <input type="hidden" id="up_check_id" name="up_check_id[]" value="{{ $val->id }}">
                                    <div class="row" id="{{ $val->id }}_removeCheckList">
                                        <div class="col-sm-6">
                                            <div class="form-group">
                                                <label>CheckList Title</label>
                                                <input type="text" class="form-control" value="{{ $val->title }}" name="up_check_title[]" require placeholder="Enter CheckList Title">
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="form-group">
                                                <label>CheckList Date</label>
                                                <div class="input-group date" id="{{ $key }}_edit_check_reservationdate" data-target-input="nearest">
                                                    <input type="text" name="up_check_date[]" value="{{ date("m-d-Y", strtotime($val->check_date)) }}" class="form-control datetimepicker-input" data-target="#{{ $key }}_edit_check_reservationdate"/>
                                                    <div class="input-group-append" data-target="#{{ $key }}_edit_check_reservationdate" data-toggle="datetimepicker">
                                                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-2">
                                            <a onclick="removeCheckList({{ $val->id }})" class="btn mt-4 btn-danger"><i class="fa fa-trash"></i></a>
                                        </div>
                                    </div>
                                    <script>
                                        $(function () {
                                            $('#{{ $key }}_edit_check_reservationdate').datetimepicker({
                                                format: 'L'
                                            });
                                        });
                                    </script>
                                @endforeach
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
<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLongTitle">Documnets Files</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    @foreach($attestment as $val)
                        @if(pathinfo($val->file, PATHINFO_EXTENSION) == 'pdf')
                            <div class="col-sm-2" id="{{ $val->id }}_imgFullDiv">
                                <img style="height: 173px;"
                                    src="{{ asset('images/PDF_file_icon.png') }}"
                                    class="img-fluid w-100 shadow-1-strong rounded mb-4"
                                    alt="Boat on Calm Water"
                                />
                                <div style="text-align: center;">
                                    <a class="btn btn-danger" onclick="removeImg({{$val->id}})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                    <a class="btn btn-primary" href="{{ asset('images/all/'. $val->file) }}" download><i class="fa fa-download" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        @else
                            <div class="col-sm-2" id="{{ $val->id }}_imgFullDiv">
                                <img style="height: 173px;"
                                    src="{{ asset('public/images/all/'. $val->file) }}"
                                    class="img-fluid w-100 shadow-1-strong rounded mb-4"
                                    alt="Boat on Calm Water"
                                />
                                <div style="text-align: center;">
                                    <a class="btn btn-danger" onclick="removeImg({{$val->id}})"><i class="fa fa-trash" aria-hidden="true"></i></a>
                                    <a class="btn btn-primary" href="{{ asset('images/all/'. $val->file) }}" download><i class="fa fa-download" aria-hidden="true"></i></a>
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>  
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
                
                
                row += '<div class="col-sm-3">';
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
                
                
                row += '<div class="col-sm-3">';
                row += '<div class="form-group">';
                row += '<label>Sub Task Type</label>';
                row += '<select class="select2"  name="sub_type[]" id="'+x+'_sub_type" data-placeholder="Select a Sub Task Type" style="width: 100%;">';
                row += '<option value="0">Pedding</option>';
                row += '<option value="1">Other</option>';
                row += '</select>';
                row += '</div>';
                row += '</div>';
                
                
                row += '<div class="col-sm-2">';
                row += '<a href="#" class="btn mt-4 btn-danger removefield"><i class="fa fa-trash"></i></a>';
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
                row += '<label>CheckList Date</label>';
                row += '<div class="input-group date" id="'+x+'_check_reservationdate" data-target-input="nearest">';
                row += '<input type="text" name="checkList_date[]" class="form-control datetimepicker-input" data-target="#'+x+'_check_reservationdate"/>';
                row += '<div class="input-group-append" data-target="#'+x+'_check_reservationdate" data-toggle="datetimepicker">';
                row += '<div class="input-group-text"><i class="fa fa-calendar"></i></div>';
                row += '</div>';
                row += '</div>';
                row += '</div>';
                row += '</div>';
                
                
                row += '<div class="col-sm-2">';
                row += '<a href="#" class="btn mt-4 btn-danger remove_checklist"><i class="fa fa-trash"></i></a>';
                row += '</div>';
                
                $(wrapper).append(row);
                $('#'+x+'_check_reservationdate').datetimepicker({
                    format: 'L'
                });
                $('#'+x+'_sub_type').select2();
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
        $('.select2').select2();
        $('#reservation').daterangepicker();
    });

    function removeImg(id) {
        var id =id;
        $.ajax({
            type:"get",
            url: "{{ url('removeImg') }}/" + id,
            success : function(results) {
                if(results == 1){
                    $('#'+id+'_imgFullDiv').hide();
                } else {

                }  
            }
        });
    }

    function removeSubTask(id) {
        var id =id;
        // $.ajaxSetup({
        //     headers: {
        //             'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        //     }
        // });
        $.ajax({
            type: "GET",  
            url: "{{ url('removeSubTask') }}/" + id,  
            success: function(results) {
                if (results == 1) {
                    $('#' + id + '_removeSubTask').hide();
                } else {

                }
            }
        });
    }

    function removeCheckList(id) {
        var id =id;
        $.ajax({
            type:"get",
            url: "{{ url('removeCheckList') }}/" + id,
            success : function(results) {
            if(results == 1){
                $('#'+id+'_removeCheckList').hide();
            } else {

            }  
            }
        });
    }

    $(document).ready(function () {
        $('#members_id').change(function() {
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


