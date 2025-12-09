<?php
/**
 * Two-Factor Authentication Service
 * Handles generation and verification of 2FA codes
 */
class TwoFactorAuthService {
    private $pdo;
    private $code_length = 6;
    private $code_expiry = 600; // 10 minutes in seconds

    /**
     * Constructor
     * 
     * @param PDO $pdo Database connection
     */
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Generate and save 2FA code for user
     * 
     * @param int $user_id User ID
     * @return string Generated 2FA code
     */
    public function generateCode($user_id) {
        // Generate random 6-digit code
        $code = str_pad(random_int(0, 999999), $this->code_length, '0', STR_PAD_LEFT);
        
        // Save to database
        $this->save2FACode($user_id, $code);
        
        return $code;
    }

    /**
     * Save 2FA code to database
     * 
     * @param int $user_id User ID
     * @param string $code 2FA code
     * @return bool
     */
    private function save2FACode($user_id, $code) {
        try {
            // First, check if table exists
            $this->ensure2FATable();

            // Delete any existing codes for this user
            $stmt = $this->pdo->prepare("DELETE FROM two_factor_auth WHERE user_id = ? AND created_at < DATE_SUB(NOW(), INTERVAL 10 MINUTE)");
            $stmt->execute([$user_id]);

            // Save new code
            $stmt = $this->pdo->prepare("INSERT INTO two_factor_auth (user_id, code, created_at) VALUES (?, ?, NOW())");
            return $stmt->execute([$user_id, hash('sha256', $code)]);
        } catch (PDOException $e) {
            error_log("2FA Save Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify 2FA code
     * 
     * @param int $user_id User ID
     * @param string $code Code to verify
     * @return bool
     */
    public function verifyCode($user_id, $code) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id FROM two_factor_auth 
                WHERE user_id = ? 
                AND code = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
                LIMIT 1
            ");
            $stmt->execute([$user_id, hash('sha256', $code)]);

            if ($stmt->rowCount() > 0) {
                // Mark code as used
                $code_id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
                $delete_stmt = $this->pdo->prepare("DELETE FROM two_factor_auth WHERE id = ?");
                $delete_stmt->execute([$code_id]);
                return true;
            }

            return false;
        } catch (PDOException $e) {
            error_log("2FA Verification Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if 2FA is enabled for user
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function is2FAEnabled($user_id) {
        try {
            $stmt = $this->pdo->prepare("SELECT two_fa_enabled FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return (bool)$result['two_fa_enabled'];
            }

            return false;
        } catch (PDOException $e) {
            error_log("2FA Check Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Enable 2FA for user
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function enable2FA($user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET two_fa_enabled = 1 WHERE id = ?");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Enable 2FA Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disable 2FA for user
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public function disable2FA($user_id) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET two_fa_enabled = 0 WHERE id = ?");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Disable 2FA Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ensure 2FA table exists in database
     */
    private function ensureTable($tableName, $createSQL) {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE '$tableName'");
            if ($stmt->rowCount() == 0) {
                $this->pdo->exec($createSQL);
            }
        } catch (PDOException $e) {
            error_log("Table Check Error: " . $e->getMessage());
        }
    }

    /**
     * Ensure 2FA table exists
     */
    private function ensure2FATable() {
        $sql = "CREATE TABLE IF NOT EXISTS two_factor_auth (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            code VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX (user_id)
        )";
        
        $this->ensureTable('two_factor_auth', $sql);
    }
}
?>
