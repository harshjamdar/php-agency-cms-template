# Architecture Overview

## Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         setup.php                                │
│                    (130 lines - UI Layer)                        │
│                                                                   │
│  • Handles HTTP request/response                                 │
│  • Displays HTML form and success messages                       │
│  • Minimal business logic                                        │
└────────────────────────────┬────────────────────────────────────┘
                             │
                             │ uses
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      SetupService.php                            │
│                  (180 lines - Service Layer)                     │
│                                                                   │
│  • Orchestrates the setup workflow                               │
│  • Coordinates validation and database operations                │
│  • Manages transactions                                          │
│  • Returns success/error messages                                │
└──────┬──────────────────────────┬───────────────────────────────┘
       │                          │
       │ uses                     │ uses
       ▼                          ▼
┌─────────────────┐      ┌────────────────────────┐
│ ValidationHelper│      │   DatabaseManager      │
│   (220 lines)   │      │     (640 lines)        │
│                 │      │                        │
│ • Username      │      │ • Create 17 tables     │
│ • Password      │      │ • Insert default data  │
│ • Email         │      │ • Create admin user    │
│ • Sanitization  │      │ • Transaction support  │
└─────────────────┘      └────────────────────────┘
```

## Data Flow

```
User Input (HTML Form)
        │
        ▼
    setup.php
        │
        ├─► ValidationHelper.sanitizeOutput()  (for display)
        │
        ▼
  SetupService.processSetup($_POST)
        │
        ├─► ValidationHelper.validateUsername()
        ├─► ValidationHelper.validatePassword()
        ├─► ValidationHelper.validatePasswordMatch()
        │
        ▼
  SetupService.executeSetup()
        │
        ├─► $pdo->beginTransaction()
        │
        ├─► DatabaseManager.createTables()
        │       ├─► Create users table
        │       ├─► Create projects table
        │       ├─► Create blog_posts table
        │       └─► ... (14 more tables)
        │
        ├─► DatabaseManager.insertDefaultFaqData()
        ├─► DatabaseManager.insertDefaultServicesData()
        ├─► DatabaseManager.insertDefaultSiteSettings()
        ├─► DatabaseManager.createAdminUser()
        │
        ├─► $pdo->commit()  (success)
        │   OR
        └─► $pdo->rollback()  (on error)
```

## Class Hierarchy

```
┌─────────────────────────────────────────────┐
│         Helper Classes (Utilities)          │
└─────────────────────────────────────────────┘

┌─────────────────────┐
│  ValidationHelper   │
├─────────────────────┤
│ Properties:         │
│ - errors: array     │
│                     │
│ Methods:            │
│ + validateUsername()│
│ + validatePassword()│
│ + validateEmail()   │
│ + sanitizeInput()   │
│ + sanitizeOutput()  │
│ + getFirstError()   │
│ + hasErrors()       │
└─────────────────────┘

┌─────────────────────┐
│  SecurityHelper     │
├─────────────────────┤
│ Static Methods:     │
│ + generateCsrfToken()
│ + validateCsrfToken()
│ + hashPassword()    │
│ + verifyPassword()  │
│ + sanitizeHtml()    │
│ + setSecurityHeaders()
│ + checkRateLimit()  │
│ + getClientIp()     │
└─────────────────────┘

┌─────────────────────┐
│  DatabaseManager    │
├─────────────────────┤
│ Properties:         │
│ - pdo: PDO          │
│ - messages: array   │
│                     │
│ Methods:            │
│ + createTables()    │
│ + createUsersTable()│
│ + createProjectsTable()
│ + insertDefaultData()
│ + createAdminUser() │
│ + getMessages()     │
└─────────────────────┘

┌─────────────────────┐
│   SetupService      │
├─────────────────────┤
│ Properties:         │
│ - pdo: PDO          │
│ - dbManager         │
│ - validator         │
│ - messages: array   │
│                     │
│ Dependencies:       │
│ → DatabaseManager   │
│ → ValidationHelper  │
│                     │
│ Methods:            │
│ + processSetup()    │
│ + validateInput()   │
│ + executeSetup()    │
│ + getError()        │
│ + selfDestruct()    │
└─────────────────────┘
```

## Security Layers

```
┌────────────────────────────────────────────────────┐
│              Layer 1: Client-Side                  │
├────────────────────────────────────────────────────┤
│ • HTML5 form validation (pattern, minlength)      │
│ • ARIA accessibility attributes                    │
└────────────────────────────────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────────┐
│              Layer 2: Input Validation             │
├────────────────────────────────────────────────────┤
│ ValidationHelper:                                  │
│ • validateUsername() - 3+ chars, alphanumeric      │
│ • validatePassword() - 6+ chars                    │
│ • validatePasswordMatch() - passwords match        │
└────────────────────────────────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────────┐
│          Layer 3: Business Logic Security          │
├────────────────────────────────────────────────────┤
│ SetupService:                                      │
│ • Transaction-based operations                     │
│ • Rollback on errors                               │
│ • Self-destruct after success                      │
└────────────────────────────────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────────┐
│          Layer 4: Database Security                │
├────────────────────────────────────────────────────┤
│ DatabaseManager:                                   │
│ • PDO prepared statements                          │
│ • Password hashing (password_hash)                 │
│ • Foreign key constraints                          │
└────────────────────────────────────────────────────┘
                     │
                     ▼
