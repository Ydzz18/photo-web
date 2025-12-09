<?php
/**
 * Environment Loader
 * Loads .env file into PHP environment variables
 */

function loadEnv() {
    $env_file = __DIR__ . '/../.env';
    
    if (!file_exists($env_file)) {
        return false;
    }
    
    $env_vars = parse_ini_file($env_file);
    if ($env_vars === false) {
        return false;
    }
    
    foreach ($env_vars as $key => $value) {
        if (!getenv($key)) {
            putenv("{$key}={$value}");
        }
    }
    
    return true;
}

loadEnv();
?>
