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
        'client_id',
        'service_id',
        'follower_id',
        'file',
        'status',
        'priority',
        'pin',
        'completed',
        'completed_date',
        'status_date',
    ];

    // public function user() {
    //     return $this->belongsTo('App\Models\User');
    // }
    
}
