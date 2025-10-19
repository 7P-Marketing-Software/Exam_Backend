<?php

namespace Modules\Exam\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'starts_at',
        'ends_at',
        'exam_time',
        'grade',
        'model_answer'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function questions()
    {
        return $this->hasMany(Question::class, 'exam_id');
    }

}
