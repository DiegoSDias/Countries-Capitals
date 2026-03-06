<?php

namespace App\Services;

class PrepareQuizService{

    private $app_data;

    public function __construct() {
        $this->app_data = require(app_path('app_data.php')); 
    }

    public function prepareQuiz($totalQuestions) {
        $questions = [];
        $totalCountries = count($this->app_data);
        $indexes = range(0, $totalCountries - 1);
        shuffle($indexes);
        $indexes = array_slice($indexes, 0, $totalQuestions);

        $question_number = 1;
        foreach($indexes as $index) {
            $question['questionNumber'] = $question_number++;
            $question['country'] = $this->app_data[$index]['country'];
            $question['correctAnswer'] = $this->app_data[$index]['capital'];

            $other_capitals = array_column($this->app_data, 'capital');
            $other_capitals = array_diff($other_capitals, [$question['correctAnswer']]);

            shuffle($other_capitals);
            $question['wrongAnswer'] = array_slice($other_capitals, 0, 3);
            $question['correct'] = null;
            $questions[] = $question;
        }
        return $questions;
    }

}