<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\NoteController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });

Auth::routes();

Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/mailCheck', [App\Http\Controllers\Controller::class, 'mailCheck'])->name('mailCheck');

Route::group(['middleware' => ['auth']], function() {
    // users
    Route::resource('users', UserController::class);
    Route::post('/users/ajaxView/{id}', [UserController::class, 'ajaxView'])->name('ajaxView');
    
    // roles
    Route::resource('roles', RoleController::class);
    
    // service
    Route::resource('service', ServiceController::class);
    
    // project
    Route::resource('project', ProjectController::class);
    Route::get('/team', [ProjectController::class, 'teamList'])->name('team');
    Route::get('/team/create', [ProjectController::class, 'teamCreate'])->name('teamCreate');
    Route::post('/team/store', [ProjectController::class, 'teamStore'])->name('teamStore');
    Route::get('/team/edit/{id}', [ProjectController::class, 'teamEdit'])->name('teamEdit');
    Route::post('/team/update/{id}', [ProjectController::class, 'teamUpdate'])->name('teamUpdate');
    Route::get('/team/destroy/{id}', [ProjectController::class, 'teamDestroy'])->name('teamDestroy');
    Route::get('team/getMembersData/{id}', 'App\Http\Controllers\ProjectController@getMembersData')->name('getMembersData');

    // task
    Route::resource('task', TaskController::class);

    Route::get('/subTask', [TaskController::class, 'subTask'])->name('subTask');
    Route::get('/subTask/subTaskCreate', [TaskController::class, 'subTaskCreate'])->name('subTaskCreate');
    Route::get('subTask/getSubMembersData/{id}', 'App\Http\Controllers\TaskController@getSubMembersData')->name('getSubMembersData');
    Route::get('subTask/edit/getSubMembersData/{id}', 'App\Http\Controllers\TaskController@getMembersData')->name('getMembersData');

    // company
    Route::resource('company', CompanyController::class);

    // Note
    Route::resource('note', NoteController::class);
    
    Route::get('task/getassigneData/{id}', 'App\Http\Controllers\TaskController@getassigneData')->name('getassigneData');
    Route::get('removeImg/{id}', 'App\Http\Controllers\TaskController@removeImg')->name('removeImg');
    Route::get('removeSubTask/{id}', 'App\Http\Controllers\TaskController@removeSubTask')->name('removeSubTask');
    Route::get('removeCheckList/{id}', 'App\Http\Controllers\TaskController@removeCheckList')->name('removeCheckList');
});