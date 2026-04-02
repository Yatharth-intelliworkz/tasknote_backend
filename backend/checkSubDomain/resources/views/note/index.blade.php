@extends('layouts.app')
@section('content')
<div class="content-wrapper" style="height: auto;">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Notes</h1>
                </div>
                <!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Notes</li>
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
                        @can('note-create')
                            <div class="card-header">
                                <a class="btn btn-primary float-right" href="{{ route('note.create') }}"><i class="fa fa-plus" aria-hidden="true"></i> Create New Note</a>
                            </div>
                        @endcan
                   
                        <!-- /.card-header -->
                        <div class="card card-primary card-outline card-tabs">
                            <div class="card-header p-0 pt-1 border-bottom-0">
                                <ul class="nav nav-tabs" id="custom-tabs-three-tab" role="tablist">
                                    <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-three-home-tab" data-toggle="pill" href="#custom-tabs-three-home" role="tab" aria-controls="custom-tabs-three-home" aria-selected="true">Created</a>
                                    </li>
                                    <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-three-profile-tab" data-toggle="pill" href="#custom-tabs-three-profile" role="tab" aria-controls="custom-tabs-three-profile" aria-selected="false">Shared</a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content" id="custom-tabs-three-tabContent">
                                    <div class="tab-pane fade show active" id="custom-tabs-three-home" role="tabpanel" aria-labelledby="custom-tabs-three-home-tab">
                                        <table id="example1" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($note as $val)
                                                    <tr>
                                                    <td>{{ $val->title }}</td>
                                                    <td>
                                                        {{-- <a href="{{ route('service.show',$val->id) }}" class="btn btn-info btn-rounded btn-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> --}}
                                                        @can('note-edit')
                                                            <a class="btn btn-primary btn-rounded btn-icon" href="{{ route('note.edit',$val->id) }}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                                        @endcan
                                                        @can('note-delete')
                                                        {{-- <a class="btn btn-danger btn-rounded btn-icon" href="{{ route('roles.destroy'.$val->id) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa fa-trash" aria-hidden="true"></i></a> --}}
                                                            {!! Form::open(['method' => 'DELETE','route' => ['note.destroy', $val->id],'style'=>'display:inline']) !!}
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
                                                    <th>Title</th>
                                                    <th>Action</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-three-profile" role="tabpanel" aria-labelledby="custom-tabs-three-profile-tab">
                                        <table id="example2" class="table table-bordered table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Title1</th>
                                                    <th>Action1</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($note as $val)
                                                    <tr>
                                                    <td>{{ $val->title }}</td>
                                                    <td>
                                                        {{-- <a href="{{ route('service.show',$val->id) }}" class="btn btn-info btn-rounded btn-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> --}}
                                                        @can('note-edit')
                                                            <a class="btn btn-primary btn-rounded btn-icon" href="{{ route('note.edit',$val->id) }}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                                        @endcan
                                                        @can('note-delete')
                                                        {{-- <a class="btn btn-danger btn-rounded btn-icon" href="{{ route('roles.destroy'.$val->id) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa fa-trash" aria-hidden="true"></i></a> --}}
                                                            {!! Form::open(['method' => 'DELETE','route' => ['note.destroy', $val->id],'style'=>'display:inline']) !!}
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
                                                    <th>Title</th>
                                                    <th>Action</th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            
                            
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
