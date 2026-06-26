<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\View\View;

class StudentController extends Controller
{
    private const EXPORT_FILE = 'students.csv';
    private const XLSX_MAIN_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const OLE_END_OF_CHAIN = 0xFFFFFFFE;
    private const OLE_FREE_SECTOR = 0xFFFFFFFF;

    public function index(Request $request): View
    {
        $allStudents = Student::all()->toArray();
        $filteredData = $this->filteredStudentData($request, $allStudents);

        return view('students.index', [
            'students' => $filteredData['students'],
            'search' => $filteredData['search'],
            'searchType' => $filteredData['searchType'],
            'sortType' => $filteredData['sortType'],
            'stats' => $this->studentStats($allStudents),
            'analysis' => $this->studentAnalysis($allStudents),
            'complexities' => $this->complexities(),
        ]);
    }

    public function create(): View
    {
        return view('students.create', [
            'nextStudentId' => $this->nextStudentId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $data = $this->validatedData($request, null, false);
            Student::create($this->recordFromArray($data)->toArray() + ['major' => $data['major'] ?? null]);

            return redirect()->route('students.index')
                ->with('success', 'Student data added successfully.')
                ->with('alert_type', 'add');
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function edit(Student $student): View
    {
        return view('students.edit', compact('student'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        try {
            $data = $this->validatedData($request, $student->id);
            $student->update($this->recordFromArray($data)->toArray() + ['major' => $data['major'] ?? null]);

            return redirect()->route('students.index')
                ->with('success', 'Student data updated successfully.')
                ->with('alert_type', 'edit');
        } catch (\Throwable $exception) {
            return back()->withInput()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function destroy(Student $student): RedirectResponse
    {
        try {
            $student->delete();

            return redirect()->route('students.index')
                ->with('success', 'Student data deleted successfully.')
                ->with('alert_type', 'delete');
        } catch (\Throwable $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function export(Request $request): BinaryFileResponse|RedirectResponse
    {
        try {
            $filteredData = $this->filteredStudentData($request, Student::all()->toArray());
            $stream = fopen('php://temp', 'w+b');
            if ($stream === false) {
                throw new \RuntimeException('Unable to prepare the CSV export.');
            }

            try {
                fputcsv($stream, ['student_id', 'name', 'email', 'gpa', 'major'], ',', '"', '');

                foreach ($filteredData['students'] as $student) {
                    fputcsv($stream, [
                        $student['student_id'],
                        $student['name'],
                        $student['email'],
                        number_format((float) $student['gpa'], 2, '.', ''),
                        $student['major'] ?? '',
                    ], ',', '"', '');
                }

                rewind($stream);
                $csv = stream_get_contents($stream);
            } finally {
                fclose($stream);
            }

            if ($csv === false) {
                throw new \RuntimeException('Unable to generate the CSV export.');
            }

            if (! Storage::disk('local')->put(self::EXPORT_FILE, $csv)) {
                throw new \RuntimeException('Unable to save the student CSV file.');
            }

            return response()->download(
                Storage::disk('local')->path(self::EXPORT_FILE),
                self::EXPORT_FILE,
                ['Content-Type' => 'text/csv; charset=UTF-8']
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    public function upload(Request $request): RedirectResponse
    {
        try {
            $request->validate([
                'student_file' => ['required', 'file', 'max:5120'],
            ]);

            $file = $request->file('student_file');
            if (! $file instanceof UploadedFile) {
                throw new \InvalidArgumentException('Please choose a CSV, XLS, or XLSX file.');
            }

            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, ['csv', 'xls', 'xlsx'], true)) {
                throw new \InvalidArgumentException('Only CSV, XLS, and XLSX files are supported.');
            }

            $records = $this->recordsFromUploadedFile($file, $extension);
            if ($records === []) {
                throw new \InvalidArgumentException('The uploaded file does not contain student rows.');
            }

            $created = 0;
            $skipped = 0;

            foreach ($records as $record) {
                $data = $record['data'];
                if (trim((string) ($data['student_id'] ?? '')) === '') {
                    $data['student_id'] = $this->nextStudentId();
                }

                try {
                    $this->validateRecord($data);
                } catch (\Throwable $exception) {
                    throw new \InvalidArgumentException('Row '.$record['row'].': '.$exception->getMessage(), 0, $exception);
                }

                if ($this->studentAlreadyExists($data)) {
                    $skipped++;
                    continue;
                }

                Student::create($this->recordFromArray($data)->toArray() + ['major' => $data['major'] ?: null]);
                $created++;
            }

            $message = 'Imported '.$created.' new student record(s) from '.strtoupper($extension).' file.';
            if ($skipped > 0) {
                $message .= ' Skipped '.$skipped.' existing record(s).';
            }

            return redirect()->route('students.index')
                ->with('success', $message)
                ->with('alert_type', 'add');
        } catch (\Throwable $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    private function validatedData(Request $request, ?int $ignoreId = null, bool $requiresStudentId = true): array
    {
        $studentUnique = 'unique:students,student_id'.($ignoreId ? ','.$ignoreId : '');
        $emailUnique = 'unique:students,email'.($ignoreId ? ','.$ignoreId : '');

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'regex:/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', $emailUnique],
            'gpa' => ['required', 'numeric', 'between:0,4'],
            'major' => ['nullable', 'string', 'max:100'],
        ];

        if ($requiresStudentId) {
            $rules['student_id'] = ['required', 'regex:/^S[0-9]{3,6}$/', $studentUnique];
        }

        $data = $request->validate($rules);
        if (! $requiresStudentId) {
            $data['student_id'] = $this->nextStudentId();
        }

        return $data;
    }

    private function recordsFromUploadedFile(UploadedFile $file, string $extension): array
    {
        $path = $file->getRealPath();
        if ($path === false) {
            throw new \RuntimeException('Unable to read the uploaded file.');
        }

        $rows = match ($extension) {
            'csv' => $this->rowsFromCsv($path),
            'xls' => $this->rowsFromXls($path),
            'xlsx' => $this->rowsFromXlsx($path),
            default => throw new \InvalidArgumentException('Unsupported file format.'),
        };

        return $this->studentRecordsFromRows($rows);
    }

    private function rowsFromCsv(string $path): array
    {
        return $this->rowsFromDelimitedText($path, 'CSV');
    }

    private function rowsFromDelimitedText(string $path, string $fileType): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new \RuntimeException('Unable to open the '.$fileType.' file.');
        }

        $rows = [];
        $lineNumber = 1;
        $delimiter = $this->detectDelimiter($path);

        try {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                $rows[] = [
                    'number' => $lineNumber,
                    'values' => $this->cleanRow($row),
                ];
                $lineNumber++;
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function detectDelimiter(string $path): string
    {
        $sample = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $sample = array_slice($sample, 0, 5);
        $delimiters = [',' => 0, ';' => 0, "\t" => 0, '|' => 0];

        foreach ($sample as $line) {
            foreach (array_keys($delimiters) as $delimiter) {
                $columns = str_getcsv($line, $delimiter);
                $delimiters[$delimiter] += max(0, count($columns) - 1);
            }
        }

        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }

    private function rowsFromXls(string $path): array
    {
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Unable to read the XLS file.');
        }

        if (str_starts_with($content, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1")) {
            return $this->rowsFromBinaryXls($content);
        }

        if (stripos($content, '<table') !== false || stripos($content, '<html') !== false) {
            return $this->rowsFromHtmlTable($content);
        }

        return $this->rowsFromDelimitedText($path, 'XLS');
    }

    private function rowsFromHtmlTable(string $html): array
    {
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument();
        $loaded = $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new \RuntimeException('Unable to read the XLS HTML table.');
        }

        $xpath = new \DOMXPath($document);
        $rows = [];
        $rowNumber = 1;

        foreach ($xpath->query('//tr') ?: [] as $tr) {
            $values = [];
            foreach ($xpath->query('./th|./td', $tr) ?: [] as $cell) {
                $values[] = trim($cell->textContent);
            }

            if ($values !== []) {
                $rows[] = [
                    'number' => $rowNumber,
                    'values' => $this->cleanRow($values),
                ];
            }
            $rowNumber++;
        }

        return $rows;
    }

    private function rowsFromBinaryXls(string $binary): array
    {
        $workbook = $this->workbookStreamFromOle($binary);

        return $this->rowsFromBiffWorkbook($workbook);
    }

    private function workbookStreamFromOle(string $binary): string
    {
        $sectorSize = 1 << $this->readUInt16($binary, 30);
        $fatSectorCount = $this->readUInt32($binary, 44);
        $firstDirectorySector = $this->readUInt32($binary, 48);
        $difat = [];

        for ($offset = 76; $offset < 512 && count($difat) < $fatSectorCount; $offset += 4) {
            $sector = $this->readUInt32($binary, $offset);
            if ($sector !== self::OLE_FREE_SECTOR) {
                $difat[] = $sector;
            }
        }

        $fat = [];
        foreach ($difat as $sector) {
            $sectorData = $this->oleSector($binary, $sector, $sectorSize);
            for ($offset = 0; $offset < strlen($sectorData); $offset += 4) {
                $fat[] = $this->readUInt32($sectorData, $offset);
            }
        }

        $directory = $this->oleChain($binary, $firstDirectorySector, $fat, $sectorSize);
        for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
            $entry = substr($directory, $offset, 128);
            $nameLength = $this->readUInt16($entry, 64);
            if ($nameLength < 2) {
                continue;
            }

            $name = iconv('UTF-16LE', 'UTF-8//IGNORE', substr($entry, 0, $nameLength - 2));
            if (! in_array($name, ['Workbook', 'Book'], true)) {
                continue;
            }

            $startSector = $this->readUInt32($entry, 116);
            $streamSize = $this->readUInt32($entry, 120);

            return substr($this->oleChain($binary, $startSector, $fat, $sectorSize), 0, $streamSize);
        }

        throw new \RuntimeException('Unable to find a readable worksheet in the XLS file.');
    }

    private function oleSector(string $binary, int $sector, int $sectorSize): string
    {
        return substr($binary, ($sector + 1) * $sectorSize, $sectorSize);
    }

    private function oleChain(string $binary, int $startSector, array $fat, int $sectorSize): string
    {
        $data = '';
        $sector = $startSector;
        $seen = [];

        while ($sector !== self::OLE_END_OF_CHAIN && $sector !== self::OLE_FREE_SECTOR && isset($fat[$sector]) && ! isset($seen[$sector])) {
            $seen[$sector] = true;
            $data .= $this->oleSector($binary, $sector, $sectorSize);
            $sector = $fat[$sector];
        }

        return $data;
    }

    private function rowsFromBiffWorkbook(string $workbook): array
    {
        $rows = [];
        $sharedStrings = [];
        $offset = 0;
        $length = strlen($workbook);

        while ($offset + 4 <= $length) {
            $recordType = $this->readUInt16($workbook, $offset);
            $recordLength = $this->readUInt16($workbook, $offset + 2);
            $recordData = substr($workbook, $offset + 4, $recordLength);
            $offset += 4 + $recordLength;

            if ($recordType === 0x00FC) {
                while ($offset + 4 <= $length && $this->readUInt16($workbook, $offset) === 0x003C) {
                    $continueLength = $this->readUInt16($workbook, $offset + 2);
                    $recordData .= substr($workbook, $offset + 4, $continueLength);
                    $offset += 4 + $continueLength;
                }
                $sharedStrings = $this->sharedStringsFromBiff($recordData);
                continue;
            }

            if ($recordType === 0x00FD && strlen($recordData) >= 10) {
                $row = $this->readUInt16($recordData, 0);
                $column = $this->readUInt16($recordData, 2);
                $index = $this->readUInt32($recordData, 6);
                $rows[$row][$column] = $sharedStrings[$index] ?? '';
                continue;
            }

            if ($recordType === 0x0204 && strlen($recordData) >= 8) {
                $row = $this->readUInt16($recordData, 0);
                $column = $this->readUInt16($recordData, 2);
                $stringOffset = 6;
                $rows[$row][$column] = $this->readBiffString($recordData, $stringOffset);
                continue;
            }

            if ($recordType === 0x0203 && strlen($recordData) >= 14) {
                $row = $this->readUInt16($recordData, 0);
                $column = $this->readUInt16($recordData, 2);
                $rows[$row][$column] = $this->formatSpreadsheetNumber(unpack('d', substr($recordData, 6, 8))[1]);
                continue;
            }

            if ($recordType === 0x027E && strlen($recordData) >= 10) {
                $row = $this->readUInt16($recordData, 0);
                $column = $this->readUInt16($recordData, 2);
                $rows[$row][$column] = $this->formatSpreadsheetNumber($this->decodeRk($this->readUInt32($recordData, 6)));
                continue;
            }

            if ($recordType === 0x00BD && strlen($recordData) >= 10) {
                $row = $this->readUInt16($recordData, 0);
                $firstColumn = $this->readUInt16($recordData, 2);
                $cellCount = intdiv(strlen($recordData) - 6, 6);

                for ($i = 0; $i < $cellCount; $i++) {
                    $cellOffset = 4 + ($i * 6);
                    if ($cellOffset + 6 > strlen($recordData) - 2) {
                        break;
                    }
                    $rows[$row][$firstColumn + $i] = $this->formatSpreadsheetNumber($this->decodeRk($this->readUInt32($recordData, $cellOffset + 2)));
                }
            }
        }

        return $this->orderedRows($rows);
    }

    private function sharedStringsFromBiff(string $data): array
    {
        if (strlen($data) < 8) {
            return [];
        }

        $offset = 8;
        $uniqueCount = $this->readUInt32($data, 4);
        $strings = [];

        for ($i = 0; $i < $uniqueCount && $offset < strlen($data); $i++) {
            $strings[] = $this->readBiffString($data, $offset);
        }

        return $strings;
    }

    private function readBiffString(string $data, int &$offset): string
    {
        if ($offset + 3 > strlen($data)) {
            return '';
        }

        $characterCount = $this->readUInt16($data, $offset);
        $offset += 2;
        $flags = ord($data[$offset]);
        $offset++;

        $isWide = (bool) ($flags & 0x01);
        $hasRichText = (bool) ($flags & 0x08);
        $hasExtended = (bool) ($flags & 0x04);
        $richTextRuns = 0;
        $extendedSize = 0;

        if ($hasRichText && $offset + 2 <= strlen($data)) {
            $richTextRuns = $this->readUInt16($data, $offset);
            $offset += 2;
        }

        if ($hasExtended && $offset + 4 <= strlen($data)) {
            $extendedSize = $this->readUInt32($data, $offset);
            $offset += 4;
        }

        $byteLength = $characterCount * ($isWide ? 2 : 1);
        $raw = substr($data, $offset, $byteLength);
        $offset += $byteLength + ($richTextRuns * 4) + $extendedSize;

        if ($isWide) {
            return trim((string) iconv('UTF-16LE', 'UTF-8//IGNORE', $raw));
        }

        return trim(mb_convert_encoding($raw, 'UTF-8', 'ISO-8859-1'));
    }

    private function decodeRk(int $rk): float|int
    {
        $divideBy100 = (bool) ($rk & 0x01);
        $isInteger = (bool) ($rk & 0x02);

        if ($isInteger) {
            $value = $rk >> 2;
        } else {
            $value = unpack('d', pack('V2', 0, $rk & 0xFFFFFFFC))[1];
        }

        return $divideBy100 ? $value / 100 : $value;
    }

    private function formatSpreadsheetNumber(float|int $value): string
    {
        if (abs($value - round($value)) < 0.0000001) {
            return (string) (int) round($value);
        }

        return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
    }

    private function orderedRows(array $rows): array
    {
        ksort($rows);
        $orderedRows = [];

        foreach ($rows as $rowNumber => $cells) {
            ksort($cells);
            $values = [];
            $lastColumn = max(array_keys($cells));
            for ($column = 0; $column <= $lastColumn; $column++) {
                $values[] = $cells[$column] ?? '';
            }

            $orderedRows[] = [
                'number' => $rowNumber + 1,
                'values' => $this->cleanRow($values),
            ];
        }

        return $orderedRows;
    }

    private function readUInt16(string $data, int $offset): int
    {
        return unpack('v', substr($data, $offset, 2))[1];
    }

    private function readUInt32(string $data, int $offset): int
    {
        return unpack('V', substr($data, $offset, 4))[1];
    }

    private function rowsFromXlsx(string $path): array
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('The PHP Zip extension is required to read XLSX files.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Unable to open the XLSX file.');
        }

        try {
            $sharedStrings = $this->sharedStringsFromXlsx($zip);
            $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
            if ($sheetXml === false) {
                throw new \RuntimeException('The XLSX file does not contain a readable first worksheet.');
            }

            $worksheet = $this->xmlFromString($sheetXml, 'Unable to read the XLSX worksheet.');
            $worksheet->registerXPathNamespace('main', self::XLSX_MAIN_NAMESPACE);
            $rows = [];

            foreach ($worksheet->xpath('//main:sheetData/main:row') ?: [] as $rowXml) {
                $rowXml->registerXPathNamespace('main', self::XLSX_MAIN_NAMESPACE);
                $rowNumber = (int) ($rowXml['r'] ?? count($rows) + 1);
                $values = [];

                foreach ($rowXml->xpath('main:c') ?: [] as $cellXml) {
                    $cellReference = (string) ($cellXml['r'] ?? '');
                    $columnIndex = $cellReference !== ''
                        ? $this->columnIndexFromCellReference($cellReference)
                        : count($values);

                    $values[$columnIndex] = $this->xlsxCellValue($cellXml, $sharedStrings);
                }

                if ($values === []) {
                    continue;
                }

                ksort($values);
                $orderedValues = [];
                $lastColumn = max(array_keys($values));
                for ($column = 0; $column <= $lastColumn; $column++) {
                    $orderedValues[] = $values[$column] ?? '';
                }

                $rows[] = [
                    'number' => $rowNumber,
                    'values' => $this->cleanRow($orderedValues),
                ];
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    private function sharedStringsFromXlsx(\ZipArchive $zip): array
    {
        $sharedStringXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringXml === false) {
            return [];
        }

        $sharedStrings = $this->xmlFromString($sharedStringXml, 'Unable to read XLSX shared strings.');
        $sharedStrings->registerXPathNamespace('main', self::XLSX_MAIN_NAMESPACE);
        $values = [];

        foreach ($sharedStrings->xpath('//main:si') ?: [] as $item) {
            $item->registerXPathNamespace('main', self::XLSX_MAIN_NAMESPACE);
            $textNodes = $item->xpath('.//main:t') ?: [];
            $text = '';

            foreach ($textNodes as $textNode) {
                $text .= (string) $textNode;
            }

            $values[] = $text;
        }

        return $values;
    }

    private function xlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings): string
    {
        $cell->registerXPathNamespace('main', self::XLSX_MAIN_NAMESPACE);
        $type = (string) ($cell['t'] ?? '');
        $valueNodes = $cell->xpath('main:v') ?: [];
        $value = $valueNodes === [] ? '' : (string) $valueNodes[0];

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'inlineStr') {
            $textNodes = $cell->xpath('main:is//main:t') ?: [];
            $text = '';

            foreach ($textNodes as $textNode) {
                $text .= (string) $textNode;
            }

            return $text;
        }

        return $value;
    }

    private function columnIndexFromCellReference(string $cellReference): int
    {
        if (! preg_match('/^([A-Z]+)/i', $cellReference, $matches)) {
            return 0;
        }

        $letters = strtoupper($matches[1]);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return $index - 1;
    }

    private function xmlFromString(string $xml, string $errorMessage): \SimpleXMLElement
    {
        $previous = libxml_use_internal_errors(true);
        $element = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $element instanceof \SimpleXMLElement) {
            throw new \RuntimeException($errorMessage);
        }

        return $element;
    }

    private function studentRecordsFromRows(array $rows): array
    {
        $rows = array_values(array_filter($rows, fn (array $row) => ! $this->isBlankRow($row['values'])));
        if ($rows === []) {
            return [];
        }

        $headerMap = $this->headerMap($rows[0]['values']);
        $records = [];

        foreach ($rows as $position => $row) {
            if ($position === 0 && $headerMap !== []) {
                continue;
            }

            $records[] = [
                'row' => $row['number'],
                'data' => $headerMap !== []
                    ? $this->recordFromHeaderRow($row['values'], $headerMap)
                    : $this->recordFromOrderedRow($row['values']),
            ];
        }

        return $records;
    }

    private function headerMap(array $row): array
    {
        $aliases = [
            'studentid' => 'student_id',
            'studentnumber' => 'student_id',
            'id' => 'student_id',
            'nim' => 'student_id',
            'name' => 'name',
            'studentname' => 'name',
            'fullname' => 'name',
            'email' => 'email',
            'emailaddress' => 'email',
            'gpa' => 'gpa',
            'gradepointaverage' => 'gpa',
            'major' => 'major',
            'program' => 'major',
            'studyprogram' => 'major',
            'department' => 'major',
        ];

        $map = [];
        foreach ($row as $index => $heading) {
            $normalized = $this->normalizeHeader((string) $heading);
            if (isset($aliases[$normalized])) {
                $map[$aliases[$normalized]] = $index;
            }
        }

        if ($map === []) {
            return [];
        }

        foreach (['name', 'email', 'gpa'] as $requiredField) {
            if (! array_key_exists($requiredField, $map)) {
                throw new \InvalidArgumentException('CSV/XLS/XLSX header must include name, email, and gpa columns. student_id is optional because it can be generated automatically.');
            }
        }

        return $map;
    }

    private function normalizeHeader(string $header): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower($header)) ?? '';
    }

    private function recordFromHeaderRow(array $values, array $headerMap): array
    {
        return [
            'student_id' => $this->valueAt($values, $headerMap['student_id'] ?? null) ?? '',
            'name' => $this->valueAt($values, $headerMap['name']) ?? '',
            'email' => $this->valueAt($values, $headerMap['email']) ?? '',
            'gpa' => $this->valueAt($values, $headerMap['gpa']) ?? '',
            'major' => $this->valueAt($values, $headerMap['major'] ?? null),
        ];
    }

    private function recordFromOrderedRow(array $values): array
    {
        if (preg_match('/^S[0-9]{3,6}$/', (string) ($values[0] ?? ''))) {
            [$studentId, $name, $email, $gpa, $major] = array_pad($values, 5, null);
        } else {
            [$name, $email, $gpa, $major] = array_pad($values, 4, null);
            $studentId = '';
        }

        return [
            'student_id' => $studentId ?? '',
            'name' => $name ?? '',
            'email' => $email ?? '',
            'gpa' => $gpa ?? '',
            'major' => $major,
        ];
    }

    private function valueAt(array $values, ?int $index): ?string
    {
        if ($index === null || ! array_key_exists($index, $values)) {
            return null;
        }

        return trim((string) $values[$index]);
    }

    private function cleanRow(array $row): array
    {
        return array_map(function ($value): string {
            $value = trim((string) $value);

            return preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
        }, $row);
    }

    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function studentAlreadyExists(array $data): bool
    {
        return Student::where('student_id', $data['student_id'])
            ->orWhere('email', $data['email'])
            ->exists();
    }

    private function nextStudentId(): string
    {
        $highestNumber = Student::pluck('student_id')
            ->map(function (string $studentId): int {
                return preg_match('/^S([0-9]{3,6})$/', $studentId, $matches)
                    ? (int) $matches[1]
                    : 0;
            })
            ->max() ?? 0;

        $nextNumber = $highestNumber + 1;
        if ($nextNumber > 999999) {
            throw new \RuntimeException('Unable to generate a new student ID because the ID limit has been reached.');
        }

        return 'S'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function studentStats(array $students): array
    {
        $total = count($students);
        $gpaTotal = array_sum(array_map(fn (array $student) => (float) $student['gpa'], $students));
        $majors = array_filter(array_map(fn (array $student) => $student['major'] ?? null, $students));

        return [
            'total' => $total,
            'average_gpa' => $total > 0 ? number_format($gpaTotal / $total, 2) : '0.00',
            'majors' => count(array_unique($majors)),
            'uploads' => 'CSV/XLS/XLSX',
        ];
    }

    private function studentAnalysis(array $students): array
    {
        $total = count($students);
        $gpas = array_map(fn (array $student): float => (float) $student['gpa'], $students);
        sort($gpas);

        $medianGpa = 0.0;
        if ($total > 0) {
            $middle = intdiv($total, 2);
            $medianGpa = $total % 2 === 0
                ? ($gpas[$middle - 1] + $gpas[$middle]) / 2
                : $gpas[$middle];
        }

        $averageGpa = $total > 0 ? array_sum($gpas) / $total : 0.0;
        $highestStudent = null;
        foreach ($students as $student) {
            if ($highestStudent === null || (float) $student['gpa'] > (float) $highestStudent['gpa']) {
                $highestStudent = $student;
            }
        }

        $distributionRanges = [
            ['label' => 'Excellent', 'range' => '3.50 - 4.00', 'min' => 3.5, 'max' => 4.01],
            ['label' => 'Strong', 'range' => '3.00 - 3.49', 'min' => 3.0, 'max' => 3.5],
            ['label' => 'Developing', 'range' => '2.50 - 2.99', 'min' => 2.5, 'max' => 3.0],
            ['label' => 'Needs attention', 'range' => 'Below 2.50', 'min' => 0.0, 'max' => 2.5],
        ];

        $gpaDistribution = array_map(function (array $range) use ($gpas, $total): array {
            $count = count(array_filter(
                $gpas,
                fn (float $gpa): bool => $gpa >= $range['min'] && $gpa < $range['max']
            ));

            return [
                'label' => $range['label'],
                'range' => $range['range'],
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }, $distributionRanges);

        $majorData = [];
        foreach ($students as $student) {
            $major = trim((string) ($student['major'] ?? '')) ?: 'Undeclared';
            if (! isset($majorData[$major])) {
                $majorData[$major] = ['count' => 0, 'gpa_total' => 0.0];
            }
            $majorData[$major]['count']++;
            $majorData[$major]['gpa_total'] += (float) $student['gpa'];
        }

        uasort($majorData, function (array $left, array $right): int {
            return $right['count'] <=> $left['count'];
        });

        $majorDistribution = [];
        foreach ($majorData as $major => $data) {
            $majorDistribution[] = [
                'major' => $major,
                'count' => $data['count'],
                'percentage' => $total > 0 ? round(($data['count'] / $total) * 100, 1) : 0,
                'average_gpa' => number_format($data['gpa_total'] / $data['count'], 2),
            ];
        }

        $successfulStudents = count(array_filter($gpas, fn (float $gpa): bool => $gpa >= 3.0));
        $atRiskStudents = count(array_filter($gpas, fn (float $gpa): bool => $gpa < 2.5));
        $aboveAverageStudents = count(array_filter($gpas, fn (float $gpa): bool => $gpa >= $averageGpa));

        return [
            'median_gpa' => number_format($medianGpa, 2),
            'highest_gpa' => $highestStudent ? number_format((float) $highestStudent['gpa'], 2) : '0.00',
            'highest_student' => $highestStudent['name'] ?? 'No student data',
            'success_rate' => $total > 0 ? round(($successfulStudents / $total) * 100, 1) : 0,
            'at_risk' => $atRiskStudents,
            'above_average' => $aboveAverageStudents,
            'gpa_distribution' => $gpaDistribution,
            'major_distribution' => $majorDistribution,
        ];
    }

    private function validateRecord(array $data): void
    {
        if (! preg_match('/^S[0-9]{3,6}$/', (string) ($data['student_id'] ?? ''))) {
            throw new \InvalidArgumentException('Invalid student ID in file.');
        }

        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new \InvalidArgumentException('Name cannot be empty in file.');
        }

        if (! preg_match('/^[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}$/', (string) ($data['email'] ?? ''))) {
            throw new \InvalidArgumentException('Invalid email in file.');
        }

        if (! is_numeric($data['gpa'] ?? null) || $data['gpa'] < 0 || $data['gpa'] > 4) {
            throw new \InvalidArgumentException('Invalid GPA in file.');
        }

        if (($data['major'] ?? null) !== null && strlen((string) $data['major']) > 100) {
            throw new \InvalidArgumentException('Major is too long in file.');
        }
    }

    private function recordFromArray(array $data): StudentRecord
    {
        return new StudentRecord(
            $data['student_id'],
            $data['name'],
            $data['email'],
            (float) $data['gpa']
        );
    }

    private function filteredStudentData(Request $request, array $allStudents): array
    {
        $students = $allStudents;
        $searchType = $request->string('search_type', 'sequential_name')->toString();
        $searchTerm = trim($request->string('search', '')->toString());
        $sortType = $request->string('sort', 'student_id_bubble')->toString();

        if ($searchTerm !== '') {
            $students = $this->searchStudents($students, $searchType, $searchTerm);
        }

        return [
            'students' => $this->sortStudents($students, $sortType),
            'search' => $searchTerm,
            'searchType' => $searchType,
            'sortType' => $sortType,
        ];
    }

    private function searchStudents(array $students, string $type, string $term): array
    {
        return match ($type) {
            'binary' => $this->binarySearchByStudentId($students, $term),
            'sequential_name' => $this->sequentialSearchByName($students, $term),
            default => $this->linearSearchByStudentId($students, $term),
        };
    }

    private function linearSearchByStudentId(array $students, string $studentId): array
    {
        foreach ($students as $student) {
            if (strcasecmp($student['student_id'], $studentId) === 0) {
                return [$student];
            }
        }

        return [];
    }

    private function sequentialSearchByName(array $students, string $name): array
    {
        $matches = [];
        foreach ($students as $student) {
            if (stripos($student['name'], $name) !== false) {
                $matches[] = $student;
            }
        }

        return $matches;
    }

    private function binarySearchByStudentId(array $students, string $studentId): array
    {
        usort(
            $students,
            fn (array $left, array $right): int => strcasecmp($left['student_id'], $right['student_id'])
        );
        $low = 0;
        $high = count($students) - 1;

        while ($low <= $high) {
            $mid = intdiv($low + $high, 2);
            $comparison = strcasecmp($students[$mid]['student_id'], $studentId);

            if ($comparison === 0) {
                return [$students[$mid]];
            }

            if ($comparison < 0) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        return [];
    }

    private function sortStudents(array $students, string $type): array
    {
        $comparator = match ($type) {
            'name_selection' => fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']),
            'gpa_insertion' => fn (array $left, array $right): int => (float) $right['gpa'] <=> (float) $left['gpa'],
            default => fn (array $left, array $right): int => strcasecmp($left['student_id'], $right['student_id']),
        };

        usort($students, $comparator);
        return $students;
    }

    private function complexities(): array
    {
        return [
            'Create / update / delete' => 'O(1) indexed record access',
            'Name or ID search' => 'O(n), or O(log n) after preparation',
            'Record sorting' => 'O(n log n)',
            'Import / export' => 'O(n)',
        ];
    }
}
