<?php

namespace Modules\Exam\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Exam\Http\Requests\StoreExamRequest;
use Modules\Exam\Http\Requests\UpdateExamRequest;
use Modules\Exam\Services\ExamService;
use Modules\Exam\Models\Exam;
use Illuminate\Http\Request;
use Carbon\Carbon;


class ExamController extends Controller
{
    protected $examService;

    public function __construct(ExamService $examService)
    {
        $this->examService = $examService;
    }

    public function index(Request $request)
    {
        $admin = auth('sanctum')->user();
        $query = Exam::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

         if (!$admin) {
            $today = Carbon::today();

            $query->whereDate('start_date', '<=', $today)
                  ->whereDate('end_date', '>=', $today);
        }
        $query->latest();

        $page = $request->get('page', 1);
        $exams = $query->paginate(null, ['*'], 'page', $page)->withPath($request->url());

        return $this->respondOk($exams);
    }

    public function store(StoreExamRequest $request)
    {
        try {
            $exam = $this->examService->createExam($request);
            return $this->respondCreated($exam, 'Exam created successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 'Failed to create exam');
        }
    }

    public function update(UpdateExamRequest $request, $examId)
    {
        try {
            $exam = $this->examService->updateExam($request, $examId);
            return $this->respondOk($exam, 'Exam updated successfully');
        } catch (\Exception $e) {
            return $this->respondError($e->getMessage(), 'Failed to update exam');
        }
    }

    public function destroy($examId)
    {
        $exam = Exam::find($examId);

        if (!$exam) {
            return $this->respondNotFound(null, "Exam not found.");
        }

        if ($exam->model_answer) {
            $this->deleteFromSpaces($exam->model_answer);
        }

        $exam->delete();

        return $this->respondOk(null, 'Exam deleted successfully');
    }
}
