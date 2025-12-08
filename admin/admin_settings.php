<?php
session_start();

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once '../db_connect.php';
require_once '../settings.php';
require_once 'rbac.php';

// Check permission to edit settings
requirePermission('edit_settings');

$settings = new SiteSettings();
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

$current_settings = [
    'site_name' => $settings->get('site_name', 'LensCraft'),
    'site_tagline' => $settings->get('site_tagline', 'Professional Photography Showcase'),
    'contact_email' => $settings->get('contact_email', ''),
    'contact_phone' => $settings->get('contact_phone', ''),
    'smtp_host' => $settings->get('smtp_host', ''),
    'smtp_port' => $settings->get('smtp_port', '587'),
    'smtp_username' => $settings->get('smtp_username', ''),
    'smtp_password' => $settings->get('smtp_password', ''),
    'smtp_from_email' => $settings->get('smtp_from_email', ''),
    'smtp_from_name' => $settings->get('smtp_from_name', ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $upload_error = '';
    
    $site_name = isset($_POST['site_name']) ? trim($_POST['site_name']) : '';
    $site_tagline = isset($_POST['site_tagline']) ? trim($_POST['site_tagline']) : '';
    $contact_email = isset($_POST['contact_email']) ? trim($_POST['contact_email']) : '';
    $contact_phone = isset($_POST['contact_phone']) ? trim($_POST['contact_phone']) : '';
    $smtp_host = isset($_POST['smtp_host']) ? trim($_POST['smtp_host']) : '';
    $smtp_port = isset($_POST['smtp_port']) ? trim($_POST['smtp_port']) : '';
    $smtp_username = isset($_POST['smtp_username']) ? trim($_POST['smtp_username']) : '';
    $smtp_password = isset($_POST['smtp_password']) ? trim($_POST['smtp_password']) : '';
    $smtp_from_email = isset($_POST['smtp_from_email']) ? trim($_POST['smtp_from_email']) : '';
    $smtp_from_name = isset($_POST['smtp_from_name']) ? trim($_POST['smtp_from_name']) : '';
    
    if (!$site_name) {
        $error_message = "Site name is required.";
    } else {
        $all_saved = true;
        
        $settings->set('site_name', $site_name);
        $settings->set('site_tagline', $site_tagline);
        $settings->set('contact_email', $contact_email);
        $settings->set('contact_phone', $contact_phone);
        $settings->set('smtp_host', $smtp_host);
        $settings->set('smtp_port', $smtp_port);
        $settings->set('smtp_username', $smtp_username);
        $settings->set('smtp_password', $smtp_password);
        $settings->set('smtp_from_email', $smtp_from_email);
        $settings->set('smtp_from_name', $smtp_from_name);
        
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['size'] > 0) {
            $upload_dir = '../assets/img/';
            $file = $_FILES['site_logo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $upload_error = "Invalid file type. Please upload an image.";
                $all_saved = false;
            } elseif ($file['size'] > $max_size) {
                $upload_error = "File size exceeds 5MB limit.";
                $all_saved = false;
            } else {
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'logo_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $settings->set('site_logo', $filename);
                } else {
                    $upload_error = "Failed to upload logo.";
                    $all_saved = false;
                }
            }
        }
        
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['size'] > 0) {
            $upload_dir = '../assets/img/';
            $file = $_FILES['site_favicon'];
            $allowed_types = ['image/x-icon', 'image/png', 'image/jpeg'];
            $max_size = 1 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $upload_error .= ($upload_error ? " " : "") . "Invalid favicon type.";
                $all_saved = false;
            } elseif ($file['size'] > $max_size) {
                $upload_error .= ($upload_error ? " " : "") . "Favicon exceeds 1MB limit.";
                $all_saved = false;
            } else {
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $filename = 'favicon_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                $filepath = $upload_dir . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $settings->set('site_favicon', $filename);
                } else {
                    $upload_error .= ($upload_error ? " " : "") . "Failed to upload favicon.";
                    $all_saved = false;
                }
            }
        }
        
        if ($all_saved) {
            $all_settings = $settings->getAll();
            $settings->generateConfigFile($all_settings);
            $success_message = "Settings saved successfully!" . ($upload_error ? " Warning: " . $upload_error : "");
            
            $current_settings = [
                'site_name' => $site_name,
                'site_tagline' => $site_tagline,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'smtp_host' => $smtp_host,
                'smtp_port' => $smtp_port,
                'smtp_username' => $smtp_username,
                'smtp_password' => $smtp_password,
                'smtp_from_email' => $smtp_from_email,
                'smtp_from_name' => $smtp_from_name,
            ];
        } else {
            $error_message = $upload_error ? "Settings partially saved. " . $upload_error : "Failed to save settings.";
        }
    }
}

