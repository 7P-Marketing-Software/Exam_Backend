<?php

namespace Modules\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\OneCorrectAnswer;

class StoreExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'starts_at' => 'required|date_format:Y-m-d h:i:s A',
            'ends_at' => 'required|date_format:Y-m-d h:i:s A',
            'exam_time' => 'required|integer',
            'model_answer' => 'nullable|file|mimes:pdf,doc,docx',
            'questions' => ['required', 'array'],
            'questions.*.title' => 'required_without:questions.*.image|string',
            'questions.*.image' => 'required_without:questions.*.title|image|mimes:jpeg,png,jpg,gif',
            'questions.*.grade' => 'required|numeric',
            'questions.*.versions' => 'array',
            'questions.*.versions.*.title' => 'nullable|string',
            'questions.*.versions.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'questions.*.answers' => ['required', 'array', new OneCorrectAnswer],
            'questions.*.answers.*.title' => 'required|string',
            'questions.*.answers.*.isCorrect' => ['required', 'boolean'],
        ];
    }
}
