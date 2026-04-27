/**
 * FCNCHBD ISP WhatsApp Bot
 * ─────────────────────────────────────────────────────────────
 * Commands (in ISP groups or direct message):
 *   !bill <phone>     — check customer bill
 *   !pay <phone> <amount> — record payment
 *   !stats            — today's collection stats
 *   !due              — list top 10 due customers
 *   !status <phone>   — customer connection status
 *   !help             — show all commands
 *
 * Install: npm install whatsapp-web.js qrcode-terminal axios
 * Run:     node whatsapp_bot.js
 */

const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const axios  = require('axios');

// ── Config ────────────────────────────────────────────────────
const ISP_URL      = 'http://localhost/ispd/public';
const ISP_USER     = 'admin';
const ISP_PASS     = 'Admin@1234';
const ISP_GROUPS   = ['isp', 'FCNCHBD ISP', 'customers', 'support'];  // lowercase partial match
const ADMIN_NUMBER = '8801XXXXXXXXX@c.us';  // your number — gets all alerts

// ── ISP API ───────────────────────────────────────────────────
let apiToken = null;

async function ispLogin() {
    try {
        const res = await axios.post(`${ISP_URL}/api/v1/auth/login`, {
            username: ISP_USER, password: ISP_PASS
        });
        apiToken = res.data.token;
        console.log('✅ ISP API authenticated');
        return true;
    } catch (e) {
        console.error('❌ ISP login failed:', e.message);
        return false;
    }
}

function apiHeaders() {
    return { Authorization: `Bearer ${apiToken}` };
}

async function searchCustomer(query) {
    const res = await axios.get(`${ISP_URL}/api/v1/customers/search`,
        { params: { q: query }, headers: apiHeaders() });
    return res.data.customers || [];
}

async function getStats() {
    const res = await axios.get(`${ISP_URL}/api/v1/dashboard/stats`, { headers: apiHeaders() });
    return res.data;
}

async function getCustomerInvoices(customerId) {
    const res = await axios.get(`${ISP_URL}/api/v1/customers/${customerId}/invoices`,
        { headers: apiHeaders() });
    return res.data.invoices || [];
}

async function recordPayment(invoiceId, amount, method = 'cash') {
    const res = await axios.post(`${ISP_URL}/api/v1/payments`,
        { invoice_id: invoiceId, amount, payment_method: method },
        { headers: apiHeaders() });
    return res.data;
}

// ── Format helpers ────────────────────────────────────────────
function fmtMoney(n) { return `৳${parseFloat(n || 0).toLocaleString('en-BD', { minimumFractionDigits: 2 })}`; }
function fmtDate(d)  { return d ? new Date(d).toLocaleDateString('en-BD', { day:'2-digit', month:'short', year:'numeric' }) : 'N/A'; }

// ── Command handlers ──────────────────────────────────────────
async function handleBill(phone) {
    const customers = await searchCustomer(phone);
    if (!customers.length) return `❌ No customer found for: ${phone}`;

    const c = customers[0];
    const invoices = await getCustomerInvoices(c.id);
    const unpaid   = invoices.filter(i => i.status !== 'paid');
    const latest   = unpaid[0] || invoices[0];

    return `📋 *Customer Bill*\n` +
           `━━━━━━━━━━━━━━━━━━\n` +
           `👤 ${c.full_name}\n` +
           `📱 ${c.phone}\n` +
           `🆔 ${c.customer_code}\n` +
           `📦 ${c.package_name || 'N/A'}\n` +
           `💰 Due: *${fmtMoney(c.due_amount)}*\n` +
           `📅 Due Date: ${latest ? fmtDate(latest.due_date) : 'N/A'}\n` +
           `🔴 Status: ${c.status.toUpperCase()}\n` +
           `━━━━━━━━━━━━━━━━━━\n` +
           `Pay: ${ISP_URL}/portal/login`;
}

async function handlePay(phone, amount) {
    const customers = await searchCustomer(phone);
    if (!customers.length) return `❌ No customer found for: ${phone}`;

    const c        = customers[0];
    const invoices = await getCustomerInvoices(c.id);
    const unpaid   = invoices.find(i => i.status !== 'paid');

    if (!unpaid) return `✅ ${c.full_name} has no unpaid invoices.`;

    const result = await recordPayment(unpaid.id, parseFloat(amount), 'cash');
    if (result.success) {
        return `✅ *Payment Recorded*\n` +
               `👤 ${c.full_name}\n` +
               `💵 Amount: *${fmtMoney(amount)}*\n` +
               `🧾 Receipt: ${result.receipt_number}\n` +
               `📅 ${new Date().toLocaleString('en-BD')}`;
    }
    return `❌ Payment failed: ${result.error || 'Unknown error'}`;
}

