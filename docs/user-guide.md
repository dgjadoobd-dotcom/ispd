# RADIUS Service Enhancement — User Guide

**Audience:** Network Administrators  
**Version:** 1.0

---

## 1. Overview

The RADIUS Service Enhancement extends the FCNCHBD ISP ERP system with a full suite of tools for managing subscriber authentication, monitoring live network sessions, analysing usage trends, and responding to network events.

### Key features

| Feature | What it does |
|---|---|
| User Management | Add, edit, delete, and bulk-import RADIUS subscribers |
| Session Monitoring | View and terminate active PPPoE/RADIUS sessions in real time |
| Usage Analytics | Charts and summaries of bandwidth consumption and session counts |
| Alerts | Automated warnings for high session counts and failed authentication spikes |

---

## 2. User Management

### 2.1 Adding a user

1. Navigate to **RADIUS → Users → Add User**.
2. Enter the **Username** and **Password** (required).
3. Assign a **Group** (e.g. `residential`, `business`).
4. Optionally fill in the profile fields (see section 2.4).
5. Click **Save**. The system creates entries in `radcheck`, `radusergroup`, and `radreply` automatically.

### 2.2 Editing a user

1. Find the user using search (section 2.3).
2. Click the username to open the profile.
3. Update the desired fields and click **Save**.

### 2.3 Deleting a user

1. Open the user profile.
2. Click **Delete User** and confirm the prompt.

> All associated RADIUS records are removed and linked customer records are updated automatically.

### 2.4 Searching and filtering users

Use the search bar and filter panel on the Users list page. Available filters:

| Filter | Description |
|---|---|
| Username | Partial match (e.g. `john` matches `john_doe`) |
| Group | Exact group name |
| IP Address | Framed IP currently assigned to the user |
| Online Status | Show only online or offline users |
| MAC Address | Exact MAC address bound to the profile |

Results are paginated (50 per page by default).

### 2.5 User profile fields

Each user can have an extended profile with the following fields:

| Field | Description |
|---|---|
| MAC Address | Locks authentication to a specific device MAC address |
| IP Binding | Assigns a fixed framed IP address to the user |
| Concurrent Session Limit | Maximum simultaneous sessions allowed (1–10) |
| Notes | Free-text notes (also used as the `profile` column in CSV imports) |

---

## 3. Session Monitoring

### 3.1 Viewing active sessions

Go to **RADIUS → Dashboard**. The top summary cards show:

- **Active Sessions** — total currently connected users
- **Bandwidth In / Out** — aggregate traffic across all sessions
- **Unique NAS** — number of distinct Network Access Servers reporting sessions
- **Unresolved Alerts** — count of open alerts

The **Active Sessions** table below the cards lists each session with username, NAS IP, framed IP, session duration, and bytes transferred. The page auto-refreshes every 30 seconds.

### 3.2 Filtering sessions

Use the filter controls above the sessions table to narrow results:

| Filter | Match type |
|---|---|
| Username | Partial match |
| NAS IP | Exact IP address |
| Framed IP | Exact IP address |

### 3.3 Terminating a session (admin kick)

1. Locate the session in the Active Sessions table.
2. Click **Terminate** (or the kick icon) on the session row.
3. Confirm the action. The session is stopped immediately with terminate cause `Admin-Reset`.

To terminate all sessions for a specific user at once, open the user profile and click **Disconnect All Sessions**.

### 3.4 Session timeout configuration

The system automatically terminates sessions that exceed the configured idle/absolute timeout. The default timeout is **1440 minutes (24 hours)**. To change this, update the `timeout_minutes` parameter in the session timeout cron job (`cron_radius_rollup.php`) or via the admin settings panel.

---

## 4. Usage Analytics

### 4.1 Accessing the analytics dashboard

Navigate to **RADIUS → Analytics**. The dashboard provides three views selectable by the period picker: **Today**, **Last 7 Days**, and **Last 30 Days**.

### 4.2 Top users chart

The **Top Users by Usage** chart ranks subscribers by total data consumed (upload + download combined) for the selected period. Each entry shows:

