<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class ResumeParsingService
{
    public function parse(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        $content = match ($extension) {
            'pdf' => $this->parsePdf($file),
            'docx' => $this->parseDocx($file),
            default => throw new \InvalidArgumentException('Unsupported file format'),
        };

        return [
            'raw_content' => trim($content),
            'structured_data' => $this->extractStructuredData($content),
        ];
    }

    private function parsePdf(UploadedFile $file): string
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($file->getPathname());

        return $pdf->getText();
    }

    private function parseDocx(UploadedFile $file): string
    {
        $phpWord = IOFactory::load($file->getPathname());
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $text .= $this->extractElementText($element);
            }
        }

        return $text;
    }

    private function extractElementText($element): string
    {
        if (method_exists($element, 'getText')) {
            return $element->getText() . "\n";
        }

        if ($element instanceof TextRun) {
            $text = '';
            foreach ($element->getElements() as $child) {
                $text .= $this->extractElementText($child);
            }

            return $text;
        }

        if (method_exists($element, 'getElements')) {
            $text = '';
            foreach ($element->getElements() as $child) {
                $text .= $this->extractElementText($child);
            }

            return $text;
        }

        return '';
    }

    private function extractStructuredData(string $content): array
    {
        $commonSkills = ['PHP', 'Laravel', 'Python', 'JavaScript', 'React', 'Vue.js', 'SQL', 'MySQL', 'AWS', 'Docker', 'Git'];
        $skills = array_values(array_filter(
            $commonSkills,
            fn ($skill) => stripos($content, $skill) !== false
        ));

        return [
            'skills' => $skills,
            'experience' => [],
            'education' => [],
        ];
    }
}
