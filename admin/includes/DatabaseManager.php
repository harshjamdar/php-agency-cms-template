<?php

/**
 * DatabaseManager Class
 *
 * Handles database table creation and schema management operations.
 * Provides a centralized, organized approach to database setup and migrations.
 *
 * @package    CodeFiesta
 * @subpackage Admin
 * @author     CodeFiesta Development Team
 * @version    1.0.0
 */
class DatabaseManager
{
    /**
     * @var PDO Database connection instance
     */
    private PDO $pdo;

    /**
     * @var array Collection of messages logged during operations
     */
    private array $messages = [];

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Check if setup has already been completed
     *
     * @return bool True if users table exists and has records
     */
    public function isSetupComplete(): bool
    {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            // Tables don't exist yet
            return false;
        }
    }

    /**
     * Create all required database tables
     *
     * @return bool True on success, false on failure
     * @throws PDOException If database operation fails
     */
    public function createTables(): bool
    {
        try {
            $this->createUsersTable();
            $this->createProjectsTable();
            $this->createBlogPostsTable();
            $this->createInquiriesTable();
            $this->createTeamMembersTable();
            $this->createPageViewsTable();
            $this->createApiSettingsTable();
            $this->createAnalyticsSessionsTable();
            $this->createAnalyticsPageviewsTable();
            $this->createSeoMetaTable();
            $this->createNewsletterSubscribersTable();
            $this->createFaqTable();
            $this->createServicesTable();
            $this->createBookingsTable();
            $this->createSiteSettingsTable();
            $this->createTestimonialsTable();
            $this->updateUsersTableWithRoles();

            return true;
        } catch (PDOException $e) {
            $this->addMessage("❌ Error creating tables: " . htmlspecialchars($e->getMessage()));
            throw $e;
        }
    }

    /**
     * Create users table
     *
     * @return void
     */
    private function createUsersTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'users' created");
    }

    /**
     * Create projects table
     *
     * @return void
     */
    private function createProjectsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(100) NOT NULL,
            slug VARCHAR(255) UNIQUE,
            description TEXT,
            content TEXT,
            image_url VARCHAR(255),
            category VARCHAR(50),
            tags VARCHAR(255),
            project_url VARCHAR(255),
            is_featured TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'projects' created");
    }

    /**
     * Create blog_posts table
     *
     * @return void
     */
    private function createBlogPostsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS blog_posts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            content TEXT NOT NULL,
            excerpt TEXT,
            author VARCHAR(100),
            image_url VARCHAR(255),
            status ENUM('draft', 'published') DEFAULT 'draft',
            is_featured TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'blog_posts' created");
    }

    /**
     * Create inquiries table
     *
     * @return void
     */
    private function createInquiriesTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS inquiries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            subject VARCHAR(255),
            message TEXT NOT NULL,
            notes TEXT,
            status ENUM('new', 'read', 'replied') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'inquiries' created");
    }

    /**
     * Create team_members table
     *
     * @return void
     */
    private function createTeamMembersTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS team_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            role VARCHAR(100) NOT NULL,
            image_url VARCHAR(255),
            bio TEXT,
            linkedin_url VARCHAR(255),
            github_url VARCHAR(255),
            twitter_url VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'team_members' created");
    }

    /**
     * Create page_views table
     *
     * @return void
     */
    private function createPageViewsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS page_views (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_name VARCHAR(100) NOT NULL UNIQUE,
            view_count INT DEFAULT 0,
            last_viewed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'page_views' created");
    }

    /**
     * Create api_settings table
     *
     * @return void
     */
    private function createApiSettingsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS api_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'api_settings' created");
    }

    /**
     * Create analytics_sessions table for advanced user tracking
     *
     * @return void
     */
    private function createAnalyticsSessionsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS analytics_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL UNIQUE,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            referrer TEXT,
            device_type VARCHAR(20),
            browser VARCHAR(50),
            country VARCHAR(100),
            country_code VARCHAR(5),
            city VARCHAR(100),
            region VARCHAR(100),
            latitude DECIMAL(10, 8),
            longitude DECIMAL(11, 8),
            page_count INT DEFAULT 1,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_session_id (session_id),
            INDEX idx_started_at (started_at),
            INDEX idx_country (country_code),
            INDEX idx_last_activity (last_activity)
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'analytics_sessions' created");
    }

    /**
     * Create analytics_pageviews table for detailed page tracking
     *
     * @return void
     */
    private function createAnalyticsPageviewsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS analytics_pageviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            page_name VARCHAR(200) NOT NULL,
            page_url TEXT,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session_id (session_id),
            INDEX idx_page_name (page_name),
            INDEX idx_viewed_at (viewed_at),
            FOREIGN KEY (session_id) REFERENCES analytics_sessions(session_id) ON DELETE CASCADE
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'analytics_pageviews' created");
    }

    /**
     * Create seo_meta table for SEO management
     *
     * @return void
     */
    private function createSeoMetaTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS seo_meta (
            id INT AUTO_INCREMENT PRIMARY KEY,
            page_slug VARCHAR(255) NOT NULL UNIQUE,
            page_title VARCHAR(200),
            meta_description TEXT,
            meta_keywords TEXT,
            og_title VARCHAR(200),
            og_description TEXT,
            og_image VARCHAR(255),
            canonical_url VARCHAR(255),
            robots VARCHAR(50) DEFAULT 'index, follow',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'seo_meta' created");
    }

    /**
     * Create newsletter_subscribers table
     *
     * @return void
     */
    private function createNewsletterSubscribersTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL UNIQUE,
            name VARCHAR(100),
            status ENUM('active', 'unsubscribed') DEFAULT 'active',
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at TIMESTAMP NULL,
            ip_address VARCHAR(45),
            source VARCHAR(50) DEFAULT 'website',
            INDEX idx_email (email),
            INDEX idx_status (status)
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'newsletter_subscribers' created");
    }

    /**
     * Create FAQ table
     *
     * @return void
     */
    private function createFaqTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS faq (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question VARCHAR(500) NOT NULL,
            answer TEXT NOT NULL,
            category VARCHAR(100) DEFAULT 'general',
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'faq' created");
    }

    /**
     * Create services table
     *
     * @return void
     */
    private function createServicesTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS services (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            icon VARCHAR(50) DEFAULT 'code',
            color VARCHAR(50) DEFAULT 'blue',
            is_featured TINYINT(1) DEFAULT 0,
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'services' created");
    }

    /**
     * Create bookings/consultations table
     *
     * @return void
     */
    private function createBookingsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            service_type VARCHAR(100),
            booking_date DATE NOT NULL,
            booking_time TIME NOT NULL,
            duration INT DEFAULT 30,
            message TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            notes TEXT,
            reminder_sent TINYINT(1) DEFAULT 0,
            zoom_link VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_booking_date (booking_date),
            INDEX idx_status (status)
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'bookings' created");
    }

    /**
     * Create site_settings table for white label configuration
     *
     * @return void
     */
    private function createSiteSettingsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS site_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL UNIQUE,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'site_settings' created");
    }

    /**
     * Create testimonials table
     *
     * @return void
     */
    private function createTestimonialsTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_name VARCHAR(255) NOT NULL,
            client_position VARCHAR(255),
            client_company VARCHAR(255),
            client_image VARCHAR(255),
            content TEXT NOT NULL,
            rating INT DEFAULT 5,
            display_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
        $this->addMessage("✓ Table 'testimonials' created");
    }

    /**
     * Update users table with additional role-based columns
     *
     * @return void
     */
    private function updateUsersTableWithRoles(): void
    {
        $sql = "ALTER TABLE users 
                ADD COLUMN IF NOT EXISTS role ENUM('admin', 'editor', 'viewer') DEFAULT 'editor',
                ADD COLUMN IF NOT EXISTS full_name VARCHAR(100),
                ADD COLUMN IF NOT EXISTS email VARCHAR(100),
                ADD COLUMN IF NOT EXISTS status ENUM('active', 'inactive') DEFAULT 'active',
                ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL";
        
        try {
            $this->pdo->exec($sql);
            $this->addMessage("✓ Users table updated with roles");
        } catch (PDOException $e) {
            // Columns might already exist - this is acceptable
            $this->addMessage("✓ Users table checked");
        }
    }

    /**
     * Insert default FAQ data
     *
     * @return void
     */
    public function insertDefaultFaqData(): void
    {
        $defaultFAQs = [
            [
                'How long does it take to build a website or app?',
                'Timelines vary by complexity. A standard business website typically takes 2-4 weeks, while a custom mobile app MVP can take 6-12 weeks. We provide a detailed timeline after our initial consultation.',
                1
            ],
            [
                'Do you provide post-launch support?',
                'Absolutely. We offer 30 days of free support after launch. Beyond that, we have flexible maintenance packages to ensure your software remains secure, updated, and performing optimally.',
                2
            ],
            [
                'What technology stack do you use?',
                'We specialize in modern, scalable stacks. For web, we use React, Next.js, and Node.js. For mobile, we use Flutter and React Native. Our cloud infrastructure is built on AWS and Google Cloud.',
                3
            ],
            [
                'What are your pricing models?',
                'We offer flexible pricing models including fixed-price projects, monthly retainers, and hourly rates. Pricing varies based on project scope, timeline, and technology requirements. Contact us for a detailed quote.',
                4
            ],
            [
                'Do you work with startups?',
                'Yes! We specialize in helping startups build MVPs and scale their products. We offer startup-friendly packages and can even discuss equity partnerships for the right projects.',
                5
            ]
        ];

        try {
            $stmt = $this->pdo->prepare("INSERT INTO faq (question, answer, display_order) VALUES (?, ?, ?)");
            foreach ($defaultFAQs as $faq) {
                $stmt->execute($faq);
            }
            $this->addMessage("✓ Default FAQ data inserted");
        } catch (PDOException $e) {
            $this->addMessage("⚠️ FAQ defaults skipped (" . htmlspecialchars($e->getMessage()) . ")");
        }
    }

    /**
     * Insert default services data
     *
     * @return void
     */
    public function insertDefaultServicesData(): void
    {
        $defaultServices = [
            [
                'SEO-Friendly Website Development',
                'We build lightning-fast, responsive websites designed to rank high on Google. Perfect for small businesses looking for affordable web design without compromising quality.',
                'globe',
                'blue',
                1,
                1
            ],
            [
                'Native Android & iOS Solutions',
                'Top-tier Android app development services in India. We create seamless mobile experiences using React Native and Flutter for maximum reach and performance.',
                'smartphone',
                'purple',
                1,
                2
            ],
            [
                'ROI-Focused Digital Marketing',
                'Our digital marketing agency focuses on one metric: Profit. SEO, PPC, and Social Media campaigns that bring leads, not just likes.',
                'trending-up',
                'green',
                1,
                3
            ],
            [
                'AI Integration & Cloud',
                'Future-proof your business with custom AI chatbots and scalable cloud architecture.',
                'cpu',
                'orange',
                0,
                4
            ],
            [
                'E-commerce Solutions',
                'Build powerful online stores with seamless payment integration, inventory management, and customer analytics.',
                'shopping-cart',
                'cyan',
                0,
                5
            ],
            [
                'Custom Software Development',
                'Enterprise-grade custom software solutions tailored to your unique business requirements and workflows.',
                'code',
                'indigo',
                0,
                6
            ]
        ];

        try {
            $stmt = $this->pdo->prepare("INSERT INTO services (title, description, icon, color, is_featured, display_order) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($defaultServices as $service) {
                $stmt->execute($service);
            }
            $this->addMessage("✓ Default services data inserted");
        } catch (PDOException $e) {
            $this->addMessage("⚠️ Services defaults skipped (" . htmlspecialchars($e->getMessage()) . ")");
        }
    }

    /**
     * Insert default site settings
     *
     * @return void
     */
    public function insertDefaultSiteSettings(): void
    {
        $defaultSettings = [
            ['site_name', 'My Agency', 'text'],
            ['site_tagline', 'Custom Software Development Company', 'text'],
            ['primary_color', '#8b5cf6', 'color'],
            ['secondary_color', '#ec4899', 'color'],
            ['logo_url', '', 'image'],
            ['favicon_url', '', 'image'],
            ['contact_email', 'admin@example.com', 'email'],
            ['contact_phone', '', 'text'],
            ['footer_text', '© 2025 My Agency. All rights reserved.', 'text'],
            ['active_theme', 'default', 'text']
        ];

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO site_settings (setting_key, setting_value, setting_type) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE setting_value = setting_value"
            );
            foreach ($defaultSettings as $setting) {
                $stmt->execute($setting);
            }
            $this->addMessage("✓ Default site settings created");
        } catch (PDOException $e) {
            $this->addMessage("⚠️ Site settings skipped (" . htmlspecialchars($e->getMessage()) . ")");
        }
    }

    /**
     * Create admin user with provided credentials
     *
     * @param string $username Admin username
     * @param string $password Plain text password (will be hashed)
     * @param string $email    Admin email address
     * @param string $fullName Admin full name
     * @return bool True on success
     * @throws PDOException If database operation fails
     */
    public function createAdminUser(
        string $username,
        string $password,
        string $email = 'admin@example.com',
        string $fullName = 'Administrator'
    ): bool {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (username, password, role, email, full_name) 
             VALUES (:username, :password, 'admin', :email, :full_name)"
        );
        
        $result = $stmt->execute([
            'username' => $username,
            'password' => $hashedPassword,
            'email' => $email,
            'full_name' => $fullName
        ]);

        if ($result) {
            $this->addMessage("✓ Admin user created successfully with admin role");
        }

        return $result;
    }

    /**
     * Add a message to the message collection
     *
     * @param string $message Message to add
     * @return void
     */
    private function addMessage(string $message): void
    {
        $this->messages[] = $message;
    }

    /**
     * Get all logged messages
     *
     * @return array Array of message strings
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Get messages as HTML string
     *
     * @return string Messages formatted as HTML
     */
    public function getMessagesAsHtml(): string
    {
        return implode('<br>', $this->messages);
    }
}
