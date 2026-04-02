@extends('layouts.app')
@section('content')
<div class="content-wrapper" style="height: auto;">
  <!-- Content Header (Page header) -->
   <div class="content-header">
      <div class="container-fluid">
         <div class="row mb-2">
            <div class="col-sm-6">
               <h1 class="m-0">Users</h1>
            </div>
            <!-- /.col -->
            <div class="col-sm-6">
               <ol class="breadcrumb float-sm-right">
                  <li class="breadcrumb-item"><a href="#">Home</a></li>
                  <li class="breadcrumb-item active">Users</li>
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
                  @can('user-create')
                     <div class="card-header">
                        <a class="btn btn-primary float-right" href="{{ route('users.create') }}"><i class="fa fa-plus" aria-hidden="true"></i> Create New User</a>
                     </div>
                  @endcan
                  <!-- /.card-header -->
                  <div class="card-body">
                     <table id="example1" class="table table-bordered table-striped">
                        <thead>
                           <tr>
                              <th>Name</th>
                              <th>Email</th>
                              <th>Roles</th>
                              <th>Action</th>
                           </tr>
                        </thead>
                        <tbody>
                           @foreach ($data as $val)
                              <tr>
                                 <td>{{ $val->name }}</td>
                                 <td>{{ $val->email }}</td>
                                 <td>
                                    @if(!empty($val->getRoleNames()))
                                       @foreach($val->getRoleNames() as $v)
                                          <label class="badge badge-success">{{ $v }}</label>
                                       @endforeach
                                    @endif
                                 </td>
                                 <td>
                                    <a data-id="{{ $val->id }}" class="btn btn-primary btn-rounded btn-icon user_data"><i class="fa fa-eye" aria-hidden="true"></i></a>
                                    @can('user-edit')
                                       <a class="btn btn-primary btn-rounded btn-icon" href="{{ route('users.edit',$val->id) }}"><i class="fa fa-edit" aria-hidden="true"></i></a>
                                    @endcan
                                    @can('user-delete')
                                       {!! Form::open(['method' => 'DELETE','route' => ['users.destroy', $val->id],'style'=>'display:inline']) !!}
                                             <button type="submit" class="btn btn-danger btn-rounded btn-icon"><i class="fa fa-trash" aria-hidden="true"></i></button>
                                       {!! Form::close() !!}
                                    @endcan
                                    @if(Auth::user()->assignRole()->roles[0]['name'] == 'SuperAdmin')
                                       <a class="btn btn-warning btn-rounded btn-icon copy_text" href="{{ url('login') }}"><i class="fa fa-copy" aria-hidden="true"></i></a>
                                    @endif
                                 </td>
                              </tr>
                           @endforeach
                        </tbody>
                        <tfoot>
                           <tr>
                              <th>Name</th>
                              <th>Email</th>
                              <th>Roles</th>
                              <th>Action</th>
                           </tr>
                        </tfoot>
                     </table>
                  </div>
                  <div class="card-body" id="showViewByUser"></div>
               </div>
            </div>
         </div>
      </div>
     <!-- /.container-fluid -->
   </section>
   <!-- /.content -->
</div>
@endsection 
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script>
   
   $(document).ready(function() {
      $('.copy_text').click(function (e) {
         e.preventDefault();
         var copyText = $(this).attr('href');

         document.addEventListener('copy', function(e) {
            e.clipboardData.setData('text/plain', copyText);
            e.preventDefault();
         }, true);

         document.execCommand('copy');  
         console.log('copied text : ', copyText);
         alert('copied text: ' + copyText); 
      });
      $('.user_data').click(function() {
            $.ajaxSetup({
               headers: {
                     'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
               }
            });
            var id =($(this).attr("data-id"));
            $.ajax({
               type:"POST",
               url:"users/ajaxView/"+id,
               success : function(results) {
                     // console.log(results);
                     $('#showViewByUser').html(results);
               }
            });
      });
   
   });
</script>