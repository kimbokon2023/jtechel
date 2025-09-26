# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a PHP-based web application for J-TECH (제이테크), an elevator business management system. The application manages customer information, estimates, work orders, and various business operations.

## Database Configuration

- Database connection is managed through `/lib/mydb.php`
- Uses PDO for MySQL database connectivity
- Connection details:
  - Host: localhost
  - Database: jtechel
  - Character set: UTF-8

## Project Structure

The application has two main work areas:
- `/jtech/` - Main J-TECH business system
  - Uses database table: `jtech`
  - Working directory: `http://j-techel.co.kr/jtech/`
- `/mywork/` - Alternative work area (김재구 팀장)
  - Uses database table: `mywork`
  - Working directory: `http://j-techel.co.kr/mywork/`
  - Has its own git repository

Key directories:
- `/lib/` - Database connection and utility libraries
- `/login/` - User authentication system
- `/common/` - Shared components
- `/dbeditor/` - phpMyAdmin installation for database management
- `/PHPExcel_1.8.0/` - Excel file processing library
- `/tcpdf/` - PDF generation library
- `/plugin/` - Various frontend plugins
- `/uploads/` - File upload storage
- `/file/` and `/filedata/` - File management functions
- `/pdf/` - PDF processing utilities
- `/QRcode/` - QR code generation library

## Key Configuration Files

### Environment Initialization
Each work area has its own configuration:
- `/jtech/ini.php` - Main area session and environment setup
- `/jtech/settings.ini` - Main area configuration
- `/mywork/ini.php` - Alternative area session and environment setup  
- `/mywork/settings.ini` - Alternative area configuration

Both `ini.php` files:
- Initialize PHP sessions
- Check authentication levels (level > 8 requires re-login)
- Load environment settings from `settings.ini`
- Handle common request parameters

### Global Components
- `/load_header.php` - Common header with CDN dependencies
- `/common.js` - Shared JavaScript functions
- `/index.php` - Main entry point

## Development Commands

### Local Development
Since this is a PHP application, ensure you have:
- PHP 7.0+ with PDO MySQL extension
- MySQL/MariaDB database server
- Web server (Apache/Nginx) configured for PHP

### Database Access
- phpMyAdmin is available at `/dbeditor/` for database management

## Session Management

The application uses PHP sessions for authentication:
- Session variables: `$_SESSION["level"]`, `$_SESSION["name"]`, `$_SESSION["userid"]`, `$_SESSION["part"]`
- Access levels are checked in `ini.php` (level > 8 requires re-login)
- Login/logout handled through:
  - `/login/login_form.php` - Login form
  - `/login/login_result.php` - Authentication processing
  - `/login/logout.php` - Session termination
- Redirects to `http://j-techel.co.kr/login/logout.php` on authentication failure

## Frontend Dependencies

The application uses various CDN-hosted libraries:
- jQuery 3.4.1
- Bootstrap 5.3.0
- jQuery UI 1.12.1
- Chart.js 2.9.4
- Highcharts
- SweetAlert2
- Summernote editor
- TUI Grid
- Ionicons

Local frontend resources:
- `/css/` - Stylesheets
- `/js/` - JavaScript libraries
- `/img/` - Image assets
- `/plugin/` - jQuery and other plugins

## File Upload Handling

- Image uploads are stored in `/uploads/` directory
- File management functions in `/file/` and `/filedata/` directories
- Picture upload tracking in `picuploads` database table
- Upload handling includes:
  - Image processing and storage
  - File deletion utilities (`deljpg.php`, `delalljpg.php`)
  - Picture management (`delpic.php`)

## Business Functions

Key business modules include:
- Customer management (`customer_input.php`, `customer_save.php`, `customer_print.php`)
- Estimate generation (`estimate.php`, `et_insert.php`)
- Excel export/import (`excelform.php`, `excelpdf.php`)
- Company registration (`registcompany.php`, `registsupplier.php`)
- Error type management (`registerrortype_process.php`)
- Steel item registration (`registsteelitem_process.php`)

## Important Security Notes

- Database credentials are stored in plain text in `/lib/mydb.php`
- Session-based authentication with level-based access control
- Mobile device detection implemented in `load_header.php`
- Each work area (`/jtech/` and `/mywork/`) has isolated configuration and database tables