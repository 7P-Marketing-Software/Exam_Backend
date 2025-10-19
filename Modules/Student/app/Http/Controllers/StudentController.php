<?php

namespace Modules\Student\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Student\Models\Student;

class StudentController extends Controller
{

    public function index(Request $request)
    {
        $query = Student::query();

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('phone')) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        if ($request->filled('national_id')) {
            $query->where('national_id', 'like', '%' . $request->national_id . '%');
        }

        $page = $request->get('page', 1);
        $students = $query->paginate(null, ['*'], 'page', $page)->withPath($request->url());

        return $this->respondOk($students);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'national_id' => 'required|string|max:20',
        ]);

        $student = Student::where('national_id', $validated['national_id'])->first();

        if ($student) {
            return $this->respondOk($student, 'Student already exists');
        }

        $student = Student::create($validated);

        return $this->respondCreated($student, 'Student created successfully');
    }
}
