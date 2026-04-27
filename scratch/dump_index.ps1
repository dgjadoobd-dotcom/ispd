$c = Get-Content views/dashboard/index.php
for($i=0; $i -lt $c.Length; $i++) {
    Write-Output "$($i+1): $($c[$i])"
}
