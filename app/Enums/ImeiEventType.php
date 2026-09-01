<?php

namespace App\Enums;

enum ImeiEventType: string
{
    case OPENING_STOCK = 'opening_stock';
    case PURCHASE = 'purchase';
    case PURCHASE_RETURN = 'purchase_return';
    case SALE = 'sale';
    case SALE_RETURN = 'sale_return';
    case TRANSFER_OUT = 'transfer_out';
    case TRANSFER_IN = 'transfer_in';
    case ADJUSTMENT = 'adjustment';

    /**
     * English Comment: Return user-friendly labels for audit logs display screen
     */
    public function label(): string
    {
        return match($this) {
            self::OPENING_STOCK => 'Opening Stock Allocated',
            self::PURCHASE => 'Stock Purchased In',
            self::PURCHASE_RETURN => 'Returned to Supplier',
            self::SALE => 'Item Sold to Customer',
            self::SALE_RETURN => 'Returned by Customer',
            self::TRANSFER_OUT => 'Transferred Out to Branch',
            self::TRANSFER_IN => 'Transferred In from Branch',
            self::ADJUSTMENT => 'Inventory Stock Adjusted',
        };
    }
}
