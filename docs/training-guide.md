# RADIUS Service Enhancement — Training Guide

**Audience:** New Network Administrators  
**Estimated Time:** ~80 minutes  
**Reference:** See [user-guide.md](user-guide.md) for full documentation

---

## Before You Begin

Make sure you have:
- Admin credentials for the Digital ISP ERP system
- Access to the RADIUS module (ask your system administrator if you don't see it in the menu)
- A text editor for the CSV exercises

---

## Module 1: Getting Started (15 minutes)

### Overview of the RADIUS Dashboard

The RADIUS dashboard is your command center for monitoring the health of your network. It gives you a live snapshot of who is connected, how much bandwidth is being used, and whether any alerts need your attention.

To open it, navigate to **RADIUS → Dashboard** from the main menu.

### Navigating the Main Sections

The RADIUS module has four main sections accessible from the left sidebar:

| Section | What you'll find there |
|---|---|
| **Dashboard** | Live session summary cards, active sessions table, recent alerts |
| **Users** | Full subscriber list, search/filter, add/import/export |
| **Analytics** | Bandwidth charts, top users, hourly session counts, daily summaries |
| **Alerts** | All alerts with severity badges, resolve controls |

The dashboard auto-refreshes every 30 seconds — you don't need to reload the page manually.

### Reading the Summary Cards

At the top of the dashboard you'll see four cards:

1. **Active Sessions** — how many subscribers are connected right now
2. **Bandwidth In / Out** — aggregate traffic across all sessions
3. **Unique NAS** — number of Network Access Servers currently reporting
4. **Unresolved Alerts** — open alerts that need attention (aim to keep this at zero)

### Exercise 1.1: Log In and Identify the Active Session Count

1. Log in to the Digital ISP ERP system with your admin credentials.
2. Click **RADIUS** in the main navigation menu.
3. Click **Dashboard**.
4. Look at the **Active Sessions** card in the top row.
5. Write down the current session count: ___________
6. Note the time you checked: ___________

> This is your baseline. You'll refer back to it in later modules.

---

## Module 2: Managing Users (20 minutes)

### Adding Your First RADIUS User

Every subscriber who connects through RADIUS needs a user account. Here's how to create one:

1. Go to **RADIUS → Users → Add User**.
2. Fill in the **Username** field — this is what the subscriber's router will send during authentication.
3. Enter a **Password** — use a strong password in production.
4. Select a **Group** from the dropdown (e.g. `residential`, `business`).
5. Click **Save**.

The system automatically creates the required entries in `radcheck`, `radusergroup`, and `radreply` — you don't need to touch those tables directly.

### Setting Up a User Profile (MAC Binding, Session Limits)

After saving a user, you can add profile settings to control how they connect:

1. Open the user by clicking their username in the Users list.
2. Scroll to the **Profile** section.
3. Configure any of the following:

| Field | What it does | Example |
|---|---|---|
| **MAC Address** | Locks the account to one specific device | `AA:BB:CC:DD:EE:FF` |
| **IP Binding** | Assigns a fixed IP address to this user | `192.168.1.100` |
| **Concurrent Session Limit** | Max simultaneous connections (1–10) | `2` |
| **Notes** | Free-text notes about this subscriber | `Standard plan, floor 3` |

4. Click **Save** after making changes.

> MAC binding is useful for preventing credential sharing. If a subscriber's MAC doesn't match, authentication is denied even with the correct password.

### Exercise 2.1: Create a Test User

1. Go to **RADIUS → Users → Add User**.
2. Enter the following details:
   - **Username:** `training_user01`
   - **Password:** `Training@2024`
   - **Group:** `test`
3. Click **Save**.
4. The system should redirect you to the user profile page. Confirm you see `training_user01` in the page title.
5. Scroll to the **Profile** section.
6. Set **Concurrent Session Limit** to `2`.
7. Click **Save**.

### Exercise 2.2: Search for the User and Verify It Appears

1. Go to **RADIUS → Users**.
2. In the search bar, type `training_user01`.
3. Press **Enter** or click the search icon.
4. Confirm the user appears in the results table.
5. Check that the **Group** column shows `test`.

> If the user doesn't appear, double-check the spelling and try again. The search uses partial matching, so even `training` should return the result.

---

## Module 3: Bulk Operations (15 minutes)

### Preparing a CSV Import File

When you need to create many users at once, CSV import is much faster than adding them one by one. The import file needs a header row followed by one user per line.

Required columns:

| Column | Required | Notes |
|---|---|---|
| `username` | Yes | Must be unique |
| `password` | Yes | Cleartext |
| `group` | No | Defaults to none if omitted |
| `profile` | No | Free-text notes |

Column names are case-insensitive and can appear in any order.

Example file content:

```csv
username,password,group,profile
alice_test,pass001,residential,Training import
bob_test,pass002,residential,Training import
carol_test,pass003,business,Training import
```

> The import limit is 500 rows per file. Rows missing `username` or `password` are skipped and reported — the rest still import successfully.

### Importing Users

1. Go to **RADIUS → Users → Import**.
2. Click **Choose File** and select your CSV file.
3. Click **Import**.
4. Review the summary:
   - **Imported** — rows that were created or updated
   - **Skipped** — rows with validation errors
   - **Errors** — file-level or row-level issues

If a user already exists, the import updates (upserts) their record rather than creating a duplicate.

### Exporting Users to Verify

1. Go to **RADIUS → Users**.
2. Click **Export CSV**.
3. Open the downloaded file in a spreadsheet application.
4. Verify your imported users appear in the list.

The exported file uses the same four-column format and can be re-imported after editing.

### Exercise 3.1: Create a CSV and Import 3 Test Users

1. Open a text editor and create a file named `training_import.csv`.
2. Paste the following content:

```csv
username,password,group,profile
import_user01,Import@001,test,Bulk training exercise
import_user02,Import@002,test,Bulk training exercise
import_user03,Import@003,test,Bulk training exercise
```

3. Save the file.
4. Go to **RADIUS → Users → Import**.
5. Click **Choose File** and select `training_import.csv`.
6. Click **Import**.
7. Confirm the summary shows **3 Imported** and **0 Errors**.
8. Click **Export CSV** and open the file to verify all three users are present.

---

## Module 4: Session Monitoring (15 minutes)

### Reading the Active Sessions Dashboard

The **Active Sessions** table on the dashboard shows every currently connected subscriber. Each row contains:

| Column | What it means |
|---|---|
| **Username** | The subscriber's RADIUS username |
| **NAS IP** | The router or access point they're connected through |
| **Framed IP** | The IP address assigned to their connection |
| **Duration** | How long they've been connected in this session |
| **Bytes In / Out** | Data transferred in this session |

The table refreshes automatically every 30 seconds.

### Filtering Sessions

If you have many active sessions, use the filter controls above the table:

- **Username** — partial match (type part of the name)
- **NAS IP** — exact IP address of the access point
- **Framed IP** — exact IP address assigned to the subscriber

### Terminating a Session

Sometimes you need to disconnect a subscriber — for example, if they're causing network issues or their account has been suspended.

To terminate a single session:
1. Find the session in the Active Sessions table.
2. Click **Terminate** on that row.
3. Confirm the action.

The session stops immediately with terminate cause `Admin-Reset`. The subscriber will need to reconnect.

To disconnect all sessions for one user at once:
1. Open the user's profile (**RADIUS → Users → [username]**).
2. Click **Disconnect All Sessions**.

### Understanding Bandwidth Metrics

The **Bandwidth In / Out** summary card shows aggregate traffic across all active sessions. "In" refers to traffic coming into the network (subscriber downloads), and "Out" refers to traffic leaving (subscriber uploads).

In the sessions table, the **Bytes In / Out** columns show per-session data. Sort by these columns to quickly spot heavy users.

### Exercise 4.1: Identify the Top Bandwidth Consumer

1. Go to **RADIUS → Dashboard**.
2. Look at the Active Sessions table.
3. Click the **Bytes In** column header to sort by download usage (descending).
4. The user at the top of the list is your current top bandwidth consumer.
5. Write down their username: ___________
6. Write down their current session duration: ___________

> If there are no active sessions, check back during peak hours or ask a colleague to connect a test device.

---

## Module 5: Alerts and Analytics (15 minutes)

### Understanding Alert Severity Levels

The system generates two types of automated alerts:

| Alert Type | Severity | What triggered it |
|---|---|---|
| `high_session_count` | 🟡 Warning | Active sessions reached or exceeded the threshold (default: 1,000) |
| `high_failed_auth_rate` | 🔴 Critical | Too many failed authentication attempts in a short window (default: 50 failures in 5 minutes) |

**Critical** alerts need immediate attention — a spike in failed authentications can indicate a brute-force attack or a misconfigured device flooding the server.

**Warning** alerts are informational — high session counts are normal during peak hours but worth monitoring.

### Viewing Alerts

1. Go to **RADIUS → Dashboard**.
2. Scroll to the **Recent Alerts** table at the bottom of the page.
3. Use the severity filter dropdown to show only Critical, Warning, or Info alerts.

### Resolving an Alert

Once you've investigated an alert and confirmed the situation is under control:

1. Find the alert in the Recent Alerts table.
2. Click **Resolve**.
3. The alert is marked resolved and removed from the unresolved list.

Resolved alerts are kept in the audit log — you can always look back at the history.

### Reading the Analytics Charts

Navigate to **RADIUS → Analytics**. Use the period picker to switch between **Today**, **Last 7 Days**, and **Last 30 Days**.

The analytics section has three views:

1. **Top Users by Usage** — ranks subscribers by total data consumed. Useful for identifying heavy users or potential abuse.
2. **Hourly Sessions** — shows how many sessions started in each hour of the day. Use this to find your peak usage window.
3. **Daily Summary** — per-day totals for bytes in/out, session count, and unique users over a date range.

### Exercise 5.1: Navigate to Analytics and Identify Peak Usage Hour

1. Go to **RADIUS → Analytics**.
2. Select **Last 7 Days** from the period picker.
3. Click on the **Hourly Sessions** chart.
4. Find the hour with the tallest bar — this is your peak usage hour.
5. Write down the peak hour: ___________
6. Write down the approximate session count at that hour: ___________

> Use this information to schedule maintenance windows during off-peak hours (typically late night or early morning).

---

## Quick Reference Card

### Key URLs

| Page | Path |
|---|---|
| RADIUS Dashboard | `/radius/dashboard` |
| Users List | `/radius/users` |
| Add User | `/radius/users/add` |
| Import Users | `/radius/users/import` |
| Analytics | `/radius/analytics` |
| Alerts | `/radius/dashboard` (scroll to bottom) |

### Common Tasks at a Glance

| Task | Steps |
|---|---|
| Add a single user | RADIUS → Users → Add User → fill form → Save |
| Edit a user | RADIUS → Users → click username → edit → Save |
| Delete a user | RADIUS → Users → click username → Delete User → confirm |
| Search for a user | RADIUS → Users → type in search bar → Enter |
| Import users from CSV | RADIUS → Users → Import → Choose File → Import |
| Export users to CSV | RADIUS → Users → Export CSV |
| View active sessions | RADIUS → Dashboard → Active Sessions table |
| Terminate a session | Dashboard → find session → Terminate → confirm |
| Resolve an alert | Dashboard → Recent Alerts → Resolve |
| View analytics | RADIUS → Analytics → select period |

### CSV Import Format Reminder

```
username,password,group,profile
```

- `username` and `password` are required
- `group` and `profile` are optional
- Maximum 500 rows per file

### Who to Contact for Help

| Issue | Contact |
|---|---|
| Can't log in / access denied | System Administrator |
| RADIUS authentication failures | Network Operations team |
| Billing or subscriber account issues | Billing team |
| Critical alerts (🔴) | On-call Network Engineer |
| Feature requests or bugs | IT Support |

---

## Next Steps

Once you're comfortable with the basics covered in this guide, explore the following topics in the [user-guide.md](user-guide.md):

- **Section 3.4** — Configuring session timeout thresholds
- **Section 5.4** — Setting up Slack notifications for alerts
- **Section 4.4** — Using the Daily Summary for capacity planning

For API access and automation, refer to the API documentation.
