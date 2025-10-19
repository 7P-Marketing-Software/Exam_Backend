<?php

namespace Modules\ExamAnswer\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Exam\Models\Exam;
use Modules\ExamAnswer\Models\ExamAnswer;
use Modules\Student\Models\Student;
use Carbon\Carbon;
use Modules\ExamAnswer\Http\Requests\SubmitExamRequest;
use Modules\ExamAnswer\Services\ExamAnswerService;

class ExamAnswerController extends Controller
{
    public function __construct(private ExamAnswerService $service) {}

    public function startExam(Request $request)
    {
        $validated=$request->validate([
            'student_id' => 'required|integer|exists:students,id',
            'exam_id' => 'required|integer|exists:exams,id',
        ]);

        $user = Student::findOrFail($validated['student_id']);
        $exam = Exam::with(['questions:id,exam_id,title,grade,image,versions', 'questions.answers:id,question_id,title'])->find($validated['exam_id']);

        $examAnswerDoc = ExamAnswer::where('student_id', $user->id)->first();
        if ($examAnswerDoc) {
            $examAnswersCollection = collect($examAnswerDoc->exam_answers);
            $examAnswerById = $examAnswersCollection->firstWhere('exam_id', (string)$exam->id);

            if($examAnswerById)
            {
                if($examAnswerById['status'] == 'In Progress' && $examAnswerById['expires_at'] > Carbon::now())
                {
                    return $this->respondNotFound(null, 'You have already started this exam before, please submit first.');
                }
                if($examAnswerById['status'] == 'Success')
                {
                    return $this->respondNotFound(null, 'You have already passed this exam before');
                }
            }
        }

        return $this->respondOk($this->service->startExam($validated['student_id'], $validated['exam_id']));
    }

    public function submitExam(SubmitExamRequest $request)
    {
        return $this->respondOk($this->service->submitExam($request));
    }
}
