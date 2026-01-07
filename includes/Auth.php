<?php declare(strict_types=1);

/**
 * Authentication class for user login and session management
 * 
 * Features:
 * - Password hashing with bcrypt
 * - Session management
 * - CSRF token generation
 * - Role-based access control
 * - Session regeneration on login
 * 
 * @package Weba
 * @author Danny Duong
 */
class Auth {
    private Database $db;
    private const SESSION_USER_KEY = 'user_id';
    private const SESSION_ROLE_KEY = 'user_role';
    private const SESSION_USERNAME_KEY = 'username';

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Authenticate user with username/email and password
     * 
     * @param string $usernameOrEmail Username or email
     * @param string $password Plain text password
     * @return bool True if authentication successful
     */
    public function login(string $usernameOrEmail, string $password): bool {
        try {
            $sql = "SELECT id, username, email, password_hash, role, full_name 
                    FROM users 
                    WHERE (username = :username OR email = :email) 
                    LIMIT 1";
            
            $user = $this->db->fetchOne($sql, ['username' => $usernameOrEmail, 'email' => $usernameOrEmail]);

            if (!$user) {
                return false;
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Set session variables
            $_SESSION[self::SESSION_USER_KEY] = $user['id'];
            $_SESSION[self::SESSION_ROLE_KEY] = $user['role'];
            $_SESSION[self::SESSION_USERNAME_KEY] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();

            // Update last login time
            $this->db->update(
                'users',
                ['last_login' => date('Y-m-d H:i:s')],
                'id = :id',
                ['id' => $user['id']]
            );

            return true;

        } catch (Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Logout current user
     * 
     * @return void
     */
    public function logout(): void {
        $_SESSION = [];
        
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        session_destroy();
    }

    /**
     * Check if user is logged in
     * 
     * @return bool
     */
    public function isLoggedIn(): bool {
        if (!isset($_SESSION[self::SESSION_USER_KEY])) {
            return false;
        }

        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            if ($elapsed > SESSION_LIFETIME) {
                $this->logout();
                return false;
            }
        }

        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }

    /**
     * Get current user ID
     * 
     * @return int|null
     */
    public function getUserId(): ?int {
        return $_SESSION[self::SESSION_USER_KEY] ?? null;
    }

    /**
     * Get current user role
     * 
     * @return string|null
     */
    public function getUserRole(): ?string {
        return $_SESSION[self::SESSION_ROLE_KEY] ?? null;
    }

    /**
     * Get current username
     * 
     * @return string|null
     */
    public function getUsername(): ?string {
        return $_SESSION[self::SESSION_USERNAME_KEY] ?? null;
    }

    /**
     * Check if user has specific role
     * 
     * @param string $role Role to check (admin, editor, author)
     * @return bool
     */
    public function hasRole(string $role): bool {
        return $this->getUserRole() === $role;
    }

    /**
     * Check if user has admin privileges
     * 
     * @return bool
     */
    public function isAdmin(): bool {
        return $this->hasRole('admin');
    }

    /**
     * Require user to be logged in (redirect to login if not)
     * 
     * @param string $redirectUrl URL to redirect after login
     * @return void
     */
    public function requireLogin(string $redirectUrl = '/admin/login.php'): void {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Require specific role (redirect if not authorized)
     * 
     * @param string $role Required role
     * @param string $redirectUrl URL to redirect if unauthorized
     * @return void
     */
    public function requireRole(string $role, string $redirectUrl = '/admin'): void {
        $this->requireLogin();
        
        if (!$this->hasRole($role)) {
            header('Location: ' . $redirectUrl);
            exit;
        }
    }

    /**
     * Generate CSRF token
     * 
     * @return string
     */
    public function generateCSRFToken(): string {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool
     */
    public function validateCSRFToken(string $token): bool {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    /**
     * Generate HTML input for CSRF token
     * 
     * @return string
     */
    public function getCSRFInput(): string {
        $token = $this->generateCSRFToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Hash password (for user creation/update)
     * 
     * @param string $password Plain text password
     * @return string Hashed password
     */
    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Create new user
     * 
     * @param array $userData User data (username, email, password, full_name, role)
     * @return int|false User ID if successful, false otherwise
     */
    public function createUser(array $userData) {
        try {
            // Validate required fields
            $required = ['username', 'email', 'password', 'full_name'];
            foreach ($required as $field) {
                if (empty($userData[$field])) {
                    throw new Exception("Field {$field} is required");
                }
            }

            // Check if username or email already exists
            if ($this->db->exists('users', 'username = :username', ['username' => $userData['username']])) {
                throw new Exception('Username already exists');
            }

            if ($this->db->exists('users', 'email = :email', ['email' => $userData['email']])) {
                throw new Exception('Email already exists');
            }

            // Prepare data
            $data = [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password_hash' => self::hashPassword($userData['password']),
                'full_name' => $userData['full_name'],
                'role' => $userData['role'] ?? 'author'
            ];

            return $this->db->insert('users', $data);

        } catch (Exception $e) {
            error_log('Create user error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user data by ID
     * 
     * @param int $userId User ID
     * @return array|false
     */
    public function getUserById(int $userId) {
        $sql = "SELECT id, username, email, full_name, role, two_factor_enabled, last_login, created_at 
                FROM users 
                WHERE id = :id 
                LIMIT 1";
        
        return $this->db->fetchOne($sql, ['id' => $userId]);
    }
}
