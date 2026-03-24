<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class PlannerService
{
    public function createPlan($goal)
    {
        $prompt = "Break this goal into actionable steps: " . $goal;

       $response = Http::withToken(env('OPENAI_API_KEY'))
            ->post('https://api.openai.com/v1/responses', [
                'model' => 'gpt-4.1-mini',
                'input' => $prompt
            ]);

            return $response->json();
    }
}
