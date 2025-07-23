<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{   

    //Display  list of all Feedback submissions.
    public function index()
    {
        try {
            // Get all feedback entries
            $feedback = Feedback::all();

            return response()->json([
                'message' => 'All Feedback retrieved successfully!',
                'data' => $feedback
            ], 200); // 200 OK
        } catch (\Exception $e) {
            // Handle any unexpected errors here.
            return response()->json([
                'message' => 'An error occurred while retrieving feedback.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

    // Create New Feedback Submission
    public function store(Request $request)
    {
        try {
            // Input validation
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:feedback'],
                'message' => ['required', 'string'],
            ]);

            // Create and save the feedback
            $feedback = Feedback::create($validated);

            return response()->json([
                'message' => 'Feedback Created Successfully.',
                'feedback' => $feedback
            ], 201); // 201 Created
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'message' => 'Validation Error',
                'errors' => $e->errors()
            ], 422); // 422 Unprocessable Entity
        } catch (QueryException $e) {
            // Handle database errors (e.g., duplicate email not caught by unique rule if race condition)
            return response()->json([
                'message' => 'Database Error: Could not save feedback.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            return response()->json([
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage()
            ], 500); // 500 Internal Server Error
        }
    }

}
