-- ============================================================
-- Migration: 2024_01_03_010_hr_module
-- Date: 2024-01-03
-- Description: Creates all HR & Payroll module tables.
--              Run this file to set up only the HR module.
--              Tables: departments, designations, employees,
--                      attendance, salary_slips,
--                      performance_appraisals, leave_balances
-- Requirements: 2.1-2.10
-- Depends on: branches table, users table
-- ============================================================

USE digital_isp;

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ============================================================
-- 1. DEPARTMENTS
-- Req 2.1: departments table with name, branch, head-of-department
-- ============================================================
CREATE TABLE IF NOT EXISTS departments (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    branch_id           INT UNSIGNED NOT NULL                   COMMENT 'FK to branches.id',
    name                VARCHAR(150) NOT NULL,
    head_of_department  INT UNSIGNED                            COMMENT 'FK to employees.id — added via ALTER after employees table',
    description         TEXT,
    is_active           TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    KEY idx_dept_branch (branch_id),
    CONSTRAINT fk_dept_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='HR departments per branch — Req 2.1';

-- ============================================================
-- 2. DESIGNATIONS
-- Req 2.2: designations table with title, department, grade
-- ============================================================
CREATE TABLE IF NOT EXISTS designations (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id   INT UNSIGNED NOT NULL                       COMMENT 'FK to departments.id',
    title           VARCHAR(150) NOT NULL,
    grade           VARCHAR(50),
    description     TEXT,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_desig_department (department_id),
    CONSTRAINT fk_desig_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Job designations linked to departments — Req 2.2';

-- ============================================================
-- 3. EMPLOYEES
-- Req 2.3: employees table linked to users, with joining date,
--          designation, department, salary grade, bank account,
--          NID, and emergency contact
-- Req 2.4: employee creation auto-creates a linked users account
--          (handled at application layer in HrService)
-- ============================================================
CREATE TABLE IF NOT EXISTS employees (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                 INT UNSIGNED                            COMMENT 'FK to users.id — NULL until user account is created',
    branch_id               INT UNSIGNED NOT NULL                   COMMENT 'FK to branches.id',
    department_id           INT UNSIGNED                            COMMENT 'FK to departments.id',
    designation_id          INT UNSIGNED                            COMMENT 'FK to designations.id',
    employee_code           VARCHAR(30) NOT NULL,
    full_name               VARCHAR(150) NOT NULL,
    phone                   VARCHAR(20),
    email                   VARCHAR(150),
    nid_number              VARCHAR(50)                             COMMENT 'National ID number — Req 2.3',
    joining_date            DATE                                    COMMENT 'Req 2.3',
    basic_salary            DECIMAL(10,2) NOT NULL DEFAULT 0.00     COMMENT 'Salary grade — Req 2.3',
    allowances              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    bank_account            VARCHAR(50)                             COMMENT 'Req 2.3',
    bank_name               VARCHAR(100),
    emergency_contact       VARCHAR(20)                             COMMENT 'Req 2.3',
    emergency_contact_name  VARCHAR(100),
    status                  ENUM('active','inactive','terminated') NOT NULL DEFAULT 'active',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_employee_code (employee_code),
    KEY idx_emp_branch (branch_id),
    KEY idx_emp_user (user_id),
    KEY idx_emp_department (department_id),
    KEY idx_emp_designation (designation_id),

    CONSTRAINT fk_emp_user        FOREIGN KEY (user_id)        REFERENCES users(id)         ON DELETE SET NULL,
    CONSTRAINT fk_emp_branch      FOREIGN KEY (branch_id)      REFERENCES branches(id),
    CONSTRAINT fk_emp_department  FOREIGN KEY (department_id)  REFERENCES departments(id)   ON DELETE SET NULL,
    CONSTRAINT fk_emp_designation FOREIGN KEY (designation_id) REFERENCES designations(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Employee profiles linked to users — Req 2.3, 2.4';

-- ============================================================
-- 3a. Back-fill FK: departments.head_of_department → employees
-- Must run AFTER employees table is created.
-- ============================================================
ALTER TABLE departments
    ADD CONSTRAINT fk_dept_head
    FOREIGN KEY (head_of_department) REFERENCES employees(id) ON DELETE SET NULL;

-- ============================================================
-- 4. ATTENDANCE
-- Req 2.5: daily attendance with status present/absent/late/
--          half_day/leave; UNIQUE per employee per date
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED NOT NULL                       COMMENT 'FK to employees.id',
    branch_id       INT UNSIGNED NOT NULL                       COMMENT 'FK to branches.id',
    attendance_date DATE NOT NULL,
    status          ENUM('present','absent','late','half_day','leave') NOT NULL DEFAULT 'present'
                                                                COMMENT 'Req 2.5',
    check_in        TIME,
    check_out       TIME,
    notes           TEXT,
    recorded_by     INT UNSIGNED                                COMMENT 'FK to users.id',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_employee_date (employee_id, attendance_date),
    KEY idx_att_employee (employee_id),
    KEY idx_att_date (attendance_date),
    KEY idx_att_branch (branch_id),

    CONSTRAINT fk_att_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_att_branch   FOREIGN KEY (branch_id)   REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Daily employee attendance records — Req 2.5';

-- ============================================================
-- 5. SALARY SLIPS
-- Req 2.6: gross_pay = basic_salary + allowances − deductions
-- Req 2.7: salary slip PDF per employee
-- Req 2.9: prevent duplicate slip for same employee+month
-- ============================================================
CREATE TABLE IF NOT EXISTS salary_slips (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED NOT NULL                       COMMENT 'FK to employees.id',
    branch_id       INT UNSIGNED NOT NULL                       COMMENT 'FK to branches.id',
    salary_month    DATE NOT NULL                               COMMENT 'First day of the salary month — Req 2.9',
    basic_salary    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allowances      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    deductions      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    gross_pay       DECIMAL(10,2) NOT NULL DEFAULT 0.00         COMMENT 'basic_salary + allowances − deductions — Req 2.6',
    net_pay         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    present_days    INT NOT NULL DEFAULT 0,
    absent_days     INT NOT NULL DEFAULT 0,
    leave_days      INT NOT NULL DEFAULT 0,
    payment_status  ENUM('pending','paid') NOT NULL DEFAULT 'pending',
    payment_date    DATE,
    payment_method  ENUM('cash','bank_transfer','mobile_banking') DEFAULT 'cash',
    notes           TEXT,
    generated_by    INT UNSIGNED                                COMMENT 'FK to users.id',
    generated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_employee_month (employee_id, salary_month),
    KEY idx_slip_employee (employee_id),
    KEY idx_slip_month (salary_month),
    KEY idx_slip_branch (branch_id),

    CONSTRAINT fk_slip_employee FOREIGN KEY (employee_id) REFERENCES employees(id),
    CONSTRAINT fk_slip_branch   FOREIGN KEY (branch_id)   REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Monthly salary slips per employee — Req 2.6, 2.7, 2.9';

-- ============================================================
-- 6. PERFORMANCE APPRAISALS
-- Req 2.8: rating 1-5, reviewer, review period, comments
-- ============================================================
CREATE TABLE IF NOT EXISTS performance_appraisals (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id             INT UNSIGNED NOT NULL               COMMENT 'FK to employees.id',
    reviewer_id             INT UNSIGNED                        COMMENT 'FK to users.id',
    review_period_start     DATE NOT NULL,
    review_period_end       DATE NOT NULL,
    rating                  TINYINT UNSIGNED NOT NULL           COMMENT '1-5 rating scale — Req 2.8',
    comments                TEXT,
    goals_achieved          TEXT,
    areas_for_improvement   TEXT,
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    KEY idx_appr_employee (employee_id),
    KEY idx_appr_period (review_period_start, review_period_end),

    CONSTRAINT fk_appr_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    CONSTRAINT fk_appr_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id)     ON DELETE SET NULL,
    CONSTRAINT chk_appr_rating  CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Employee performance appraisal records — Req 2.8';

-- ============================================================
-- 7. LEAVE BALANCES
-- Req 2.10: leave balances per employee per type (annual/sick/casual)
-- ============================================================
CREATE TABLE IF NOT EXISTS leave_balances (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id     INT UNSIGNED NOT NULL                       COMMENT 'FK to employees.id',
    leave_type      ENUM('annual','sick','casual') NOT NULL     COMMENT 'Req 2.10',
    year            YEAR NOT NULL,
    total_days      INT NOT NULL DEFAULT 0,
    used_days       INT NOT NULL DEFAULT 0,
    remaining_days  INT GENERATED ALWAYS AS (total_days - used_days) STORED
                                                                COMMENT 'Computed: total_days − used_days',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uniq_employee_leave_year (employee_id, leave_type, year),
    KEY idx_lb_employee (employee_id),

    CONSTRAINT fk_lb_employee FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Leave balance tracking per employee per type per year — Req 2.10';

-- ============================================================
-- Re-enable FK checks
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- END OF HR MODULE MIGRATION
-- ============================================================
