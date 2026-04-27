<?php
/**
 * FCNCHBD ISP ERP — Demo Excel File Generator
 *
 * Generates sample import/export Excel (.xlsx) files for:
 *   1. customers_import_template.xlsx  — import template with demo rows
 *   2. customers_export_sample.xlsx    — export format sample
 *
 * Usage:
 *   php scripts/generate_demo_excel.php
 *
 * Output: docs/samples/
 */

define('BASE_PATH', dirname(__DIR__));

$outputDir = BASE_PATH . '/docs/samples';
if (!is_dir($outputDir)) mkdir($outputDir, 0755, true);

// ── Minimal XLSX writer (no external library) ─────────────────────────────────
class SimpleXlsx {
    private array $sheets = [];
    private string $currentSheet = '';

    public function addSheet(string $name): void {
        $this->currentSheet = $name;
        $this->sheets[$name] = ['rows' => [], 'colWidths' => []];
    }

    public function addRow(array $cells, array $styles = []): void {
        $this->sheets[$this->currentSheet]['rows'][] = ['cells' => $cells, 'styles' => $styles];
    }

    public function setColWidths(array $widths): void {
        $this->sheets[$this->currentSheet]['colWidths'] = $widths;
    }

    public function save(string $path): void {
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Cannot create: $path");
        }

        $sheetNames = array_keys($this->sheets);
        $sheetXmls  = [];
        $sharedStrings = [];
        $ssIndex = [];

        // Build shared strings
        foreach ($this->sheets as $sheet) {
            foreach ($sheet['rows'] as $row) {
                foreach ($row['cells'] as $cell) {
                    $v = (string)$cell;
                    if (!is_numeric($v) && $v !== '') {
                        if (!isset($ssIndex[$v])) {
                            $ssIndex[$v] = count($sharedStrings);
                            $sharedStrings[] = $v;
                        }
                    }
                }
            }
        }

        // Shared strings XML
        $ssXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" count="'
            . count($sharedStrings) . '" uniqueCount="' . count($sharedStrings) . '">';
        foreach ($sharedStrings as $s) {
            $ssXml .= '<si><t xml:space="preserve">' . htmlspecialchars($s, ENT_XML1) . '</t></si>';
        }
        $ssXml .= '</sst>';

        // Styles XML — header (bold+bg), normal, number, date
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="3">
    <font><sz val="10"/><name val="Calibri"/></font>
    <font><b/><sz val="10"/><name val="Calibri"/><color rgb="FFFFFFFF"/></font>
    <font><b/><sz val="11"/><name val="Calibri"/><color rgb="FF1F3864"/></font>
  </fonts>
  <fills count="4">
    <fill><patternFill patternType="none"/></fill>
    <fill><patternFill patternType="gray125"/></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FF1F3864"/></patternFill></fill>
    <fill><patternFill patternType="solid"><fgColor rgb="FFE8F0FE"/></patternFill></fill>
  </fills>
  <borders count="2">
    <border><left/><right/><top/><bottom/><diagonal/></border>
    <border>
      <left style="thin"><color rgb="FFB0C4DE"/></left>
      <right style="thin"><color rgb="FFB0C4DE"/></right>
      <top style="thin"><color rgb="FFB0C4DE"/></top>
      <bottom style="thin"><color rgb="FFB0C4DE"/></bottom>
    </border>
  </borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="6">
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    <xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf>
    <xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/>
    <xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/>
    <xf numFmtId="4" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyNumberFormat="1"/>
    <xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/>
  </cellXfs>
</styleSheet>';

