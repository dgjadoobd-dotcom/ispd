# Security Policy

## Supported Versions

| Version | Supported          |
|---------|-------------------|
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

If you discover a security vulnerability within FCNCHBD ISP ERP, please send an email to **security@digitalisp.xyz**. All security vulnerabilities will be promptly addressed.

Please include the following information:
- Type of vulnerability
- Full path of source file(s) related to the vulnerability
- Location of the affected source file(s)
- Any special configuration required to reproduce the issue
- Step-by-step instructions to reproduce the issue
- Proof-of-concept or exploit code (if possible)
- Impact of the issue, including how it might attack the system

## Response Timeline

- **Initial Response**: Within 48 hours
- **Status Update**: Within 7 days
- **Resolution**: Within 30 days (depending on severity)

## Security Features

### Authentication
- Password hashing with bcrypt
- Session management with secure cookies
- MFA (TOTP) support
- Login attempt rate limiting

### Authorization
- Role-based access control (RBAC)
- Granular permission system
- Branch-level data isolation

### Data Protection
- PDO prepared statements (SQL injection prevention)
- Input sanitization
- Output encoding (XSS prevention)
- CSRF token validation

### Network Security
- IP access control lists
- API rate limiting
- Restricted CORS (configurable)

### Audit
- Login/logout logging
- Permission change tracking
- Account modification logs

## Security Recommendations

1. **Keep PHP updated** - Use PHP 8.1+
2. **Use HTTPS** - Never run over HTTP in production
3. **Restrict file permissions** - Follow principle of least privilege
4. **Monitor logs** - Review `storage/logs/` regularly
5. **Regular backups** - Test restore procedures
6. **Firewall** - Configure UFW or iptables
7. **Two-factor auth** - Enable MFA for admin accounts

## Third-Party Dependencies

Run `composer audit` to check for known vulnerabilities in dependencies.

---

*Thank you for helping keep FCNCHBD ISP ERP secure!*