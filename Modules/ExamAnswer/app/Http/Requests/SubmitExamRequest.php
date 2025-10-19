<?php

namespace Modules\ExamAnswer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitExamRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|integer|exists:students,id',
            'exam_id'    => 'required|integer|exists:exams,id',
            'answers'    => 'required|array',
            'answers.*'  => 'integer|nullable',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }
}
