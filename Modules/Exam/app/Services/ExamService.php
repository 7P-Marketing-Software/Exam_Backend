<?php

namespace Modules\Exam\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Exam\Models\Exam;
use Modules\Exam\Models\Answer;
use App\Http\Traits\HasDigitalOceanSpaces;

class ExamService
{
    use HasDigitalOceanSpaces;

    public function createExam($request)
    {
        $starts_at = Carbon::createFromFormat('Y-m-d h:i:s A', $request->starts_at);
        $ends_at = Carbon::createFromFormat('Y-m-d h:i:s A', $request->ends_at);

        if ($starts_at < now() || $ends_at < now()) {
            throw new \Exception("Can't create Exam in the past");
        }

        if ($starts_at >= $ends_at) {
            throw new \Exception("Ends at can't be before Starts at");
        }

        $url = $request->hasFile('model_answer')
            ? $this->uploadToSpaces($request->file('model_answer'), 'Exams', 'model_answers', 'model_answer_' . time() . '.' . $request->file('model_answer')->getClientOriginalExtension())
            : null;

        $exam = Exam::create([
            'title' => $request->title,
            'starts_at' => $starts_at,
            'ends_at' => $ends_at,
            'exam_time' => $request->exam_time,
            'model_answer' => $url,
        ]);

        $fullGrade = 0;

        foreach ($request->questions as $index => $questionData) {
            $question = $this->createQuestion($exam, $questionData, $index, $request);
            $fullGrade += $question->grade;
        }

        $exam->update(['grade' => $fullGrade]);
        return $exam->load('questions.answers');
    }

    public function updateExam($request, $examId)
    {
        $exam = Exam::with('questions.answers')->findOrFail($examId);

        DB::beginTransaction();
        try {
           $starts_at = $request->starts_at
                ? Carbon::createFromFormat('Y-m-d h:i:s A', $request->starts_at)
                : $exam->starts_at;

            $ends_at = $request->ends_at
                ? Carbon::createFromFormat('Y-m-d h:i:s A', $request->ends_at)
                : $exam->ends_at;

            if ($starts_at >= $ends_at) {
                throw new \Exception("Ends at can't be before Starts at");
            }

            $updateData = [
                'title' => $request->title ?? $exam->title,
                'exam_time' => $request->exam_time ?? $exam->exam_time,
                'starts_at' => $starts_at,
                'ends_at' => $ends_at,
            ];

            if ($request->hasFile('model_answer')) {
                if ($exam->model_answer) {
                    $this->deleteFromSpaces($exam->model_answer);
                }
                $file = $request->file('model_answer');
                $updateData['model_answer'] = $this->uploadToSpaces(
                    $file,
                    'Exams',
                    'model_answers',
                    'model_answer_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                );
            }

            $exam->update($updateData);

            $existingQuestions = $exam->questions()->orderBy('id')->get();
            $fullGrade = 0;

            if (!empty($request->questions)) {
                foreach ($request->questions as $index => $questionData) {
                    $question = $existingQuestions[$index] ?? null;
                    $updateQ = [];

                    if ($question) {
                        if (isset($questionData['title'])) {
                            $updateQ['title'] = $questionData['title'];
                        }
                        if (isset($questionData['grade'])) {
                            $updateQ['grade'] = $questionData['grade'];
                        }

                        $file = $request->file("questions.$index.image");
                        if ($file) {
                            if ($question->image) {
                                $this->deleteFromSpaces($question->image);
                            }
                            $url = $this->uploadToSpaces(
                                $file,
                                'Exams',
                                'questions',
                                'question_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                            );
                            $updateQ['image'] = $url;
                        }

                        if (isset($questionData['versions'])) {
                            $versionsData = [];
                            foreach ($questionData['versions'] as $vIndex => $version) {
                                $versionData = ['title' => $version['title'] ?? null];
                                $file = $request->file("questions.$index.versions.$vIndex.image");

                                if ($file) {
                                    $url = $this->uploadToSpaces(
                                        $file,
                                        'Exams',
                                        'questions_versions',
                                        'question_version_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                                    );
                                    $versionData['image'] = $url;
                                } elseif (!empty($version['image'])) {
                                    $versionData['image'] = $version['image'];
                                }

                                $versionsData[] = $versionData;
                            }
                            $updateQ['versions'] = $versionsData;
                        }

                        $question->update($updateQ);
                    }

                    else {
                        $question = $this->createQuestion($exam, $questionData, $index, $request);
                    }

                    if (!empty($questionData['answers'])) {
                        foreach ($questionData['answers'] as $aData) {
                            if (!empty($aData['id'])) {
                                $answer = $question->answers()->find($aData['id']);
                                if ($answer) {
                                    $answer->update([
                                        'title' => $aData['title'] ?? $answer->title,
                                        'isCorrect' => $aData['isCorrect'] ?? $answer->isCorrect,
                                    ]);
                                }
                            } else {
                                $answer = $question->answers()->create([
                                    'title' => $aData['title'],
                                    'isCorrect' => $aData['isCorrect'] ?? false,
                                ]);
                            }

                            if (!empty($aData['isCorrect'])) {
                                $question->update(['correctAnswer' => $answer->id]);
                            }
                        }
                    }

                    $fullGrade += $question->grade;
                }

                $exam->update(['grade' => $fullGrade]);
            }

            DB::commit();
            return $exam->load('questions.answers');
        } catch (\Throwable $e) {
            DB::rollBack();
            throw new \Exception($e->getMessage());
        }
    }

    private function createQuestion($exam, $questionData, $index, $request)
    {
        $dataToAdd = [
            'title' => $questionData['title'] ?? null,
            'grade' => $questionData['grade'] ?? 0,
        ];

        $file = $request->file("questions.$index.image");
        if ($file) {
            $dataToAdd['image'] = $this->uploadToSpaces(
                $file,
                'Exams',
                'questions',
                'question_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
            );
        }

        if (isset($questionData['versions'])) {
            $versionsData = [];
            foreach ($questionData['versions'] as $vIndex => $version) {
                $versionData = ['title' => $version['title'] ?? null];
                $file = $request->file("questions.$index.versions.$vIndex.image");
                if ($file) {
                    $versionData['image'] = $this->uploadToSpaces(
                        $file,
                        'Exams',
                        'questions_versions',
                        'question_version_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension()
                    );
                }
                $versionsData[] = $versionData;
            }
            $dataToAdd['versions'] = $versionsData;
        }

        $question = $exam->questions()->create($dataToAdd);

        foreach ($questionData['answers'] as $answerData) {
            $answer = Answer::create([
                'title' => $answerData['title'],
                'isCorrect' => $answerData['isCorrect'] ?? false,
                'question_id' => $question->id,
            ]);

            if (!empty($answerData['isCorrect'])) {
                $question->update(['correctAnswer' => $answer->id]);
            }
        }

        return $question;
    }
}
