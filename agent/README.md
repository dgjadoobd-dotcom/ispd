# Digital ISP Personal Agent

Automates your daily ISP tasks — WhatsApp bot, Excel reports, billing, due reminders.

## Quick Start

```bash
# 1. Run setup (installs everything)
setup.bat

# 2. Edit config.py with your details
#    - ISP_URL, ISP_USERNAME, ISP_PASSWORD
#    - MY_WHATSAPP_NUMBER
#    - ISP_GROUPS (group names to monitor)

# 3. Start WhatsApp bot (scan QR code once)
node whatsapp_bot.js

# 4. Start daily agent (new terminal)
python daily_agent.py
```

## WhatsApp Commands

| Command | Description |
|---------|-------------|
| `!bill 01XXXXXXXXX` | Check customer bill |
| `!pay 01XXXXXXXXX 500` | Record ৳500 payment |
| `!stats` | Today's collection stats |
| `!due` | Top 10 due customers |
| `!status 01XXXXXXXXX` | Customer connection status |
| `!help` | Show all commands |

## Daily Schedule

| Time | Task |
|------|------|
| 09:00 | Morning report + Excel update |
| 09:00 | Due reminders via WhatsApp |
| 18:00 | Evening collection summary |
| 08:00 (1st) | Monthly billing automation |

## Manual Run

```bash
python daily_agent.py morning   # Run morning tasks now
python daily_agent.py due       # Send due reminders now
python daily_agent.py evening   # Send evening summary now
python daily_agent.py billing   # Run billing now
```

## Excel Report

Auto-generated at `reports/billing_report.xlsx` with 3 sheets:
- **Summary** — key stats
- **Customers** — all customers with color coding
- **Due Customers** — sorted by due amount (highest first)
