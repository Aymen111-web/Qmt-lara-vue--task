<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Todo;

class TodoController extends Controller
{
    // GET ALL TODOS (for logged user)
    public function index(Request $request)
    {
        return Todo::where('user_id', $request->user()->id)
            ->latest()
            ->get();
    }

    // CREATE TODO
    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required',
                'description' => 'nullable',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date',
            ]);

            return Todo::create([
                'user_id' => $request->user()->id,
                'title' => $request->title,
                'description' => $request->description,
                'start_date' => $request->start_date,
                'due_date' => $request->due_date,
                'status' => 'todo'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], 500);
        }
    }

    // UPDATE TODO
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
        ]);

        $todo = Todo::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $todo->update($validated);

        return $todo;
    }

    // DELETE TODO
    public function destroy(Request $request, $id)
    {
        $todo = Todo::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $todo->delete();

        return response()->json(['message' => 'Deleted']);
    }

    // CHANGE STATUS (optional but useful for your UI buttons)
    public function changeStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:todo,pending,completed,overdue'
        ]);

        $todo = Todo::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $todo->status = $request->status;
        $todo->save();

        return $todo;
    }
}
