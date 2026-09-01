<?php

namespace App\Services;

use App\Models\Unit;
use Exception;
use Illuminate\Support\Facades\Log;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class UnitFormulaService
{
    protected ExpressionLanguage $expressionLanguage;
    protected int $defaultPrecision = 4;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
    }

    /**
     * Get precision from unit or context
     */
    private function getPrecision(Unit|array $unit, array $context = []): int
    {
        if (is_array($unit)) {
            return $unit['precision'] ?? $context['precision'] ?? $this->defaultPrecision;
        }
        return $unit->precision ?? $context['precision'] ?? $this->defaultPrecision;
    }

    /**
     * Calculate base quantity for one level with precision.
     */
    public function getBaseQuantity(float $quantity, Unit $unit, array $context = []): float
    {
        if ($unit->is_base_unit) {
            return $quantity;
        }

        $precision = $this->getPrecision($unit, $context);
        $data = array_merge(['x' => $quantity], $context);

        try {
            if ($unit->is_formulaic && !empty($unit->formula)) {
                $result = (float) $this->expressionLanguage->evaluate($unit->formula, $data);
            } else {
                $result = $unit->operator === '*'
                    ? $quantity * $unit->operator_value
                    : $quantity / $unit->operator_value;
            }

            return round($result, $precision);
        } catch (Exception $e) {
            Log::error("Formula Calculation Failed: " . $e->getMessage(), [
                'unit_id' => $unit->id,
                'formula' => $unit->formula,
                'data' => $data
            ]);
            return 0.00;
        }
    }

    /**
     * Recursive: Higher Unit -> Global Base Unit (e.g., Box -> Sqft)
     */
    public function getFinalBaseQuantity(float $quantity, Unit $unit, array $allContexts = []): float
    {
        $currentContext = $allContexts[$unit->id] ?? $allContexts;
        $currentBaseQty = $this->getBaseQuantity($quantity, $unit, $currentContext);

        if ($unit->is_base_unit || is_null($unit->base_unit_id)) {
            return $currentBaseQty;
        }

        $parentUnit = $unit->baseUnit ?? Unit::find($unit->base_unit_id);

        if ($parentUnit) {
            return $this->getFinalBaseQuantity($currentBaseQty, $parentUnit, $allContexts);
        }

        return $currentBaseQty;
    }

    /**
     * Simple Reverse: Base -> Unit (One Level)
     */
    public function getQuantityFromBase(float $baseQuantity, Unit $unit, array $context = []): float
    {
        if ($unit->is_base_unit) {
            return $baseQuantity;
        }

        try {
            $precision = $this->getPrecision($unit, $context);
            // Calculate how much 1 Unit is in Base
            $oneUnitInBase = $this->getBaseQuantity(1, $unit, $context);

            if ($oneUnitInBase > 0) {
                return round($baseQuantity / $oneUnitInBase, $precision);
            }
            return 0.00;
        } catch (Exception $e) {
            return 0.00;
        }
    }

    /**
     * Recursive Reverse: Global Base Unit -> Higher Unit (e.g., Gram -> Anna)
     */
    public function getFinalQuantityFromBase(float $baseQuantity, Unit $targetUnit, array $allContexts = []): float
    {
        if ($targetUnit->is_base_unit || is_null($targetUnit->base_unit_id)) {
            return $baseQuantity;
        }

        $parentUnit = $targetUnit->baseUnit ?? Unit::find($targetUnit->base_unit_id);

        if ($parentUnit) {
            // First get quantity in the parent unit
            $quantityInParentUnit = $this->getFinalQuantityFromBase($baseQuantity, $parentUnit, $allContexts);

            $currentContext = $allContexts[$targetUnit->id] ?? $allContexts;
            $precision = $this->getPrecision($targetUnit, $currentContext);

            // Convert parent unit quantity to target unit
            $oneUnitValue = $this->getBaseQuantity(1, $targetUnit, $currentContext);

            if ($oneUnitValue > 0) {
                return round($quantityInParentUnit / $oneUnitValue, $precision);
            }
        }

        return $baseQuantity;
    }


    /**
     * Calculate Unit Price based on base price and ratio
     */
    public function calculateUnitPrice($basePrice, $targetUnitId, $unitDetailsJSON)
    {
        $unitDetails = json_decode($unitDetailsJSON, true);

        if (!isset($unitDetails[$targetUnitId])) {
            return $basePrice;
        }

        $targetUnit = $unitDetails[$targetUnitId];
        $precision = $this->getPrecision($targetUnit);

        // Get the conversion ratio
        $ratio = $this->getRatioFromJSON($unitDetails, $targetUnitId);

        // Price usually needs standard rounding or specific precision
        return round($basePrice * $ratio, $precision);
    }

    /**
     * Get Ratio recursively from JSON details with Precision and Operator support.
     */
    public function getRatioFromJSON(array $unitDetails, $targetUnitId)
    {
        // 1. If unit is not found, return default 1
        if (!isset($unitDetails[$targetUnitId])) {
            return 1.00;
        }

        $unit = $unitDetails[$targetUnitId];
        $precision = $this->getPrecision($unit);
        $currentRatio = 1.00;

        // operator and formula handling
        $operator = $unit['operator'] ?? '*';
        $val = 1.00;

        // check if formula exists and is formulaic, then evaluate it. Otherwise, fallback to operator handling
        if (($unit['is_formulaic'] ?? false) && !empty($unit['formula'])) {
            $context = array_merge(['x' => 1], $unit['user_vars'] ?? []);
            try {
                // Evaluate the formula with the given context. The formula should return how many base units are in 1 of this unit.
                $val = (float) $this->expressionLanguage->evaluate($unit['formula'], $context);

                // If formula is valid, use the result as the ratio
                $currentRatio = $val;
            } catch (\Exception $e) {
                // If formula fails, use operator value as fallback
                $val = (float) ($unit['operator_val'] ?? $unit['operator_value'] ?? 1);
                $currentRatio = ($operator === '/') ? (1 / $val) : $val;
            }
        } else {
            // generic operator handling (without formula)
            $val = (float) ($unit['operator_val'] ?? $unit['operator_value'] ?? 1);

            // If operator is division, we need to take reciprocal for ratio calculation
            if ($operator === '/') {
                $currentRatio = $val != 0 ? (1 / $val) : 1.00;
            } else {
                $currentRatio = $val;
            }
        }

        // if has parent unit, get parent's ratio and multiply with current ratio to get final ratio
        if (!empty($unit['base_unit_id']) && isset($unitDetails[$unit['base_unit_id']])) {
            $parentRatio = $this->getRatioFromJSON($unitDetails, $unit['base_unit_id']);

            // Multiply with parent's ratio to get the final ratio for this unit
            return round($currentRatio * $parentRatio, $precision);
        }

        return round($currentRatio, $precision);
    }

    /**
     * Validate formula syntax
     */
    public function isValidFormula(string $formula, array $testVariables = ['x']): bool
    {
        try {
            $this->expressionLanguage->compile($formula, $testVariables);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
