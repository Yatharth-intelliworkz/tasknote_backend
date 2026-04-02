<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupDiscussion extends Model
{
    use HasFactory;
    protected $table = 'group_discussion';
    protected $primarykey = 'id';
}
