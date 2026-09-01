<?php
namespace App\Jobs;

use App\Models\Category;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\LeadSubject;
use App\Models\Status;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class ProcessLeadImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200;
    protected $filePath;
    protected $userId;
    protected $disk;

    public function __construct(string $filePath, int $userId, string $disk = 's3')
    {
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->disk = $disk;
    }

    public function handle()
    {
        if (!Storage::disk($this->disk)->exists($this->filePath)) {
            throw new \Exception("File not found on disk [{$this->disk}]: {$this->filePath}");
        }

        $extension = strtolower(pathinfo($this->filePath, PATHINFO_EXTENSION));

        $tempLocalPath = tempnam(sys_get_temp_dir(), 'import_') . '.' . $extension;
        $inputStream = Storage::disk($this->disk)->readStream($this->filePath);
        $outputStream = fopen($tempLocalPath, 'w+b');
        stream_copy_to_stream($inputStream, $outputStream);
        fclose($inputStream);
        fclose($outputStream);

        $categories = Category::pluck('id', 'name')->transform(fn($id, $name) => [mb_strtolower($name) => $id])->collapse();
        $leadSubjects = LeadSubject::pluck('id', 'name')->transform(fn($id, $name) => [mb_strtolower($name) => $id])->collapse();
        $leadSources = LeadSource::pluck('id', 'name')->transform(fn($id, $name) => [mb_strtolower($name) => $id])->collapse();
        $statuses = Status::pluck('id', 'name')->transform(fn($id, $name) => [mb_strtolower($name) => $id])->collapse();
        $users = User::pluck('id', 'name')->transform(fn($id, $name) => [mb_strtolower($name) => $id])->collapse();

        $generator = in_array($extension, ['csv', 'txt']) 
            ? $this->readCsv($tempLocalPath) 
            : $this->readXlsx($tempLocalPath);

        $headers = [];
        if ($generator->valid()) {
            $headers = array_map(fn($h) => strtolower(trim($h)), $generator->current());
            $generator->next();
        }

        $chunkBuffer = [];
        $rawRowsBuffer = []; 
        $chunkSize = 100;
        
        $totalRows = 0;
        $successCount = 0;
        $failedCount = 0;
        
        // ফেল করা রো জমানোর অ্যারে
        $failedRows = []; 
        $rowNumber = 1;

        while ($generator->valid()) {
            $row = $generator->current();
            $generator->next();
            $rowNumber++;

            if (empty(array_filter($row))) continue;

            $totalRows++;

            if (count($headers) !== count($row)) {
                $failedCount++;
                $rowWithReason = $row;
                $rowWithReason['fail_reason'] = "Column count mismatch";
                $failedRows[] = $rowWithReason;
                continue;
            }

            $rowData = array_combine($headers, array_map('trim', $row));

            if (empty($rowData['name'])) {
                $failedCount++;
                $rowData['fail_reason'] = "Name field is required";
                $failedRows[] = $rowData;
                continue;
            }
            // 🟢 ২. Phone, Email অথবা Username-এর যেকোনো একটি থাকার কন্ডিশন
            $phone    = trim($rowData['phone'] ?? '');
            $email    = trim($rowData['email'] ?? '');
            $username = trim($rowData['username'] ?? '');

            if (empty($phone) && empty($email) && empty($username)) {
                $failedCount++;
                $rowData['fail_reason'] = "At least one contact info (Phone, Email, or Username) is required";
                $failedRows[] = $rowData;
                continue;
            }

            $categoryId = $rowData['category_id'] ?? ($categories[mb_strtolower($rowData['category'] ?? '')] ?? null);
            $subjectId = $rowData['lead_subject_id'] ?? ($leadSubjects[mb_strtolower($rowData['subject'] ?? '')] ?? null);
            $sourceId = $rowData['lead_source_id'] ?? ($leadSources[mb_strtolower($rowData['source'] ?? '')] ?? null);
            $statusId = $rowData['status_id'] ?? ($statuses[mb_strtolower($rowData['status'] ?? '')] ?? null);
            $managerId = $rowData['manager_id'] ?? ($users[mb_strtolower($rowData['manager'] ?? '')] ?? null);
            $assignedToId = $rowData['assigned_to_id'] ?? ($users[mb_strtolower($rowData['assigned_to'] ?? '')] ?? $this->userId);

            $followUpDate = !empty($rowData['follow_up_date']) ? Carbon::parse($rowData['follow_up_date'])->toDateTimeString() : null;

            // Phone formatting helper
            $phone = $rowData['phone'] ?? null;
            if (!empty($phone)) {
                // যদি Excel সায়েন্টিফিক ফরম্যাটে (যেমন 1.751477386E9) রিটার্ন করে
                if (stripos($phone, 'e') !== false || is_numeric($phone)) {
                    $phone = sprintf('%.0f', (float)$phone);
                }
            }

            // Website clean helper (লিংকের আগে http/https না থাকলে ঠিক করা)
            $website = $rowData['website'] ?? $rowData['website_link'] ?? null;
            if (!empty($website)) {
                $website = trim($website);
            }

            $insertData = [
                'category_id'     => $categoryId,
                'lead_subject_id' => $subjectId,
                'lead_source_id'  => $sourceId,
                'status_id'       => $statusId,
                'manager_id'      => $managerId,
                'assigned_to_id'  => $assignedToId,
                'type'            => $rowData['type'] ?? 'lead',
                'name'            => $rowData['name'],
                'company_name'    => $rowData['company_name'] ?? null,
                'phone'           => $phone,
                'effective_phone' => $rowData['effective_phone'] ?? $phone,
                'email'           => $rowData['email'] ?? null,
                'username'        => $rowData['username'] ?? null,
                'description'     => $rowData['description'] ?? null,
                'priority'        => strtolower($rowData['priority'] ?? 'medium'),
                'expected_value'  => is_numeric($rowData['expected_value'] ?? null) ? $rowData['expected_value'] : null,
                'follow_up_date'  => $followUpDate,
                'address'         => !empty($rowData['address']) ? ['address' => $rowData['address']] : null,
                'website'         => $website,
                'created_by'      => $this->userId,
                'updated_by'      => $this->userId,
                'created_at'      => now(),
                'updated_at'      => now(),
            ];

            $chunkBuffer[] = $insertData;
            $rawRowsBuffer[] = $rowData; // আসল র ডাটা ট্র্যাকিংয়ের জন্য

            if (count($chunkBuffer) >= $chunkSize) {
                $res = $this->insertChunkSafely($chunkBuffer, $rawRowsBuffer);
                $successCount += $res['success'];
                $failedCount += count($res['failed_items']);
                $failedRows = array_merge($failedRows, $res['failed_items']);
                
                $chunkBuffer = [];
                $rawRowsBuffer = [];
            }
        }

        if (!empty($chunkBuffer)) {
            $res = $this->insertChunkSafely($chunkBuffer, $rawRowsBuffer);
            $successCount += $res['success'];
            $failedCount += count($res['failed_items']);
            $failedRows = array_merge($failedRows, $res['failed_items']);
        }

        // ফাইল ও টেম্প ক্লিনআপ
        if (file_exists($tempLocalPath)) @unlink($tempLocalPath);
        Storage::disk($this->disk)->delete($this->filePath);

        // ফেল করা ডাটা থাকলে CSV ফাইল জেনারেট করা
        $failedFileUrl = null;
        if (!empty($failedRows)) {
            $failedFileUrl = $this->generateFailedRowsCsv($headers, $failedRows);
        }

        return [
            'total'            => $totalRows,
            'success'          => $successCount,
            'failed'           => $failedCount,
            'failed_file_url'  => $failedFileUrl,
            'failed_samples'   => array_slice($failedRows, 0, 5) // স্ক্রিনে দ্রুত দেখানোর জন্য প্রথম ৫টি
        ];
    }
 
    private function insertChunkSafely(array $chunkBuffer, array $rawRowsBuffer): array
    {
        try {
            // ১. প্রথমে পুরো ব্যাচ দ্রুত ইনসার্ট করার চেষ্টা করবে (Fastest)
            Lead::insert($chunkBuffer);

            return [
                'success'      => count($chunkBuffer),
                'failed_items' => []
            ];

        } catch (\Exception $e) {
            // ২. যদি কোনো কারণে পুরো ব্যাচে এরর আসে, তবে ১টি ১টি করে পয়েন্ট আউট করবে
            $failedItems = [];
            $success = 0;

            foreach ($chunkBuffer as $index => $singleRow) {
                try {
                    Lead::create($singleRow);
                    $success++;
                } catch (\Exception $ex) {
                    $raw = $rawRowsBuffer[$index] ?? $singleRow;
                    $raw['fail_reason'] = $ex->getMessage();
                    $failedItems[] = $raw;
                }
            }

            return [
                'success'      => $success,
                'failed_items' => $failedItems
            ];
        }
    }

    private function generateFailedRowsCsv(array $headers, array $failedRows): string
    {
        if (!in_array('fail_reason', $headers)) {
            $headers[] = 'fail_reason';
        }

        $fileName = 'failed_imports/failed_leads_' . time() . '_' . rand(1000, 9999) . '.csv';
        
        $tempPath = tempnam(sys_get_temp_dir(), 'failed_');
        $handle = fopen($tempPath, 'w');

        // CSV-তে BOM যোগ যাতে বাংলায় ক্যারেক্টার না ভাঙে
        fputs($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);

        foreach ($failedRows as $row) {
            $csvRow = [];
            foreach ($headers as $header) {
                $csvRow[] = $row[$header] ?? '';
            }
            fputcsv($handle, $csvRow);
        }
        fclose($handle);

        // Storage-এ ফাইল সেভ
        Storage::disk($this->disk)->put($fileName, file_get_contents($tempPath));
        @unlink($tempPath);

        // স্টোরেজ ডাউনলোড লিঙ্ক রিটার্ন
        return Storage::disk($this->disk)->url($fileName);
    }

    private function readCsv($filePath)
    {
        $handle = fopen($filePath, 'r');
        $firstLine = fgets($handle);
        $firstLine = preg_replace('/\x{EF}\x{BB}\x{BF}/', '', $firstLine);
        yield str_getcsv($firstLine);

        while (($row = fgetcsv($handle)) !== false) {
            yield $row;
        }
        fclose($handle);
    }

    private function readXlsx($filePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) !== true) return;

        $sharedStrings = [];
        if (($index = $zip->locateName('xl/sharedStrings.xml')) !== false) {
            $xml = simplexml_load_string($zip->getFromIndex($index));
            foreach ($xml->si as $val) {
                // যদি হাইপারলিংক বা টেক্সটের ভেতরে একাধিক ট্যাগ (t / r) থাকে তা সঠিকভাবে রিড করা
                if (isset($val->r)) {
                    $text = '';
                    foreach ($val->r as $run) {
                        $text .= (string) $run->t;
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = (string) $val->t;
                }
            }
        }

        if (($sheetIndex = $zip->locateName('xl/worksheets/sheet1.xml')) !== false) {
            $sheetStream = $zip->getStream('xl/worksheets/sheet1.xml');
            $reader = new \XMLReader();
            $reader->xml(stream_get_contents($sheetStream));

            while ($reader->read()) {
                if ($reader->nodeType == \XMLReader::ELEMENT && $reader->name == 'row') {
                    $rowXml = new \SimpleXMLElement($reader->readOuterXml());
                    $rowCells = [];

                    foreach ($rowXml->c as $cell) {
                        $cellValue = (string) $cell->v;
                        $cellType = (string) $cell['t'];

                        if ($cellType === 's' && isset($sharedStrings[$cellValue])) {
                            $cellValue = $sharedStrings[$cellValue];
                        }
                        $rowCells[] = $cellValue;
                    }
                    yield $rowCells;
                }
            }
            $reader->close();
            fclose($sheetStream);
        }
        $zip->close();
    }
}