-- ============================================================
-- Migration: 2024_01_04_001_support_tables
-- Description: Support module extended tables
--   - problem_categories
--   - ticket_comments
--   - ticket_assignments
--   - sla_violations
--   - ALTER support_tickets to add SLA and category columns
-- Requirements: 3.1-3.10
-- ============================================================

-- ============================================================
-- 1. PROBLEM CATEGORIES
-- ============================================================
CREATE TABLE IF NOT EXISTS problem_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. TICKET COMMENTS (full activity thread per ticket)
-- ============================================================
CREATE TABLE IF NOT EXISTS ticket_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER,
    author_name VARCHAR(100),
    message TEXT NOT NULL,
    is_internal INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

-- ============================================================
-- 3. TICKET ASSIGNMENTS (assignment history per ticket)
-- ============================================================
CREATE TABLE IF NOT EXISTS ticket_assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    assigned_to INTEGER,
    assigned_by INTEGER,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

-- ============================================================
-- 4. SLA VIOLATIONS (tracks breached tickets)
-- ============================================================
CREATE TABLE IF NOT EXISTS sla_violations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL UNIQUE,
    priority VARCHAR(20),
    sla_deadline TIMESTAMP,
    violated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolution_time_minutes INTEGER,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
);

-- ============================================================
-- 5. ALTER support_tickets — add SLA and category columns
--    (SQLite does not support multi-column ALTER in one statement;
--     each column is added separately)
-- ============================================================

-- SLA deadline: calculated at ticket creation based on priority
--   urgent=2h, high=8h, medium=24h, low=72h  (Req 3.2)
ALTER TABLE support_tickets ADD COLUMN sla_deadline DATETIME NULL;

-- Flag set to 1 when current_time > sla_deadline and status
-- is not 'resolved' or 'closed'  (Req 3.3)
ALTER TABLE support_tickets ADD COLUMN sla_breached INTEGER DEFAULT 0;

-- Foreign key to problem_categories  (Req 3.6)
ALTER TABLE support_tickets ADD COLUMN category_id INTEGER NULL;

-- Resolution notes recorded when ticket is resolved  (Req 3.8)
ALTER TABLE support_tickets ADD COLUMN resolution_notes TEXT NULL;

-- ============================================================
-- 6. INDEXES for new tables
-- ============================================================
CREATE INDEX IF NOT EXISTS idx_ticket_comments_ticket ON ticket_comments (ticket_id);
CREATE INDEX IF NOT EXISTS idx_ticket_assignments_ticket ON ticket_assignments (ticket_id);
CREATE INDEX IF NOT EXISTS idx_sla_violations_ticket ON sla_violations (ticket_id);
CREATE INDEX IF NOT EXISTS idx_support_tickets_sla_deadline ON support_tickets (sla_deadline);
CREATE INDEX IF NOT EXISTS idx_support_tickets_sla_breached ON support_tickets (sla_breached);
CREATE INDEX IF NOT EXISTS idx_support_tickets_category_id ON support_tickets (category_id);

-- ============================================================
-- 7. SEED DEFAULT PROBLEM CATEGORIES  (Req 3.6)
-- ============================================================
INSERT OR IGNORE INTO problem_categories (name, description, is_active) VALUES
('no_connection',    'Customer has no internet connection',          1),
('slow_speed',       'Customer experiencing slow internet speed',    1),
('billing_dispute',  'Customer has a billing or payment dispute',    1),
('hardware_fault',   'Hardware or equipment fault reported',         1),
('new_request',      'New service or feature request',               1),
('other',            'Other / miscellaneous issue',                  1);
