<?php
header('Content-Type: text/plain');
define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/app.php';
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/app/Services/MikroTikService.php';
$billing = Database::getInstance('default');
$mt = new MikroTikService(['ip'=>'180.149.232.33','port'=>1121,'username'=>'test','password'=>'test123','timeout'=>15]);
if (!$mt->connect()) { echo "FAILED\n"; exit; }
$raw = $mt->communicate(['/ppp/active/print','=.proplist=name,address,caller-id']);
$sessions = $mt->parseResponse($raw);
echo count($sessions)." sessions\n";
$synced=$noMac=$updated=0;
foreach ($sessions as $s) {
    $u=trim($s['name']??''); $mac=strtoupper(trim($s['caller-id']??'')); $ip=trim($s['address']??'');
    if(!$u) continue;
    $cust=$billing->fetchOne("SELECT id FROM customers WHERE pppoe_username=?",[$u]);
    if($cust){$billing->update('customers',['current_ip'=>$ip,'last_online_at'=>date('Y-m-d H:i:s')],'id=?',[$cust['id']]);$updated++;}
    if(strlen($mac)<11){$noMac++;continue;}
    $ex=$billing->fetchOne("SELECT id FROM mac_bindings WHERE username=? AND mac_address=?",[$u,$mac]);
    if(!$ex){$billing->insert('mac_bindings',['username'=>$u,'mac_address'=>$mac,'caller_id'=>$mac,'nas_id'=>2,'customer_id'=>$cust['id']??null,'is_active'=>1,'is_allowed'=>1,'description'=>'sync '.date('Y-m-d'),'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);$synced++;}
}
$mt->disconnect();
echo "Updated IPs: $updated | New MACs: $synced | No MAC: $noMac\n";
$t=$billing->fetchOne("SELECT COUNT(*) as c FROM mac_bindings")['c'];
$u2=$billing->fetchOne("SELECT COUNT(DISTINCT username) as c FROM mac_bindings")['c'];
echo "Total bindings: $t | Unique users: $u2\n\n";
$rows=$billing->fetchAll("SELECT username, mac_address FROM mac_bindings ORDER BY username LIMIT 50");
foreach($rows as $r) echo $r['username']." | ".$r['mac_address']."\n";