async function handleStats() {
    const s = await getStats();
    return `📊 *Today's Stats*\n` +
           `━━━━━━━━━━━━━━━━━━\n` +
           `👥 Total Customers: ${s.total_customers}\n` +
           `✅ Active: ${s.active}\n` +
           `⛔ Suspended: ${s.suspended}\n` +
           `💰 Today Collection: *${fmtMoney(s.today_collection)}*\n` +
           `📅 Month Collection: *${fmtMoney(s.month_collection)}*\n` +
           `🔴 Total Due: *${fmtMoney(s.total_due)}*\n` +
           `━━━━━━━━━━━━━━━━━━\n` +
           `🕐 ${new Date().toLocaleString('en-BD')}`;
}

async function handleDue() {
    const customers = await searchCustomer('');
    const due = customers
        .filter(c => parseFloat(c.due_amount) > 0)
        .sort((a, b) => parseFloat(b.due_amount) - parseFloat(a.due_amount))
        .slice(0, 10);

    if (!due.length) return '✅ No customers with outstanding dues!';

    let msg = `🔴 *Top Due Customers*\n━━━━━━━━━━━━━━━━━━\n`;
    due.forEach((c, i) => {
        msg += `${i + 1}. ${c.full_name} — *${fmtMoney(c.due_amount)}*\n   📱 ${c.phone}\n`;
    });
    return msg;
}

function handleHelp() {
    return `🤖 *ISP Bot Commands*\n` +
           `━━━━━━━━━━━━━━━━━━\n` +
           `!bill <phone>         — Check bill\n` +
           `!pay <phone> <amount> — Record payment\n` +
           `!stats                — Today's stats\n` +
           `!due                  — Top due list\n` +
           `!status <phone>       — Customer status\n` +
           `!help                 — This menu\n` +
           `━━━━━━━━━━━━━━━━━━\n` +
           `Powered by FCNCHBD ISP ERP`;
}

// ── WhatsApp Client ───────────────────────────────────────────
const client = new Client({
    authStrategy: new LocalAuth({ clientId: 'isp-bot' }),
    puppeteer: {
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    }
});

client.on('qr', qr => {
    console.log('\n📱 Scan this QR code with WhatsApp:\n');
    qrcode.generate(qr, { small: true });
});

client.on('ready', async () => {
    console.log('✅ WhatsApp Bot is ready!');
    await ispLogin();

    // Notify yourself
    try {
        await client.sendMessage(ADMIN_NUMBER,
            `🤖 *ISP Bot Started*\n${new Date().toLocaleString('en-BD')}\nType !help for commands.`);
    } catch (e) { /* admin number may not be set */ }
});

client.on('auth_failure', () => console.error('❌ WhatsApp auth failed'));
client.on('disconnected', reason => console.log('⚠ Disconnected:', reason));

// ── Message handler ───────────────────────────────────────────
client.on('message', async msg => {
    const body = msg.body.trim();
    if (!body.startsWith('!')) return;

    const chat    = await msg.getChat();
    const isGroup = chat.isGroup;
    const chatName = chat.name?.toLowerCase() || '';

    // Only respond in ISP groups OR direct messages
    const isIspGroup = ISP_GROUPS.some(g => chatName.includes(g));
    if (isGroup && !isIspGroup) return;

    const parts   = body.split(/\s+/);
    const command = parts[0].toLowerCase();

    console.log(`📨 Command: ${body} | From: ${msg.from} | Chat: ${chat.name || 'DM'}`);

    // Re-login if token expired
    if (!apiToken) await ispLogin();

    let reply = '';
    try {
        switch (command) {
            case '!bill':
                if (!parts[1]) { reply = '❌ Usage: !bill <phone>'; break; }
                reply = await handleBill(parts[1]);
                break;

            case '!pay':
                if (!parts[1] || !parts[2]) { reply = '❌ Usage: !pay <phone> <amount>'; break; }
                reply = await handlePay(parts[1], parts[2]);
                break;

            case '!stats':
                reply = await handleStats();
                break;

            case '!due':
                reply = await handleDue();
                break;

            case '!status':
                if (!parts[1]) { reply = '❌ Usage: !status <phone>'; break; }
                const customers = await searchCustomer(parts[1]);
                if (!customers.length) { reply = `❌ Not found: ${parts[1]}`; break; }
                const c = customers[0];
                reply = `👤 ${c.full_name}\n🔌 Status: *${c.status.toUpperCase()}*\n💰 Due: ${fmtMoney(c.due_amount)}`;
                break;

            case '!help':
                reply = handleHelp();
                break;

            default:
                return; // ignore unknown commands
        }
    } catch (e) {
        console.error('Command error:', e.message);
        reply = `❌ Error: ${e.message}`;
    }

    if (reply) await msg.reply(reply);
});

client.initialize();
console.log('🚀 Starting WhatsApp Bot...');

// ── HTTP API so Python agent can send messages ────────────────
const http = require('http');

const server = http.createServer(async (req, res) => {
    if (req.method !== 'POST' || req.url !== '/send') {
        res.writeHead(404); res.end('Not found'); return;
    }
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', async () => {
        try {
            const { to, message } = JSON.parse(body);
            await client.sendMessage(to, message);
            res.writeHead(200, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: true }));
        } catch (e) {
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, error: e.message }));
        }
    });
});

server.listen(3001, () => console.log('📡 WhatsApp HTTP API on port 3001'));
