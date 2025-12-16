# CodeFiesta - Modern Agency CMS & Landing Page

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/php-%5E8.0-777BB4.svg)
![Tailwind CSS](https://img.shields.io/badge/tailwindcss-%5E3.0-38B2AC.svg)
![Status](https://img.shields.io/badge/status-active-success.svg)

A high-performance, SEO-optimized website solution designed specifically for digital agencies, software startups, and freelance developers. Built with native PHP and Tailwind CSS, it features a comprehensive Admin Dashboard (CMS), Blog engine, Portfolio management, and built-in Analytics.

## 🚀 Key Features

### Public Facing
- **Modern Design**: Fully responsive, dark-mode themed UI built with Tailwind CSS.
- **SEO Optimized**: Dynamic meta tags, Open Graph support, auto-generated sitemap, and schema markup.
- **Performance**: Lightweight architecture with no heavy framework overhead.
- **Lead Generation**: Integrated inquiry forms, cost estimator, and newsletter subscription.
- **Content Sections**: Services, Portfolio/Projects, Blog, Testimonials, Team, and FAQ.

### Admin Dashboard (CMS)
- **Dashboard Overview**: Real-time stats, active sessions, and recent inquiries.
- **Content Management**:
  - **Blog Editor**: Rich text editing for articles.
  - **Project Manager**: Add and update portfolio items.
  - **Service Manager**: Manage service offerings and pricing.
- **Analytics Suite**: Built-in visitor tracking, page views, and session analysis (no third-party cookies required).
- **A/B Testing**: Create and track split tests for conversion optimization (e.g., Hero Headlines).
- **SEO Manager**: Edit meta titles, descriptions, and keywords for any page directly from the admin.
- **Whitelabeling**: Customize the agency name, logo, and branding settings.

## 🛡️ Security Features

Built with a security-first approach:
- **CSRF Protection**: Automated token generation and validation for all forms.
- **XSS Prevention**: Input sanitization and output encoding.
- **Secure Headers**: Implements HSTS, X-Frame-Options, and CSP.
- **Session Management**: Secure, HTTP-only cookies with strict SameSite policies.
- **PDO Prepared Statements**: Full protection against SQL injection.

## 📂 Project Structure

```
codefiesta/
├── admin/                  # Admin Panel & CMS
│   ├── includes/           # Admin helper classes (Security, Database)
│   ├── ab-testing.php      # A/B Testing Manager
│   ├── analytics.php       # Analytics Dashboard
│   └── setup.php           # Initial Setup Script
├── api/                    # Internal API endpoints (Tracking, Forms)
├── assets/                 # Static assets (CSS, JS, Images)
├── includes/               # Frontend components & helpers
│   ├── helpers/            # SEO, Tracking, Email helpers
│   └── sections/           # Page sections (Hero, Services, etc.)
├── index.php               # Main Landing Page
├── config.php              # Database Configuration
└── README.md               # Documentation
```

## 🛠️ Tech Stack

- **Backend**: PHP 8.0+ (Native, no framework)
- **Frontend**: HTML5, Tailwind CSS (via CDN or CLI), Vanilla JavaScript
- **Database**: MySQL / MariaDB
- **Security**: PDO Prepared Statements, CSRF Protection, XSS Filtering, Security Headers

## 📦 Installation

1. **Clone the Repository**
   ```bash
   git clone https://github.com/yourusername/php-agency-cms-template.git
   cd php-agency-cms-template
   ```

2. **Server Requirements**
   - PHP >= 8.0
   - MySQL >= 5.7
   - Apache/Nginx web server

3. **Setup Database**
   - Create a new MySQL database (e.g., `codefiesta_db`).
   - Navigate to `http://localhost/your-project/admin/setup.php` in your browser.
   - Follow the on-screen wizard to connect the database and create your admin account.
   - The setup script will automatically create the necessary tables and `.env` file.

4. **Configure Environment**
   - Ensure the `admin/` directory is writable during setup.
   - After setup, the `admin/setup.php` file should be deleted for security.

## 🖥️ Usage

### Accessing the Admin Panel
Navigate to `/admin` and log in with the credentials created during setup.

### Customizing the Theme
- Edit `assets/css/style.css` for custom styles.
- Tailwind classes can be modified directly in the PHP files.
- Theme settings (colors, logos) can be managed via **Admin > Settings > Whitelabel**.

## 📊 Analytics & A/B Testing

The platform includes a privacy-focused analytics engine that tracks:
- **Page Views & Unique Sessions**
- **Traffic Sources (Referrers)**
- **User Devices & Browsers**
- **Conversion Rates**

The **A/B Testing** module allows you to run experiments on headlines and content to optimize conversion rates without external tools.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👤 Author

**Harsh Jamdar**
- GitHub: [@harshjamdar](https://github.com/harshjamdar)
- LinkedIn: [Harsh Jamdar](https://www.linkedin.com/in/harsh-jamdar/)

---
*Built with ❤️ for the developer community.*
