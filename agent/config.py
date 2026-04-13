# ============================================================
# Digital ISP Personal Agent — Configuration
# Edit these values to match your setup
# ============================================================

# ── ISP ERP ──────────────────────────────────────────────────
ISP_URL      = "http://localhost/ispd/public"
ISP_USERNAME = "admin"
ISP_PASSWORD = "Admin@1234"

# ── WhatsApp ──────────────────────────────────────────────────
# Your WhatsApp number (international format, no +)
MY_WHATSAPP_NUMBER = "8801XXXXXXXXX"

# Group names to monitor (partial match, case-insensitive)
ISP_GROUPS = ["ISP", "Digital ISP", "Customers", "Support"]

# ── Excel ─────────────────────────────────────────────────────
EXCEL_FILE   = "reports/billing_report.xlsx"
EXCEL_BACKUP = "reports/backups/"

# ── Schedule ──────────────────────────────────────────────────
MORNING_TIME  = "09:00"   # Daily morning tasks
EVENING_TIME  = "18:00"   # Daily evening summary
BILLING_TIME  = "08:00"   # Auto billing run (1st of month)

# ── Notifications ─────────────────────────────────────────────
# Send WhatsApp message to yourself when tasks complete
NOTIFY_SELF = True

# ── Due Reminder Settings ─────────────────────────────────────
DUE_REMINDER_DAYS_BEFORE = 3   # Remind X days before due date
DUE_REMINDER_MESSAGE = (
    "Dear {name}, your internet bill of ৳{amount} is due on {date}. "
    "Please pay to avoid service interruption. "
    "Pay online: {portal_url}"
)
