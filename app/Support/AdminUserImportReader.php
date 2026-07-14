<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use RuntimeException;

/**
 * Reads admin bulk user import files (CSV, XML, PDF text export).
 */
final class AdminUserImportReader
{
    public const MAX_KILOBYTES = 10240;

    /** @var list<string> */
    public const ALLOWED_EXTENSIONS = ['csv', 'txt', 'xml', 'pdf'];

    /**
     * @return list<array<string, string>>
     */
    public static function read(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => self::readCsv($file->getRealPath()),
            'xml' => self::readXml($file->getRealPath()),
            'pdf' => self::readPdf($file->getRealPath()),
            default => throw new RuntimeException('Unsupported import file type. Use CSV, XML, or PDF.'),
        };
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new RuntimeException('Could not open the import file.');
        }

        $header = array_map(static fn ($col) => self::normalizeHeader((string) $col), fgetcsv($handle) ?: []);
        if ($header === []) {
            fclose($handle);

            throw new RuntimeException('The import file is empty or has no header row.');
        }

        $rows = [];
        $lineNumber = 1;

        while (($data = fgetcsv($handle)) !== false) {
            $lineNumber++;

            if (count(array_filter($data, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }

            $row = array_combine($header, $data);
            if ($row === false) {
                fclose($handle);

                throw new RuntimeException("Invalid row at line {$lineNumber}.");
            }

            $rows[] = array_map(static fn ($v) => trim((string) $v), $row);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readXml(string $path): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = simplexml_load_file($path);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if ($document === false) {
            throw new RuntimeException('The XML file is not valid.');
        }

        $rows = [];

        $userNodes = $document->xpath('//user') ?: $document->xpath('//row') ?: $document->xpath('//record');
        if ($userNodes === false || $userNodes === []) {
            if ($document->getName() === 'users' || $document->getName() === 'import') {
                $userNodes = $document->children();
            } else {
                $userNodes = [$document];
            }
        }

        foreach ($userNodes as $node) {
            $row = [];
            foreach ($node->children() as $child) {
                $row[self::normalizeHeader($child->getName())] = trim((string) $child);
            }

            if ($row === []) {
                foreach ($node->attributes() as $name => $value) {
                    $row[self::normalizeHeader((string) $name)] = trim((string) $value);
                }
            }

            if (count(array_filter($row, static fn ($v) => $v !== '')) === 0) {
                continue;
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            throw new RuntimeException('No user records were found in the XML file.');
        }

        return $rows;
    }

    /**
     * @return list<array<string, string>>
     */
    private static function readPdf(string $path): array
    {
        $content = (string) file_get_contents($path);
        $text = self::extractPdfText($content);

        if (trim($text) === '') {
            throw new RuntimeException(
                'Could not read tabular text from the PDF. Export the list as CSV or XML, or use a PDF generated from a spreadsheet export.'
            );
        }

        return self::parseDelimitedText($text);
    }

    private static function extractPdfText(string $content): string
    {
        $chunks = [];

        if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $content, $matches)) {
            foreach ($matches[1] as $stream) {
                $decoded = @gzuncompress($stream);
                if ($decoded === false) {
                    $decoded = @gzuncompress(substr($stream, 2));
                }

                if ($decoded !== false) {
                    $chunks[] = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', ' ', $decoded) ?? '';
                }
            }
        }

        if ($chunks === []) {
            $chunks[] = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', ' ', $content) ?? '';
        }

        return trim(implode("\n", $chunks));
    }

    /**
     * @return list<array<string, string>>
     */
    private static function parseDelimitedText(string $text): array
    {
        $lines = preg_split('/\R+/', trim($text)) ?: [];
        $lines = array_values(array_filter($lines, static fn ($line) => trim((string) $line) !== ''));

        if ($lines === []) {
            return [];
        }

        $delimiter = str_contains((string) $lines[0], ',') ? ',' : (str_contains((string) $lines[0], "\t") ? "\t" : ',');
        $header = str_getcsv((string) array_shift($lines), $delimiter);
        $header = array_map(static fn ($col) => self::normalizeHeader((string) $col), $header);

        if ($header === [] || $header[0] === '') {
            throw new RuntimeException('Could not detect column headers in the PDF text.');
        }

        $rows = [];
        foreach ($lines as $index => $line) {
            $data = str_getcsv((string) $line, $delimiter);
            if (count(array_filter($data, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            if (count($data) < count($header)) {
                $data = array_pad($data, count($header), '');
            }

            $row = array_combine($header, $data);
            if ($row === false) {
                throw new RuntimeException('Invalid row at PDF text line '.($index + 2).'.');
            }

            $rows[] = array_map(static fn ($v) => trim((string) $v), $row);
        }

        return $rows;
    }

    private static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace([' ', '-'], '_', $header);

        return match ($header) {
            'reg_no', 'regno', 'reg_number', 'registration_no', 'registration_number', 'reg_number' => 'login_id',
            'staff_email', 'staffemail', 'staff_id', 'staff_number' => 'login_id',
            'sex' => 'gender',
            'year', 'study_year' => 'year_of_study',
            'phone', 'mobile', 'mobile_number', 'cell', 'cellphone' => 'phone_number',
            default => $header,
        };
    }
}
