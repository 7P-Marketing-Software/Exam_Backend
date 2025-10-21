<?php

namespace Modules\Exam\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|string',
            'starts_at' => 'sometimes|date_format:Y-m-d h:i:s A',
            'ends_at' => 'sometimes|date_format:Y-m-d h:i:s A',
            'exam_time' => 'sometimes|integer',
            'model_answer' => 'nullable|file|mimes:pdf,doc,docx',
            'questions' => 'sometimes|array',
            'questions.*.title' => 'required_without:questions.*.image|string',
            'questions.*.image' => 'required_without:questions.*.title|image|mimes:jpeg,png,jpg,gif',
            'questions.*.grade' => 'sometimes|numeric',
            'questions.*.versions' => 'array',
            'questions.*.versions.*.title' => 'nullable|string',
            'questions.*.versions.*.image' => 'nullable|image|mimes:jpeg,png,jpg,gif',
            'questions.*.answers' => ['sometimes', 'array'],
            'questions.*.answers.*.title' => 'required|string',
            'questions.*.answers.*.isCorrect' => ['required', 'boolean'],
        ];
    }
}