        // Build sheet XMLs
        foreach ($this->sheets as $sheetName => $sheet) {
            $xml  = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
            $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

            // Column widths
            if (!empty($sheet['colWidths'])) {
                $xml .= '<cols>';
                foreach ($sheet['colWidths'] as $i => $w) {
                    $col = $i + 1;
                    $xml .= '<col min="' . $col . '" max="' . $col . '" width="' . $w . '" customWidth="1"/>';
                }
                $xml .= '</cols>';
            }

            $xml .= '<sheetData>';
            foreach ($sheet['rows'] as $ri => $row) {
                $rowNum = $ri + 1;
                $xml .= '<row r="' . $rowNum . '" ht="18" customHeight="1">';
                foreach ($row['cells'] as $ci => $cell) {
                    $col    = $this->colLetter($ci);
                    $ref    = $col . $rowNum;
                    $style  = $row['styles'][$ci] ?? 0;
                    $v      = (string)$cell;

                    if ($v === '') {
                        $xml .= '<c r="' . $ref . '" s="' . $style . '"/>';
                    } elseif (is_numeric($v) && !str_starts_with($v, '0')) {
                        $xml .= '<c r="' . $ref . '" t="n" s="' . $style . '"><v>' . $v . '</v></c>';
                    } else {
                        $si   = $ssIndex[$v] ?? 0;
                        $xml .= '<c r="' . $ref . '" t="s" s="' . $style . '"><v>' . $si . '</v></c>';
                    }
                }
                $xml .= '</row>';
            }
            $xml .= '</sheetData>';
            $xml .= '<sheetViews><sheetView workbookViewId="0"><selection activeCell="A2"/></sheetView></sheetViews>';
            $xml .= '</worksheet>';
            $sheetXmls[$sheetName] = $xml;
        }

        // workbook.xml
        $wbXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            . 'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>';
        foreach ($sheetNames as $i => $name) {
            $wbXml .= '<sheet name="' . htmlspecialchars($name, ENT_XML1) . '" sheetId="' . ($i+1) . '" r:id="rId' . ($i+1) . '"/>';
        }
        $wbXml .= '</sheets></workbook>';

        // workbook rels
        $wbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">';
        foreach ($sheetNames as $i => $name) {
            $wbRels .= '<Relationship Id="rId' . ($i+1) . '" '
                . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" '
                . 'Target="worksheets/sheet' . ($i+1) . '.xml"/>';
        }
        $wbRels .= '<Relationship Id="rId' . (count($sheetNames)+1) . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" '
            . 'Target="sharedStrings.xml"/>'
            . '<Relationship Id="rId' . (count($sheetNames)+2) . '" '
            . 'Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" '
            . 'Target="styles.xml"/>'
            . '</Relationships>';

        // [Content_Types].xml
        $ct = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>';
        foreach ($sheetNames as $i => $name) {
            $ct .= '<Override PartName="/xl/worksheets/sheet' . ($i+1) . '.xml" '
                . 'ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        $ct .= '</Types>';

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $zip->addFromString('[Content_Types].xml', $ct);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $wbXml);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $wbRels);
        $zip->addFromString('xl/sharedStrings.xml', $ssXml);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        foreach ($sheetNames as $i => $name) {
            $zip->addFromString('xl/worksheets/sheet' . ($i+1) . '.xml', $sheetXmls[$name]);
        }
        $zip->close();
    }

    private function colLetter(int $index): string {
        $letter = '';
        $index++;
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index  = (int)($index / 26);
        }
        return $letter;
    }
}

// ── Style constants ───────────────────────────────────────────────────────────
// 0 = normal+border, 1 = header (bold white on dark blue), 2 = alt row (light blue bg)
// 3 = normal, 4 = number, 5 = title

// ── FILE 1: customers_import_template.xlsx ────────────────────────────────────
$xlsx1 = new SimpleXlsx();

// Sheet 1: Import Template
$xlsx1->addSheet('Customer Import');
$xlsx1->setColWidths([8, 14, 22, 16, 18, 16, 16, 14, 14, 14, 14, 14, 14, 20, 12]);

// Title row
$xlsx1->addRow(['FCNCHBD ISP ERP — Customer Import Template', '', '', '', '', '', '', '', '', '', '', '', '', '', ''], array_fill(0, 15, 5));

// Header row (style 1 = bold white on dark blue)
$headers = [
    'full_name *', 'phone *', 'address *', 'package_name', 'zone_name',
    'area_name', 'connection_type', 'pppoe_username', 'pppoe_password',
    'static_ip', 'email', 'monthly_charge', 'billing_day', 'connection_date', 'notes'
];
$xlsx1->addRow($headers, array_fill(0, 15, 1));

