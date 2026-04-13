#!/usr/bin/env python3
# ============================================================
# Digital ISP Daily Agent
# Runs all automated tasks on schedule
#
# Install: pip install requests openpyxl schedule
# Run:     python daily_agent.py
# ============================================================

import os
import sys
import time
import schedule
import requests
import subprocess
from datetime import datetime, date

# Add agent dir to path
sys.path.insert(0, os.path.dirname(__file__))

from config import (
    MORNING_TIME, EVENING_TIME, BILLING_TIME,
    DUE_REMINDER_DAYS_BEFORE, DUE_REMINDER_MESSAGE,
    NOTIFY_SELF, MY_WHATSAPP_NUMBER, ISP_URL
)
from isp_api import IspApi
from excel_manager import update_billing_report

api = IspApi()

def log(msg: str):
    ts = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
    print(f"[{ts}] {msg}")
    # Append to log file
    os.makedirs('logs', exist_ok=True)
    with open('logs/agent.log', 'a', encoding='utf-8') as f:
        f.write(f"[{ts}] {msg}\n")

# ── Task: Morning Report ──────────────────────────────────────
def task_morning_report():
    log("🌅 Starting morning tasks...")

    if not api.login():
        log("❌ Cannot login to ISP API — skipping morning tasks")
        return

    # 1. Get stats
    stats = api.get_stats()
    log(f"📊 Stats: {stats.get('total_customers')} customers | "
        f"Today: ৳{stats.get('today_collection', 0):.2f} | "
        f"Due: ৳{stats.get('total_due', 0):.2f}")

    # 2. Get all customers
    customers = api.search_customer("")
    log(f"👥 Fetched {len(customers)} customers")

    # 3. Update Excel
    excel_path = update_billing_report(customers, stats)
    log(f"📊 Excel updated: {excel_path}")

    # 4. Send WhatsApp summary to yourself
    if NOTIFY_SELF:
        msg = (
            f"🌅 *Morning Report — {date.today().strftime('%d %b %Y')}*\n"
            f"━━━━━━━━━━━━━━━━━━\n"
            f"👥 Customers: {stats.get('total_customers', 0)}\n"
            f"✅ Active: {stats.get('active', 0)}\n"
            f"⛔ Suspended: {stats.get('suspended', 0)}\n"
            f"💰 Today: ৳{stats.get('today_collection', 0):,.2f}\n"
            f"📅 Month: ৳{stats.get('month_collection', 0):,.2f}\n"
            f"🔴 Total Due: ৳{stats.get('total_due', 0):,.2f}\n"
            f"━━━━━━━━━━━━━━━━━━\n"
            f"📊 Excel report updated ✅"
        )
        send_whatsapp_to_self(msg)

    log("✅ Morning tasks complete")

# ── Task: Due Reminders ───────────────────────────────────────
def task_due_reminders():
    log("📨 Sending due reminders...")

    if not api.token and not api.login():
        return

    due_customers = api.get_due_customers(DUE_REMINDER_DAYS_BEFORE)
    log(f"🔴 Found {len(due_customers)} customers with dues")

    sent = 0
    for c in due_customers:
        if float(c.get('due_amount', 0)) <= 0:
            continue

        # Get their latest invoice for due date
        invoices = api.get_customer_invoices(c['id'])
        unpaid   = [i for i in invoices if i['status'] != 'paid']
        due_date = unpaid[0]['due_date'] if unpaid else 'N/A'

        msg = DUE_REMINDER_MESSAGE.format(
            name       = c['full_name'],
            amount     = f"{float(c['due_amount']):,.2f}",
            date       = due_date,
            portal_url = f"{ISP_URL}/portal/login",
        )

        # Send via WhatsApp bot API (if bot is running)
        success = send_whatsapp_message(c['phone'], msg)
        if success:
            sent += 1
            log(f"  ✉ Sent to {c['full_name']} ({c['phone']})")
        time.sleep(2)  # avoid spam

    log(f"✅ Due reminders sent: {sent}/{len(due_customers)}")

