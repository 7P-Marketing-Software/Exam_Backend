<?php

namespace Modules\ExamAnswer\Services;

use Carbon\Carbon;
use Exception;
use Modules\Exam\Models\Exam;
use Modules\ExamAnswer\Models\ExamAnswer;

class ExamOperationsService
{
    public function checkValidExam(int $exam_id)
    {
        $exam = Exam::find($exam_id);

        if ($exam->ends_at < Carbon::now()) {
            throw new Exception('Exam has already finished.');
        }

        if ($exam->starts_at > Carbon::now()) {
            throw new Exception("Exam hasn't started yet.");
        }

        return $exam;
    }

    public function prepareExam($exam, $expires_at): Exam
    {
        $expires_at = Carbon::parse($expires_at);

        $diff = $expires_at->diff(Carbon::now());

        $exam->load(['questions:id,exam_id,title,grade,image,versions', 'questions.answers:id,question_id,title']);

        $exam->setRelation('questions', $exam->questions->shuffle());

        foreach ($exam->questions as $question) {
            $titleOptions = [$question->title];
            $imageOptions = [$question->image];

            if (!empty($question->versions) && is_array($question->versions)) {
                foreach ($question->versions as $version) {
                    if (!empty($version['title'])) {
                        $titleOptions[] = $version['title'];
                    }
                    if (!empty($version['image'])) {
                        $imageOptions[] = $version['image'];
                    }
                }
            }

            $question->title = $titleOptions[array_rand($titleOptions)];
            $question->image = $imageOptions[array_rand($imageOptions)];
        }

        $exam->remainingTime = [
            'years' => $diff->y,
            'months' => $diff->m,
            'days' => $diff->d,
            'hours' => $diff->h,
            'minutes' => $diff->i,
            'seconds' => $diff->s,
        ];

        return $exam;
    }

    public function updateLastExamAnswer($lastTaskAnswer, $exam_time, $user_id, $exam_id)
    {
        $nowTime = Carbon::now();
        $exam_answers = [];

        $exam_answers['status'] = 'In Progress';
        $exam_answers['grade'] = null;
        $exam_answers['numberOfRightAnswers'] = null;

        $exam_answers['attempts'] = ($lastTaskAnswer['attempts'] ?? 0) + 1;
        $exam_answers['numberOfFails'] = ($exam_answers['numberOfFails'] ?? 0) + 1;

        $exam_answers['starts_at'] = $nowTime->toISOString();
        $exam_answers['expires_at'] = $nowTime->copy()->addMinutes($exam_time)->toISOString();

        $examAnswers = ExamAnswer::where('student_id', $user_id)->first();
        $examAnswersCollection = collect($examAnswers->exam_answers);
        $index = $examAnswersCollection->search(function ($item) use ($exam_id) {
            return $item['exam_id'] == (string)$exam_id;
        });

        if ($index !== false) {
            $examAnswersCollection[$index] = array_merge(
                $examAnswersCollection[$index],
                $exam_answers
            );

            $examAnswers->update([
                'exam_answers' => $examAnswersCollection->toArray(),
            ]);
        }
    }

    public function checkSubmitValidExamAnswer($exam, int $user_id)
    {
        $lastExamAnswer = ExamAnswer::where('student_id', $user_id)->first();
        $examAnswersCollection = collect($lastExamAnswer->exam_answers);
        $examAnswerById = $examAnswersCollection->where('exam_id', (string)$exam->id)->last();

        if (! $examAnswerById) {
            throw new Exception("You haven't started any exams yet.");
        }

        if ($examAnswerById['status'] === 'Fail') {
            throw new Exception('You failed before, please start the exam again.');
        }

        return [
            'examAnswerModel' => $lastExamAnswer,
            'examAnswerById'  => $examAnswerById,
        ];
    }

    public function gradingTheExam($questions, $answers, $examAnswer, $minPassingGrade = 0, $user_id, $exam_id)
    {
        $studentGrade = 0;
        $numberOfRightAnswers = 0;
        $numberOfFails = 0;
        $myAnswers = $answers;
        $max_grade = 0;

        foreach ($questions as $index => $question) {

            $max_grade += $question->grade;

            if (isset($myAnswers[$index])) {

                if ($question->correctAnswer == $myAnswers[$index]) {
                    $numberOfRightAnswers++;
                    $studentGrade += $question->grade;
                } else {
                    $numberOfFails++;
                }
            }
        }

        if ($studentGrade >= $minPassingGrade) {
            $status = 'Success';
        } else {
            $status = 'Fail';
        }

        $exam_answers = [];

        $exam_answers['numberOfRightAnswers'] = $numberOfRightAnswers;
        $exam_answers['grade'] = $studentGrade;
        $exam_answers['maximum_grade'] = $max_grade;
        $exam_answers['status'] = $status;
        $exam_answers['answers'] = $myAnswers;
        $exam_answers['numberOfFails'] = $numberOfFails;

        $examAnswers = ExamAnswer::where('student_id', $user_id)->first();
        $examAnswersCollection = collect($examAnswers->exam_answers);
        $index = $examAnswersCollection->search(function ($item) use ($exam_id) {
            return $item['exam_id'] == (string)$exam_id;
        });
        $exam_answers['attempts'] =  $examAnswersCollection[$index]['attempts'] ?? 1;;

        if ($index !== false) {

            $examAnswersCollection[$index] = array_merge(
                $examAnswersCollection[$index],
                $exam_answers
            );

            $examAnswers->update([
                'exam_answers' => $examAnswersCollection->toArray(),
            ]);
        }
        return $examAnswers;
    }

    public function getExamAnswersOfUser($user_id,$course_id)
    {
        $taskAnswers = ExamAnswer::where('student_id', (integer)$user_id)->get();
        $allExamAnswers = collect();

        foreach ($taskAnswers as $taskAnswer) {
            foreach ($taskAnswer->exam_answers ?? [] as $examAnswer) {
                $examId = $examAnswer['exam_id'] ?? null;
                if (! $examId) continue;

                $exam = Exam::find($examId);

                if ($exam) {
                    $examAnswer['exam_data'] = $exam;
                    $examAnswer['user_id'] = $user_id;
                    $allExamAnswers->push($examAnswer);
                }
            }
        }

        return $allExamAnswers->values();
    }
}
