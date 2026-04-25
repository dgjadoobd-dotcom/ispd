<?php

/**
 * BtrcReportService — Business logic for the BTRC Report module.
 *
 * Covers Requirements 15.1–15.7:
 *   - 15.1: Generate BTRC DIS report with total/new/disconnected/active subscribers
 *           and revenue figures, aggregated by division/district.
 *   - 15.2: CSV export matching BTRC-specified column headers and order.
 *   - 15.3: PDF export with company letterhead and signatory fields.
 *   - 15.4: Aggregate customer data by division/district from zone/area hierarchy.
 *   - 15.5: Preview report data before exporting.
 *   - 15.6: Log every generation action with user, timestamp, and period.
 *   - 15.7: Generate zero-value report when no customer data exists for the period.
 */
class BtrcReportService extends BaseService
{
    // ── Report Generation ─────────────────────────────────────────

    /**
     * Generate (or regenerate) a BTRC DIS report for a given month/year.
     *
     * Aggregates customer data from the customers table using the zone/area
     * hierarchy to derive division and district fields.
     *
     * Requirement 15.1: total subscribers, new connections, disconnections,
     *                   active subscribers by division/district, revenue figures.
     * Requirement 15.7: returns a zero-value report when no data exists.
     *
     * @param  int $month  1–12
     * @param  int $year   e.g. 2024
     * @return array       The generated btrc_reports row
     * @throws \RuntimeException on database failure
     */
    public function generateReport(int $month, int $year): array
    {
        $reportPeriod = sprintf('%04d-%02d-01', $year, $month);
        $periodStart  = $reportPeriod;
        $periodEnd    = date('Y-m-t', strtotime($reportPeriod)); // last day of month

        // ── Aggregate subscriber counts ───────────────────────────

        // Total active subscribers at end of period
        $totalRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM customers
             WHERE status IN ('active','suspended')
               AND DATE(created_at) <= ?",
            [$periodEnd]
        );
        $totalSubscribers = (int)($totalRow['cnt'] ?? 0);

