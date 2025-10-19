<?php

namespace Modules\Exam\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'isCorrect',
        'question_id'
    ];

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }
}
