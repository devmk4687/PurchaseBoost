<?php

namespace App\Services;

class ExecutionService
{
    public function execute($plan)
    {
        print_r($plan);exit;
        $steps = explode("\n",$plan);
        $results = [];

        foreach ($steps as $step) {
            $results[] = $this->handleStep($step);
        }

        return $results;
    }

    private function handleStep($step)
    {
        if (str_contains($step, 'email')) {
            return $this->generateEmail($step);
        }

        return "Executed: " . $step;
    }

    private function generateEmail($context)
    {
        return "Generated Email for: " . $context;
    }
}