$site_logo = $settings->get('site_logo', 'logo.png');
$site_favicon = $settings->get('site_favicon', 'favicon.ico');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="assets/img/admin.png">
    <style>
        .settings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 30px;
        }

        .settings-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .settings-section h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-section h3 i {
            color: #667eea;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="email"]:focus,
        .form-group input[type="tel"]:focus,
        .form-group input[type="number"]:focus,
        .form-group input[type="password"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .file-input-wrapper {
            position: relative;
            margin-bottom: 20px;
        }

        .file-input-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .file-input-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s;
        }

        .file-input-button:hover {
            border-color: #667eea;
            background: #f0f2ff;
        }

        .file-input-button input[type="file"] {
            display: none;
        }

        .file-input-text {
            text-align: center;
            color: #666;
        }

        .file-input-text i {
            display: block;
            font-size: 28px;
            margin-bottom: 10px;
            color: #667eea;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
        }

        .file-name {
            color: #667eea;
            font-weight: 600;
            margin-top: 10px;
            font-size: 13px;
        }

        .btn-save {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.3s;
            width: 100%;
        }

        .btn-save:hover {
            background: #764ba2;
        }

        .btn-save:active {
            transform: scale(0.98);
        }

        .helper-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }

        .settings-form {
            margin-top: 0;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header class="top-bar">
            <h1>Site Settings</h1>
            <div class="admin-info">
                <i class="fas fa-user-shield"></i>
                <span><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
            </div>
        </header>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="settings-form">
            <div class="settings-grid">
                <!-- General Settings -->
                <div class="settings-section">
                    <h3>
                        <i class="fas fa-globe"></i> General Settings
                    </h3>
                    
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" required>
                        <div class="helper-text">The name of your photography website</div>
                    </div>

                    <div class="form-group">
                        <label for="site_tagline">Tagline</label>
                        <input type="text" id="site_tagline" name="site_tagline" value="<?php echo htmlspecialchars($current_settings['site_tagline']); ?>">
                        <div class="helper-text">Short description of your site</div>
                    </div>

                    <div class="form-group">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" id="contact_email" name="contact_email" value="<?php echo htmlspecialchars($current_settings['contact_email']); ?>">
                        <div class="helper-text">Email address for inquiries</div>
                    </div>

                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" id="contact_phone" name="contact_phone" value="<?php echo htmlspecialchars($current_settings['contact_phone']); ?>">
                        <div class="helper-text">Phone number for contact</div>
                    </div>
                </div>

                <!-- Email Settings -->
                <div class="settings-section">
                    <h3>
                        <i class="fas fa-envelope"></i> Email Settings
                    </h3>
                    
                    <div class="form-group">
                        <label for="smtp_host">SMTP Host</label>
                        <input type="text" id="smtp_host" name="smtp_host" value="<?php echo htmlspecialchars($current_settings['smtp_host']); ?>" placeholder="e.g., smtp.gmail.com">
                        <div class="helper-text">SMTP server address</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="smtp_port">SMTP Port</label>
                            <input type="number" id="smtp_port" name="smtp_port" value="<?php echo htmlspecialchars($current_settings['smtp_port']); ?>" placeholder="587">
                            <div class="helper-text">Usually 587 or 465</div>
                        </div>

                        <div class="form-group">
                            <label for="smtp_username">SMTP Username</label>
                            <input type="text" id="smtp_username" name="smtp_username" value="<?php echo htmlspecialchars($current_settings['smtp_username']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="smtp_password">SMTP Password</label>
                        <input type="password" id="smtp_password" name="smtp_password" value="<?php echo htmlspecialchars($current_settings['smtp_password']); ?>">
                        <div class="helper-text">Password for SMTP authentication</div>
                    </div>

                    <div class="form-group">
                        <label for="smtp_from_email">From Email</label>
                        <input type="email" id="smtp_from_email" name="smtp_from_email" value="<?php echo htmlspecialchars($current_settings['smtp_from_email']); ?>">
                        <div class="helper-text">Email address for outgoing messages</div>
                    </div>

                    <div class="form-group">
                        <label for="smtp_from_name">From Name</label>
                        <input type="text" id="smtp_from_name" name="smtp_from_name" value="<?php echo htmlspecialchars($current_settings['smtp_from_name']); ?>">
                        <div class="helper-text">Name that appears in emails</div>
                    </div>
                </div>
            </div>

            <!-- Branding Section -->
            <div class="settings-section" style="margin-top: 30px;">
                <h3>
                    <i class="fas fa-image"></i> Branding
                </h3>

                <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 30px;">
                    <!-- Logo Upload -->
                    <div>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">Site Logo</label>
                            <label class="file-input-button">
                                <div class="file-input-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <p style="font-size: 12px; color: #999;">PNG, JPG, GIF, WEBP (Max 5MB)</p>
                                </div>
                                <input type="file" id="site_logo" name="site_logo" accept="image/*" onchange="previewFile('site_logo', 'logo-preview')">
                            </label>
                            <div id="logo-preview" class="file-name"></div>
                            <img id="logo-preview-img" class="preview-image" alt="Logo preview">
                        </div>
                        <?php if ($site_logo && file_exists("../assets/img/" . $site_logo)): ?>
                            <div style="margin-top: 15px;">
                                <p style="font-size: 12px; color: #666; margin-bottom: 8px;">Current logo:</p>
                                <img src="../assets/img/<?php echo htmlspecialchars($site_logo); ?>" alt="Current logo" style="max-width: 100%; max-height: 150px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Favicon Upload -->
                    <div>
                        <div class="file-input-wrapper">
                            <label class="file-input-label">Site Favicon</label>
                            <label class="file-input-button">
                                <div class="file-input-text">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Click to upload or drag and drop</p>
                                    <p style="font-size: 12px; color: #999;">ICO, PNG, JPG (Max 1MB)</p>
                                </div>
                                <input type="file" id="site_favicon" name="site_favicon" accept="image/*" onchange="previewFile('site_favicon', 'favicon-preview')">
                            </label>
                            <div id="favicon-preview" class="file-name"></div>
                            <img id="favicon-preview-img" class="preview-image" alt="Favicon preview">
                        </div>
                        <?php if ($site_favicon && file_exists("../assets/img/" . $site_favicon)): ?>
                            <div style="margin-top: 15px;">
                                <p style="font-size: 12px; color: #666; margin-bottom: 8px;">Current favicon:</p>
                                <img src="../assets/img/<?php echo htmlspecialchars($site_favicon); ?>" alt="Current favicon" style="max-width: 100%; max-height: 150px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save All Settings
                </button>
            </div>
        </form>
    </div>

    <script>
        function previewFile(inputId, previewId) {
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const previewImg = document.getElementById(previewId + '-img');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const reader = new FileReader();
                
                preview.textContent = 'Selected: ' + file.name;
                
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    previewImg.style.display = 'block';
                }
                
                reader.readAsDataURL(file);
            }
        }

        document.querySelectorAll('.file-input-button').forEach(button => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                button.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                button.addEventListener(eventName, () => {
                    button.style.borderColor = '#667eea';
                    button.style.background = '#f0f2ff';
                });
            });

            ['dragleave', 'drop'].forEach(eventName => {
                button.addEventListener(eventName, () => {
                    button.style.borderColor = '#ddd';
                    button.style.background = '#f8f9fa';
                });
            });

            button.addEventListener('drop', (e) => {
                const dt = e.dataTransfer;
                const files = dt.files;
                const input = button.querySelector('input[type="file"]');
                input.files = files;
                const event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            });
        });
    </script>
</body>
</html>
