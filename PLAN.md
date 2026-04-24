# Dolibarr Multi-Brands Module v1.1.0 - Architecture & Error Handling Plan

## Overview

The Multi-Brands module enables a single Dolibarr instance to issue proposals, orders, and invoices under multiple brand identities (DBA - "Doing Business As"). Each brand has its own logo, address, legal name, bank details, and email signature. Documents are automatically branded based on either the brand assigned to the document or the brand of the associated third party.

## Architecture

### Directory Structure
```
multi-brands/
├── multi-brands.info.yml          # Dolibarr module descriptor
├── module_builder.php              # Module loader/bootstrap
├── core/
│   └── tpl/
│       ├── pdf_branded_propale.modules.php
│       ├── pdf_branded_invoice.modules.php
│       └── pdf_branded_order.modules.php
├── class/
│   ├── multibrand.class.php        # Core brand entity class
│   ├── multibrandline.class.php    # Brand detail lines
│   └── actions_multibrands.class.php
├── triggers/
│   └── interface_90_multibrands.class.php
├── admin/
│   ├── setup.php                   # Module configuration page
│   └── multibrand.php              # Brand CRUD management
├── sql/
│   └── llx_multibrand.sql          # Database schema
├── langs/
│   ├── en_US/
│   │   └── multibrands.lang
│   └── fr_FR/
│       └── multibrands.lang
├── pdf/
│   └── (generated branded PDFs)
├── doc/
│   └── README.md
└── debug.php                       # Diagnostic tool
```

## Error Handling Strategy

### 1. Include Path Resolution
Dolibarr installations vary in structure. The module must locate Dolibarr core files regardless of whether the module is installed in:
- `/custom/multi-brands/`
- `/htdocs/custom/multi-brands/`
- Any other custom directory configured in Dolibarr

**Solution**: Use a helper function `multibrands_get_main_dir()` that probes multiple paths using `__DIR__` relative resolution:
- `__DIR__ . '/../../'` (standard custom path)
- `__DIR__ . '/../../../'` (nested path)
- `$_SERVER['DOCUMENT_ROOT'] . '/../'` (document root)
- Environment variable `DOLIBARR_MAIN_DOCUMENT_ROOT`

Each path is tested with `file_exists()` before use. If none work, a clear error is logged and displayed.

### 2. Class Autoloading & Namespacing
Dolibarr uses a flat class loading mechanism. Our classes follow strict naming:
- `multibrand.class.php` → `MultiBrand` class
- `actions_multibrands.class.php` → `ActionsMultiBrands` class
- `interface_90_multibrands.class.php` → `InterfaceMultiBrands` class

**Error Handling**:
- All class files use `if (!class_exists())` guards
- Constructor failures throw `Exception` with descriptive messages
- `try/catch` blocks around all database operations
- `setEventMessages()` for user-facing errors

### 3. Database Operations
All DB operations use Dolibarr's `$db` object with:
- Transaction wrappers (`begin()` / `commit()` / `rollback()`)
- `escape()` on all user inputs
- Error logging via `dol_syslog()`
- Return status checking on every query

### 4. File System Operations
Logo uploads, PDF generation, and file writing all use:
- `is_writable()` checks before writes
- `mkdir()` with recursive mode and permission checks
- `try/catch` around file operations
- Fallback to Dolibarr's temp directory if custom dirs fail

### 5. PDF Template Error Handling
PDF generation extends Dolibarr's PDF classes. Each template:
- Validates required brand data before generation
- Falls back to default Dolibarr template if brand data is missing
- Catches exceptions from TCPDF and returns `setEventMessages()` error
- Logs full stack trace via `dol_syslog()`

### 6. Trigger Error Handling
The auto-assignment trigger:
- Checks object type before processing
- Validates brand existence before assignment
- Never fails the parent transaction — errors are logged but not thrown
- Uses `dol_syslog()` for all decisions

### 7. Debug/Diagnostic Tool
`debug.php` provides a standalone diagnostic page that:
- Checks PHP version and extensions
- Verifies Dolibarr constants and path resolution
- Lists loaded classes and available methods
- Shows database connectivity status
- Displays module configuration values
- Lists all brands with validation status
- Shows recent error logs

## Security Considerations

- All admin pages check `restrictedArea()`
- File uploads validate mime types
- SQL injection prevented via parameterized queries
- XSS prevention via `dol_htmlentitiesbr()` on output
- CSRF tokens on all forms

## Version Consistency

All files declare version `1.1.0` in:
- Module descriptor (`$this->version`)
- Class constants (`const VERSION = '1.1.0'`)
- File headers (`// Version 1.1.0`)
- Info YAML file

## Deployment Checklist

1. Upload to `/custom/multi-brands/`
2. Activate module in Dolibarr admin
3. Run database schema installation (automatic on activation)
4. Configure brands via Admin → Multi-Brands
5. Test debug.php for diagnostics
6. Generate test documents for each brand
7. Verify triggers fire correctly
8. Test email substitutions