// Demo rows (alternating style 0 / style 2)
$demoCustomers = [
    ['Md. Rahim Uddin',    '01711234567', 'House 12, Road 4, Mirpur-1, Dhaka',    '10 Mbps',  'Mirpur Zone',  'Mirpur-1',  'pppoe', 'rahim.uddin',   'Pass@1234', '',              'rahim@gmail.com',  '500',  '1',  '2026-01-01', ''],
    ['Fatema Begum',       '01812345678', 'Flat 3B, Green Tower, Uttara, Dhaka',   '20 Mbps',  'Uttara Zone',  'Uttara-4',  'pppoe', 'fatema.begum',  'Pass@5678', '',              '',                 '800',  '5',  '2026-01-15', 'New connection'],
    ['Karim Hossain',      '01912345678', 'Village: Kaliganj, Gazipur',            '5 Mbps',   'Gazipur Zone', 'Kaliganj',  'pppoe', 'karim.h',       'Kh@2024',   '',              'karim@yahoo.com',  '300',  '1',  '2026-02-01', ''],
    ['Nasrin Akter',       '01611234567', 'Plot 7, Block C, Bashundhara R/A',      '50 Mbps',  'Bashundhara',  'Block-C',   'pppoe', 'nasrin.akter',  'Na@9999',   '192.168.1.100', 'nasrin@bd.com',    '1500', '10', '2026-02-10', 'Static IP assigned'],
    ['Jahangir Alam',      '01511234567', 'Holding 45, Ward 12, Narayanganj',      '10 Mbps',  'Narayanganj',  'Ward-12',   'pppoe', 'jahangir.a',    'Ja@2024',   '',              '',                 '500',  '1',  '2026-03-01', ''],
    ['Sumaiya Khanam',     '01311234567', 'House 8, Lane 3, Dhanmondi, Dhaka',     '30 Mbps',  'Dhanmondi',    'Dhanmondi', 'pppoe', 'sumaiya.k',     'Sk@2024',   '',              'sumaiya@gmail.com','1000', '15', '2026-03-05', ''],
    ['Rafiqul Islam',      '01411234567', 'Mawna, Sreepur, Gazipur',               '5 Mbps',   'Gazipur Zone', 'Sreepur',   'pppoe', 'rafiqul.i',     'Ri@2024',   '',              '',                 '300',  '1',  '2026-03-10', ''],
    ['Taslima Parvin',     '01711111111', 'Sector 7, Uttara, Dhaka',               '20 Mbps',  'Uttara Zone',  'Uttara-7',  'pppoe', 'taslima.p',     'Tp@2024',   '',              'taslima@bd.com',   '800',  '1',  '2026-03-15', ''],
    ['Monir Hossain',      '01811111111', 'Chowrasta, Comilla Sadar',              '10 Mbps',  'Comilla Zone', 'Sadar',     'pppoe', 'monir.h',       'Mh@2024',   '',              '',                 '500',  '1',  '2026-04-01', ''],
    ['Roksana Begum',      '01911111111', 'Agrabad, Chittagong',                   '50 Mbps',  'Chittagong',   'Agrabad',   'pppoe', 'roksana.b',     'Rb@2024',   '10.0.0.50',     'roksana@ctg.com',  '1500', '5',  '2026-04-05', 'VIP customer'],
];

foreach ($demoCustomers as $i => $row) {
    $style = $i % 2 === 0 ? 0 : 2;
    $xlsx1->addRow($row, array_fill(0, 15, $style));
}

// Sheet 2: Field Reference
$xlsx1->addSheet('Field Reference');
$xlsx1->setColWidths([20, 12, 12, 35, 25]);

$xlsx1->addRow(['FCNCHBD ISP ERP — Import Field Reference', '', '', '', ''], array_fill(0, 5, 5));
$xlsx1->addRow(['Column Name', 'Required', 'Type', 'Description', 'Example'], array_fill(0, 5, 1));