# ── Task: Evening Summary ─────────────────────────────────────
def task_evening_summary():
    log("🌆 Evening summary...")

    if not api.token and not api.login():
        return

    collections = api.get_today_collections()
    stats       = api.get_stats()

    msg = (
        f"🌆 *Evening Summary — {date.today().strftime('%d %b %Y')}*\n"
        f"━━━━━━━━━━━━━━━━━━\n"
        f"💰 Today Collected: ৳{collections.get('total', 0):,.2f}\n"
        f"🧾 Payments: {collections.get('count', 0)}\n"
        f"📅 Month Total: ৳{stats.get('month_collection', 0):,.2f}\n"
        f"🔴 Still Due: ৳{stats.get('total_due', 0):,.2f}\n"
        f"━━━━━━━━━━━━━━━━━━"
    )

    if NOTIFY_SELF:
        send_whatsapp_to_self(msg)

    log("✅ Evening summary sent")

# ── Task: Monthly Billing (1st of month) ─────────────────────
def task_monthly_billing():
    if date.today().day != 1:
        return

    log("💳 Running monthly billing automation...")

    if not api.token and not api.login():
        return

    success = api.run_billing_automation()
    status  = "✅ Success" if success else "❌ Failed"
    log(f"💳 Billing automation: {status}")

    if NOTIFY_SELF:
        send_whatsapp_to_self(
            f"💳 *Monthly Billing*\n"
            f"Date: {date.today().strftime('%d %b %Y')}\n"
            f"Status: {status}"
        )

# ── WhatsApp helpers ──────────────────────────────────────────
def send_whatsapp_to_self(message: str) -> bool:
    return send_whatsapp_message(MY_WHATSAPP_NUMBER, message)

def send_whatsapp_message(phone: str, message: str) -> bool:
    """Send via local WhatsApp bot HTTP API (whatsapp_bot.js must be running)"""
    try:
        # Clean phone number
        phone = phone.replace('+', '').replace('-', '').replace(' ', '')
        if not phone.endswith('@c.us'):
            phone = f"{phone}@c.us"

        r = requests.post("http://localhost:3001/send", json={
            "to":      phone,
            "message": message,
        }, timeout=10)
        return r.status_code == 200
    except Exception as e:
        log(f"⚠ WhatsApp send failed ({phone}): {e}")
        return False

# ── Manual run ────────────────────────────────────────────────
def run_now(task_name: str):
    tasks = {
        'morning':  task_morning_report,
        'evening':  task_evening_summary,
        'due':      task_due_reminders,
        'billing':  task_monthly_billing,
    }
    if task_name in tasks:
        log(f"▶ Running task manually: {task_name}")
        tasks[task_name]()
    else:
        print(f"Unknown task. Available: {', '.join(tasks.keys())}")

# ── Scheduler ─────────────────────────────────────────────────
def start_scheduler():
    log("🤖 Digital ISP Daily Agent started")
    log(f"   Morning tasks:  {MORNING_TIME}")
    log(f"   Evening report: {EVENING_TIME}")
    log(f"   Due reminders:  {MORNING_TIME} daily")
    log(f"   Billing:        {BILLING_TIME} (1st of month)")

    schedule.every().day.at(MORNING_TIME).do(task_morning_report)
    schedule.every().day.at(MORNING_TIME).do(task_due_reminders)
    schedule.every().day.at(EVENING_TIME).do(task_evening_summary)
    schedule.every().day.at(BILLING_TIME).do(task_monthly_billing)

    log("⏰ Scheduler running — press Ctrl+C to stop\n")

    while True:
        schedule.run_pending()
        time.sleep(30)

# ── Entry point ───────────────────────────────────────────────
if __name__ == '__main__':
    if len(sys.argv) > 1:
        run_now(sys.argv[1])
    else:
        start_scheduler()