        // Active subscribers (status = active) at end of period
        $activeRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM customers
             WHERE status = 'active'
               AND DATE(created_at) <= ?",
            [$periodEnd]
        );
        $activeSubscribers = (int)($activeRow['cnt'] ?? 0);

        // New connections during the period
        $newRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM customers
             WHERE DATE(created_at) BETWEEN ? AND ?",
            [$periodStart, $periodEnd]
        );
        $newConnections = (int)($newRow['cnt'] ?? 0);

        // Disconnections during the period (status changed to cancelled/terminated)
        $disconnRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM customers
             WHERE status IN ('cancelled','terminated')
               AND DATE(updated_at) BETWEEN ? AND ?",
            [$periodStart, $periodEnd]
        );
        $disconnections = (int)($disconnRow['cnt'] ?? 0);

        // ── Revenue figures ───────────────────────────────────────

        // Total revenue collected during the period
        $revenueRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM payments
             WHERE DATE(payment_date) BETWEEN ? AND ?",
            [$periodStart, $periodEnd]
        );
        $totalRevenue = (float)($revenueRow['total'] ?? 0.0);

        // New connection revenue (installation fees / OTC)
        $newConnRevRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM payments p
             JOIN invoices i ON i.id = p.invoice_id
             WHERE DATE(p.payment_date) BETWEEN ? AND ?
               AND i.invoice_type = 'installation'",
            [$periodStart, $periodEnd]
        );
        $newConnectionRevenue = (float)($newConnRevRow['total'] ?? 0.0);

        $monthlyBillRevenue = $totalRevenue - $newConnectionRevenue;

        // ── Division/District breakdown ───────────────────────────
        // Requirement 15.4: aggregate by division/district from zone hierarchy.
        $divisionData = $this->aggregateByDivisionDistrict($periodStart, $periodEnd);

        // ── Upsert the report record ──────────────────────────────
        $existing = $this->db->fetchOne(
            "SELECT id FROM btrc_reports WHERE report_period = ? LIMIT 1",
            [$reportPeriod]
        );

        $reportData = [
            'report_period'          => $reportPeriod,
            'report_year'            => $year,
            'report_month'           => $month,
            'total_subscribers'      => $totalSubscribers,
            'new_connections'        => $newConnections,
            'disconnections'         => $disconnections,
            'active_subscribers'     => $activeSubscribers,
            'total_revenue'          => $totalRevenue,
            'new_connection_revenue' => $newConnectionRevenue,
            'monthly_bill_revenue'   => $monthlyBillRevenue,
            'division_district_data' => json_encode($divisionData),
            'status'                 => 'draft',
            'generated_by'           => $_SESSION['user_id'] ?? null,
        ];

        if ($existing) {
            $reportId = (int)$existing['id'];
            $this->db->update('btrc_reports', $reportData, 'id = ?', [$reportId]);
        } else {
            $reportId = $this->db->insert('btrc_reports', $reportData);
        }

        // ── Log the generation action ─────────────────────────────
        $this->logAction($reportId, 'generated', $reportPeriod);

        return $this->getReport($reportId) ?? [];
    }

    /**
     * Aggregate active/new/disconnected subscriber counts by division and district.
     * Requirement 15.4: uses zone/area hierarchy to derive division and district.
     *
     * @param  string $periodStart  Y-m-d
     * @param  string $periodEnd    Y-m-d
     * @return array  Array of ['division', 'district', 'active', 'new', 'disconnected']
     */
    public function aggregateByDivisionDistrict(string $periodStart, string $periodEnd): array
    {
        // Try to join customers → zones → areas to get division/district.
        // Falls back to 'Unknown' if the columns don't exist in the schema.
        try {
            $rows = $this->db->fetchAll(
                "SELECT
                    COALESCE(z.division, 'Unknown')  AS division,
                    COALESCE(z.district, 'Unknown')  AS district,
                    COUNT(CASE WHEN c.status = 'active' THEN 1 END)                                    AS active,
                    COUNT(CASE WHEN DATE(c.created_at) BETWEEN ? AND ? THEN 1 END)                     AS new_connections,
                    COUNT(CASE WHEN c.status IN ('cancelled','terminated')
                                AND DATE(c.updated_at) BETWEEN ? AND ? THEN 1 END)                     AS disconnected
                 FROM customers c
                 LEFT JOIN zones z ON z.id = c.zone_id
                 WHERE DATE(c.created_at) <= ?
                 GROUP BY division, district
                 ORDER BY division ASC, district ASC",
                [$periodStart, $periodEnd, $periodStart, $periodEnd, $periodEnd]
            );

            return array_map(fn($r) => [
                'division'     => $r['division'],
                'district'     => $r['district'],
                'active'       => (int)$r['active'],
                'new'          => (int)$r['new_connections'],
                'disconnected' => (int)$r['disconnected'],
            ], $rows);
        } catch (\Throwable $e) {
            $this->logError('aggregateByDivisionDistrict failed', $e);
            return [];
        }
    }

    // ── Report Retrieval ──────────────────────────────────────────

    /**
     * Return a single report by ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function getReport(int $id): ?array
    {
        try {
            $report = $this->db->fetchOne(
                "SELECT r.*, u.full_name AS generated_by_name
                 FROM btrc_reports r
                 LEFT JOIN users u ON u.id = r.generated_by
                 WHERE r.id = ? LIMIT 1",
                [$id]
            );

            if ($report && !empty($report['division_district_data'])) {
                $report['division_district_data'] = json_decode(
                    $report['division_district_data'],
                    true
                ) ?? [];
            }

            return $report;
        } catch (\Throwable $e) {
            $this->logError('getReport failed', $e, ['id' => $id]);
            return null;
        }
    }

    /**
     * Return a report by its period (YYYY-MM-01).
     *
     * @param  string $period  e.g. '2024-06-01'
     * @return array|null
     */
    public function getReportByPeriod(string $period): ?array
    {
        try {
            $row = $this->db->fetchOne(
                "SELECT id FROM btrc_reports WHERE report_period = ? LIMIT 1",
                [$period]
            );
            return $row ? $this->getReport((int)$row['id']) : null;
        } catch (\Throwable $e) {
            $this->logError('getReportByPeriod failed', $e);
            return null;
        }
    }

    /**
     * Return all reports ordered by period descending.
     *
     * @param  int $limit
     * @return array
     */
    public function getReports(int $limit = 24): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT r.*, u.full_name AS generated_by_name
                 FROM btrc_reports r
                 LEFT JOIN users u ON u.id = r.generated_by
                 ORDER BY r.report_period DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (\Throwable $e) {
            $this->logError('getReports failed', $e);
            return [];
        }
    }

    /**
     * Mark a report as final.
     *
     * @param  int $id
     * @return void
     */
    public function finaliseReport(int $id): void
    {
        $this->db->update('btrc_reports', ['status' => 'final'], 'id = ?', [$id]);
    }

    // ── CSV Export ────────────────────────────────────────────────

    /**
     * Build a CSV string for a BTRC DIS report.
     * Requirement 15.2: CSV format matching BTRC-specified column headers and order.
     *
     * @param  array $report  Report row from getReport()
     * @return string         CSV content
     */
    public function buildCsv(array $report): string
    {
        $lines = [];

        // ── Summary section ───────────────────────────────────────
        $lines[] = $this->csvRow([
            'Report Period',
            'Total Subscribers',
            'New Connections',
            'Disconnections',
            'Active Subscribers',
            'Total Revenue (BDT)',
            'New Connection Revenue (BDT)',
            'Monthly Bill Revenue (BDT)',
        ]);

        $lines[] = $this->csvRow([
            date('F Y', strtotime($report['report_period'])),
            $report['total_subscribers'],
            $report['new_connections'],
            $report['disconnections'],
            $report['active_subscribers'],
            number_format((float)$report['total_revenue'], 2, '.', ''),
            number_format((float)$report['new_connection_revenue'], 2, '.', ''),
            number_format((float)$report['monthly_bill_revenue'], 2, '.', ''),
        ]);

        $lines[] = $this->csvRow([]);  // blank separator

        // ── Division/District breakdown ───────────────────────────
        $lines[] = $this->csvRow([
            'Division',
            'District',
            'Active Subscribers',
            'New Connections',
            'Disconnections',
        ]);

        $divData = $report['division_district_data'] ?? [];
        if (empty($divData)) {
            $lines[] = $this->csvRow(['N/A', 'N/A', 0, 0, 0]);
        } else {
            foreach ($divData as $row) {
                $lines[] = $this->csvRow([
                    $row['division']     ?? 'Unknown',
                    $row['district']     ?? 'Unknown',
                    $row['active']       ?? 0,
                    $row['new']          ?? 0,
                    $row['disconnected'] ?? 0,
                ]);
            }
        }

        return implode("\r\n", $lines);
    }

    /**
     * Build a single CSV row, properly quoting fields that contain commas or quotes.
     *
     * @param  array $fields
     * @return string
     */
    private function csvRow(array $fields): string
    {
        $escaped = array_map(function ($field) {
            $field = (string)$field;
            if (str_contains($field, ',') || str_contains($field, '"') || str_contains($field, "\n")) {
                $field = '"' . str_replace('"', '""', $field) . '"';
            }
            return $field;
        }, $fields);

        return implode(',', $escaped);
    }

    // ── PDF Export ────────────────────────────────────────────────

    /**
     * Build an HTML string suitable for PDF conversion for a BTRC DIS report.
     * Requirement 15.3: includes company letterhead and authorised signatory fields.
     *
     * The HTML is designed to be rendered by a headless browser or a PHP PDF
     * library (e.g. mPDF, TCPDF, Dompdf). The caller is responsible for
     * converting the HTML to PDF.
     *
     * @param  array  $report       Report row from getReport()
     * @param  array  $companyInfo  ['name', 'address', 'phone', 'email', 'logo_url']
     * @return string               HTML content
     */
    public function buildPdfHtml(array $report, array $companyInfo = []): string
    {
        $period       = date('F Y', strtotime($report['report_period']));
        $companyName  = htmlspecialchars($companyInfo['name']  ?? 'ISP Company');
        $companyAddr  = htmlspecialchars($companyInfo['address'] ?? '');
        $companyPhone = htmlspecialchars($companyInfo['phone']   ?? '');
        $companyEmail = htmlspecialchars($companyInfo['email']   ?? '');
        $logoUrl      = htmlspecialchars($companyInfo['logo_url'] ?? '');
        $generatedAt  = date('d M Y H:i', strtotime($report['generated_at'] ?? 'now'));
        $generatedBy  = htmlspecialchars($report['generated_by_name'] ?? 'System');

        $divRows = '';
        $divData = $report['division_district_data'] ?? [];
        if (empty($divData)) {
            $divRows = '<tr><td colspan="5" style="text-align:center;color:#888;">No division/district data available.</td></tr>';
        } else {
            foreach ($divData as $row) {
                $divRows .= sprintf(
                    '<tr><td>%s</td><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>',
                    htmlspecialchars($row['division']     ?? 'Unknown'),
                    htmlspecialchars($row['district']     ?? 'Unknown'),
                    (int)($row['active']       ?? 0),
                    (int)($row['new']          ?? 0),
                    (int)($row['disconnected'] ?? 0)
                );
            }
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>BTRC DIS Report — {$period}</title>
<style>
  body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 20px; }
  .letterhead { display: flex; align-items: center; border-bottom: 2px solid #003366; padding-bottom: 12px; margin-bottom: 20px; }
  .letterhead img { max-height: 60px; margin-right: 16px; }
  .letterhead .company-info h1 { margin: 0; font-size: 18px; color: #003366; }
  .letterhead .company-info p  { margin: 2px 0; font-size: 11px; color: #555; }
  .report-title { text-align: center; font-size: 16px; font-weight: bold; color: #003366; margin: 16px 0 4px; }
  .report-subtitle { text-align: center; font-size: 12px; color: #555; margin-bottom: 20px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
  th { background: #003366; color: #fff; padding: 7px 10px; text-align: left; font-size: 11px; }
  td { padding: 6px 10px; border-bottom: 1px solid #ddd; font-size: 11px; }
  tr:nth-child(even) td { background: #f5f8ff; }
  .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 20px; }
  .summary-card { border: 1px solid #cce; border-radius: 4px; padding: 10px; text-align: center; }
  .summary-card .value { font-size: 22px; font-weight: bold; color: #003366; }
  .summary-card .label { font-size: 10px; color: #666; margin-top: 4px; }
  .signatory { margin-top: 40px; display: flex; justify-content: space-between; }
  .signatory .sig-block { text-align: center; width: 200px; }
  .signatory .sig-block .sig-line { border-top: 1px solid #333; margin-top: 40px; padding-top: 6px; font-size: 11px; }
  .footer { margin-top: 30px; border-top: 1px solid #ccc; padding-top: 8px; font-size: 10px; color: #888; text-align: center; }
</style>
</head>
<body>

<!-- Letterhead -->
<div class="letterhead">
  {$this->logoTag($logoUrl)}
  <div class="company-info">
    <h1>{$companyName}</h1>
    <p>{$companyAddr}</p>
    <p>Phone: {$companyPhone} &nbsp;|&nbsp; Email: {$companyEmail}</p>
  </div>
</div>

<div class="report-title">BTRC Digital ISP (DIS) Monthly Report</div>
<div class="report-subtitle">Reporting Period: {$period} &nbsp;|&nbsp; Generated: {$generatedAt} by {$generatedBy}</div>

<!-- Summary Cards -->
<div class="summary-grid">
  <div class="summary-card">
    <div class="value">{$report['total_subscribers']}</div>
    <div class="label">Total Subscribers</div>
  </div>
  <div class="summary-card">
    <div class="value">{$report['new_connections']}</div>
    <div class="label">New Connections</div>
  </div>
  <div class="summary-card">
    <div class="value">{$report['disconnections']}</div>
    <div class="label">Disconnections</div>
  </div>
  <div class="summary-card">
    <div class="value">{$report['active_subscribers']}</div>
    <div class="label">Active Subscribers</div>
  </div>
</div>

<!-- Revenue Summary -->
<table>
  <thead>
    <tr>
      <th>Revenue Category</th>
      <th>Amount (BDT)</th>
    </tr>
  </thead>
  <tbody>
    <tr><td>Total Revenue</td><td>৳ {$this->fmt($report['total_revenue'])}</td></tr>
    <tr><td>New Connection Revenue</td><td>৳ {$this->fmt($report['new_connection_revenue'])}</td></tr>
    <tr><td>Monthly Bill Revenue</td><td>৳ {$this->fmt($report['monthly_bill_revenue'])}</td></tr>
  </tbody>
</table>

<!-- Division/District Breakdown -->
<table>
  <thead>
    <tr>
      <th>Division</th>
      <th>District</th>
      <th>Active Subscribers</th>
      <th>New Connections</th>
      <th>Disconnections</th>
    </tr>
  </thead>
  <tbody>
    {$divRows}
  </tbody>
</table>

<!-- Authorised Signatory -->
<div class="signatory">
  <div class="sig-block">
    <div class="sig-line">Prepared By</div>
  </div>
  <div class="sig-block">
    <div class="sig-line">Checked By</div>
  </div>
  <div class="sig-block">
    <div class="sig-line">Authorised Signatory</div>
  </div>
</div>

<div class="footer">
  This report is generated by the Digital ISP ERP system for submission to the Bangladesh Telecommunication Regulatory Commission (BTRC).
</div>

</body>
</html>
HTML;

        return $html;
    }

    /**
     * Return an <img> tag for the company logo, or empty string if no URL.
     */
    private function logoTag(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        return '<img src="' . $url . '" alt="Company Logo">';
    }

    /**
     * Format a number as currency string.
     */
    private function fmt(mixed $value): string
    {
        return number_format((float)$value, 2);
    }

    // ── Report Log ────────────────────────────────────────────────

    /**
     * Log a report action to btrc_report_logs.
     * Requirement 15.6: store log with user, timestamp, and period.
     *
     * @param  int|null $reportId
     * @param  string   $action        generated|exported_csv|exported_pdf|previewed|deleted
     * @param  string   $reportPeriod  Y-m-d (first day of month)
     * @param  string   $exportFormat  csv|pdf (for export actions)
     * @param  string   $filePath      Path to exported file
     * @return void
     */
    public function logAction(
        ?int $reportId,
        string $action,
        string $reportPeriod,
        string $exportFormat = '',
        string $filePath = ''
    ): void {
        try {
            $this->db->insert('btrc_report_logs', [
                'report_id'     => $reportId,
                'action'        => $action,
                'report_period' => $reportPeriod,
                'export_format' => $exportFormat ?: null,
                'file_path'     => $filePath ?: null,
                'performed_by'  => $_SESSION['user_id'] ?? null,
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $this->logError('logAction failed', $e, ['action' => $action]);
        }
    }

    /**
     * Return the generation log for a specific report.
     *
     * @param  int $reportId
     * @return array
     */
    public function getReportLogs(int $reportId): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT l.*, u.full_name AS performed_by_name
                 FROM btrc_report_logs l
                 LEFT JOIN users u ON u.id = l.performed_by
                 WHERE l.report_id = ?
                 ORDER BY l.performed_at DESC",
                [$reportId]
            );
        } catch (\Throwable $e) {
            $this->logError('getReportLogs failed', $e, ['report_id' => $reportId]);
            return [];
        }
    }

    /**
     * Return all report generation logs (for the history page).
     *
     * @param  int $limit
     * @return array
     */
    public function getAllLogs(int $limit = 100): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT l.*, u.full_name AS performed_by_name
                 FROM btrc_report_logs l
                 LEFT JOIN users u ON u.id = l.performed_by
                 ORDER BY l.performed_at DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (\Throwable $e) {
            $this->logError('getAllLogs failed', $e);
            return [];
        }
    }

    // ── Preview ───────────────────────────────────────────────────

    /**
     * Build a preview data array for a given month/year without persisting.
     * Requirement 15.5: allow preview before exporting.
     *
     * @param  int $month
     * @param  int $year
     * @return array  Preview data (same structure as a report row)
     */
    public function previewReport(int $month, int $year): array
    {
        $reportPeriod = sprintf('%04d-%02d-01', $year, $month);
        $periodEnd    = date('Y-m-t', strtotime($reportPeriod));

        // Check if a saved report already exists — return it for preview
        $existing = $this->getReportByPeriod($reportPeriod);
        if ($existing) {
            // Log the preview action
            $this->logAction((int)$existing['id'], 'previewed', $reportPeriod);
            return $existing;
        }

        // Build a transient preview without saving
        $periodStart = $reportPeriod;

        $totalRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM customers WHERE status IN ('active','suspended') AND DATE(created_at) <= ?",
            [$periodEnd]
        );
        $activeRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM customers WHERE status = 'active' AND DATE(created_at) <= ?",
            [$periodEnd]
        );
        $newRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM customers WHERE DATE(created_at) BETWEEN ? AND ?",
            [$periodStart, $periodEnd]
        );
        $disconnRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt FROM customers WHERE status IN ('cancelled','terminated') AND DATE(updated_at) BETWEEN ? AND ?",
            [$periodStart, $periodEnd]
        );
        $revenueRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE DATE(payment_date) BETWEEN ? AND ?",
            [$periodStart, $periodEnd]
        );

        $divisionData = $this->aggregateByDivisionDistrict($periodStart, $periodEnd);

        return [
            'id'                     => null,
            'report_period'          => $reportPeriod,
            'report_year'            => $year,
            'report_month'           => $month,
            'total_subscribers'      => (int)($totalRow['cnt'] ?? 0),
            'new_connections'        => (int)($newRow['cnt'] ?? 0),
            'disconnections'         => (int)($disconnRow['cnt'] ?? 0),
            'active_subscribers'     => (int)($activeRow['cnt'] ?? 0),
            'total_revenue'          => (float)($revenueRow['total'] ?? 0.0),
            'new_connection_revenue' => 0.0,
            'monthly_bill_revenue'   => (float)($revenueRow['total'] ?? 0.0),
            'division_district_data' => $divisionData,
            'status'                 => 'draft',
            'generated_by_name'      => null,
            'generated_at'           => date('Y-m-d H:i:s'),
            'is_preview'             => true,
        ];
    }
}
