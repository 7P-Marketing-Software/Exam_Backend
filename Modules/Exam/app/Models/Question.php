<?php

namespace Modules\Exam\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'image',
        'grade',
        'correctAnswer',
        'versions',
        'exam_id',
    ];

    protected $casts = [
        'versions' => 'array',
    ];

    public function exam()
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_id');
    }


}
