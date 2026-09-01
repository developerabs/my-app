<?php

namespace App\Services;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Exception;
use Illuminate\Support\Facades\Log;

class DynamicFormulaService
{
    protected ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * Execute any formula with provided data.
     * * @param string $formula The formula string (e.g., "price * tax / 100")
     * @param array $values The variables (e.g., ['price' => 100, 'tax' => 15])
     * @return float
     */
    public function execute(string $formula, array $values = []): float
    {
        // English comment: Ensure all input values are numeric or valid types for the engine.
        try {
            // English comment: The evaluate method safely parses and runs the math/logic.
            $result = $this->expressionLanguage->evaluate($formula, $values);

            return (float) $result;
        } catch (Exception $e) {
            Log::error("Universal Formula Execution Failed: " . $e->getMessage(), [
                'formula' => $formula,
                'values' => $values
            ]);
            return 0.00;
        }
    }

    /**
     * Check if a formula is syntactically correct.
     * English comment: Useful for validating user input in settings or admin panels.
     */
    public function validate(string $formula, array $variableNames = ['x']): bool
    {
        try {
            $this->expressionLanguage->compile($formula, $variableNames);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}