<?php

require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/ValidationHelper.php';

/**
 * SetupService Class
 *
 * Orchestrates the application setup process including validation,
 * database initialization, and user creation.
 *
 * @package    CodeFiesta
 * @subpackage Admin
 * @author     CodeFiesta Development Team
 * @version    1.0.0
 */
class SetupService
{
    /**
     * @var PDO Database connection
     */
    private PDO $pdo;

    /**
     * @var DatabaseManager Database operations manager
     */
    private DatabaseManager $dbManager;

    /**
     * @var ValidationHelper Input validation helper
     */
    private ValidationHelper $validator;

    /**
     * @var array Success messages
     */
    private array $successMessages = [];

    /**
     * @var string|null Error message
     */
    private ?string $errorMessage = null;

    /**
     * Constructor
     *
     * @param PDO $pdo Database connection instance
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->dbManager = new DatabaseManager($pdo);
        $this->validator = new ValidationHelper();
    }

    /**
     * Check if setup has already been completed
     *
     * @return bool True if setup is complete
     */
    public function isSetupComplete(): bool
    {
        return $this->dbManager->isSetupComplete();
    }

    /**
     * Process setup form submission
     *
     * @param array $postData POST request data
     * @return bool True if setup successful, false otherwise
     */
    public function processSetup(array $postData): bool
    {
        // Extract and sanitize input data
        $username = trim($postData['username'] ?? '');
        $password = $postData['password'] ?? '';
        $confirmPassword = $postData['confirm_password'] ?? '';

        // Validate input
        if (!$this->validateSetupInput($username, $password, $confirmPassword)) {
            return false;
        }

        // Perform database setup
        try {
            return $this->executeSetup($username, $password);
        } catch (PDOException $e) {
            $this->errorMessage = "Database Error: " . htmlspecialchars($e->getMessage());
            return false;
        }
    }

    /**
     * Validate setup input data
     *
     * @param string $username       Username
     * @param string $password       Password
     * @param string $confirmPassword Confirmation password
     * @return bool True if validation passes
     */
    private function validateSetupInput(
        string $username,
        string $password,
        string $confirmPassword
    ): bool {
        // Validate username
        if (!$this->validator->validateUsername($username)) {
            $this->errorMessage = $this->validator->getFirstError();
            return false;
        }

        // Validate password
        if (!$this->validator->validatePassword($password)) {
            $this->errorMessage = $this->validator->getFirstError();
            return false;
        }

        // Validate password match
        if (!$this->validator->validatePasswordMatch($password, $confirmPassword)) {
            $this->errorMessage = $this->validator->getFirstError();
            return false;
        }

        return true;
    }

    /**
     * Execute the complete setup process
     *
     * @param string $username Admin username
     * @param string $password Admin password
     * @return bool True on success
     * @throws PDOException If database operation fails
     */
    private function executeSetup(string $username, string $password): bool
    {
        try {
            // Create all tables (DDL causes implicit commit in MySQL, so we run this outside the transaction)
            $this->dbManager->createTables();

            // Start transaction for atomic data insertion
            $this->pdo->beginTransaction();

            // Insert default data
            $this->dbManager->insertDefaultFaqData();
            $this->dbManager->insertDefaultServicesData();
            $this->dbManager->insertDefaultSiteSettings();

            // Create admin user
            $this->dbManager->createAdminUser($username, $password);

            // Commit transaction
            $this->pdo->commit();

            // Collect success messages
            $this->successMessages = $this->dbManager->getMessages();

            return true;
        } catch (Exception $e) {
            // Rollback on error if a transaction is active
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get error message
     *
     * @return string|null Error message or null if no error
     */
    public function getError(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * Get success messages as HTML string
     *
     * @return string HTML formatted success messages
     */
    public function getSuccessMessagesHtml(): string
    {
        return implode('<br>', $this->successMessages);
    }

    /**
     * Get success messages array
     *
     * @return array Success messages
     */
    public function getSuccessMessages(): array
    {
        return $this->successMessages;
    }

    /**
     * Self-destruct setup file for security
     *
     * @param string $filename Full path to setup file
     * @return void
     */
    public static function selfDestruct(string $filename): void
    {
        // Register shutdown function to delete the file
        register_shutdown_function(function() use ($filename) {
            if (file_exists($filename)) {
                @unlink($filename);
            }
        });
    }
}
