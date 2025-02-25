<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Log;

class DocxService {

    public function extractQuestions($filePath) {
        try {
            \Log::info('Extracting questions from file: ' . $filePath);

            // Verify file exists
            if (!file_exists($filePath)) {
                \Log::error('File does not exist at resolved path: ' . $filePath);
                throw new \Exception('File does not exist.');
            }

            // Extract text from DOCX
            $text = $this->readDocxText($filePath);
            \Log::info('Extracted text from DOCX:', ['text' => $text]);

            if (empty(trim($text))) {
                \Log::error('Extracted text is empty. Possible file format issue.');
                return [];
            }

            return $this->parseQuestions($text);
        } catch (\Exception $e) {
            \Log::error('Error extracting questions: ' . $e->getMessage());
            throw $e;
        }
    }

    private function readDocxText($filePath) {
        $content = '';
        $zip = new \ZipArchive();

        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $xmlData = $zip->getFromIndex($index);
                $zip->close();

                // Strip XML tags and extract readable text
                $content = strip_tags(str_replace('</w:t>', ' ', $xmlData));
                $content = preg_replace('/<w:t[^>]*>/', '', $content);
            }
        } else {
            \Log::error('Failed to open .docx file.');
            throw new \Exception('Invalid DOCX file.');
        }

        return trim($content);
    }

    private function parseQuestions($text) {
        \Log::info('Raw extracted text:', ['text' => $text]);

        // Normalize spaces and remove extra line breaks
        $text = preg_replace("/\s+/", " ", $text);

        // Improved regex for both single-line and multi-line formats
        preg_match_all('/(\d+\..*?)\s*a\)(.*?)\s*b\)(.*?)\s*c\)(.*?)\s*d\)(.*?)\s*Answer:\s*([a-d])\)\s*(.*?)\s*Explanation:\s*(.*?)(?=\d+\.|\z)/s',
                $text, $matches, PREG_SET_ORDER);

        $questions = [];

        foreach ($matches as $match) {
            $questions[] = [
                'question' => trim($match[1]),
                'options' => [
                    'a' => trim($match[2]),
                    'b' => trim($match[3]),
                    'c' => trim($match[4]),
                    'd' => trim($match[5]),
                ],
                'correct_answer' => $match[6], // a, b, c, or d
                'answer_text' => trim($match[7]), // Full answer text
                'explanation' => trim($match[8]), // Capture explanation
            ];
        }

        if (empty($questions)) {
            \Log::error('No questions matched. Possible format issue.');
        } else {
            \Log::info('Successfully parsed questions:', ['count' => count($questions)]);
        }

        return $questions;
    }
}
