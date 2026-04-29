<?php

namespace App\Http\Controllers;

use App\Models\LoyaltyMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LoyaltyMemberController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only([
            'customerId',
            'firstName',
            'lastName',
            'company',
            'city',
            'country',
            'phone1',
            'phone2',
            'email',
            'subscriptionDate',
            'website',
        ]);

        $members = LoyaltyMember::query()
            ->when($filters['customerId'] ?? null, fn ($query, $value) => $query->where('customerId', 'like', '%' . $value . '%'))
            ->when($filters['firstName'] ?? null, fn ($query, $value) => $query->where('firstName', 'like', '%' . $value . '%'))
            ->when($filters['lastName'] ?? null, fn ($query, $value) => $query->where('lastName', 'like', '%' . $value . '%'))
            ->when($filters['company'] ?? null, fn ($query, $value) => $query->where('company', 'like', '%' . $value . '%'))
            ->when($filters['city'] ?? null, fn ($query, $value) => $query->where('city', 'like', '%' . $value . '%'))
            ->when($filters['country'] ?? null, fn ($query, $value) => $query->where('country', 'like', '%' . $value . '%'))
            ->when($filters['phone1'] ?? null, fn ($query, $value) => $query->where('phone1', 'like', '%' . $value . '%'))
            ->when($filters['phone2'] ?? null, fn ($query, $value) => $query->where('phone2', 'like', '%' . $value . '%'))
            ->when($filters['email'] ?? null, fn ($query, $value) => $query->where('email', 'like', '%' . $value . '%'))
            ->when($filters['subscriptionDate'] ?? null, fn ($query, $value) => $query->where('subscriptionDate', 'like', '%' . $value . '%'))
            ->when($filters['website'] ?? null, fn ($query, $value) => $query->where('website', 'like', '%' . $value . '%'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('loyalty-members.index', compact('members', 'filters'));
    }

    public function create(): View
    {
        return view('loyalty-members.create', [
            'member' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        LoyaltyMember::create($this->validatedData($request));

        return redirect()
            ->route('loyalty-members.index')
            ->with('success', 'Loyalty member created successfully.');
    }

    public function edit(LoyaltyMember $loyalty_member): View
    {
        return view('loyalty-members.edit', [
            'member' => $loyalty_member,
        ]);
    }

    public function update(Request $request, LoyaltyMember $loyalty_member): RedirectResponse
    {
        $loyalty_member->update($this->validatedData($request, $loyalty_member->id));

        return redirect()
            ->route('loyalty-members.index')
            ->with('success', 'Loyalty member updated successfully.');
    }

    public function destroy(LoyaltyMember $loyalty_member): RedirectResponse
    {
        $loyalty_member->delete();

        return redirect()
            ->route('loyalty-members.index')
            ->with('success', 'Loyalty member deleted successfully.');
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        $path = $request->file('csv_file')->getRealPath();

        if (! $path) {
            return redirect()
                ->route('loyalty-members.index')
                ->withErrors(['csv_file' => 'Unable to read the uploaded CSV file.']);
        }

        $rows = $this->readCsvRows($path);

        if ($rows === []) {
            return redirect()
                ->route('loyalty-members.index')
                ->withErrors(['csv_file' => 'The uploaded CSV file is empty.']);
        }

        $header = array_map([$this, 'normalizeHeader'], $rows[0]);
        $hasHeader = $this->looksLikeHeader($header) || $this->rowLooksLikeHeader($rows[0]);

        $dataRows = $hasHeader ? array_slice($rows, 1) : $rows;

        if ($dataRows === []) {
            return redirect()
                ->route('loyalty-members.index')
                ->withErrors(['csv_file' => 'The uploaded CSV file does not contain any member rows.']);
        }

        $imported = 0;

        try {
            DB::transaction(function () use ($dataRows, $header, $hasHeader, &$imported): void {
                foreach ($dataRows as $index => $row) {
                    $payload = $hasHeader
                        ? $this->mapAssociativeRow($header, $row)
                        : $this->mapPositionalRow($row);
                    $payload = $this->repairPayload($payload, $row);

                    $validator = validator($payload, $this->rules());

                    if ($validator->fails()) {
                        $line = $hasHeader ? $index + 2 : $index + 1;
                        $message = $validator->errors()->first();

                        throw new \RuntimeException("CSV row {$line}: {$message}");
                    }

                    LoyaltyMember::updateOrCreate(
                        ['customerId' => $payload['customerId']],
                        $validator->validated()
                    );

                    $imported++;
                }
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('loyalty-members.index')
                ->withErrors(['csv_file' => $exception->getMessage()]);
        }

        return redirect()
            ->route('loyalty-members.index')
            ->with('success', "{$imported} loyalty members imported successfully.");
    }

    private function validatedData(Request $request, ?int $memberId = null): array
    {
        return $request->validate($this->rules($memberId));
    }

    private function rules(?int $memberId = null): array
    {
        return [
            'customerId' => 'required|string|max:255|unique:customer_details,customerId,' . ($memberId ?? 'NULL') . ',id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'country' => 'required|string|max:255',
            'phone1' => 'required|string|max:255',
            'phone2' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'subscriptionDate' => 'required|date',
            'website' => 'nullable|string|max:255',
        ];
    }

    private function looksLikeHeader(array $header): bool
    {
        $knownHeaders = [
            'index',
            'customerid',
            'customercode',
            'memberid',
            'firstname',
            'fname',
            'lastname',
            'lname',
            'company',
            'city',
            'country',
            'phone1',
            'phone',
            'primaryphone',
            'phone2',
            'secondaryphone',
            'email',
            'emailaddress',
            'subscriptiondate',
            'subscription',
            'joindate',
            'website',
            'url',
        ];

        return count(array_intersect($header, $knownHeaders)) >= 3;
    }

    private function rowLooksLikeHeader(array $row): bool
    {
        $joined = strtolower(implode(' ', array_map(fn ($value) => (string) $value, $row)));

        return str_contains($joined, 'customer')
            || str_contains($joined, 'email')
            || str_contains($joined, 'subscription');
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
            'customerId' => $mapped['customerid'] ?? $mapped['customercode'] ?? $mapped['memberid'] ?? null,
            'firstName' => $mapped['firstname'] ?? $mapped['fname'] ?? null,
            'lastName' => $mapped['lastname'] ?? $mapped['lname'] ?? null,
            'company' => $mapped['company'] ?? '',
            'city' => $mapped['city'] ?? '',
            'country' => $mapped['country'] ?? '',
            'phone1' => $mapped['phone1'] ?? $mapped['phone'] ?? $mapped['primaryphone'] ?? '',
            'phone2' => $mapped['phone2'] ?? $mapped['secondaryphone'] ?? '',
            'email' => $mapped['email'] ?? $mapped['emailaddress'] ?? null,
            'subscriptionDate' => $mapped['subscriptiondate'] ?? $mapped['subscription'] ?? $mapped['joindate'] ?? null,
            'website' => $mapped['website'] ?? $mapped['url'] ?? '',
        ];
    }

    private function mapPositionalRow(array $row): array
    {
        $offset = $this->detectPositionalOffset($row);

        return [
            'customerId' => $this->rowValue($row, $offset + 0),
            'firstName' => $this->rowValue($row, $offset + 1),
            'lastName' => $this->rowValue($row, $offset + 2),
            'company' => $this->rowValue($row, $offset + 3, ''),
            'city' => $this->rowValue($row, $offset + 4, ''),
            'country' => $this->rowValue($row, $offset + 5, ''),
            'phone1' => $this->rowValue($row, $offset + 6, ''),
            'phone2' => $this->rowValue($row, $offset + 7, ''),
            'email' => $this->rowValue($row, $offset + 8),
            'subscriptionDate' => $this->rowValue($row, $offset + 9),
            'website' => $this->rowValue($row, $offset + 10, ''),
        ];
    }

    private function repairPayload(array $payload, array $row): array
    {
        $normalizedRow = array_values(array_filter(
            array_map([$this, 'sanitizeCell'], $row),
            static fn ($value) => $value !== null && $value !== ''
        ));

        $indexedRow = $this->stripIndexColumn($normalizedRow);

        if (empty($payload['customerId'])) {
            $payload['customerId'] = $indexedRow[0] ?? $this->extractCustomerId($row);
        }

        if (empty($payload['firstName'])) {
            $payload['firstName'] = $indexedRow[1] ?? null;
        }

        if (empty($payload['lastName'])) {
            $payload['lastName'] = $indexedRow[2] ?? null;
        }

        if (empty($payload['company'])) {
            $payload['company'] = $indexedRow[3] ?? '';
        }

        if (empty($payload['city'])) {
            $payload['city'] = $indexedRow[4] ?? '';
        }

        if (empty($payload['country'])) {
            $payload['country'] = $indexedRow[5] ?? '';
        }

        if (empty($payload['phone1'])) {
            $payload['phone1'] = $indexedRow[6] ?? '';
        }

        if (empty($payload['phone2'])) {
            $payload['phone2'] = $indexedRow[7] ?? '';
        }

        if (empty($payload['email']) || ! filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            foreach ($indexedRow as $value) {
                $value = $this->sanitizeCell($value);

                if ($value && filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $payload['email'] = $value;
                    break;
                }
            }
        }

        if (empty($payload['subscriptionDate']) || ! $this->looksLikeDate($payload['subscriptionDate'])) {
            foreach ($indexedRow as $value) {
                $value = $this->sanitizeCell($value);

                if ($value && $this->looksLikeDate($value)) {
                    $payload['subscriptionDate'] = $value;
                    break;
                }
            }
        }

        if (! empty($payload['subscriptionDate'])) {
            $payload['subscriptionDate'] = $this->normalizeDateValue($payload['subscriptionDate']);
        }

        if (empty($payload['website'])) {
            foreach ($indexedRow as $value) {
                $value = $this->sanitizeCell($value);

                if ($value && filter_var($value, FILTER_VALIDATE_URL)) {
                    $payload['website'] = $value;
                    break;
                }
            }
        }

        return $payload;
    }

    private function stripIndexColumn(array $row): array
    {
        if (count($row) >= 11 && isset($row[0], $row[1]) && ctype_digit($row[0]) && preg_match('/^[A-Za-z0-9]+$/', $row[1])) {
            array_shift($row);
        }

        return array_values($row);
    }

    private function extractCustomerId(array $row): ?string
    {
        $values = array_values(array_filter(array_map([$this, 'sanitizeCell'], $row), static fn ($value) => $value !== null && $value !== ''));

        if ($values === []) {
            return null;
        }

        if (isset($values[1]) && preg_match('/^[A-Za-z0-9]+$/', $values[1])) {
            return $values[1];
        }

        foreach ($values as $index => $value) {
            if ($index === 0 && ctype_digit($value) && isset($values[1])) {
                continue;
            }

            if (preg_match('/^[A-Za-z0-9]+$/', $value) && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                return $value;
            }
        }

        return null;
    }

    private function detectPositionalOffset(array $row): int
    {
        $offsets = [0, 1];

        foreach ($offsets as $offset) {
            $email = $this->rowValue($row, $offset + 8);
            $customerId = $this->rowValue($row, $offset + 0);

            if ($customerId && $email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $offset;
            }
        }

        return count($row) >= 12 ? 1 : 0;
    }

    private function rowValue(array $row, int $index, ?string $default = null): ?string
    {
        if (! isset($row[$index])) {
            return $default;
        }

        return $this->sanitizeCell($row[$index]);
    }

    private function looksLikeDate(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return $this->normalizeDateValue($value) !== null;
    }

    private function normalizeDateValue(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $value = trim($value);

        $formats = [
            'Y-m-d',
            'd-m-Y',
            'm-d-Y',
            'd/m/Y',
            'm/d/Y',
            'd.m.Y',
            'm.d.Y',
            'Y/m/d',
            'Y.m.d',
        ];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            if ($date && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
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
            $sanitized = array_map([$this, 'sanitizeCell'], $row);
            $hasValues = count(array_filter($sanitized, static fn ($value) => $value !== null && $value !== '')) > 0;

            if ($hasValues) {
                $rows[] = $sanitized;
            }
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

    private function sanitizeCell($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;
        $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
        $value = str_replace("\xC2\xA0", ' ', $value);
        $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
        $value = trim($value);

        return $value;
    }
}
