$c = Get-Content views/dashboard/index.php
$n = @()

$activity_html = @'
<!-- Real-time Activity Feed -->
<div class="card" style="margin-bottom:24px; display:none;" id="activityFeedCard">
    <div style="padding:12px 16px; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex; align-items:center; gap:8px;">
            <div style="width:32px; height:32px; border-radius:6px; background:linear-gradient(135deg,var(--blue),#3b82f6); display:flex; align-items:center; justify-content:center;">
                <i class="fa-solid fa-bolt" style="color:#fff; font-size:14px;"></i>
            </div>
            <div>
                <div style="font-weight:700; font-size:14px;">Real-time System Activity</div>
                <div style="font-size:11px; color:var(--text2);">Live events from across the system</div>
            </div>
        </div>
        <div style="font-size:10px; color:var(--green); font-weight:700; display:flex; align-items:center; gap:4px;">
            <span class="status-dot dot-online" style="width:8px; height:8px;"></span> LIVE
        </div>
    </div>
    <div id="activityFeedList" style="max-height:320px; overflow-y:auto; padding:8px 0;">
        <div id="noActivityPlaceholder" style="padding:40px; text-align:center; color:var(--text2); font-size:13px;">
            <i class="fa-solid fa-satellite-dish" style="font-size:24px; margin-bottom:8px; display:block; opacity:0.5;"></i>
            Waiting for live events...
        </div>
    </div>
</div>
'@

foreach($l in $c){
    if($l -like '*<div class="device-grid fade-in fade-in-delay-4">*'){
        $n += $activity_html
    }
    $n += $l
}
$n | Set-Content views/dashboard/index.php
