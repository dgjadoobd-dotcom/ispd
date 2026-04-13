# RADIUS API Documentation

Base URL: `/` (relative to application root)

---

## Authentication

All endpoints require one of the following:

- **Session cookie** — a valid PHP session with `user_id` set (browser-based login)
- **API key header** — `X-API-Key: <key>` where the key matches the `API_KEY` environment variable

> Note: `api/radius/usage.php` and `api/radius/analytics.php` accept session-based auth only.

Unauthorized requests return:

```json
{ "success": false, "error": "Unauthorized", "code": 401 }
```

---

## Standard Response Envelope

Success:
```json
{ "success": true, "data": { ... } }
```

Error:
```json
{ "success": false, "error": "Message", "code": 400 }
```

---

## Users API

**File:** `api/v1/radius/users.php`  
**Base path:** `/api/v1/radius/users`

---

### GET /api/v1/radius/users

List users with optional filtering.

**Query Parameters**

| Parameter  | Type    | Required | Description                          |
|------------|---------|----------|--------------------------------------|
| `username` | string  | No       | Filter by username (partial match)   |
| `group`    | string  | No       | Filter by RADIUS group               |
| `online`   | boolean | No       | `true` to return only online users   |
| `limit`    | integer | No       | Max results (default: 50, min: 1)    |
| `offset`   | integer | No       | Pagination offset (default: 0)       |

**Response**

```json
{
  "success": true,
  "data": [
    {
      "username": "john_doe",
      "group": "standard",
      "online": false,
      "mac_address": "AA:BB:CC:DD:EE:FF",
      "concurrent_session_limit": 2
    }
  ]
}
```

---

### GET /api/v1/radius/users?id={username}

Get a single user's profile including RADIUS attributes.

**Query Parameters**

| Parameter | Type   | Required | Description   |
|-----------|--------|----------|---------------|
| `id`      | string | Yes      | Username      |

**Response**

```json
{
  "success": true,
  "data": {
    "username": "john_doe",
    "group": "standard",
    "mac_address": "AA:BB:CC:DD:EE:FF",
    "concurrent_session_limit": 2,
    "radius_attributes": {
      "Framed-IP-Address": "10.0.0.5",
      "Session-Timeout": 3600
    }
  }
}
```

---

### POST /api/v1/radius/users

Create a new RADIUS user.

**Request Body** (JSON)

| Field                     | Type    | Required | Description                        |
|---------------------------|---------|----------|------------------------------------|
| `username`                | string  | Yes      | Unique username                    |
| `password`                | string  | Yes      | User password                      |
| `group`                   | string  | No       | RADIUS group to assign             |
| `mac_address`             | string  | No       | MAC address for the user profile   |
| `concurrent_session_limit`| integer | No       | Max simultaneous sessions          |

**Example Request**

```json
{
  "username": "jane_doe",
  "password": "s3cur3pass",
  "group": "premium",
  "mac_address": "11:22:33:44:55:66",
  "concurrent_session_limit": 3
}
```

**Response** — `201 Created`

```json
{
  "success": true,
  "data": { "username": "jane_doe" }
}
```

---

### PUT /api/v1/radius/users?id={username}

Update a user's profile fields.

**Query Parameters**

| Parameter | Type   | Required | Description   |
|-----------|--------|----------|---------------|
| `id`      | string | Yes      | Username      |

**Request Body** (JSON) — any subset of profile fields:

```json
{
  "mac_address": "AA:BB:CC:DD:EE:FF",
  "concurrent_session_limit": 1
}
```

**Response**

```json
{
  "success": true,
  "data": { "username": "jane_doe" }
}
```

---

### DELETE /api/v1/radius/users?id={username}

Delete a RADIUS user.

**Query Parameters**

| Parameter | Type   | Required | Description   |
|-----------|--------|----------|---------------|
| `id`      | string | Yes      | Username      |

**Response**

```json
{
  "success": true,
  "data": { "username": "jane_doe" }
}
```

---

## Sessions API

**File:** `api/v1/radius/sessions.php`  
**Base path:** `/api/v1/radius/sessions`

---

### GET /api/v1/radius/sessions

List active sessions with optional filters.

**Query Parameters**

| Parameter  | Type   | Required | Description              |
|------------|--------|----------|--------------------------|
| `username` | string | No       | Filter by username       |
| `nas_ip`   | string | No       | Filter by NAS IP address |

**Response**

```json
{
  "success": true,
  "data": [
    {
      "session_id": "abc123",
      "username": "john_doe",
      "nas_ip": "192.168.1.1",
      "start_time": "2024-01-15T10:30:00Z",
      "bytes_in": 1048576,
      "bytes_out": 2097152
    }
  ]
}
```

---

### GET /api/v1/radius/sessions?stats=1

Return aggregate session statistics.

