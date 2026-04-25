<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for BtrcReportService.
 *
 * Validates Requirements:
 *   - 15.1: Report contains total/new/disconnected/active subscribers and revenue
 *   - 15.2: CSV export matches BTRC-specified column headers and order
 *   - 15.4: Data aggregated by division/district
 *   - 15.6: Generation actions are logged
 *   - 15.7: Zero-value report generated when no customer data exists
 *
 * Uses a testable subclass to inject a mock Database, avoiding real DB connections.
 */
class BtrcReportServiceTest extends TestCase
{
    /** @var MockObject&\Database */
    private MockObject $db;

    /** @var \BtrcReportService */
    private \BtrcReportService $service;

    protected function setUp(): void
    {
        $this->db      = $this->createMock(\Database::class);
        $this->service = new \TestableBtrcReportService($this->db);

        // Ensure no session side-effects
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }
        $_SESSION['user_id'] = 1;
    }

    // ── Data Aggregation (Req 15.1) ───────────────────────────────

    /**
     * Req 15.1: generateReport() returns all required subscriber and revenue fields.
     */
    public function testGenerateReportReturnsRequiredFields(): void
    {
        $month = 6;
        $year  = 2024;

        $this->mockGenerateReportDb(
            totalSubscribers: 500,
            activeSubscribers: 480,
            newConnections: 30,
            disconnections: 5,
            totalRevenue: 150000.00,
            newConnRevenue: 15000.00
        );

        $report = $this->service->generateReport($month, $year);

        $this->assertArrayHasKey('total_subscribers',      $report);
        $this->assertArrayHasKey('new_connections',        $report);
        $this->assertArrayHasKey('disconnections',         $report);
        $this->assertArrayHasKey('active_subscribers',     $report);
        $this->assertArrayHasKey('total_revenue',          $report);
        $this->assertArrayHasKey('new_connection_revenue', $report);
        $this->assertArrayHasKey('monthly_bill_revenue',   $report);
        $this->assertArrayHasKey('division_district_data', $report);
    }

    /**
     * Req 15.1: Subscriber counts are correctly populated from DB aggregates.
     */
    public function testGenerateReportSubscriberCounts(): void
    {
        $this->mockGenerateReportDb(
            totalSubscribers: 200,
            activeSubscribers: 190,
            newConnections: 15,
            disconnections: 3,
            totalRevenue: 50000.00,
            newConnRevenue: 5000.00
        );

        $report = $this->service->generateReport(3, 2024);

        $this->assertSame(200, (int)$report['total_subscribers']);
        $this->assertSame(190, (int)$report['active_subscribers']);
        $this->assertSame(15,  (int)$report['new_connections']);
        $this->assertSame(3,   (int)$report['disconnections']);
    }

    /**
     * Req 15.1: Revenue figures are correctly calculated.
     * monthly_bill_revenue = total_revenue - new_connection_revenue
     */
    public function testGenerateReportRevenueCalculation(): void
    {
        $this->mockGenerateReportDb(
            totalSubscribers: 100,
            activeSubscribers: 95,
            newConnections: 10,
            disconnections: 2,
            totalRevenue: 80000.00,
            newConnRevenue: 10000.00
        );

        $report = $this->service->generateReport(4, 2024);

        $this->assertEqualsWithDelta(80000.00, (float)$report['total_revenue'],          0.01);
        $this->assertEqualsWithDelta(10000.00, (float)$report['new_connection_revenue'],  0.01);
        $this->assertEqualsWithDelta(70000.00, (float)$report['monthly_bill_revenue'],    0.01,
            'monthly_bill_revenue should be total_revenue - new_connection_revenue');
    }

    // ── Zero-Value Report (Req 15.7) ──────────────────────────────

    /**
     * Req 15.7: When no customer data exists, generateReport() returns a zero-value
     * report rather than throwing an error.
     */
    public function testGenerateReportZeroValueWhenNoData(): void
    {
        $this->mockGenerateReportDb(
            totalSubscribers: 0,
            activeSubscribers: 0,
            newConnections: 0,
            disconnections: 0,
            totalRevenue: 0.0,
            newConnRevenue: 0.0
        );

        // Should not throw
        $report = $this->service->generateReport(1, 2024);

        $this->assertSame(0, (int)$report['total_subscribers'],  'Zero subscribers expected');
        $this->assertSame(0, (int)$report['new_connections'],    'Zero new connections expected');
        $this->assertSame(0, (int)$report['disconnections'],     'Zero disconnections expected');
        $this->assertSame(0, (int)$report['active_subscribers'], 'Zero active subscribers expected');
        $this->assertEqualsWithDelta(0.0, (float)$report['total_revenue'], 0.01, 'Zero revenue expected');
    }

    /**
     * Req 15.7: previewReport() also returns zero-value data when no customers exist.
     */
    public function testPreviewReportZeroValueWhenNoData(): void
    {
        // No existing saved report
        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql) {
                if (str_contains($sql, 'FROM btrc_reports')) {
                    return null; // no saved report
                }
                // All count/sum queries return zero
                return ['cnt' => 0, 'total' => 0];
            });

        $this->db->method('fetchAll')->willReturn([]);

        $preview = $this->service->previewReport(2, 2024);

        $this->assertSame(0, (int)$preview['total_subscribers']);
        $this->assertSame(0, (int)$preview['new_connections']);
        $this->assertSame(0, (int)$preview['active_subscribers']);
        $this->assertTrue($preview['is_preview'], 'Preview flag should be set');
        $this->assertNull($preview['id'], 'Preview should not have a saved ID');
    }

    // ── CSV Format Validation (Req 15.2) ─────────────────────────

    /**
     * Req 15.2: buildCsv() includes the required BTRC column headers.
     */
    public function testBuildCsvContainsRequiredHeaders(): void
    {
        $report = $this->makeSampleReport();
        $csv    = $this->service->buildCsv($report);

        $this->assertStringContainsString('Report Period',          $csv);
        $this->assertStringContainsString('Total Subscribers',      $csv);
        $this->assertStringContainsString('New Connections',        $csv);
        $this->assertStringContainsString('Disconnections',         $csv);
        $this->assertStringContainsString('Active Subscribers',     $csv);
        $this->assertStringContainsString('Total Revenue',          $csv);
        $this->assertStringContainsString('Division',               $csv);
        $this->assertStringContainsString('District',               $csv);
    }

    /**
     * Req 15.2: buildCsv() includes the correct data values.
     */
    public function testBuildCsvContainsCorrectValues(): void
    {
        $report = $this->makeSampleReport(
            totalSubscribers: 350,
            newConnections: 25,
            disconnections: 8,
            activeSubscribers: 340,
            totalRevenue: 120000.00
        );

        $csv = $this->service->buildCsv($report);

        $this->assertStringContainsString('350', $csv);
        $this->assertStringContainsString('25',  $csv);
        $this->assertStringContainsString('8',   $csv);
        $this->assertStringContainsString('340', $csv);
        $this->assertStringContainsString('120000.00', $csv);
    }

    /**
     * Req 15.2: buildCsv() includes division/district breakdown rows.
     */
    public function testBuildCsvIncludesDivisionDistrictBreakdown(): void
    {
        $report = $this->makeSampleReport();
        $report['division_district_data'] = [
            ['division' => 'Dhaka',     'district' => 'Dhaka',     'active' => 200, 'new' => 15, 'disconnected' => 3],
            ['division' => 'Chittagong','district' => 'Chittagong','active' => 80,  'new' => 5,  'disconnected' => 1],
        ];

        $csv = $this->service->buildCsv($report);

        $this->assertStringContainsString('Dhaka',      $csv);
        $this->assertStringContainsString('Chittagong', $csv);
        $this->assertStringContainsString('200',        $csv);
        $this->assertStringContainsString('80',         $csv);
    }

    /**
     * Req 15.2: buildCsv() handles empty division/district data gracefully.
     */
    public function testBuildCsvHandlesEmptyDivisionData(): void
    {
        $report = $this->makeSampleReport();
        $report['division_district_data'] = [];

        $csv = $this->service->buildCsv($report);

        // Should still have headers and summary row without throwing
        $this->assertStringContainsString('Division', $csv);
        $this->assertStringContainsString('N/A',      $csv);
    }

    /**
     * Req 15.2: buildCsv() properly escapes fields containing commas.
     */
    public function testBuildCsvEscapesCommasInFields(): void
    {
        $report = $this->makeSampleReport();
        $report['division_district_data'] = [
            ['division' => 'Dhaka, North', 'district' => 'Dhaka', 'active' => 10, 'new' => 1, 'disconnected' => 0],
        ];

        $csv = $this->service->buildCsv($report);

        // Field with comma should be quoted
        $this->assertStringContainsString('"Dhaka, North"', $csv);
    }

    // ── Division/District Aggregation (Req 15.4) ─────────────────

    /**
     * Req 15.4: aggregateByDivisionDistrict() returns rows with required keys.
     */
    public function testAggregateByDivisionDistrictReturnsCorrectStructure(): void
    {
        $this->db->method('fetchAll')
            ->willReturn([
                ['division' => 'Dhaka', 'district' => 'Dhaka', 'active' => 150, 'new_connections' => 10, 'disconnected' => 2],
                ['division' => 'Sylhet','district' => 'Sylhet', 'active' => 50,  'new_connections' => 3,  'disconnected' => 1],
            ]);

        $result = $this->service->aggregateByDivisionDistrict('2024-06-01', '2024-06-30');

        $this->assertCount(2, $result);

        foreach ($result as $row) {
            $this->assertArrayHasKey('division',     $row);
            $this->assertArrayHasKey('district',     $row);
            $this->assertArrayHasKey('active',       $row);
            $this->assertArrayHasKey('new',          $row);
            $this->assertArrayHasKey('disconnected', $row);
        }
    }

    /**
     * Req 15.4: aggregateByDivisionDistrict() returns empty array on DB failure.
     */
    public function testAggregateByDivisionDistrictReturnsEmptyOnFailure(): void
    {
        $this->db->method('fetchAll')
            ->willThrowException(new \RuntimeException('DB error'));

        $result = $this->service->aggregateByDivisionDistrict('2024-06-01', '2024-06-30');

        $this->assertIsArray($result);
        $this->assertEmpty($result, 'Should return empty array on DB failure');
    }

    /**
     * Req 15.4: aggregateByDivisionDistrict() casts counts to integers.
     */
    public function testAggregateByDivisionDistrictCastsToIntegers(): void
    {
        $this->db->method('fetchAll')
            ->willReturn([
                ['division' => 'Dhaka', 'district' => 'Dhaka', 'active' => '120', 'new_connections' => '8', 'disconnected' => '2'],
            ]);

        $result = $this->service->aggregateByDivisionDistrict('2024-06-01', '2024-06-30');

        $this->assertSame(120, $result[0]['active'],       'active should be cast to int');
        $this->assertSame(8,   $result[0]['new'],          'new should be cast to int');
        $this->assertSame(2,   $result[0]['disconnected'], 'disconnected should be cast to int');
    }

    // ── Report Logging (Req 15.6) ─────────────────────────────────

    /**
     * Req 15.6: logAction() inserts a record into btrc_report_logs.
     */
    public function testLogActionInsertsRecord(): void
    {
        $insertedTable = null;
        $insertedData  = null;

        $this->db->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$insertedTable, &$insertedData) {
                $insertedTable = $table;
                $insertedData  = $data;
                return 1;
            });

        $this->service->logAction(42, 'generated', '2024-06-01');

        $this->assertSame('btrc_report_logs', $insertedTable);
        $this->assertSame(42,          $insertedData['report_id']);
        $this->assertSame('generated', $insertedData['action']);
        $this->assertSame('2024-06-01',$insertedData['report_period']);
    }

    /**
     * Req 15.6: logAction() records export format for export actions.
     */
    public function testLogActionRecordsExportFormat(): void
    {
        $insertedData = null;

        $this->db->method('insert')
            ->willReturnCallback(function (string $table, array $data) use (&$insertedData) {
                $insertedData = $data;
                return 1;
            });

        $this->service->logAction(10, 'exported_csv', '2024-05-01', 'csv', '/exports/btrc_2024_05.csv');

        $this->assertSame('exported_csv',              $insertedData['action']);
        $this->assertSame('csv',                       $insertedData['export_format']);
        $this->assertSame('/exports/btrc_2024_05.csv', $insertedData['file_path']);
    }

    /**
     * Req 15.6: logAction() does not throw when DB insert fails.
     */
    public function testLogActionDoesNotThrowOnDbFailure(): void
    {
        $this->db->method('insert')
            ->willThrowException(new \RuntimeException('DB error'));

        // Should not throw
        $this->service->logAction(1, 'generated', '2024-06-01');
        $this->addToAssertionCount(1);
    }

    // ── PDF HTML Generation (Req 15.3) ────────────────────────────

    /**
     * Req 15.3: buildPdfHtml() returns HTML containing company letterhead elements.
     */
    public function testBuildPdfHtmlContainsLetterhead(): void
    {
        $report = $this->makeSampleReport();
        $companyInfo = [
            'name'    => 'Test ISP Ltd',
            'address' => '123 Main Street, Dhaka',
            'phone'   => '+880-1234-567890',
            'email'   => 'info@testisp.com',
        ];

        $html = $this->service->buildPdfHtml($report, $companyInfo);

        $this->assertStringContainsString('Test ISP Ltd',          $html);
        $this->assertStringContainsString('123 Main Street, Dhaka',$html);
        $this->assertStringContainsString('+880-1234-567890',      $html);
        $this->assertStringContainsString('info@testisp.com',      $html);
    }

    /**
     * Req 15.3: buildPdfHtml() includes authorised signatory fields.
     */
    public function testBuildPdfHtmlContainsSignatoryFields(): void
    {
        $report = $this->makeSampleReport();
        $html   = $this->service->buildPdfHtml($report);

        $this->assertStringContainsString('Authorised Signatory', $html);
        $this->assertStringContainsString('Prepared By',          $html);
        $this->assertStringContainsString('Checked By',           $html);
    }

    /**
     * Req 15.3: buildPdfHtml() includes BTRC report title.
     */
    public function testBuildPdfHtmlContainsReportTitle(): void
    {
        $report = $this->makeSampleReport();
        $html   = $this->service->buildPdfHtml($report);

        $this->assertStringContainsString('BTRC', $html);
        $this->assertStringContainsString('DIS',  $html);
    }

    // ── Report Period Handling ────────────────────────────────────

    /**
     * generateReport() correctly sets report_period to first day of month.
     */
    public function testGenerateReportSetsPeriodToFirstDayOfMonth(): void
    {
        $this->mockGenerateReportDb();

        $report = $this->service->generateReport(6, 2024);

        $this->assertSame('2024-06-01', $report['report_period']);
        $this->assertSame(6,            (int)$report['report_month']);
        $this->assertSame(2024,         (int)$report['report_year']);
    }

    /**
     * generateReport() upserts when a report for the period already exists.
     */
    public function testGenerateReportUpsertExistingReport(): void
    {
        $updateCalled = false;
        $insertCalled = false;

        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) {
                if (str_contains($sql, 'FROM btrc_reports') && str_contains($sql, 'report_period')) {
                    return ['id' => 99]; // existing report
                }
                if (str_contains($sql, 'FROM btrc_reports') && str_contains($sql, 'id = ?')) {
                    return $this->makeSampleReport() + ['id' => 99, 'generated_by_name' => 'Admin'];
                }
                return ['cnt' => 0, 'total' => 0];
            });

        $this->db->method('fetchAll')->willReturn([]);

        $this->db->method('update')
            ->willReturnCallback(function () use (&$updateCalled) {
                $updateCalled = true;
                return 1;
            });

        $this->db->method('insert')
            ->willReturnCallback(function (string $table) use (&$insertCalled) {
                if ($table === 'btrc_reports') {
                    $insertCalled = true;
                }
                return 1;
            });

        $this->service->generateReport(6, 2024);

        $this->assertTrue($updateCalled,  'Should call update() for existing report');
        $this->assertFalse($insertCalled, 'Should NOT call insert() for btrc_reports when report exists');
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * Build a minimal sample report array for testing.
     */
    private function makeSampleReport(
        int   $totalSubscribers  = 100,
        int   $newConnections    = 10,
        int   $disconnections    = 2,
        int   $activeSubscribers = 95,
        float $totalRevenue      = 50000.00,
        float $newConnRevenue    = 5000.00
    ): array {
        return [
            'id'                     => 1,
            'report_period'          => '2024-06-01',
            'report_year'            => 2024,
            'report_month'           => 6,
            'total_subscribers'      => $totalSubscribers,
            'new_connections'        => $newConnections,
            'disconnections'         => $disconnections,
            'active_subscribers'     => $activeSubscribers,
            'total_revenue'          => $totalRevenue,
            'new_connection_revenue' => $newConnRevenue,
            'monthly_bill_revenue'   => $totalRevenue - $newConnRevenue,
            'division_district_data' => [
                ['division' => 'Dhaka', 'district' => 'Dhaka', 'active' => $activeSubscribers, 'new' => $newConnections, 'disconnected' => $disconnections],
            ],
            'status'                 => 'draft',
            'generated_by_name'      => 'Test User',
            'generated_at'           => '2024-06-30 10:00:00',
            'updated_at'             => '2024-06-30 10:00:00',
        ];
    }

    /**
     * Set up DB mock for generateReport() calls.
     * Configures fetchOne, fetchAll, insert, and update expectations.
     */
    private function mockGenerateReportDb(
        int   $totalSubscribers  = 0,
        int   $activeSubscribers = 0,
        int   $newConnections    = 0,
        int   $disconnections    = 0,
        float $totalRevenue      = 0.0,
        float $newConnRevenue    = 0.0
    ): void {
        $callCount = 0;

        $this->db->method('fetchOne')
            ->willReturnCallback(function (string $sql, array $params) use (
                $totalSubscribers, $activeSubscribers, $newConnections,
                $disconnections, $totalRevenue, $newConnRevenue, &$callCount
            ) {
                $callCount++;

                // Check for existing report (upsert logic)
                if (str_contains($sql, 'FROM btrc_reports') && str_contains($sql, 'report_period')) {
                    return null; // no existing report → insert path
                }

                // Return the saved report after insert
                if (str_contains($sql, 'FROM btrc_reports') && str_contains($sql, 'id = ?')) {
                    return [
                        'id'                     => 1,
                        'report_period'          => '2024-06-01',
                        'report_year'            => 2024,
                        'report_month'           => 6,
                        'total_subscribers'      => $totalSubscribers,
                        'new_connections'        => $newConnections,
                        'disconnections'         => $disconnections,
                        'active_subscribers'     => $activeSubscribers,
                        'total_revenue'          => $totalRevenue,
                        'new_connection_revenue' => $newConnRevenue,
                        'monthly_bill_revenue'   => $totalRevenue - $newConnRevenue,
                        'division_district_data' => '[]',
                        'status'                 => 'draft',
                        'generated_by_name'      => 'Test User',
                        'generated_at'           => '2024-06-30 10:00:00',
                        'updated_at'             => '2024-06-30 10:00:00',
                    ];
                }

                // Total subscribers (status IN active/suspended)
                if (str_contains($sql, "status IN ('active','suspended')")) {
                    return ['cnt' => $totalSubscribers];
                }

                // Active subscribers
                if (str_contains($sql, "status = 'active'")) {
                    return ['cnt' => $activeSubscribers];
                }

                // New connections (created_at BETWEEN)
                if (str_contains($sql, 'created_at') && str_contains($sql, 'BETWEEN')) {
                    return ['cnt' => $newConnections];
                }

                // Disconnections
                if (str_contains($sql, "status IN ('cancelled','terminated')")) {
                    return ['cnt' => $disconnections];
                }

                // Total revenue
                if (str_contains($sql, 'FROM payments') && !str_contains($sql, 'JOIN invoices')) {
                    return ['total' => $totalRevenue];
                }

                // New connection revenue (installation invoices)
                if (str_contains($sql, 'FROM payments') && str_contains($sql, 'JOIN invoices')) {
                    return ['total' => $newConnRevenue];
                }

                return ['cnt' => 0, 'total' => 0];
            });

        $this->db->method('fetchAll')->willReturn([]);
        $this->db->method('insert')->willReturn(1);
        $this->db->method('update')->willReturn(1);
    }
}
