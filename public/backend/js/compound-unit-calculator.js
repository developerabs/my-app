/**
 * Global Compound Unit Conversion Engine for POS Layouts
 */
class CompoundUnitCalculator {
    constructor(unitDetails) {
        this.unitDetails = unitDetails || {};
    }

    /**
     * Calculates the conversion ratio dynamically matching the PHP backend counterpart
     */
    calculateRatio(unitId) {
        if (!this.unitDetails[unitId]) return 1;
        const unit = this.unitDetails[unitId];
        let ratio = 1;

        if (unit.is_formulaic && unit.formula) {
            let formula = unit.formula;
            formula = formula.replace(/\bx\b/g, '1');

            if (unit.user_vars) {
                Object.keys(unit.user_vars).forEach(key => {
                    const val = unit.user_vars[key] || 0;
                    const regex = new RegExp('\\b' + key + '\\b', 'g');
                    formula = formula.replace(regex, val);
                });
            }

            try {
                ratio = Function('"use strict"; return (' + formula + ')')() || 1;
            } catch (e) {
                console.error("Formula Evaluation Error:", e);
                ratio = parseFloat(unit.operator_val || 1);
            }
        } else {
            ratio = parseFloat(unit.operator_val || 1);
        }

        if (unit.operator === '/') {
            if (!unit.is_formulaic) {
                ratio = 1 / ratio;
            }
        }

        if (unit.base_unit_id && this.unitDetails[unit.base_unit_id]) {
            return ratio * this.calculateRatio(unit.base_unit_id);
        }

        return ratio;
    }

    /**
     * Returns unit configuration array sorted from largest to smallest scales
     */
    getSortedUnits() {
        return Object.values(this.unitDetails).sort((a, b) => {
            return this.calculateRatio(b.unit_id) - this.calculateRatio(a.unit_id);
        });
    }

    /**
     * Distributes raw base quantities into structured DOM input boxes sequentially
     */
    distributeQtyToInputs($row, finalQty) {
        if (finalQty <= 0) return;
        
        const sortedUnits = this.getSortedUnits();
        let remainingQty = finalQty;
        const totalUnitsCount = sortedUnits.length;

        sortedUnits.forEach((unit, index) => {
            const ratio = this.calculateRatio(unit.unit_id);
            const unitPrecision = unit.precision !== undefined ? parseInt(unit.precision) : 2;

            if (ratio > 0 && remainingQty >= 0) {
                // FIX: Check if this is the absolute smallest unit in the sorted array
                if (index === totalUnitsCount - 1) {
                    const currentUnitQty = Number((remainingQty / ratio).toFixed(unitPrecision));
                    if (currentUnitQty > 0) {
                        $row.find(`.compound-qty[data-unit-id="${unit.unit_id}"]`).val(currentUnitQty);
                    }
                    remainingQty = 0;
                } else {
                    // For higher units, calculate whole integer using floor after precision-safe division
                    let currentUnitQty = Math.floor(Number((remainingQty / ratio).toFixed(7)));
                    
                    if (currentUnitQty > 0) {
                        $row.find(`.compound-qty[data-unit-id="${unit.unit_id}"]`).val(currentUnitQty);
                        remainingQty -= currentUnitQty * ratio;
                        remainingQty = Number(remainingQty.toFixed(7)); // Floating point precision fix
                    }
                }
            }
        });
    }

    /**
     * Formats raw stock into a human-readable compound string based on unit details and individual unit precision
     * @param {number|string} totalStock - Total stock in the lowest/base unit
     * @returns {string} - e.g., "1 Vori, 3 Ana, 2 Rati"
     */
    formatStockWithUnit(totalStock) {
        let remainingQty = parseFloat(totalStock);
        const sortedUnits = this.getSortedUnits();

        if (isNaN(remainingQty) || remainingQty <= 0) {
            const baseUnit = sortedUnits.length > 0 ? sortedUnits[sortedUnits.length - 1] : null;
            const baseUnitName = baseUnit ? baseUnit.actual_name : 'Pcs';
            const basePrecision = baseUnit && baseUnit.precision !== undefined ? parseInt(baseUnit.precision) : 2;
            return `${(0).toFixed(basePrecision)} ${baseUnitName}`;
        }

        let resultStrings = [];
        const totalUnitsCount = sortedUnits.length;

        sortedUnits.forEach((unit, index) => {
            const ratio = this.calculateRatio(unit.unit_id);
            const unitPrecision = unit.precision !== undefined ? parseInt(unit.precision) : 2;
            
            if (ratio > 0 && remainingQty > 0) {
                // FIX: Check if this is the absolute smallest unit instead of ratio === 1
                if (index === totalUnitsCount - 1) {
                    const currentUnitQty = Number((remainingQty / ratio).toFixed(unitPrecision));
                    if (currentUnitQty > 0) {
                        resultStrings.push(`${currentUnitQty} ${unit.actual_name}`);
                    }
                    remainingQty = 0; // Everything is consumed
                } else {
                    // For higher units, take the absolute whole number after rounding for JS safe floats
                    const currentUnitQty = Math.floor(Number((remainingQty / ratio).toFixed(7)));
                    if (currentUnitQty > 0) {
                        resultStrings.push(`${currentUnitQty} ${unit.actual_name}`);
                        remainingQty -= currentUnitQty * ratio;
                        remainingQty = Number(remainingQty.toFixed(7)); // Fix JavaScript floating point precision issues
                    }
                }
            }
        });

        // Safety fallback
        if (resultStrings.length === 0) {
            const baseUnit = sortedUnits[sortedUnits.length - 1];
            const basePrecision = baseUnit && baseUnit.precision !== undefined ? parseInt(baseUnit.precision) : 2;
            return `${(0).toFixed(basePrecision)} ${baseUnit ? baseUnit.actual_name : 'Pcs'}`;
        }

        return resultStrings.join(', ');
    }
}