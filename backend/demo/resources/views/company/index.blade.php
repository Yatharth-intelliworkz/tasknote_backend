@extends('layouts.app')
@section('content')
<div class="content-wrapper" style="height: auto;">
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Companies</h1>
                </div>
                <!-- /.col -->
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Companies</li>
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
                        @can('company-create')
                            <div class="card-header">
                                <a class="btn btn-primary float-right" href="{{ route('company.create') }}"><i class="fa fa-plus" aria-hidden="true"></i> Create New Company</a>
                            </div>
                        @endcan
                        <!-- /.card-header -->
                        <div class="card-body">
                            <table id="example1" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($company as $val)
                                        <tr>
                                        <td>{{ $val->name }}</td>
                                        <td>
                                            {{-- <a href="{{ route('service.show',$val->id) }}" class="btn btn-info btn-rounded btn-icon"><i class="fa fa-eye" aria-hidden="true"></i></a> --}}
                                            @can('company-edit')
                                                <a class="btn btn-primary btn-rounded btn-icon" href="{{ route('company.edit',$val->id) }}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                            @endcan
                                            @can('company-delete')
                                            {{-- <a class="btn btn-danger btn-rounded btn-icon" href="{{ route('roles.destroy'.$val->id) }}" onclick="return confirm('Are you sure you want to delete this item?');"><i class="fa fa-trash" aria-hidden="true"></i></a> --}}
                                                {!! Form::open(['method' => 'DELETE','route' => ['company.destroy', $val->id],'style'=>'display:inline']) !!}
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
        </div>
     <!-- /.container-fluid -->
    </section>
    <!-- /.content -->
</div>
@endsection 
