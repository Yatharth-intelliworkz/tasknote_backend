@extends('layouts.app')
@section('content')
<div class="content-wrapper" style="height: auto;">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Projects</h1>
                </div>
                <!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Projects</li>
                    </ol>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
     <!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->
    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    @if(session()->has('success'))
                        <div class="alert alert-success">
                                {{ session()->get('success') }}
                        </div>
                    @endif
                    <div class="card">
                        @can('project-create')
                            <div class="card-header">
                                <a class="btn btn-primary float-right" href="{{ route('project.create') }}"><i class="fa fa-plus" aria-hidden="true"></i> Create New Project</a>
                            </div>
                        @endcan    
                   
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Create Name</th>
                                        <th>Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($project as $val)
                                        <tr>
                                            <td>{{ $val->user->name }}</td>
                                            <td>{{ $val->name }}</td>
                                            <td>{{ date("d-m-Y", strtotime($val->start_date)) }}</td>
                                            <td>{{ date("d-m-Y", strtotime($val->end_date)) }}</td>
                                            <td>
                                                @if($val->status == 0)
                                                   <small class="badge badge-primary">Upcoming</small>
                                                @elseif($val->status == 1)
                                                   <small class="badge badge-primary">success</small>
                                                @elseif($val->status == 2)
                                                   <small class="badge badge-warning">Over Due</small>
                                                @else
                                                   <small class="badge badge-danger">Closed</small>
                                                @endif
                                                
                                            </td>
                                            <td>
                                                {{-- <a href="{{ route('project.show',$val->id) }}" class="btn btn-info btn-rounded btn-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> --}}
                                                @can('project-edit')
                                                    <a class="btn btn-primary btn-rounded btn-icon" href="{{ route('project.edit',$val->id) }}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                                @endcan
                                                @can('project-delete')
                                                {{-- <a class="btn btn-danger btn-rounded btn-icon" href="{{ route('roles.destroy'.$val->id) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa fa-trash" aria-hidden="true"></i></a> --}}
                                                    {!! Form::open(['method' => 'DELETE','route' => ['project.destroy', $val->id],'style'=>'display:inline']) !!}
                                                        <button type="submit" class="btn btn-danger btn-rounded btn-icon"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                                        {{-- {!! Form::submit('Delete', ['class' => 'btn btn-danger']) !!} --}}
                                                    {!! Form::close() !!}
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Create Name</th>
                                        <th>Name</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
     <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
@endsection 
