<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderCreationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderImportController extends Controller
{
    public function __construct(private OrderCreationService $orderCreationService)
    {
    }

    public function index(): View
    {
        return view('orders.import', [
            'recentOrders' => Order::latest()->take(10)->get(),
            'importSummary' => session('import_summary'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('csv_file')->getRealPath();

        if (! $path) {
            return redirect()
                ->route('orders.import.index')
                ->withErrors(['csv_file' => 'Unable to read the uploaded CSV file.']);
        }

        $rows = $this->readCsvRows($path);

        if ($rows === []) {
            return redirect()
                ->route('orders.import.index')
                ->withErrors(['csv_file' => 'The uploaded CSV file is empty.']);
        }

        $header = array_map([$this, 'normalizeHeader'], $rows[0]);
        $dataRows = array_slice($rows, 1);

        if ($dataRows === []) {
            return redirect()
                ->route('orders.import.index')
                ->withErrors(['csv_file' => 'The uploaded CSV file only contains a header row.']);
        }

        $summary = [
            'processed' => 0,
            'created' => 0,
            'duplicates' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($dataRows as $index => $row) {
            $summary['processed']++;
            $payload = $this->repairPayload(
                $this->mapAssociativeRow($header, $row),
                $row
            );

            try {
                $validated = $this->validateRow($payload);
                $result = $this->orderCreationService->create($validated);

                if ($result['status'] === 'created') {
                    $summary['created']++;
                } elseif ($result['status'] === 'duplicate') {
                    $summary['duplicates']++;
                }
            } catch (ValidationException $exception) {
                $summary['failed']++;
                $line = $index + 2;
                $summary['errors'][] = 'Row ' . $line . ': ' . collect($exception->errors())->flatten()->first();
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $line = $index + 2;
                $summary['errors'][] = 'Row ' . $line . ': ' . $exception->getMessage();
            }
        }

        return redirect()
            ->route('orders.import.index')
            ->with('success', 'Order import completed.')
            ->with('import_summary', $summary);
    }

    private function validateRow(array $payload): array
    {
        return validator($payload, [
            'customerId' => ['nullable', 'string'],
            'custId' => ['nullable', 'string'],
            'orderId' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric'],
            'orderStatus' => ['required', 'integer'],
            'orderDate' => ['required', 'date'],
            'description' => ['nullable', 'string'],
        ])->after(function ($validator) use ($payload) {
            if (empty($payload['customerId']) && empty($payload['custId'])) {
                $validator->errors()->add('customerId', 'The customer ID field is required.');
            }
        })->validate();
    }

    private function normalizeHeader(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        return strtolower(preg_replace('/[^a-z0-9]/', '', trim($value)));
    }

    private function mapAssociativeRow(array $header, array $row): array
    {
        $mapped = [];

        foreach ($header as $index => $key) {
            $mapped[$key] = $this->rowValue($row, $index);
        }

        return [
            'customerId' => $mapped['customerid'] ?? $mapped['clientcustomerid'] ?? $mapped['initialcustomerid'] ?? null,
            'custId' => $mapped['custid'] ?? null,
            'orderId' => $mapped['orderid'] ?? $mapped['orderno'] ?? $mapped['ordernumber'] ?? $mapped['ordercode'] ?? null,
            'price' => $mapped['price'] ?? null,
            'orderStatus' => $mapped['orderstatus'] ?? $mapped['status'] ?? $mapped['orderstate'] ?? 1,
            'orderDate' => $this->normalizeDateValue($mapped['orderdate'] ?? $mapped['date'] ?? $mapped['createdat'] ?? null),
            'description' => $mapped['description'] ?? $mapped['orderdetails'] ?? $mapped['details'] ?? null,
        ];
    }

    private function repairPayload(array $payload, array $row): array
    {
        $normalizedRow = array_values(array_filter(
            array_map(fn ($value) => $this->sanitizeCell($value), $row),
            static fn ($value) => $value !== null && $value !== ''
        ));

        if (empty($payload['customerId']) && isset($normalizedRow[0])) {
            $payload['customerId'] = $normalizedRow[0];
        }

        if (empty($payload['orderId']) && isset($normalizedRow[1])) {
            $payload['orderId'] = $normalizedRow[1];
        }

        if (empty($payload['price']) && isset($normalizedRow[2])) {
            $payload['price'] = $normalizedRow[2];
        }

        if ((empty($payload['orderStatus']) || ! is_numeric($payload['orderStatus'])) && isset($normalizedRow[3])) {
            $payload['orderStatus'] = $normalizedRow[3];
        }

        if (empty($payload['orderDate']) && isset($normalizedRow[4])) {
            $payload['orderDate'] = $this->normalizeDateValue($normalizedRow[4]);
        }

        if (empty($payload['description']) && isset($normalizedRow[5])) {
            $payload['description'] = $normalizedRow[5];
        }

        return $payload;
    }

    private function rowValue(array $row, int $index, mixed $default = null): mixed
    {
        return isset($row[$index]) ? $this->sanitizeCell($row[$index]) : $default;
    }

    private function readCsvRows(string $path): array
    {
        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            return [];
        }

        $contents = $this->normalizeCsvEncoding($contents);
        $delimiter = $this->detectDelimiter($contents);
        $lines = preg_split("/\r\n|\n|\r/", $contents) ?: [];
        $rows = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line, $delimiter);
            $rows[] = array_map(fn ($value) => trim((string) $value), $row);
        }

        return $rows;
    }

    private function normalizeCsvEncoding(string $contents): string
    {
        if (str_starts_with($contents, "\xFF\xFE")) {
            return mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16LE');
        }

        if (str_starts_with($contents, "\xFE\xFF")) {
            return mb_convert_encoding(substr($contents, 2), 'UTF-8', 'UTF-16BE');
        }

        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            return substr($contents, 3);
        }

        if (! mb_check_encoding($contents, 'UTF-8')) {
            return mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');
        }

        return $contents;
    }

    private function detectDelimiter(string $contents): string
    {
        $firstLine = strtok($contents, "\r\n") ?: '';
        $delimiters = [',', ';', "\t"];
        $bestDelimiter = ',';
        $bestCount = -1;

        foreach ($delimiters as $delimiter) {
            $count = count(str_getcsv($firstLine, $delimiter));

            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $delimiter;
            }
        }

        return $bestDelimiter;
    }

    private function normalizeDateValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $formats = ['Y-m-d', 'd-m-Y', 'm-d-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d', 'd.m.Y', 'm.d.Y'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($value));

            if ($date && $date->format($format) === trim($value)) {
                return $date->format('Y-m-d H:i:s');
            }
        }

        $timestamp = strtotime($value);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private function sanitizeCell($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);

        return trim($value);
    }
}
