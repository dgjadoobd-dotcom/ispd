# Contributing to FCNCHBD ISP ERP

Thank you for your interest in contributing to FCNCHBD ISP ERP! This document outlines our contribution guidelines.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment. Harassment, discrimination, and abusive behavior will not be tolerated.

## Ways to Contribute

### 🐛 Bug Reports
Use GitHub Issues to report bugs. Include:
- Clear description
- Steps to reproduce
- Expected vs actual behavior
- Environment details (OS, PHP version, MySQL/SQLite)

### 💡 Feature Requests
Open a GitHub Issue with:
- Clear feature description
- Use case and rationale
- Potential alternatives considered

### 🔧 Pull Requests
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Make changes following our coding standards
4. Add/update tests if applicable
5. Commit with clear messages: `git commit -m "Add: description"`
6. Push to your fork: `git push origin feature/your-feature`
7. Open a Pull Request

## 🎯 Coding Standards

### PHP
- PHP 8.1+ required
- PSR-12 coding style (enforced)
- Use type hints where possible
- Document public APIs with DocBlocks
- Use strict typing: `declare(strict_types=1);`

### Naming Conventions
- **Controllers**: `PascalCase` (e.g., `CustomerController`)
- **Services**: `PascalCase` (e.g., `BillingService`)
- **Methods**: `camelCase` (e.g., `getInvoiceList`)
- **Views**: `snake_case` (e.g., `invoice_list.php`)

### Database
- Use migrations for schema changes
- Add foreign key constraints
- Use transactions for multi-table operations
- Prefix indexes with table name: `idx_customers_branch_id`

### Git
- **Branches**: `feature/`, `fix/`, `hotfix/`
- **Commits**: Imperative mood ("Add" not "Added")
- **Messages**: First line < 50 chars, details below

## 🧪 Testing

Run tests before submitting:
```bash
composer test
```

Generate coverage:
```bash
composer test:coverage
```

## 📋 Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Manual testing completed

## Checklist
- [ ] Code follows PSR-12
- [ ] Type hints added
- [ ] Documentation updated (if applicable)
```

## 📞 Getting Help

- Open a GitHub Discussion
- Check existing issues before creating new ones
- Be patient — maintainers respond within 48 hours

---

## 📄 License

By contributing, you agree that your contributions will be licensed under the project's proprietary license.