$fields = [
    ['full_name',       'YES', 'Text',    'Customer full name (max 150 chars)',                    'Md. Rahim Uddin'],
    ['phone',           'YES', 'Text',    'Primary phone (BD format: 01XXXXXXXXX)',                '01711234567'],
    ['address',         'YES', 'Text',    'Full address',                                          'House 12, Mirpur-1, Dhaka'],
    ['package_name',    'No',  'Text',    'Must match exactly a package name in Settings',         '10 Mbps'],
    ['zone_name',       'No',  'Text',    'Must match exactly a zone name in Settings',            'Mirpur Zone'],
    ['area_name',       'No',  'Text',    'Must match exactly an area name in Settings',           'Mirpur-1'],
    ['connection_type', 'No',  'Text',    'pppoe or fiber (default: pppoe)',                       'pppoe'],
    ['pppoe_username',  'No',  'Text',    'PPPoE login username (unique)',                         'rahim.uddin'],
    ['pppoe_password',  'No',  'Text',    'PPPoE password (min 6 chars)',                          'Pass@1234'],
    ['static_ip',       'No',  'IP',      'Static IP address (leave blank for dynamic)',           '192.168.1.100'],
    ['email',           'No',  'Email',   'Customer email address',                                'rahim@gmail.com'],
    ['monthly_charge',  'No',  'Number',  'Monthly bill amount in BDT (overrides package price)', '500'],
    ['billing_day',     'No',  'Number',  'Day of month for billing (1-28, default: 1)',           '1'],
    ['connection_date', 'No',  'Date',    'Connection date (YYYY-MM-DD format)',                   '2026-01-01'],
    ['notes',           'No',  'Text',    'Internal notes (not shown to customer)',                'New connection'],
];

foreach ($fields as $i => $row) {
    $style = $i % 2 === 0 ? 0 : 2;
    $xlsx1->addRow($row, array_fill(0, 5, $style));
}

// Sheet 3: Rules
$xlsx1->addSheet('Import Rules');
$xlsx1->setColWidths([60]);
$xlsx1->addRow(['FCNCHBD ISP ERP — Import Rules & Notes'], [5]);
$xlsx1->addRow([''], [0]);
$rules = [
    '1.  Columns marked * are REQUIRED. Rows missing these will be skipped.',
    '2.  The first row is the header row — do NOT delete or rename headers.',
    '3.  package_name must exactly match a package in Settings → Packages.',
    '4.  zone_name must exactly match a zone in Settings → Zones.',
    '5.  Phone numbers must be in Bangladeshi format: 01XXXXXXXXX (11 digits).',
    '6.  pppoe_username must be unique across all customers.',
    '7.  connection_date format: YYYY-MM-DD (e.g. 2026-01-15).',
    '8.  monthly_charge: if blank, the package price is used automatically.',
    '9.  static_ip: leave blank unless the customer has a reserved IP.',
    '10. Maximum 1000 rows per import file.',
    '11. Save as CSV (.csv) or Excel (.xlsx) before uploading.',
    '12. File encoding must be UTF-8 (important for Bangla names).',
];
foreach ($rules as $rule) {
    $xlsx1->addRow([$rule], [0]);
}

$file1 = $outputDir . '/customers_import_template.xlsx';
$xlsx1->save($file1);
echo "Created: customers_import_template.xlsx (" . round(filesize($file1)/1024, 1) . " KB)\n";

// ── FILE 2: customers_export_sample.xlsx ─────────────────────────────────────
$xlsx2 = new SimpleXlsx();
$xlsx2->addSheet('Customer Export');
$xlsx2->setColWidths([6, 12, 22, 16, 18, 16, 30, 12, 12, 20]);

$xlsx2->addRow(['FCNCHBD ISP ERP — Customer Export Sample', '', '', '', '', '', '', '', '', ''], array_fill(0, 10, 5));

// Export headers (exact match to exportCsv() in CustomerController)
$exportHeaders = ['ID', 'Code', 'Full Name', 'Phone', 'Package', 'Zone', 'Address', 'Status', 'Due', 'Created At'];
$xlsx2->addRow($exportHeaders, array_fill(0, 10, 1));

