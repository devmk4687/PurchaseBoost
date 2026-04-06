<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ImportCsvJob;


class CsvImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('csv');
        $path = $file->getRealPath();

        if (! $path) {
            return response()->json([
                'message' => 'Unable to read the uploaded file.',
            ], 422);
        }

        $data = array_map('str_getcsv', file($path));
        $data = array_values(array_filter($data, static function (array $row): bool {
            return count(array_filter($row, static fn ($value) => $value !== null && $value !== '')) > 0;
        }));

        if ($data === []) {
            return response()->json([
                'message' => 'The uploaded CSV is empty.',
            ], 422);
        }

        $chunks = array_chunk($data, 100); // batch size

        $jobs = [];

        foreach ($chunks as $chunk) {
            $jobs[] = new ImportCsvJob($chunk);
        }

        $batch = Bus::batch($jobs)
            ->then(function ($batch) {
                \Log::info('Batch completed');
            })
            ->catch(function ($batch, $e) {
                \Log::error('Batch failed');
            })
            ->dispatch();

        return response()->json([
            'message' => 'Import started',
            'batch_id' => $batch->id
        ]);
    }
}