┌────────────────────────────────────────────────────┐
│          Layer 5: Output Security                  │
├────────────────────────────────────────────────────┤
│ • htmlspecialchars() on all output                 │
│ • ENT_QUOTES flag for attribute protection         │
│ • Consistent sanitization via ValidationHelper    │
└────────────────────────────────────────────────────┘
```

## Database Schema (17 Tables Created)

```
Core Tables:
├─ users                    (authentication)
├─ inquiries                (contact form submissions)
└─ page_views               (analytics - simple)

Content Tables:
├─ projects                 (portfolio items)
├─ blog_posts               (blog content)
├─ team_members             (team profiles)
├─ testimonials             (client reviews)
├─ faq                      (frequently asked questions)
└─ services                 (service offerings)

Configuration Tables:
├─ site_settings            (white-label configuration)
├─ api_settings             (API credentials)
└─ seo_meta                 (SEO metadata)

Marketing Tables:
├─ newsletter_subscribers   (email list)
└─ bookings                 (consultation scheduling)

Analytics Tables (Advanced):
├─ analytics_sessions       (user session tracking)
└─ analytics_pageviews      (detailed page tracking)
```

## Transaction Flow

```
BEGIN TRANSACTION
    │
    ├─► Create all 17 tables
    │   └─► If error → ROLLBACK & EXIT
    │
    ├─► Insert default FAQ data (5 entries)
    │   └─► If error → Log warning, continue
    │
    ├─► Insert default services data (6 entries)
    │   └─► If error → Log warning, continue
    │
    ├─► Insert default site settings (9 settings)
    │   └─► If error → Log warning, continue
    │
    ├─► Create admin user
    │   └─► If error → ROLLBACK & EXIT
    │
    └─► COMMIT
        └─► Success! Self-destruct setup.php
```

## File Dependencies

```
setup.php
    │
    ├─ require_once 'config.php'
    │       └─ $pdo (PDO database connection)
    │
    ├─ require_once 'includes/SetupService.php'
    │       │
    │       ├─ require_once 'DatabaseManager.php'
    │       └─ require_once 'ValidationHelper.php'
    │
    └─ require_once 'includes/ValidationHelper.php'
            └─ (for output sanitization in HTML)
```

## Error Handling Strategy

```
┌─────────────────────────────────────────┐
│           Error Type                     │
├─────────────────────────────────────────┤
│                                          │
│  Validation Error                        │
│      ├─ ValidationHelper catches         │
│      ├─ Stored in $errors array          │
│      └─ Returned to user via form        │
│                                          │
│  Database Error (PDOException)           │
│      ├─ Transaction rollback             │
│      ├─ Error message sanitized          │
│      └─ Displayed to user                │
│                                          │
│  Setup Already Complete                  │
│      ├─ Early detection                  │
│      └─ Die with message                 │
│                                          │
│  File System Error (unlink)              │
│      ├─ Suppressed with @                │
│      └─ Non-critical (self-destruct)     │
│                                          │
└─────────────────────────────────────────┘
```

## Reusability Across Admin Panel

```
┌─────────────────────────────────────────────────┐
│         Other Admin Files Can Use:              │
└─────────────────────────────────────────────────┘

admin/index.php (Login)
    └─► ValidationHelper::validateUsername()
    └─► SecurityHelper::checkRateLimit()
    └─► SecurityHelper::hashPassword()

admin/users.php (User Management)
    └─► ValidationHelper::validateEmail()
    └─► SecurityHelper::csrfField()
    └─► ValidationHelper::sanitizeOutput()

admin/blog-edit.php (Content Editor)
    └─► ValidationHelper::validateRequired()
    └─► SecurityHelper::sanitizeHtml()
    └─► SecurityHelper::validateCsrfToken()

admin/projects.php (Portfolio Management)
    └─► ValidationHelper::validateUrl()
    └─► ValidationHelper::sanitizeInput()

ANY FORM
    └─► SecurityHelper::csrfField()
```

## Performance Characteristics

```
Operation               | Time Complexity | Space Complexity
------------------------|-----------------|------------------
Validation              | O(n)            | O(1)
Single Table Creation   | O(1)            | O(1)
All Tables Creation     | O(17) ≈ O(1)    | O(1)
FAQ Insert (5 items)    | O(5) ≈ O(1)     | O(5)
Services Insert (6)     | O(6) ≈ O(1)     | O(6)
Settings Insert (9)     | O(9) ≈ O(1)     | O(9)
Complete Setup          | O(1)            | O(1)

Note: All database operations use prepared statements for efficiency
```

## Deployment Checklist

```
✅ Before First Run:
   ├─ Verify config.php has correct database credentials
   ├─ Ensure MySQL/MariaDB is running
   ├─ Set proper file permissions (755 for directories, 644 for files)
   └─ Ensure PHP version 7.4+ (8.0+ recommended for typed properties)

✅ During Setup:
   ├─ Access setup.php via browser
   ├─ Enter strong admin credentials
   ├─ Wait for "Setup Complete" message
   └─ Verify all 17 tables created

✅ After Setup:
   ├─ Verify setup.php has self-destructed
   ├─ Test admin login at admin/index.php
   ├─ Review admin/logs/ for any warnings
   └─ Backup the database immediately

✅ For Production:
   ├─ Enable HTTPS
   ├─ Set SecurityHelper::setSecurityHeaders()
   ├─ Implement CSRF protection on all forms
   ├─ Enable rate limiting on login page
   └─ Regular security audits
```

---

**Architecture Version**: 1.0.0  
**Last Updated**: December 16, 2025  
**Maintainer**: CodeFiesta Development Team
