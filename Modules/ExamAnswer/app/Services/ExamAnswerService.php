<?php

namespace Modules\ExamAnswer\Services;
use Modules\ExamAnswer\Models\ExamAnswer;
use Carbon\Carbon;
use Modules\Exam\Models\Exam;
use Modules\Student\Models\Student;

class ExamAnswerService
{
    public function __construct(public ExamAnswer $taskAnswer, private ExamOperationsService $service) {}

    public function createExamAnswer($exam, $studentId)
    {
        $nowTime = Carbon::now();

        $max_grade = $exam->questions->sum('grade');

        $examAnswerDoc = ExamAnswer::where('student_id', $studentId)->first();

        $newExamAnswer = [
            'exam_id' => $exam->id,
            'starts_at' => $nowTime->toISOString(),
            'expires_at' => $nowTime->copy()->addMinutes($exam->time)->toISOString(),
            'maximum_grade' => $max_grade,
            'status' => 'InProgress',
            'numberOfRightAnswers' => 0,
            'grade' => 0,
            'numberOfFails' => 0,
            'attempts' => 1,
            'answers' => [],
            'questions' => [],
        ];

        if ($examAnswerDoc) {
            $examAnswerDoc->push('exam_answers', $newExamAnswer);

            return $examAnswerDoc->fresh();
        }

        $examAnswer = ExamAnswer::create([
            'student_id'   => $studentId,
            'exam_answers' => [$newExamAnswer]
        ]);

        return $examAnswer;
    }


    public function startExam($studentId, $examId)
    {
        $user = Student::findOrFail($studentId);

        $exam = $this->service->checkValidExam($examId);

        $lastTaskAnswer = ExamAnswer::where('student_id', $user->id)->first();
        if ($lastTaskAnswer) {
            $taskAnswersCollection = collect($lastTaskAnswer->exam_answers);
            $taskAnswerById = $taskAnswersCollection->firstWhere('exam_id', (string)$examId);

            if ($taskAnswerById) {

                if ($taskAnswerById['status'] === 'Drafted') {

                    $expiresAt = $taskAnswerById['expires_at'];

                    $this->service->prepareExam($exam, $expiresAt);

                    $exam->answers = $taskAnswerById['answers'] ?? [];
                    return $exam;
                }

                $expiresAt = $taskAnswerById['expires_at'];
                if (
                    ($taskAnswerById['status'] === 'Fail') ||
                    ($taskAnswerById['status'] === 'Success') ||
                    ($taskAnswerById['status'] === 'In Progress' && Carbon::parse($expiresAt)->isPast())
                ) {
                    $this->service->updateLastExamAnswer($taskAnswerById, $exam->time, $user->id, $examId);

                    $this->service->prepareExam($exam, $expiresAt);

                    return $exam;
                }
            }
        }

        $examAnswer = $this->createExamAnswer( $exam, $user->id);

        $lastTaskAnswer = ExamAnswer::where('student_id', $user->id)->first();
        $taskAnswersCollection = collect($lastTaskAnswer->exam_answers);
        $taskAnswerById = $taskAnswersCollection->firstWhere('exam_id', (string)$examId);

        $finalExpiresAt = $taskAnswerById['expires_at'] ?? $examAnswer->exam_answers['expires_at'];

        $this->service->prepareExam($exam, $finalExpiresAt);

        if ($taskAnswerById && $taskAnswerById['status'] === 'Drafted') {
            $exam->answers = $taskAnswerById['answers'] ?? [];
        }

        return $exam;
    }

    public function submitExam($request)
    {
        $user = Student::findOrFail($request['student_id']);
        $exam = Exam::findOrFail($request['exam_id']);
        $max_grade = $exam->questions->sum('grade');

        $examAnswerData = $this->service->checkSubmitValidExamAnswer($exam, $user->id);
        $examAnswerModel = $examAnswerData['examAnswerModel'];
        $examAnswerById = $examAnswerData['examAnswerById'];
        $examAnswersCollection = collect($examAnswerModel->exam_answers);

        if ($exam->type === "Exam") {
            $expiration = Carbon::parse($examAnswerById['expires_at']);
            if ($expiration->lt(Carbon::now())) {
                $this->updateExamAnswer(
                    $examAnswersCollection,
                    $exam->id,
                    [
                        'status' => 'Fail',
                        'grade' => 0,
                        'maximum_grade' => $max_grade,
                        'numberOfRightAnswers' => 0,
                        'numberOfFails' => count($exam->questions),
                        'attempts' => $examAnswerById['attempts'] ?? 1,
                    ]
                );

                $examAnswerModel->update([
                    'exam_answers' => $examAnswersCollection->toArray(),
                ]);
            }
        }


        $exam->load('questions');

        $minGrade = $exam->grade /2 ?? 0;

        $examAnswer = $this->service->gradingTheExam(
            $exam->questions,
            $request->validated()['answers'],
            $examAnswerModel,
            $minGrade,
            $user->id,
            $exam->id
        );

        $exam_answers = $examAnswer->exam_answers ?? [];

        $exam_id = (string) $exam->id;

        foreach ($exam_answers as &$answer) {
            if ($answer['exam_id'] == $exam_id) {
                $newAnswer = [
                    'exam_id' => $answer['exam_id'],
                    'starts_at' => $answer['starts_at'],
                    'expires_at' => $answer['expires_at'],
                    'maximum_grade' => $answer['maximum_grade'],
                    'status' => $answer['status'],
                    'numberOfRightAnswers' => $answer['numberOfRightAnswers'] ,
                    'grade' => $answer['grade'],
                    'answers' => $answer['answers'],
                    'numberOfFails' => $answer['numberOfFails'],
                    'attempts' => $answer['attempts'] ?? 1,
                    'questions' => $exam->questions->toArray(),
                ];
                $answer = $newAnswer;
            }
        }
        $examAnswer->update([
            'exam_answers' => $exam_answers,
        ]);

        $singleTaskAnswer = collect($exam_answers)->firstWhere('exam_id', $exam_id);

        $exam = Exam::with('questions.answers')->find($exam_id);
        $exam->result = $singleTaskAnswer;

        return $exam;
    }

    private function updateExamAnswer(&$collection, $taskId, $newData)
    {
        $index = $collection->search(fn($item) => $item['exam_id'] == (string)$taskId);

        if ($index !== false) {
            $collection[$index] = array_merge(
                $collection[$index],
                $newData
            );
        }
    }
}
