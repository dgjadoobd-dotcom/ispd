$c = Get-Content views/layouts/main.php
$n = @()

$supabase_lib = '    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>'

$supabase_init = @'
<script>
// -- SUPABASE --
const supabaseEnabled = <?= env('SUPABASE_ENABLED', false) ? 'true' : 'false' ?>;
let supabase = null;

if (supabaseEnabled) {
    const supabaseUrl = '<?= env('SUPABASE_URL') ?>';
    const supabaseKey = '<?= env('SUPABASE_ANON_KEY') ?>';
    if (supabaseUrl && supabaseKey) {
        // Fix for supabasejs not being defined if script loads late
        if (typeof supabasejs !== 'undefined') {
            supabase = supabasejs.createClient(supabaseUrl, supabaseKey);
            console.log('Supabase Realtime Initialized');

            // Example: Real-time listener for Support Tickets
            supabase
                .channel('public:support_tickets')
                .on('postgres_changes', { event: 'INSERT', schema: 'public', table: 'support_tickets' }, payload => {
                    console.log('New Ticket Received:', payload.new);
                    showNotification('New Support Ticket', `Ticket #${payload.new.id}: ${payload.new.subject}`);
                    if (typeof updateNotifCount === 'function') updateNotifCount();
                })
                .subscribe();
        } else {
            console.error('Supabase library not loaded');
        }
    }
}

function showNotification(title, message) {
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-white dark:bg-zinc-900 border-l-4 border-blue-600 shadow-xl p-4 rounded-lg flex items-start gap-4 z-[9999] transition-all transform translate-y-10 opacity-0';
    toast.innerHTML = `
        <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-full text-blue-600">
            <i class="fa-solid fa-bell"></i>
        </div>
        <div class="flex-1">
            <div class="text-sm font-bold text-gray-900 dark:text-white">${title}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${message}</div>
        </div>
        <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button>
    `;
    document.body.appendChild(toast);
    setTimeout(() => { toast.classList.remove('translate-y-10', 'opacity-0'); }, 100);
    setTimeout(() => { 
        toast.classList.add('translate-y-10', 'opacity-0');
        setTimeout(() => toast.remove(), 500);
    }, 10000);
}

async function updateNotifCount() {
    const badge = document.querySelector('.notif-badge');
    if (badge) {
        let count = parseInt(badge.textContent) || 0;
        badge.textContent = count + 1;
        badge.classList.remove('hidden');
    }
}
</script>
'@

foreach($l in $c){
    if($l -like '*</head>*'){
        $n += $supabase_lib
    }
    if($l -like '*</body>*'){
        $n += $supabase_init
    }
    $n += $l
}
$n | Set-Content views/layouts/main.php
