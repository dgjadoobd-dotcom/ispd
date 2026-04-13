<?php
$db = new SQLite3(__DIR__ . '/database/digital-isp.sqlite');

$settings = [
    'selfhosted_piprapay_enabled'              => '1',
    'selfhosted_piprapay_webhook_secret'       => 'bkdnet_webhook_secret_2026',
    'selfhosted_piprapay_auto_billing_enabled' => '1',
    'selfhosted_piprapay_retry_attempts'       => '3',
    'selfhosted_piprapay_retry_interval_hours' => '24',
    'selfhosted_piprapay_checkout_url'         => 'http://localhost/ispd/paybill',
    'portal_enabled'                           => '1',
    'portal_payment_gateway'                   => 'selfhosted_piprapay',
    'company_name'                             => 'Digital ISP ERP',
    'currency'                                 => 'BDT',
];

foreach ($settings as $key => $value) {
    $exists = $db->querySingle("SELECT id FROM settings WHERE \"key\" = '$key'");
    if ($exists) {
        $db->exec("UPDATE settings SET value = '$value' WHERE \"key\" = '$key'");
        echo "Updated: $key = $value\n";
    } else {
        $db->exec("INSERT INTO settings (\"key\", value) VALUES ('$key', '$value')");
        echo "Inserted: $key = $value\n";
    }
}

echo "\nDone.\n";
