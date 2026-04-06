<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportCsvJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    protected $rows;
    public function __construct(array $rows)
    {
       $this->rows = $rows;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->rows as $row) {
            \DB::table('customer_details')->insert([
                'customerId' => $row[1],
                'firstName' => $row[2],
                'lastName' => $row[3],
                'company' => $row[4],
                'city' => $row[5],
                'country' => $row[6],
                'phone1' => $row[7],
                'phone2' => $row[8],
                'email' => $row[9],
                'subscriptionDate' => $row[10],
                'website' => $row[11],
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
