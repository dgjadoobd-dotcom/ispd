CREATE DATABASE payment_gateway;
USE payment_gateway;

-- Payment Numbers
CREATE TABLE payment_numbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider ENUM('bkash','nagad','rocket'),
    account_type ENUM('personal','merchant','agent'),
    number VARCHAR(20),
    name VARCHAR(100),
    api_key VARCHAR(255),
    qr_url TEXT,
    status TINYINT DEFAULT 1
);

-- Transactions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(100),
    amount DECIMAL(10,2),
    provider VARCHAR(20),
    number VARCHAR(20),
    trxid VARCHAR(100),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);