# Payment Production SOP (Personal bKash + Self-Hosted)

## Scope
This SOP is for production operation at:
- Billing: `http://billing.bkdnet.xyz:8088`
- PipraPay: `http://piprapay.bkdnet.xyz:8090`

## 1) Required Settings

In `Settings -> Payment Gateways`:

1. Personal collection mode:
- Keep API bKash gateway disabled unless merchant API credentials are available.

2. Self-Hosted PipraPay:
- Enable `Self-Hosted PipraPay`
- Set a strong `Webhook Secret` (minimum 16 chars; 32+ recommended)
- Enable `Auto Billing` if you want retry automation
- Set retry attempts and interval according to policy

## 2) Windows Scheduler Setup (XAMPP)

Run once as Administrator:

```bat
C:\xampp\htdocs\ispd\scripts\windows\register_selfhosted_piprapay_task.bat
```

This creates task:
- Name: `ISPD SelfHosted PipraPay Cron`
- Frequency: every 15 minutes
- Runner: `scripts\windows\run_selfhosted_piprapay_cron.bat`

Log file:
- `C:\xampp\htdocs\ispd\storage\logs\selfhosted_piprapay_cron.log`

## 3) Daily Operations Checklist

1. Open automation log and confirm last cron runs are successful.
2. Review failed payment retries and handle exceptions.
3. Verify no duplicate settlement for same transaction reference.
4. Spot-check 3 paid invoices:
- invoice status
- payment reference
- customer due amount

## 4) Manual Verification (Personal bKash)

Before marking payment approved:
1. Match amount
2. Match sender number
3. Match transaction reference (trxID)
4. Confirm trxID has not been used before
5. Record collector and timestamp

## 5) Incident Response

If automation fails:
1. Check cron log file
2. Run manual command:
```bat
C:\xampp\php\php.exe C:\xampp\htdocs\ispd\cron_selfhosted_piprapay.php
```
3. If still failing, disable auto billing temporarily and continue manual verification mode.

## 6) Security Requirements

1. Keep `APP_DEBUG=false` in production.
2. Keep webhook secret private; rotate quarterly.
3. Restrict admin panel access by IP where possible.
4. Enforce strong admin passwords and periodic rotation.
5. Never share payment secrets via chat or screenshots.
