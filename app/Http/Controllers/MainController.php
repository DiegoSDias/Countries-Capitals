<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PrepareQuizService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

class MainController extends Controller
{
    protected PrepareQuizService $prepareQuizService;

    public function __construct(PrepareQuizService $prepareQuizService) {
        $this->prepareQuizService = $prepareQuizService; 
    }

    public function startGame() {
        session_start();
        return view('home');
    }

    public function prepareGame(Request $request) {
        $request->validate([
            'total_questions' => 'required|integer|min:3|max:30'
        ],
        [
            'total_questions.required' => 'O número de questões é obrigatório',
            'total_questions.integer' => 'O número de questões deve ser um valor inteiro',
            'total_questions.min' => 'O número mínino de questões é :min',
            'total_questions.max' => 'O número maximo de questões é :max',
        ]);

        $totalQuestions = $request->total_questions;
        $quiz = $this->prepareQuizService->prepareQuiz($totalQuestions);

        session([
            'quiz' => $quiz,
            'totalQuestions' => $totalQuestions,
            'currentQuestion' => 1,
            'correctAnswers' => 0,
            'wrongAnswers' => 0,
        ]);

        return redirect()->route('game');
    }

    public function game() {
        $quiz = session('quiz');
        $totalQuestions = session('totalQuestions');
        $currentQuestion = session('currentQuestion') - 1;

        $answers = $quiz[$currentQuestion]['wrongAnswer'];
        $answers[] = $quiz[$currentQuestion]['correctAnswer'];

        return view('game')->with([
            'country' => $quiz[$currentQuestion]['country'],
            'totalQuestions' => $totalQuestions,
            'currentQuestion' => $currentQuestion,
            'answers' => $answers,
        ]);
    }

    public function answer($answer_cript) {

        try {
            $answer = Crypt::decryptString($answer_cript);
        } catch (\Exception $e) {
            return redirect()->route('game');
        }
        
        $quiz = session('quiz');
        $currentQuestion = session('currentQuestion') - 1;
        $correctAnswer = $quiz[$currentQuestion]['correctAnswer'];
        $correctAnswers = session('correctAnswers');
        $wrongAnswers = session('wrongAnswers');
        
        if($answer == $correctAnswer) {
            $quiz[$currentQuestion]['correct'] = true;
            $correctAnswers++;
        } else {
            $quiz[$currentQuestion]['correct'] = false;
            $wrongAnswers++;
        }

        session([
            'quiz' => $quiz,
            'correctAnswers' => $correctAnswers,
            'wrongAnswers' => $wrongAnswers,
        ]);

        $data = [
            'country' => $quiz[$currentQuestion]['country'],
            'correctAnswer' => $correctAnswer,
            'choiceAnswer' => $answer,
            'currentQuestion' => $currentQuestion,
            'totalQuestions' => session('totalQuestions')
        ];

        return view('answer_result')->with($data);
    }

    public function nextQuestion() {
        $totalQuestions = session('totalQuestions');
        $currentQuestion = session('currentQuestion');
        //dd($totalQuestions, $currentQuestion);
        if($currentQuestion >= $totalQuestions) {
            return redirect()->route('show_results');
        }

        $currentQuestion++;

        session([
            'currentQuestion' => $currentQuestion
        ]);

        return redirect()->route('game');
    }

    public function showResults() {
        $totalQuestions = session('totalQuestions');
        $correctAnswers = session('correctAnswers');
        $wrongAnswers = session('wrongAnswers');
            $scoreFinal = ($correctAnswers / $totalQuestions) * 100;

        $data = [
            'totalQuestions' => $totalQuestions,
            'correctAnswers' => $correctAnswers,
            'wrongAnswers' => $wrongAnswers,
            'scoreFinal' => $scoreFinal,
        ];

        return view('final_results')->with($data);
    }
}
