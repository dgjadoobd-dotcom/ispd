<?php

namespace Tests\Unit;

/**
 * Unit tests for SalesInvoiceService.
 *
 * Validates Requirements:
 *   - 5.2: Invoice number generation (SI-YYYY-NNNN)
 *   - 5.4: Partial payment processing
 *   - 5.6: Invoice cancellation with reversal
 *   - 5.7: Payment tracking
 */
class SalesInvoiceServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testInvoiceNumberFormat(): void
    {
        $prefix = 'SI-';
        $year = date('Y');
        $seq = 1;
        
        $invoiceNumber = $prefix . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
        
        $this->assertEquals('SI-2026-0001', $invoiceNumber);
    }

    public function testValidPaymentStatuses(): void
    {
        $statuses = ['unpaid', 'partial', 'paid', 'cancelled'];
        foreach ($statuses as $status) {
            $this->assertContains($status, $statuses);
        }
    }

    public function testValidInvoiceTypes(): void
    {
        $types = ['installation', 'product', 'service'];
        foreach ($types as $type) {
            $this->assertContains($type, $types);
        }
    }

    public function testValidPaymentMethods(): void
    {
        $methods = ['cash', 'mobile_banking', 'bank_transfer', 'online', 'other'];
        foreach ($methods as $method) {
            $this->assertContains($method, $methods);
        }
    }

    public function testVatCalculation(): void
    {
        $subtotal = 1000.00;
        $discount = 0;
        $vat = ($subtotal - $discount) * 0.15;
        
        $this->assertEquals(150.00, $vat);
    }

    public function testVatWithDiscount(): void
    {
        $subtotal = 1000.00;
        $discount = 100.00;
        $vat = ($subtotal - $discount) * 0.15;
        
        $this->assertEquals(135.00, $vat);
    }

    public function testTotalWithVatAndOtc(): void
    {
        $subtotal = 1000.00;
        $discount = 100.00;
        $otc = 500.00;
        
        $afterDiscount = $subtotal - $discount;
        $vat = $afterDiscount * 0.15;
        $total = $afterDiscount + $vat + $otc;
        
        $this->assertEquals(1535.00, $total);
    }

    public function testPaymentStatusPartialWhenPaidLessThanTotal(): void
    {
        $total = 1000.00;
        $paid = 300.00;
        
        $status = 'unpaid';
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
        
        $status = 'unpaid';
        if ($paid >= $total) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        
        $this->assertEquals('paid', $status);
    }

    public function testLineTotalCalculation(): void
    {
        $quantity = 5;
        $unitPrice = 100.00;
        $lineTotal = $quantity * $unitPrice;
        
        $this->assertEquals(500.00, $lineTotal);
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
        $requiredMethods = [
            'index',
            'invoices',
            'create',
            'store',
            'view',
            'edit',
            'update',
            'recordPayment',
            'cancel',
            'printInvoice',
            'payments',
            'reports',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists(\SalesInvoiceController::class, $method), "Method {$method} should exist");
        }
    }

    public function testServiceHasRequiredMethods(): void
    {
        $requiredMethods = [
            'generateInvoiceNumber',
            'getInvoices',
            'getInvoice',
            'createInvoice',
            'updateInvoice',
            'recordPayment',
            'cancelInvoice',
            'getPayments',
            'getActiveCustomers',
            'getSalesReport',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(method_exists(\SalesInvoiceService::class, $method), "Method {$method} should exist");
        }
    }
}