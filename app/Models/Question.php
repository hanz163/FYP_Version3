<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model {

    use HasFactory;

    protected $primaryKey = 'QuestionID';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['QuestionID', 'DifficultyID', 'question_text'];

    public function difficulty() {
        return $this->belongsTo(Difficulty::class, 'DifficultyID');
    }

    public function answer() {
        return $this->hasOne(Answer::class, 'QuestionID');
    }

    protected static function boot() {
        parent::boot();
        static::creating(function ($question) {
            $lastID = self::orderBy('QuestionID', 'desc')->first();
            $nextID = $lastID ? 'Q' . str_pad(substr($lastID->QuestionID, 1) + 1, 5, '0', STR_PAD_LEFT) : 'Q00001';
            $question->QuestionID = $nextID;
        });
    }
}
