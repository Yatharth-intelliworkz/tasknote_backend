<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_id',
        'title',
        'start_date',
        'due_date',
        'description',
        'project_id',
        'follow_id',
        'client_id',
        'service_id',
        'follower_id',
        'file',
        'status',
        'priority',
        'is_recurring',
        'recurring_time',
        'pin',
        'completed',
        'completed_date',
        'status_date',
        'target_time',
        'actual_time',
        'periodic_date',
        'is_send',
        'remainingtotalCost',
        'responsible_person',
        'type_id',
        'is_send',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
    
    public function client()
    {
        return $this->belongsTo(ProjectClient::class, 'client_id');
    }
    
    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }
    
    public function status()
    {
        return $this->belongsTo(CompanyStatus::class, 'status');
    }
    
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function subtasks()
    {
        return $this->hasMany(SubTask::class);
    }
    
    public function assignees()
    {
        return $this->hasMany(TaskAssigne::class);
    }
    
}