**Query Parameters**

| Parameter | Type | Required | Description                    |
|-----------|------|----------|--------------------------------|
| `stats`   | any  | Yes      | Any non-empty value enables this mode |

**Response**

```json
{
  "success": true,
  "data": {
    "active_sessions": 42,
    "total_bytes_in": 10737418240,
    "total_bytes_out": 21474836480,
    "unique_users": 38
  }
}
```

---

### DELETE /api/v1/radius/sessions?id={session_id}

Terminate an active session.

**Query Parameters**

| Parameter | Type   | Required | Description |
|-----------|--------|----------|-------------|
| `id`      | string | Yes      | Session ID  |

**Response**

```json
{
  "success": true,
  "data": {
    "session_id": "abc123",
    "terminated": true
  }
}
```

---

## Usage API

**File:** `api/radius/usage.php`  
**Base path:** `/api/radius/usage`  
**Auth:** Session-based only

---

### GET /api/radius/usage?action=stats

Return current session statistics (same as Sessions stats endpoint).

**Response**

```json
{
  "success": true,
  "data": {
    "active_sessions": 42,
    "total_bytes_in": 10737418240,
    "total_bytes_out": 21474836480,
    "unique_users": 38
  }
}
```

---

### GET /api/radius/usage?action=active

Return all currently active sessions.

**Response**

```json
{
  "success": true,
  "data": [
    {
      "session_id": "abc123",
      "username": "john_doe",
      "nas_ip": "192.168.1.1",
      "start_time": "2024-01-15T10:30:00Z",
      "bytes_in": 1048576,
      "bytes_out": 2097152
    }
  ]
}
```

---

### GET /api/radius/usage?action=top_users

Return the top 10 users by total data usage for today.

**Response**

```json
{
  "success": true,
  "data": [
    {
      "username": "heavy_user",
      "total_bytes_in": 5368709120,
      "total_bytes_out": 1073741824,
      "total_bytes": 6442450944,
      "session_count": 3
    }
  ]
}
```

---

### GET /api/radius/usage?action=alerts

Return the 5 most recent unresolved alerts.

**Response**

```json
{
  "success": true,
  "data": [
    {
      "severity": "warning",
      "message": "User exceeded data threshold",
      "alert_type": "data_limit",
      "created_at": "2024-01-15T09:00:00Z"
    }
  ]
}
```

---

## Analytics API

**File:** `api/radius/analytics.php`  
**Base path:** `/api/radius/analytics`  
**Auth:** Session-based only

---

### GET /api/radius/analytics?action=top_users&period={period}

Return the top 10 users by data usage for a given period.

**Query Parameters**

| Parameter | Type   | Required | Description                              |
|-----------|--------|----------|------------------------------------------|
| `action`  | string | Yes      | Must be `top_users`                      |
| `period`  | string | No       | `today` (default), `week`, or `month`    |

**Response**

```json
{
  "success": true,
  "data": [
    {
      "username": "heavy_user",
      "total_bytes_in": 5368709120,
      "total_bytes_out": 1073741824,
      "total_bytes": 6442450944,
      "session_count": 12
    }
  ]
}
```

---

### GET /api/radius/analytics?action=hourly&date={date}

Return per-hour session counts for a specific date.

**Query Parameters**

| Parameter | Type   | Required | Description                          |
|-----------|--------|----------|--------------------------------------|
| `action`  | string | Yes      | Must be `hourly`                     |
| `date`    | string | No       | Date in `YYYY-MM-DD` format (default: today) |

**Response**

```json
{
  "success": true,
  "data": [
    { "hour": 0,  "session_count": 5 },
    { "hour": 1,  "session_count": 3 },
    { "hour": 12, "session_count": 47 }
  ]
}
```

---

### GET /api/radius/analytics?action=daily_summary&from={from}&to={to}

Return daily usage summary for a date range.

**Query Parameters**

| Parameter | Type   | Required | Description                                      |
|-----------|--------|----------|--------------------------------------------------|
| `action`  | string | Yes      | Must be `daily_summary`                          |
| `from`    | string | No       | Start date `YYYY-MM-DD` (default: 6 days ago)    |
| `to`      | string | No       | End date `YYYY-MM-DD` (default: today)           |

**Response**

```json
{
  "success": true,
  "data": [
    {
      "date": "2024-01-15",
      "total_sessions": 120,
      "unique_users": 85,
      "total_bytes_in": 107374182400,
      "total_bytes_out": 53687091200
    }
  ]
}
```

---

## Error Reference

| HTTP Status | Meaning                                      |
|-------------|----------------------------------------------|
| `400`       | Bad request — missing or invalid parameter   |
| `401`       | Unauthorized — missing or invalid credentials|
| `405`       | Method not allowed                           |
| `500`       | Internal server error                        |
