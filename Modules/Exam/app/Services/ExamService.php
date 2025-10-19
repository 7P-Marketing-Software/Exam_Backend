<?php

namespace Modules\Exam\Services;
use Carbon\Carbon;
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

    public function updateExam($request, $id)
    {
        $exam = Exam::with('questions.answers')->findOrFail($id);
        $starts_at = $request->starts_at ? Carbon::createFromFormat('Y-m-d h:i:s A', $request->starts_at) : $exam->starts_at;
        $ends_at = $request->ends_at ? Carbon::createFromFormat('Y-m-d h:i:s A', $request->ends_at) : $exam->ends_at;

        if ($starts_at >= $ends_at) {
            throw new \Exception("Ends at can't be before Starts at");
        }

        $exam->update([
            'title' => $request->title ?? $exam->title,
            'exam_time' => $request->exam_time ?? $exam->exam_time,
            'starts_at' => $starts_at,
            'ends_at' => $ends_at,
        ]);

        if ($request->hasFile('model_answer')) {
            if ($exam->model_answer) $this->deleteFromSpaces($exam->model_answer);
            $exam->update([
                'model_answer' => $this->uploadToSpaces(
                    $request->file('model_answer'),
                    'Exams',
                    'model_answers',
                    'model_answer_' . time() . '.' . $request->file('model_answer')->getClientOriginalExtension()
                )
            ]);
        }

        if ($request->questions) {
            $exam->questions()->delete();
            $fullGrade = 0;

            foreach ($request->questions as $index => $questionData) {
                $question = $this->createQuestion($exam, $questionData, $index, $request);
                $fullGrade += $question->grade;
            }

            $exam->update(['grade' => $fullGrade]);
        }

        return $exam->load('questions.answers');
    }

    private function createQuestion($exam, $questionData, $index, $request)
    {
        $dataToAdd = [
            'grade' => $questionData['grade'],
            'title' => $questionData['title'] ?? null,
        ];

        if (isset($request->file('questions')[$index]['image'])) {
            $image = $request->file('questions')[$index]['image'];
            $dataToAdd['image'] = $this->uploadToSpaces(
                $image,
                'Exams',
                'questions',
                'question_' . time() . '.' . $image->getClientOriginalExtension()
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
                        'question_version_' . time() . '.' . $file->getClientOriginalExtension()
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
                'isCorrect' => $answerData['isCorrect'],
                'question_id' => $question->id,
            ]);

            if ($answerData['isCorrect']) {
                $question->update(['correctAnswer' => $answer->id]);
            }
        }

        return $question;
    }
}
