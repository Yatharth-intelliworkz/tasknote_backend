@extends('layouts.app')
@section('content')
<div class="content-wrapper" style="height: auto;">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Roles</h1>
                </div>
                <!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Roles</li>
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
                        <div class="card-header">
                            <a class="btn btn-primary float-right" href="{{ route('roles.create') }}"><i class="fa fa-plus" aria-hidden="true"></i> Create New Role</a>
                        </div>
                   
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($roles as $val)
                                        {{-- @if(Auth::user()->assignRole()->roles[0]['name'] != $val->name && $val->name != 'SuperAdmin') --}}
                                        @if(Auth::user()->assignRole()->roles[0]['position'] < $val->position)
                                            <tr>
                                                <td>{{ $val->name }}</td>
                                                <td>
                                                    <a href="{{ route('roles.show',$val->id) }}" class="btn btn-info btn-rounded btn-icon"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                                    @can('role-edit')
                                                        <a class="btn btn-primary btn-rounded btn-icon" href="{{ route('roles.edit',$val->id) }}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                                    @endcan
                                                    @if($val->fixed_role == 0)
                                                        @can('role-delete')
                                                            {!! Form::open(['method' => 'DELETE','route' => ['roles.destroy', $val->id],'style'=>'display:inline']) !!}
                                                                <button type="submit" class="btn btn-danger btn-rounded btn-icon"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                                            {!! Form::close() !!}
                                                        @endcan
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Name</th>
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
