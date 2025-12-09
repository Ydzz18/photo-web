<?php
/**
 * Email Confirmation Service
 * Handles email verification tokens and confirmation
 */
class EmailConfirmationService {
    private $pdo;
    private $token_expiry = 86400; // 24 hours in seconds

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Generate confirmation token for user
     * 
     * @param int $user_id User ID
     * @return string Confirmation token
     */
    public function generateToken($user_id) {
        try {
            // Ensure table exists
            $this->ensureEmailConfirmationTable();

            // Generate token
            $token = bin2hex(random_bytes(32));

            // Save to database
            $stmt = $this->pdo->prepare("
                INSERT INTO email_confirmations (user_id, token, created_at, expires_at) 
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))
            ");

            if ($stmt->execute([$user_id, hash('sha256', $token)])) {
                return $token;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Token Generation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify confirmation token
     * 
     * @param string $token Confirmation token
     * @return int|false User ID if valid, false otherwise
     */
    public function verifyToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM email_confirmations 
                WHERE token = ? 
                AND expires_at > NOW() 
                AND confirmed_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([hash('sha256', $token)]);

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return (int)$result['user_id'];
            }

            return false;
        } catch (PDOException $e) {
            error_log("Token Verification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark email as confirmed
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function confirmEmail($user_id) {
        try {
            // Mark token as confirmed
            $stmt = $this->pdo->prepare("
                UPDATE email_confirmations 
                SET confirmed_at = NOW() 
                WHERE user_id = ? AND confirmed_at IS NULL
            ");
            $stmt->execute([$user_id]);

            // Mark user email as verified
            $user_stmt = $this->pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
            return $user_stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Email Confirmation Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email is verified for user
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function isEmailVerified($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return (bool)$result['email_verified'];
            }

            return false;
        } catch (PDOException $e) {
            error_log("Email Verification Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate password reset token
     * 
     * @param int $user_id User ID
     * @return string|false Reset token or false on failure
     */
    public function generatePasswordResetToken($user_id) {
        try {
            $this->ensurePasswordResetTable();

            $token = bin2hex(random_bytes(32));

            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (user_id, token, created_at, expires_at) 
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 24 HOUR))
            ");

            if ($stmt->execute([$user_id, hash('sha256', $token)])) {
                return $token;
            }

            return false;
        } catch (PDOException $e) {
            error_log("Password Reset Token Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify password reset token
     * 
     * @param string $token Reset token
     * @return int|false User ID if valid, false otherwise
     */
    public function verifyPasswordResetToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM password_resets 
                WHERE token = ? 
                AND expires_at > NOW() 
                AND used_at IS NULL
                LIMIT 1
            ");
            $stmt->execute([hash('sha256', $token)]);

            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return (int)$result['user_id'];
            }

            return false;
        } catch (PDOException $e) {
            error_log("Password Reset Token Verification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mark password reset token as used
     * 
     * @param string $token Reset token
     * @return bool
     */
    public function usePasswordResetToken($token) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE password_resets 
                SET used_at = NOW() 
                WHERE token = ? AND used_at IS NULL
            ");
            return $stmt->execute([hash('sha256', $token)]);
        } catch (PDOException $e) {
            error_log("Mark Password Reset Token Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure email confirmations table exists
     */
    private function ensureEmailConfirmationTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'email_confirmations'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE email_confirmations (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    token VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL DEFAULT NULL,
                    confirmed_at TIMESTAMP NULL DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX (user_id),
                    INDEX (token)
                )";
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Email Confirmation Table Error: " . $e->getMessage());
        }
    }

    /**
     * Ensure password resets table exists
     */
    private function ensurePasswordResetTable() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'password_resets'");
            if ($stmt->rowCount() == 0) {
                $sql = "CREATE TABLE password_resets (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    token VARCHAR(255) UNIQUE NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    expires_at TIMESTAMP NULL DEFAULT NULL,
                    used_at TIMESTAMP NULL DEFAULT NULL,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    INDEX (user_id),
                    INDEX (token)
                )";
                $this->pdo->exec($sql);
            }
        } catch (PDOException $e) {
            error_log("Password Reset Table Error: " . $e->getMessage());
        }
    }
}
?>