- Username
- Total bytes in (download)
- Total bytes out (upload)
- Total combined bytes
- Session count

Use this chart to identify heavy users or potential abuse.

### 4.3 Hourly session counts

The **Hourly Sessions** chart shows how many sessions started in each hour of the day (00:00–23:00) for a selected date. Use this to identify peak usage windows and plan maintenance windows accordingly.

### 4.4 Daily usage summary

The **Daily Summary** table covers a date range you specify and shows per-day totals:

| Column | Description |
|---|---|
| Date | Calendar date |
| Total Bytes In | Aggregate download across all users |
| Total Bytes Out | Aggregate upload across all users |
| Total Sessions | Number of sessions that started on that day |
| Unique Users | Number of distinct subscribers active that day |

---

## 5. Alerts

### 5.1 Alert types

The system generates two categories of automated alerts:

| Alert Type | Severity | Trigger |
|---|---|---|
| `high_session_count` | Warning 🟡 | Active session count reaches or exceeds the configured threshold (default: 1,000) |
| `high_failed_auth_rate` | Critical 🔴 | Failed authentication attempts reach or exceed the threshold within a rolling time window (default: 50 failures in 5 minutes) |

### 5.2 Viewing alerts

Open **RADIUS → Dashboard**. The **Recent Alerts** table at the bottom of the page lists all unresolved alerts with severity badge, type, message, and timestamp.

You can also filter alerts by severity (Critical / Warning / Info) using the filter dropdown above the table.

### 5.3 Resolving an alert

1. Locate the alert in the Recent Alerts table.
2. Click **Resolve**. The alert is marked resolved and removed from the unresolved list.

Resolved alerts are retained in the audit log for historical reference.

### 5.4 Configuring Slack notifications

To receive alert notifications in a Slack channel:

1. Create an **Incoming Webhook** in your Slack workspace (Slack → Apps → Incoming Webhooks).
2. Copy the webhook URL provided by Slack.
3. Set the environment variable on the server:

   ```
   SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
   ```

   Add this to your `.env` file or server environment configuration.
4. Restart the application. Alerts will now be posted to the configured channel automatically.

Slack messages include the severity emoji (🔴 Critical, 🟡 Warning, 🔵 Info), alert message, and contextual details such as session counts and thresholds.

To disable Slack notifications, remove or leave the `SLACK_WEBHOOK_URL` variable empty.

---

## 6. Bulk Operations

### 6.1 CSV import format

Prepare a `.csv` file with the following columns (header row required):

| Column | Required | Description |
|---|---|---|
| `username` | Yes | RADIUS username |
| `password` | Yes | Cleartext password |
| `group` | No | Group name to assign (e.g. `residential`) |
| `profile` | No | Profile notes (free text) |

Example file:

```csv
username,password,group,profile
alice,secret123,residential,Standard plan
bob,pass456,business,Premium plan
carol,qwerty,residential,
```

Column names are case-insensitive. Columns can appear in any order.

### 6.2 Importing users

1. Navigate to **RADIUS → Users → Import**.
2. Click **Choose File** and select your CSV file.
3. Click **Import**. The system processes the file and displays a summary:
   - **Imported** — rows successfully created or updated
   - **Skipped** — rows with validation errors (listed individually)
   - **Errors** — any row-level or file-level issues

Existing users are updated (upserted); new users are created. The entire import runs in a single database transaction — if a fatal error occurs, no changes are committed.

### 6.3 Row limits and error handling

- Maximum **500 rows** per import file. Files exceeding this limit are rejected entirely before any data is written.
- Rows missing `username` or `password` are skipped and reported in the error list; the remaining valid rows are still imported.
- If the file is empty or cannot be parsed, the import is aborted with an error message.

### 6.4 Exporting users to CSV

1. Navigate to **RADIUS → Users**.
2. Optionally apply filters (group, username search) to export a subset.
3. Click **Export CSV**. The downloaded file uses the same four-column format (`username`, `password`, `group`, `profile`) and can be re-imported after editing.
