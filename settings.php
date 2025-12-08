<?php

class SiteSettings {
    private $pdo;
    private $config_file = __DIR__ . '/config/settings.php';
    
    public function __construct() {
        $this->pdo = getDBConnection();
    }
    
    public function get($key, $default = null) {
        try {
            $stmt = $this->pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if ($result) {
                return $result['setting_value'];
            }
            return $default;
        } catch (PDOException $e) {
            return $default;
        }
    }
    
    public function set($key, $value) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO settings (setting_key, setting_value) 
                                         VALUES (?, ?) 
                                         ON DUPLICATE KEY UPDATE setting_value = ?");
            return $stmt->execute([$key, $value, $value]);
        } catch (PDOException $e) {
            error_log("Settings save error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getAll() {
        try {
            $stmt = $this->pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            return $settings;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function generateConfigFile($settings) {
        if (!is_dir(__DIR__ . '/config')) {
            mkdir(__DIR__ . '/config', 0755, true);
        }
        
        $config_code = "<?php\n";
        $config_code .= "// Auto-generated site settings configuration\n";
        $config_code .= "// Last updated: " . date('Y-m-d H:i:s') . "\n\n";
        $config_code .= "\$SITE_SETTINGS = [\n";
        
        foreach ($settings as $key => $value) {
            $escaped_value = var_export($value, true);
            $config_code .= "    '{$key}' => {$escaped_value},\n";
        }
        
        $config_code .= "];\n";
        $config_code .= "?>";
        
        return file_put_contents($this->config_file, $config_code) !== false;
    }
    
    public function delete($key) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM settings WHERE setting_key = ?");
            return $stmt->execute([$key]);
        } catch (PDOException $e) {
            return false;
        }
    }
}

?>
