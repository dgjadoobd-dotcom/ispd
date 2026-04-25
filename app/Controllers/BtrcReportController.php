<?php

/**
 * BtrcReportController — Handles all BTRC Report module HTTP requests.
 *
 * Routes are prefixed with /reports/btrc.
 * Delegates business logic to BtrcReportService.
 *
 * Requirements: 15.1–15.7
 */
class BtrcReportController
{
    private BtrcReportService $service;

    public function __construct()
    {
        $this->service = new BtrcReportService();
    }

    // ── Index / History ───────────────────────────────────────────

    /**
     * List all generated BTRC reports (history page).
     * Requirement 15.6: shows generation log.
     */
    public function index(): void
    {
        PermissionHelper::requirePermission('btrc_reports.view');

        $reports = $this->service->getReports(36);
        $logs    = $this->service->getAllLogs(50);

        $pageTitle      = 'BTRC Reports';
        $currentPage    = 'reports';
        $currentSubPage = 'btrc';
        $viewFile       = BASE_PATH . '/views/reports/btrc/index.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Generate ──────────────────────────────────────────────────

    /**
     * Show the report generation form.
     */
    public function generateForm(): void
    {
        PermissionHelper::requirePermission('btrc_reports.generate');

        $pageTitle      = 'Generate BTRC Report';
        $currentPage    = 'reports';
        $currentSubPage = 'btrc';
        $viewFile       = BASE_PATH . '/views/reports/btrc/generate.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    /**
     * Generate a BTRC DIS report for the selected month/year (POST).
     * Requirement 15.1: aggregates subscriber and revenue data.
     * Requirement 15.7: generates zero-value report when no data exists.
     */
    public function generate(): void
    {
        PermissionHelper::requirePermission('btrc_reports.generate');

        $month = (int)($_POST['month'] ?? date('n'));
        $year  = (int)($_POST['year']  ?? date('Y'));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $_SESSION['error'] = 'Invalid month or year.';
            redirect(base_url('reports/btrc/generate'));
            return;
        }

        try {
            $report = $this->service->generateReport($month, $year);
            $_SESSION['success'] = 'BTRC report generated for ' . date('F Y', mktime(0, 0, 0, $month, 1, $year)) . '.';
            redirect(base_url('reports/btrc/view/' . $report['id']));
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to generate report: ' . $e->getMessage();
            redirect(base_url('reports/btrc/generate'));
        }
    }

    // ── Preview ───────────────────────────────────────────────────

    /**
     * Preview report data for a given month/year without saving.
     * Requirement 15.5: allow preview before exporting.
     */
    public function preview(): void
    {
        PermissionHelper::requirePermission('btrc_reports.view');

        $month = (int)($_GET['month'] ?? date('n'));
        $year  = (int)($_GET['year']  ?? date('Y'));

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            $_SESSION['error'] = 'Invalid month or year.';
            redirect(base_url('reports/btrc'));
            return;
        }

        try {
            $report = $this->service->previewReport($month, $year);
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'Failed to preview report: ' . $e->getMessage();
            redirect(base_url('reports/btrc'));
            return;
        }

        $pageTitle      = 'Preview BTRC Report — ' . date('F Y', mktime(0, 0, 0, $month, 1, $year));
        $currentPage    = 'reports';
        $currentSubPage = 'btrc';
        $viewFile       = BASE_PATH . '/views/reports/btrc/preview.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── View ──────────────────────────────────────────────────────

    /**
     * View a single generated report.
     */
    public function view(int $id): void
    {
        PermissionHelper::requirePermission('btrc_reports.view');

        $report = $this->service->getReport($id);
        if (!$report) {
            $_SESSION['error'] = 'Report not found.';
            redirect(base_url('reports/btrc'));
            return;
        }

        $logs = $this->service->getReportLogs($id);

        $pageTitle      = 'BTRC Report — ' . date('F Y', strtotime($report['report_period']));
        $currentPage    = 'reports';
        $currentSubPage = 'btrc';
        $viewFile       = BASE_PATH . '/views/reports/btrc/view.php';
        require_once BASE_PATH . '/views/layouts/main.php';
    }

    // ── Finalise ──────────────────────────────────────────────────

    /**
     * Mark a report as final (POST).
     */
    public function finalise(int $id): void
    {
        PermissionHelper::requirePermission('btrc_reports.generate');

        $report = $this->service->getReport($id);
        if (!$report) {
            $_SESSION['error'] = 'Report not found.';
            redirect(base_url('reports/btrc'));
            return;
        }

        $this->service->finaliseReport($id);
        $_SESSION['success'] = 'Report marked as final.';
        redirect(base_url('reports/btrc/view/' . $id));
    }

    // ── CSV Export ────────────────────────────────────────────────

    /**
     * Export a report as CSV.
     * Requirement 15.2: CSV format matching BTRC-specified column headers and order.
     */
    public function exportCsv(int $id): void
    {
        PermissionHelper::requirePermission('btrc_reports.export');

        $report = $this->service->getReport($id);
        if (!$report) {
            $_SESSION['error'] = 'Report not found.';
            redirect(base_url('reports/btrc'));
            return;
        }

        $csv      = $this->service->buildCsv($report);
        $filename = 'BTRC_DIS_' . date('Y_m', strtotime($report['report_period'])) . '.csv';

        // Log the export action
        $this->service->logAction($id, 'exported_csv', $report['report_period'], 'csv');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        // BOM for Excel UTF-8 compatibility
        echo "\xEF\xBB\xBF";
        echo $csv;
        exit;
    }

    // ── PDF Export ────────────────────────────────────────────────

    /**
     * Export a report as PDF (HTML rendered for print/save).
     * Requirement 15.3: PDF with company letterhead and signatory fields.
     */
    public function exportPdf(int $id): void
    {
        PermissionHelper::requirePermission('btrc_reports.export');

        $report = $this->service->getReport($id);
        if (!$report) {
            $_SESSION['error'] = 'Report not found.';
            redirect(base_url('reports/btrc'));
            return;
        }

        // Load company info from settings
        $db          = Database::getInstance();
        $settings    = $db->fetchOne("SELECT * FROM settings LIMIT 1") ?? [];
        $companyInfo = [
            'name'     => $settings['company_name']  ?? 'ISP Company',
            'address'  => $settings['company_address'] ?? '',
            'phone'    => $settings['company_phone']   ?? '',
            'email'    => $settings['company_email']   ?? '',
            'logo_url' => !empty($settings['company_logo'])
                ? base_url('storage/' . $settings['company_logo'])
                : '',
        ];

        $html     = $this->service->buildPdfHtml($report, $companyInfo);
        $filename = 'BTRC_DIS_' . date('Y_m', strtotime($report['report_period'])) . '.pdf';

        // Log the export action
        $this->service->logAction($id, 'exported_pdf', $report['report_period'], 'pdf');

        // Deliver as a print-ready HTML page (browser prints to PDF).
        // If a PDF library (mPDF/Dompdf) is available, it can be wired in here.
        header('Content-Type: text/html; charset=UTF-8');
        header('Content-Disposition: inline; filename="' . $filename . '"');

        echo $html;
        echo '<script>window.onload = function(){ window.print(); }</script>';
        exit;
    }
}
