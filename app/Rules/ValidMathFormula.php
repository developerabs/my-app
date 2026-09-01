<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Services\DynamicFormulaService; // English comment: Import your service

class ValidMathFormula implements ValidationRule
{
    protected $requiredVar;
    protected $formulaService;

    // English comment: Accept the mandatory variable (like 'x') during initialization.
    public function __construct($requiredVar = 'x')
    {
        $this->requiredVar = strtolower($requiredVar);
        $this->formulaService = new DynamicFormulaService();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $formula = strtolower($value);

        // English comment: 1. Security Check - Only allow letters, numbers, and math operators.
        if (!preg_match('/^[a-z0-9\s\+\-\*\/\(\)\%\.\_]+$/', $formula)) {
            $fail("The :attribute contains invalid characters.");
            return;
        }

        // English comment: 2. Core Requirement - Formula must contain the base variable (x).
        if (!str_contains($formula, $this->requiredVar)) {
            $fail("The :attribute must contain the base variable '{$this->requiredVar}'.");
            return;
        }

        // English comment: 3. Syntax Deep Test - Using Symfony ExpressionLanguage via your Service.
        // We extract all words to pass them as variable names to the validator.
        preg_match_all('/[a-z_][a-z0-9_]*/', $formula, $matches);
        $variables = array_unique($matches[0]);

        if (!$this->formulaService->validate($value, $variables)) {
            $fail("The :attribute has a mathematical syntax error (e.g., unbalanced parentheses or wrong operator placement).");
        }
    }
}