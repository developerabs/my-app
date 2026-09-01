<?php

namespace Tests\Feature\CRM;

use App\Models\Deal;
use App\Models\DealItem;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DealManagementTest extends TestCase
{
    #[Test]
    public function it_has_deal_and_deal_item_models(): void
    {
        $this->assertTrue(class_exists(Deal::class));
        $this->assertTrue(class_exists(DealItem::class));
        $this->assertTrue(method_exists(Deal::class, 'items'));
        $this->assertTrue(method_exists(Deal::class, 'notes'));
    }
}