$exportRows = [
    ['1',  'CUST-0001', 'Md. Rahim Uddin',  '01711234567', '10 Mbps',  'Mirpur Zone',  'House 12, Road 4, Mirpur-1, Dhaka',    'active',    '0.00',    '2026-01-01 10:00:00'],
    ['2',  'CUST-0002', 'Fatema Begum',      '01812345678', '20 Mbps',  'Uttara Zone',  'Flat 3B, Green Tower, Uttara, Dhaka',  'active',    '800.00',  '2026-01-15 11:30:00'],
    ['3',  'CUST-0003', 'Karim Hossain',     '01912345678', '5 Mbps',   'Gazipur Zone', 'Village: Kaliganj, Gazipur',           'active',    '0.00',    '2026-02-01 09:00:00'],
    ['4',  'CUST-0004', 'Nasrin Akter',      '01611234567', '50 Mbps',  'Bashundhara',  'Plot 7, Block C, Bashundhara R/A',     'active',    '0.00',    '2026-02-10 14:00:00'],
    ['5',  'CUST-0005', 'Jahangir Alam',     '01511234567', '10 Mbps',  'Narayanganj',  'Holding 45, Ward 12, Narayanganj',     'suspended', '1000.00', '2026-03-01 08:00:00'],
    ['6',  'CUST-0006', 'Sumaiya Khanam',    '01311234567', '30 Mbps',  'Dhanmondi',    'House 8, Lane 3, Dhanmondi, Dhaka',    'active',    '0.00',    '2026-03-05 10:00:00'],
    ['7',  'CUST-0007', 'Rafiqul Islam',     '01411234567', '5 Mbps',   'Gazipur Zone', 'Mawna, Sreepur, Gazipur',              'active',    '300.00',  '2026-03-10 09:30:00'],
    ['8',  'CUST-0008', 'Taslima Parvin',    '01711111111', '20 Mbps',  'Uttara Zone',  'Sector 7, Uttara, Dhaka',              'active',    '0.00',    '2026-03-15 11:00:00'],
    ['9',  'CUST-0009', 'Monir Hossain',     '01811111111', '10 Mbps',  'Comilla Zone', 'Chowrasta, Comilla Sadar',             'active',    '500.00',  '2026-04-01 08:00:00'],
    ['10', 'CUST-0010', 'Roksana Begum',     '01911111111', '50 Mbps',  'Chittagong',   'Agrabad, Chittagong',                  'active',    '0.00',    '2026-04-05 10:00:00'],
];

foreach ($exportRows as $i => $row) {
    $style = $i % 2 === 0 ? 0 : 2;
    $xlsx2->addRow($row, array_fill(0, 10, $style));
}

$file2 = $outputDir . '/customers_export_sample.xlsx';
$xlsx2->save($file2);
echo "Created: customers_export_sample.xlsx (" . round(filesize($file2)/1024, 1) . " KB)\n";

// ── FILE 3: customers_import_template.csv ────────────────────────────────────
// Plain CSV version for users without Excel
$csvFile = $outputDir . '/customers_import_template.csv';
$fp = fopen($csvFile, 'w');
// UTF-8 BOM for Excel compatibility
fwrite($fp, "\xEF\xBB\xBF");
fputcsv($fp, ['full_name', 'phone', 'address', 'package_name', 'zone_name', 'area_name',
              'connection_type', 'pppoe_username', 'pppoe_password', 'static_ip',
              'email', 'monthly_charge', 'billing_day', 'connection_date', 'notes']);
foreach ($demoCustomers as $row) {
    fputcsv($fp, $row);
}
fclose($fp);
echo "Created: customers_import_template.csv (" . round(filesize($csvFile)/1024, 1) . " KB)\n";

echo "\nAll sample files saved to: docs/samples/\n";
echo "  - customers_import_template.xlsx  (import with 3 sheets: data, field reference, rules)\n";
echo "  - customers_export_sample.xlsx    (export format sample)\n";
echo "  - customers_import_template.csv   (CSV version for non-Excel users)\n";
