<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Question;
use App\Models\Difficulty;
use App\Models\Course;
use App\Models\Chapter;
use App\Models\Answer;
use App\Models\Part;
use App\Services\OpenAIService;
use App\Services\DocxService;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller {

    protected $openAIService;
    protected $docxService;

    // Show Upload Question Page
    public function create($chapterID) {
        // Check if the chapter exists
        $chapter = Chapter::where('ChapterID', $chapterID)->firstOrFail();
        $course = $chapter->course; // Get the course related to the chapter
        $part = $chapter->part; // Assuming you have a relationship defined to get the part

        $difficulties = Difficulty::all();

        return view('uploadQuestion', compact('chapter', 'course', 'part', 'difficulties'));
    }

    public function show($chapterID) {
        $chapter = Chapter::where('ChapterID', $chapterID)->firstOrFail();
        $course = $chapter->course;
        $part = $chapter->part;

        return view('uploadQuestion', compact('chapter', 'course', 'part'));
    }

    // app/Http/Controllers/QuestionController.php
    public function uploadQuestion($course, $chapter) {
        return view('upload-question', [
            'course' => $course,
            'chapter' => $chapter,
        ]);
    }

    // Store Uploaded Questions
    public function store(Request $request) {
        $request->validate([
            'chapterID' => 'required|exists:chapters,ChapterID',
            'part_id' => 'required|exists:parts,PartID',
            'difficulty_id' => 'required|exists:difficulties,DifficultyID',
            'question_text' => 'required|string',
            'correct_answer' => 'required|string',
            'explanation' => 'required|string',
        ]);

        // Generate Custom Question ID (Q00001, Q00002, ...)
        $latestQuestion = Question::orderByRaw("CAST(SUBSTRING(QuestionID, 2) AS UNSIGNED) DESC")->first();
        $newQuestionID = $latestQuestion ? 'Q' . str_pad((intval(substr($latestQuestion->QuestionID, 1)) + 1), 5, '0', STR_PAD_LEFT) : 'Q00001';

        // Generate Custom Answer ID (A00001, A00002, ...)
        $latestAnswer = Answer::orderByRaw("CAST(SUBSTRING(AnswerID, 2) AS UNSIGNED) DESC")->first();
        $newAnswerID = $latestAnswer ? 'A' . str_pad((intval(substr($latestAnswer->AnswerID, 1)) + 1), 5, '0', STR_PAD_LEFT) : 'A00001';

        DB::transaction(function () use ($request, $newQuestionID, $newAnswerID) {
            $question = Question::create([
                        'QuestionID' => $newQuestionID,
                        'PartID' => $request->part_id,
                        'DifficultyID' => $request->difficulty_id,
                        'question_text' => $request->question_text,
            ]);

            Answer::create([
                'AnswerID' => $newAnswerID,
                'QuestionID' => $newQuestionID,
                'correct_answer' => $request->correct_answer,
                'explanation' => $request->explanation,
            ]);
        });

        return back()->with('success', 'Question uploaded successfully!');
    }

    // Edit Question
    public function edit($questionID) {
        $question = Question::where('QuestionID', $questionID)->with('answer')->firstOrFail();
        return view('editQuestion', compact('question'));
    }

    // Update Question
    public function update(Request $request, $questionID) {
        $request->validate([
            'question_text' => 'required|string',
            'correct_answer' => 'required|string',
            'explanation' => 'required|string',
        ]);

        $question = Question::where('QuestionID', $questionID)->firstOrFail();
        $question->update(['question_text' => $request->question_text]);

        $answer = Answer::where('QuestionID', $questionID)->first();
        if ($answer) {
            $answer->update([
                'correct_answer' => $request->correct_answer,
                'explanation' => $request->explanation,
            ]);
        }

        return back()->with('success', 'Question updated successfully!');
    }

    // Delete Question
    public function destroy($questionID) {
        Question::where('QuestionID', $questionID)->delete();
        return back()->with('success', 'Question deleted successfully!');
    }

    public function categorizeQuestion(Request $request, OpenAIService $openAIService) {
        $request->validate([
            'question' => 'required|string',
            'partID' => 'required|string|exists:parts,PartID'
        ]);

        $questionText = $request->input('question');
        $partID = $request->input('partID');

        // Get difficulty level from OpenAI
        $responseText = $openAIService->levelQuestion($questionText, 'gpt-3.5-turbo');

        // Convert "Medium" to "Normal" to match database structure
        if ($responseText === 'Medium') {
            $responseText = 'Normal';
        }

        // Find difficulty level for the given partID
        $difficulty = Difficulty::where('PartID', $partID)
                ->where('level', $responseText)
                ->first();

        if (!$difficulty) {
            return response()->json(['error' => 'Difficulty level not found for this part'], 400);
        }

        return response()->json([
                    'message' => 'Question categorized successfully',
                    'difficulty_level' => $responseText
        ]);
    }

    public function processQuestions(Request $request) {
        try {
            \Log::info('Processing questions request received.');

            $request->validate([
                'file' => 'required|mimes:docx|max:5120',
            ]);

            $file = $request->file('file');
            if (!$file) {
                \Log::error('No file uploaded.');
                return response()->json(['error' => 'No file uploaded'], 400);
            }

            \Log::info('File uploaded: ' . $file->getClientOriginalName());

            // Store the file
            $path = $file->store('uploads', 'public');
            $absolutePath = storage_path('app/public/' . $path);

            if (!file_exists($absolutePath)) {
                \Log::error('File does not exist after upload: ' . $absolutePath);
                return response()->json(['error' => 'File upload issue. Try again.'], 400);
            }

            \Log::info('File successfully uploaded and stored at: ' . $absolutePath);

            // Extract questions
            $questions = $this->docxService->extractQuestions($absolutePath);

            if (empty($questions)) {
                \Log::error('No questions extracted from the file.');
                return response()->json(['error' => 'No questions extracted from the file'], 400);
            }

            \Log::info('Extracted questions:', ['count' => count($questions)]);

            // Categorize questions
            $categorizedQuestions = [];
            foreach ($questions as $question) {
                $difficultyLevel = $this->openAIService->levelQuestion($question['question']);
                \Log::info('Question categorized:', ['question' => $question['question'], 'difficulty' => $difficultyLevel]);

                $difficulty = Difficulty::where('level', ucfirst($difficultyLevel))->first();
                if (!$difficulty) {
                    \Log::info('Creating new difficulty level: ' . ucfirst($difficultyLevel));
                    $difficulty = Difficulty::create(['level' => ucfirst($difficultyLevel)]);
                }

                $questionModel = Question::create([
                            'QuestionID' => $this->generateQuestionID(),
                            'question_text' => $question['question'],
                            'DifficultyID' => $difficulty->DifficultyID,
                ]);

                $categorizedQuestions[] = [
                    'question' => $questionModel->question_text,
                    'difficulty' => $difficulty->name,
                ];
            }

            \Log::info('Categorized questions count:', ['count' => count($categorizedQuestions)]);

            return response()->json(['categorized_questions' => $categorizedQuestions]);
        } catch (\Exception $e) {
            \Log::error('Error processing questions: ' . $e->getMessage(), ['stack_trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'An error occurred while processing the file'], 500);
        }
    }

    private function generateQuestionID() {
        $latestQuestion = Question::orderByRaw("CAST(SUBSTRING(QuestionID, 2) AS UNSIGNED) DESC")->first();
        $nextID = $latestQuestion ? ((int) substr($latestQuestion->QuestionID, 1)) + 1 : 1;
        return 'Q' . str_pad($nextID, 5, '0', STR_PAD_LEFT);
    }

    public function __construct(OpenAIService $openAIService, DocxService $docxService) {
        $this->openAIService = $openAIService;
        $this->docxService = $docxService;
    }

    public function showUploadQuestionPage() {
        $difficulties = Difficulty::all();
        return view('UploadQuestion', compact('difficulties'));
    }
}
