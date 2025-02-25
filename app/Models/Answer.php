<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Answer extends Model {

    use HasFactory;

    protected $primaryKey = 'AnswerID';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['AnswerID', 'QuestionID', 'answer_text', 'explanation'];

    protected static function boot() {
        parent::boot();
        static::creating(function ($answer) {
            $lastID = self::orderBy('AnswerID', 'desc')->first();
            $nextID = $lastID ? 'A' . str_pad(substr($lastID->AnswerID, 1) + 1, 5, '0', STR_PAD_LEFT) : 'A00001';
            $answer->AnswerID = $nextID;
        });
    }

    public function question() {
        return $this->belongsTo(Question::class, 'QuestionID');
    }
}
