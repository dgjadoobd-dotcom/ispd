-- Create development database
CREATE DATABASE IF NOT EXISTS radius_development;
CREATE DATABASE IF NOT EXISTS radius;

-- Create development user
CREATE USER IF NOT EXISTS 'radius_dev'@'%' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON radius_development.* TO 'radius_dev'@'%';
GRANT ALL PRIVILEGES ON radius.* TO 'radius_dev'@'%';
FLUSH PRIVILEGES;

-- Use radius database
USE radius;

-- Create RADIUS tables if they don't exist
CREATE TABLE IF NOT EXISTS radcheck (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL DEFAULT '',
    attribute varchar(64) NOT NULL DEFAULT '',
    op char(2) NOT NULL DEFAULT '==',
    value varchar(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY username (username(32))
    ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radreply (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL DEFAULT '',
    attribute varchar(64) NOT NULL DEFAULT '',
    op char(2) NOT NULL DEFAULT '=',
    value varchar(253) NOT NULL DEFAULT '',
    PRIMARY KEY (id),
    KEY username (username(32))
    ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radusergroup (
    id int(11) NOT NULL AUTO_INCREMENT,
    username varchar(64) NOT NULL DEFAULT '',
    groupname varchar(64) NOT NULL DEFAULT '',
    priority int(11) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY username (username(32)))
    ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS radacct (
    radacctid bigint(21) NOT NULL AUTO_INCREMENT,
    acctsessionid varchar(64) NOT NULL DEFAULT '',
    acctuniqueid varchar(32) NOT NULL DEFAULT '',
    username varchar(64) NOT NULL DEFAULT '',
    groupname varchar(64) NOT NULL DEFAULT '',
    realm varchar(64) DEFAULT '',
    nasipaddress varchar(15) NOT NULL DEFAULT '',
    nasportid varchar(32) DEFAULT NULL,
    nasporttype varchar(32) DEFAULT NULL,
    acctstarttime datetime DEFAULT NULL,
    acctstoptime datetime DEFAULT NULL,
    acctsessiontime int(12) DEFAULT NULL,
    acctinputoctets bigint(20) DEFAULT NULL,
    acctoutputoctets bigint(20) DEFAULT NULL,
    acctterminatecause varchar(32) DEFAULT NULL,
    framedipaddress varchar(15) DEFAULT NULL,
    PRIMARY KEY (radacctid),
    KEY username (username),
    KEY framedipaddress (framedipaddress),
    KEY acctsessionid (acctsessionid),
    KEY acctsessiontime (acctsessiontime),
    KEY acctstarttime (acctstarttime),
    KEY acctinterval (acctinterval),
    KEY acctstoptime (acctstoptime),
    KEY nasipaddress (nasipaddress))
    ENGINE=InnoDB;

-- Insert test data
INSERT IGNORE INTO radcheck (username, attribute, op, value) VALUES
('testuser1', 'Cleartext-Password', ':=', 'password123'),
('testuser2', 'Cleartext-Password', ':=', 'password456');

INSERT IGNORE INTO radusergroup (username, groupname, priority) VALUES
('testuser1', 'bronze', 1),
('testuser2', 'silver', 1);

-- Create some test accounting data
INSERT IGNORE INTO radacct (acctsessionid, acctuniqueid, username, groupname, nasipaddress, acctstarttime, acctsessiontime, acctinputoctets, acctoutputoctets) VALUES
('session1', 'unique1', 'testuser1', 'bronze', '192.168.1.1', NOW() - INTERVAL 1 HOUR, 3600, 1000000, 2000000),
('session2', 'unique2', 'testuser2', 'silver', '192.168.1.1', NOW() - INTERVAL 30 MINUTE, 1800, 500000, 800000);