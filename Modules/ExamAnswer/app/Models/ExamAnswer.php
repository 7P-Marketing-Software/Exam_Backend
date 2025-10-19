<?php

namespace Modules\ExamAnswer\Models;

use MongoDB\Laravel\Eloquent\DocumentModel;
use MongoDB\Laravel\Eloquent\Model as Eloquent;

class ExamAnswer extends Eloquent
{
    use DocumentModel;

    protected $connection = 'mongodb';
    protected $collection = 'exam_answers';

    protected $fillable = [
        'student_id',
        'exam_answers',
    ];
}
