<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\TaskController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\ReportController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\GroupDiscussionController;
use App\Models\GroupDiscussion;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::controller(AuthController::class)->group(function () {
    Route::post('login', 'login');
    Route::post('register', 'register');
    Route::post('logout', 'logout');
    // Route::get('profile', [UserController::class, 'index'])->name('profile');
});
Route::middleware('auth:api')->group(function () {
    // Users
    Route::get('profile', [UserController::class, 'index'])->name('profile');
    Route::get('profileremove', [UserController::class, 'profileremove'])->name('profileremove');
    Route::post('updateProfile', [UserController::class, 'updateProfile'])->name('updateProfile');
    Route::post('updateBilling', [UserController::class, 'updateBilling'])->name('updateBilling');
    Route::post('changePassword', [UserController::class, 'changePassword'])->name('changePassword');
    Route::post('userDetails', [UserController::class, 'userDetails'])->name('userDetails');
    Route::post('userTaskList', [TaskController::class, 'userTaskList'])->name('userTaskList');
    Route::post('userFavorite', [UserController::class, 'userFavorite'])->name('userFavorite');
    Route::get('userMemberList', [UserController::class, 'userMemberList'])->name('userMemberList');
    Route::get('getShortName', [UserController::class, 'getShortName'])->name('getShortName');
    Route::get('userAllPermission', [UserController::class, 'userAllPermission'])->name('userAllPermission');

    // Tasks
    Route::post('taskList', [TaskController::class, 'index'])->name('taskList');
    Route::post('taskAdd', [TaskController::class, 'taskAdd'])->name('taskAdd');
    Route::post('taskEdit', [TaskController::class, 'taskEdit'])->name('taskEdit');
    Route::post('taskStatusUpdate', [TaskController::class, 'taskStatusUpdate'])->name('taskStatusUpdate');
    Route::post('taskPriorityUpdate', [TaskController::class, 'taskPriorityUpdate'])->name('taskPriorityUpdate');
    Route::post('taskPinUpdate', [TaskController::class, 'taskPinUpdate'])->name('taskPinUpdate');
    Route::post('taskCompleted', [TaskController::class, 'taskCompleted'])->name('taskCompleted');
    Route::post('fileUpload', [TaskController::class, 'fileUpload'])->name('fileUpload');
    Route::post('taskDetail', [TaskController::class, 'taskDetail'])->name('taskDetail');
    Route::post('taskDelete', [TaskController::class, 'taskDelete'])->name('taskDelete');
    Route::post('subTaskDelete', [TaskController::class, 'subTaskDelete'])->name('subTaskDelete');
    Route::post('fileDelete', [TaskController::class, 'fileDelete'])->name('fileDelete');
    Route::post('userByTaskList', [TaskController::class, 'userByTaskList'])->name('userByTaskList');
    Route::get('taskProjectList', [TaskController::class, 'taskProjectList'])->name('taskProjectList');
    Route::post('taskListAll', [TaskController::class, 'taskListAll'])->name('taskListAll');
    Route::post('subTaskCompleted', [TaskController::class, 'subTaskCompleted'])->name('subTaskCompleted');
    Route::post('checkListCompleted', [TaskController::class, 'checkListCompleted'])->name('checkListCompleted');
    Route::post('taskComment', [TaskController::class, 'taskComment'])->name('taskComment');
    Route::get('taskCommentList', [TaskController::class, 'taskCommentList'])->name('taskCommentList');
    Route::post('taskReminder', [TaskController::class, 'taskReminder'])->name('taskReminder');
    Route::get('taskCommentDelete', [TaskController::class, 'taskCommentDelete'])->name('taskCommentDelete');

    // Notes
    Route::get('notesList', [TaskController::class, 'notesList'])->name('notesList');
    Route::get('getNoteUserList', [TaskController::class, 'getNoteUserList'])->name('getNoteUserList');
    Route::get('getSharedNoteUserList', [TaskController::class, 'getSharedNoteUserList'])->name('getSharedNoteUserList');
    Route::get('noteGet', [TaskController::class, 'noteGet'])->name('noteGet');
    Route::post('noteAdd', [TaskController::class, 'noteAdd'])->name('noteAdd');
    Route::post('noteEdit', [TaskController::class, 'noteEdit'])->name('noteEdit');
    Route::post('notePinUpdate', [TaskController::class, 'notePinUpdate'])->name('notePinUpdate');
    Route::post('noteColorUpdate', [TaskController::class, 'noteColorUpdate'])->name('noteColorUpdate');
    Route::post('noteShare', [TaskController::class, 'noteShare'])->name('noteShare');
    Route::post('noteDelete', [TaskController::class, 'noteDelete'])->name('noteDelete');

    // Project
    Route::get('projectList', [ProjectController::class, 'index'])->name('projectList');
    Route::get('teamAndMembersList', [ProjectController::class, 'teamAndMembersList'])->name('teamAndMembersList');
    Route::get('membersGet', [ProjectController::class, 'membersGet'])->name('membersGet');
    Route::get('managerGet', [ProjectController::class, 'managerGet'])->name('managerGet');
    Route::get('projectGet', [ProjectController::class, 'projectGet'])->name('projectGet');
    Route::post('projectAdd', [ProjectController::class, 'projectAdd'])->name('projectAdd');
    Route::post('projectEdit', [ProjectController::class, 'projectEdit'])->name('projectEdit');
    Route::post('projectDelete', [ProjectController::class, 'projectDelete'])->name('projectEdit');
    Route::post('projectFavorite', [ProjectController::class, 'projectFavorite'])->name('projectFavorite');
    Route::get('projectDetail', [ProjectController::class, 'projectDetail'])->name('projectDetail');
    Route::get('projectTaskList', [ProjectController::class, 'projectTaskList'])->name('projectTaskList');
    Route::get('projectMembersList', [ProjectController::class, 'projectMembersList'])->name('projectMembersList');


    // Notification
    Route::get('notificationList', [ProjectController::class, 'notificationList'])->name('notificationList');
    Route::post('notificationView', [ProjectController::class, 'notificationView'])->name('notificationView');
    Route::get('notificationAllList', [ProjectController::class, 'notificationAllList'])->name('notificationAllList');
    Route::get('notificationViewAll', [ProjectController::class, 'notificationViewAll'])->name('notificationViewAll');
    Route::get('notificationDeleteAll', [ProjectController::class, 'notificationDeleteAll'])->name('notificationDeleteAll');


    // client
    Route::get('clientList', [AuthController::class, 'clientList'])->name('clientList');
    Route::get('clientGet', [AuthController::class, 'clientGet'])->name('clientGet');
    Route::post('clientAdd', [AuthController::class, 'clientAdd'])->name('clientAdd');
    Route::post('clientEdit', [AuthController::class, 'clientEdit'])->name('clientEdit');
    Route::post('clientDelete', [AuthController::class, 'clientDelete'])->name('clientDelete');

    // Service
    Route::get('serviceList', [ProjectController::class, 'serviceList'])->name('serviceList');
    Route::get('serviceGet', [ProjectController::class, 'serviceGet'])->name('serviceGet');
    Route::post('serviceAdd', [ProjectController::class, 'serviceAdd'])->name('serviceAdd');
    Route::post('serviceEdit', [ProjectController::class, 'serviceEdit'])->name('serviceEdit');
    Route::post('serviceDelete', [ProjectController::class, 'serviceDelete'])->name('serviceDelete');

    // Company
    Route::get('companyList', [ProjectController::class, 'companyList'])->name('companyList');
    Route::get('companyFounderList', [ProjectController::class, 'companyFounderList'])->name('companyFounderList');
    Route::get('companyGet', [ProjectController::class, 'companyGet'])->name('companyGet');
    Route::get('companyDetails', [ProjectController::class, 'companyDetails'])->name('companyDetails');
    Route::post('companyAdd', [ProjectController::class, 'companyAdd'])->name('companyAdd');
    Route::post('companyEdit', [ProjectController::class, 'companyEdit'])->name('companyEdit');
    Route::post('companyDelete', [ProjectController::class, 'companyDelete'])->name('companyDelete');
    Route::post('comapnyChange', [ProjectController::class, 'comapnyChange'])->name('comapnyChange');

    // Member
    Route::get('comapnyMemberList', [UserController::class, 'comapnyMemberList'])->name('comapnyMemberList');
    Route::get('comapnyMemberGet', [UserController::class, 'comapnyMemberGet'])->name('comapnyMemberGet');
    Route::post('comapnyMemberAdd', [UserController::class, 'comapnyMemberAdd'])->name('comapnyMemberAdd');
    Route::post('comapnyMemberEdit', [UserController::class, 'comapnyMemberEdit'])->name('comapnyMemberEdit');
    Route::post('comapnyMemberDelete', [UserController::class, 'comapnyMemberDelete'])->name('comapnyMemberDelete');


    // Role
    Route::get('roleList', [UserController::class, 'roleList'])->name('roleList');
    Route::get('roleGet', [UserController::class, 'roleGet'])->name('roleGet');
    Route::post('roleAdd', [UserController::class, 'roleAdd'])->name('roleAdd');
    Route::post('roleEdit', [UserController::class, 'roleEdit'])->name('roleEdit');
    Route::post('roleDelete', [UserController::class, 'roleDelete'])->name('roleDelete');

    // Status
    Route::get('mainStatusList', [ProjectController::class, 'mainStatusList'])->name('mainStatusList');
    Route::get('statusList', [ProjectController::class, 'statusList'])->name('statusList');
    Route::get('statusGet', [ProjectController::class, 'statusGet'])->name('statusGet');
    Route::post('statusAdd', [ProjectController::class, 'statusAdd'])->name('statusAdd');
    Route::post('statusEdit', [ProjectController::class, 'statusEdit'])->name('statusEdit');
    Route::post('statusDelete', [ProjectController::class, 'statusDelete'])->name('statusDelete');
    Route::post('statusActive', [ProjectController::class, 'statusActive'])->name('statusActive');

    // Team
    Route::get('teamList', [ProjectController::class, 'teamList'])->name('teamList');
    Route::get('teamGet', [ProjectController::class, 'teamGet'])->name('teamGet');
    Route::get('getTeamMember', [ProjectController::class, 'getTeamMember'])->name('getTeamMember');
    Route::post('teamAdd', [ProjectController::class, 'teamAdd'])->name('teamAdd');
    Route::post('teamEdit', [ProjectController::class, 'teamEdit'])->name('teamEdit');
    Route::post('teamDelete', [ProjectController::class, 'teamDelete'])->name('teamDelete');

    // Dashboard
    Route::get('dashboard', [ProjectController::class, 'dashboard'])->name('dashboard');

    // Reports
    Route::post('userReport', [ReportController::class, 'userReport'])->name('userReport');
    Route::post('projectReport', [ReportController::class, 'projectReport'])->name('projectReport');
    Route::post('statusReport', [ReportController::class, 'statusReport'])->name('statusReport');
    Route::post('userPerformanceReport', [ReportController::class, 'userPerformanceReport'])->name('userPerformanceReport');

    //chat

    Route::post('/chat/send', [ChatController::class, 'sendMessage']);
    Route::get('/chat/history/{user_id}', [ChatController::class, 'getMessageHistory'])->name('history');
    Route::get('/loadChats/{companyId}', [ChatController::class, 'loadChats'])->name('loadChats');
    Route::get('/messageread/{id}', [ChatController::class, 'messageread'])->name('messageread');
    Route::get('/unreadmesagecountsingle/{companyId}', [ChatController::class, 'unreadmesagecountsingle'])->name('unreadmesagecountsingle');

    //group discussion
    Route::post('creategroup', [GroupDiscussionController::class, 'creategroup'])->name('creategroup');
    Route::post('updategroup', [GroupDiscussionController::class, 'updategroup'])->name('updategroup');
    Route::post('groupDelete', [GroupDiscussionController::class, 'groupDelete'])->name('groupDelete');
    Route::post('sendgroupmessage', [GroupDiscussionController::class, 'sendgroupmessage'])->name('sendgroupmessage');
    Route::get('loadgroupchats/{groupId}', [GroupDiscussionController::class, 'loadgroupchats'])->name('loadgroupchats');
    Route::get('/unreadmesagecountgroup/{companyId}', [GroupDiscussionController::class, 'unreadmesagecountgroup'])->name('unreadmesagecountgroup');
    Route::get('/discussionprojects/{companyId}', [GroupDiscussionController::class, 'discussionprojects'])->name('discussionprojects');
    Route::get('/groupLists/{companyId}', [GroupDiscussionController::class, 'groupLists'])->name('groupLists');
    Route::get('/getGroupMember/{groupId}', [GroupDiscussionController::class, 'getGroupMember'])->name('getGroupMember');
    Route::get('/groupmessagesread/{groupId}', [GroupDiscussionController::class, 'groupmessagesread'])->name('groupmessagesread');
    Route::get('/groupEdit/{editId}', [GroupDiscussionController::class, 'groupEdit'])->name('groupEdit');
});
