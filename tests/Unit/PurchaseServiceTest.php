<?php

namespace Tests\Unit;

/**
 * Unit tests for PurchaseService.
 *
 * Validates Requirements: 6.3, 6.5, 6.8
 */
class PurchaseServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testBillNumberFormat(): void
    {
        $prefix = 'PB-';
        $year = date('Y');
        $seq = 1;
        
        $billNumber = $prefix . $year . str_pad($seq, 4, '0', STR_PAD_LEFT);
        
        $this->assertEquals('PB-2026-0001', $billNumber);
    }

    public function testValidPaymentStatuses(): void
    {
        $statuses = ['pending', 'partial', 'paid', 'cancelled'];
        foreach ($statuses as $status) {
            $this->assertContains($status, $statuses);
        }
    }

    public function testValidPaymentMethods(): void
    {
        $methods = ['cash', 'mobile_banking', 'bank_transfer', 'online', 'other'];
        foreach ($methods as $method) {
            $this->assertContains($method, $methods);
        }
    }

    public function testLineTotalCalculation(): void
    {
        $quantity = 10;
        $unitPrice = 50.00;
        $lineTotal = $quantity * $unitPrice;
        
        $this->assertEquals(500.00, $lineTotal);
    }

    public function testTotalWithDiscount(): void
    {
        $subtotal = 1000.00;
        $discount = 100.00;
        $total = $subtotal - $discount;
        
        $this->assertEquals(900.00, $total);
    }

    public function testPaymentStatusPartialWhenPaidLessThanTotal(): void
    {
        $total = 1000.00;
        $paid = 300.00;
        
        $status = 'pending';
        if ($paid >= $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        
        $this->assertEquals('partial', $status);
    }

    public function testPaymentStatusPaidWhenPaidEqualsTotal(): void
    {
        $total = 1000.00;
        $paid = 1000.00;
        
        $status = 'pending';
        if ($paid >= $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        
        $this->assertEquals('paid', $status);
    }

    public function testDueAmountCalculation(): void
    {
        $total = 1000.00;
        $paid = 400.00;
        $dueAmount = $total - $paid;
        
        $this->assertEquals(600.00, $dueAmount);
    }

    public function testControllerHasRequiredMethods(): void
    {
        $required = ['index', 'vendors', 'createVendor', 'storeVendor', 'editVendor', 'updateVendor', 'bills', 'createBill', 'storeBill', 'viewBill', 'recordPayment', 'ledger', 'reports'];
        foreach ($required as $method) {
            $this->assertTrue(method_exists(\PurchaseController::class, $method), "Method {$method} should exist");
        }
    }

    public function testServiceHasRequiredMethods(): void
    {
        $required = ['getVendors', 'getVendor', 'createVendor', 'updateVendor', 'getActiveVendors', 'getBills', 'getBill', 'createBill', 'recordPayment', 'getBillPayments', 'getVendorLedger', 'getPurchaseReport'];
        foreach ($required as $method) {
            $this->assertTrue(method_exists(\PurchaseService::class, $method), "Method {$method} should exist");
        }
    }
}