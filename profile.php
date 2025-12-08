<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection and logger
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'settings.php';

$logger = new UserLogger();

// Set page-specific variables
$page_title = 'Profile';
$page_css = 'profile.css';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Function to get user info
function getUserInfo($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT id, username, email, bio, profile_picture, profile_pic, cover_photo, instagram, twitter, facebook, website, is_profile_public, show_email, first_name, last_name, phone, location, followers_count, following_count, photos_count, created_at FROM users WHERE id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get user photos with like and comment counts
function getUserPhotos($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT 
            p.id, 
            p.title, 
            p.description, 
            p.image_path, 
            p.uploaded_at,
            COALESCE(l.like_count, 0) as like_count,
            COALESCE(c.comment_count, 0) as comment_count
        FROM photos p
        LEFT JOIN (
            SELECT photo_id, COUNT(*) as like_count 
            FROM likes 
            GROUP BY photo_id
        ) l ON p.id = l.photo_id
        LEFT JOIN (
            SELECT photo_id, COUNT(*) as comment_count 
            FROM comments 
            GROUP BY photo_id
        ) c ON p.id = c.photo_id
        WHERE p.user_id = :user_id
        ORDER BY p.uploaded_at DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateProfile($pdo, $user_id, $bio, $profile_picture = null) {
    try {
        if ($profile_picture) {
            $stmt = $pdo->prepare("UPDATE users SET bio = :bio, profile_picture = :profile_picture, profile_pic = :profile_picture WHERE id = :user_id");
            $stmt->bindParam(':profile_picture', $profile_picture);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET bio = :bio WHERE id = :user_id");
        }
        
        $stmt->bindParam(':bio', $bio);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updateEmail($pdo, $user_id, $new_email) {
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['email'] === $new_email) {
            return ['success' => false, 'error' => 'New email is the same as current email.'];
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :user_id");
        $stmt->bindParam(':email', $new_email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'This email is already in use.'];
        }
        
        $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :user_id");
        $stmt->bindParam(':email', $new_email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function changePassword($pdo, $user_id, $current_password, $new_password, $confirm_password) {
    try {
        if ($new_password !== $confirm_password) {
            return ['success' => false, 'error' => 'New passwords do not match.'];
        }
        
        if (strlen($new_password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters long.'];
        }
        
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :user_id");
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updateUsername($pdo, $user_id, $new_username) {
    try {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['username'] === $new_username) {
            return ['success' => false, 'error' => 'New username is the same as current username.'];
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username AND id != :user_id");
        $stmt->bindParam(':username', $new_username);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'error' => 'This username is already taken.'];
        }
        
        if (strlen($new_username) < 3) {
            return ['success' => false, 'error' => 'Username must be at least 3 characters long.'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $new_username)) {
            return ['success' => false, 'error' => 'Username can only contain letters, numbers, underscores, and hyphens.'];
        }
        
        $stmt = $pdo->prepare("UPDATE users SET username = :username WHERE id = :user_id");
        $stmt->bindParam(':username', $new_username);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updateSocialMedia($pdo, $user_id, $instagram, $twitter, $facebook, $website) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET instagram = :instagram, twitter = :twitter, facebook = :facebook, website = :website WHERE id = :user_id");
        $stmt->bindParam(':instagram', $instagram);
        $stmt->bindParam(':twitter', $twitter);
        $stmt->bindParam(':facebook', $facebook);
        $stmt->bindParam(':website', $website);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updatePersonalInfo($pdo, $user_id, $first_name, $last_name, $phone, $location) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, phone = :phone, location = :location WHERE id = :user_id");
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':location', $location);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updatePrivacySettings($pdo, $user_id, $is_profile_public, $show_email) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET is_profile_public = :is_profile_public, show_email = :show_email WHERE id = :user_id");
        $stmt->bindParam(':is_profile_public', $is_profile_public);
        $stmt->bindParam(':show_email', $show_email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function updateCoverPhoto($pdo, $user_id, $cover_photo) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET cover_photo = :cover_photo WHERE id = :user_id");
        $stmt->bindParam(':cover_photo', $cover_photo);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Function to handle profile picture upload
function uploadProfilePicture($file, $user_id) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $filesize = $file['size'];
    
    if (!in_array($filetype, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }
    
    if ($filesize > 5000000) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
    }
    
    $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
    $upload_dir = 'uploads/profiles/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $target_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

function uploadCoverPhoto($file, $user_id) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $filename = $file['name'];
    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $filesize = $file['size'];
    
    if (!in_array($filetype, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'];
    }
    
    if ($filesize > 10000000) {
        return ['success' => false, 'error' => 'File size exceeds 10MB limit.'];
    }
    
    $new_filename = 'cover_' . $user_id . '_' . time() . '.' . $filetype;
    $upload_dir = 'uploads/covers/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $target_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $new_filename];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

// Function to delete photo
function deletePhoto($pdo, $photo_id, $user_id) {
    // First verify the photo belongs to the user
    $stmt = $pdo->prepare("SELECT image_path, title FROM photos WHERE id = :photo_id AND user_id = :user_id");
    $stmt->bindParam(':photo_id', $photo_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete associated likes and comments first (cascade should handle this, but being safe)
        $pdo->prepare("DELETE FROM comments WHERE photo_id = :photo_id")->execute([':photo_id' => $photo_id]);
        $pdo->prepare("DELETE FROM likes WHERE photo_id = :photo_id")->execute([':photo_id' => $photo_id]);
        
        // Delete photo record
        $stmt = $pdo->prepare("DELETE FROM photos WHERE id = :photo_id");
        $stmt->bindParam(':photo_id', $photo_id);
        $stmt->execute();
        
        // Delete actual image file
        $image_path = 'uploads/' . $photo['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        return ['success' => true, 'title' => $photo['title']];
    }
    return ['success' => false];
}

// Function to update photo
function updatePhoto($pdo, $photo_id, $user_id, $title, $description) {
    try {
        // Verify the photo belongs to the user
        $stmt = $pdo->prepare("SELECT id FROM photos WHERE id = :photo_id AND user_id = :user_id");
        $stmt->bindParam(':photo_id', $photo_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            return ['success' => false, 'error' => 'Photo not found or permission denied'];
        }
        
        // Validate inputs
        if (empty($title)) {
            return ['success' => false, 'error' => 'Photo title is required'];
        }
        if (strlen($title) > 100) {
            return ['success' => false, 'error' => 'Title is too long (max 100 characters)'];
        }
        if (strlen($description) > 500) {
            return ['success' => false, 'error' => 'Description is too long (max 500 characters)'];
        }
        
        // Update photo
        $stmt = $pdo->prepare("UPDATE photos SET title = :title, description = :description WHERE id = :photo_id AND user_id = :user_id");
        $stmt->bindParam(':photo_id', $photo_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        
        if ($stmt->execute()) {
            return ['success' => true, 'title' => $title];
        } else {
            return ['success' => false, 'error' => 'Failed to update photo'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    try {
        $pdo = getDBConnection();
        $bio = sanitizeInput($_POST['bio'] ?? '');
        $profile_picture = null;
        
        // Handle cropped profile picture upload
        if (isset($_FILES['profile_picture_crop']) && $_FILES['profile_picture_crop']['size'] > 0) {
            $upload_result = uploadProfilePicture($_FILES['profile_picture_crop'], $user_id);
            if ($upload_result['success']) {
                $profile_picture = $upload_result['filename'];
            } else {
                $_SESSION['error'] = $upload_result['error'];
                header("Location: profile.php");
                exit();
            }
        }
        // Handle regular profile picture upload if provided
        else if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['size'] > 0) {
            $upload_result = uploadProfilePicture($_FILES['profile_picture'], $user_id);
            if ($upload_result['success']) {
                $profile_picture = $upload_result['filename'];
            } else {
                $_SESSION['error'] = $upload_result['error'];
                header("Location: profile.php");
                exit();
            }
        }
        
        // Update profile
        $result = updateProfile($pdo, $user_id, $bio, $profile_picture);
        
        if ($result['success']) {
            $_SESSION['success'] = "Profile updated successfully!";
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                "User updated their profile",
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_SUCCESS
            );
        } else {
            $_SESSION['error'] = "Failed to update profile: " . $result['error'];
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                "Failed to update profile: " . $result['error'],
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_FAILED
            );
        }
        
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your profile.";
        header("Location: profile.php");
        exit();
    }
}

// Handle cover photo upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cover_photo'])) {
    try {
        $pdo = getDBConnection();
        
        // Handle cropped cover photo upload
        if (isset($_FILES['cover_photo_crop']) && $_FILES['cover_photo_crop']['size'] > 0) {
            $upload_result = uploadCoverPhoto($_FILES['cover_photo_crop'], $user_id);
            if ($upload_result['success']) {
                $cover_photo = $upload_result['filename'];
                $result = updateCoverPhoto($pdo, $user_id, $cover_photo);
                
                if ($result['success']) {
                    $_SESSION['success'] = 'Cover photo updated successfully!';
                    $logger->log(
                        UserLogger::ACTION_UPDATE_PROFILE,
                        'User updated their cover photo',
                        $user_id,
                        null,
                        'users',
                        $user_id,
                        UserLogger::STATUS_SUCCESS
                    );
                } else {
                    $_SESSION['error'] = 'Failed to update cover photo: ' . $result['error'];
                    $logger->log(
                        UserLogger::ACTION_UPDATE_PROFILE,
                        'Failed to update cover photo: ' . $result['error'],
                        $user_id,
                        null,
                        'users',
                        $user_id,
                        UserLogger::STATUS_FAILED
                    );
                }
            } else {
                $_SESSION['error'] = $upload_result['error'];
            }
        }
        // Handle regular cover photo upload if provided
        else if (isset($_FILES['cover_photo']) && $_FILES['cover_photo']['size'] > 0) {
            $upload_result = uploadCoverPhoto($_FILES['cover_photo'], $user_id);
            if ($upload_result['success']) {
                $cover_photo = $upload_result['filename'];
                $result = updateCoverPhoto($pdo, $user_id, $cover_photo);
                
                if ($result['success']) {
                    $_SESSION['success'] = 'Cover photo updated successfully!';
                    $logger->log(
                        UserLogger::ACTION_UPDATE_PROFILE,
                        'User updated their cover photo',
                        $user_id,
                        null,
                        'users',
                        $user_id,
                        UserLogger::STATUS_SUCCESS
                    );
                } else {
                    $_SESSION['error'] = 'Failed to update cover photo: ' . $result['error'];
                    $logger->log(
                        UserLogger::ACTION_UPDATE_PROFILE,
                        'Failed to update cover photo: ' . $result['error'],
                        $user_id,
                        null,
                        'users',
                        $user_id,
                        UserLogger::STATUS_FAILED
                    );
                }
            } else {
                $_SESSION['error'] = $upload_result['error'];
            }
        } else {
            $_SESSION['error'] = 'Please select a cover photo to upload.';
        }
        
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Cover photo upload error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while uploading your cover photo.";
        header("Location: profile.php");
        exit();
    }
}

// Handle email update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email'])) {
    try {
        $pdo = getDBConnection();
        $new_email = sanitizeInput($_POST['new_email'] ?? '');
        
        if (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Please enter a valid email address.';
        } else {
            $result = updateEmail($pdo, $user_id, $new_email);
            if ($result['success']) {
                $_SESSION['success'] = 'Email updated successfully!';
                $logger->log(
                    UserLogger::ACTION_UPDATE_PROFILE,
                    'User updated their email',
                    $user_id,
                    null,
                    'users',
                    $user_id,
                    UserLogger::STATUS_SUCCESS
                );
            } else {
                $_SESSION['error'] = $result['error'];
                $logger->log(
                    UserLogger::ACTION_UPDATE_PROFILE,
                    'Failed to update email: ' . $result['error'],
                    $user_id,
                    null,
                    'users',
                    $user_id,
                    UserLogger::STATUS_FAILED
                );
            }
        }
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Email update error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your email.";
        header("Location: profile.php");
        exit();
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    try {
        $pdo = getDBConnection();
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $_SESSION['error'] = 'All password fields are required.';
        } else {
            $result = changePassword($pdo, $user_id, $current_password, $new_password, $confirm_password);
            if ($result['success']) {
                $_SESSION['success'] = 'Password changed successfully!';
                $logger->log(
                    UserLogger::ACTION_UPDATE_PROFILE,
                    'User changed their password',
                    $user_id,
                    null,
                    'users',
                    $user_id,
                    UserLogger::STATUS_SUCCESS
                );
            } else {
                $_SESSION['error'] = $result['error'];
                $logger->log(
                    UserLogger::ACTION_UPDATE_PROFILE,
                    'Failed to change password: ' . $result['error'],
                    $user_id,
                    null,
                    'users',
                    $user_id,
                    UserLogger::STATUS_FAILED
                );
            }
        }
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Password change error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while changing your password.";
        header("Location: profile.php");
        exit();
    }
}

// Handle username update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    try {
        $pdo = getDBConnection();
        $new_username = sanitizeInput($_POST['new_username'] ?? '');
        
        if (empty($new_username)) {
            $_SESSION['error'] = 'Username cannot be empty.';
        } else {
            $result = updateUsername($pdo, $user_id, $new_username);
            if ($result['success']) {
                $_SESSION['success'] = 'Username updated successfully!';
                $logger->log(
                    UserLogger::ACTION_UPDATE_PROFILE,
                    'User updated their username',
                    $user_id,
                    null,
                    'users',
                    $user_id,
                    UserLogger::STATUS_SUCCESS
                );
            } else {
                $_SESSION['error'] = $result['error'];
                $logger->log(
                    UserLogger::ACTION_UPDATE_PROFILE,
                    'Failed to update username: ' . $result['error'],
                    $user_id,
                    null,
                    'users',
                    $user_id,
                    UserLogger::STATUS_FAILED
                );
            }
        }
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Username update error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your username.";
        header("Location: profile.php");
        exit();
    }
}

// Handle social media update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_social_media'])) {
    try {
        $pdo = getDBConnection();
        $instagram = sanitizeInput($_POST['instagram'] ?? '');
        $twitter = sanitizeInput($_POST['twitter'] ?? '');
        $facebook = sanitizeInput($_POST['facebook'] ?? '');
        $website = sanitizeInput($_POST['website'] ?? '');
        
        $result = updateSocialMedia($pdo, $user_id, $instagram, $twitter, $facebook, $website);
        if ($result['success']) {
            $_SESSION['success'] = 'Social media links updated successfully!';
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                'User updated their social media links',
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_SUCCESS
            );
        } else {
            $_SESSION['error'] = $result['error'];
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                'Failed to update social media: ' . $result['error'],
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_FAILED
            );
        }
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Social media update error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your social media links.";
        header("Location: profile.php");
        exit();
    }
}

// Handle personal info update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_personal_info'])) {
    try {
        $pdo = getDBConnection();
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $location = sanitizeInput($_POST['location'] ?? '');
        
        $result = updatePersonalInfo($pdo, $user_id, $first_name, $last_name, $phone, $location);
        if ($result['success']) {
            $_SESSION['success'] = 'Personal information updated successfully!';
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                'User updated their personal information',
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_SUCCESS
            );
        } else {
            $_SESSION['error'] = $result['error'];
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                'Failed to update personal info: ' . $result['error'],
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_FAILED
            );
        }
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Personal info update error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your personal information.";
        header("Location: profile.php");
        exit();
    }
}

// Handle privacy settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_privacy'])) {
    try {
        $pdo = getDBConnection();
        $is_profile_public = isset($_POST['is_profile_public']) ? 1 : 0;
        $show_email = isset($_POST['show_email']) ? 1 : 0;
        
        $result = updatePrivacySettings($pdo, $user_id, $is_profile_public, $show_email);
        if ($result['success']) {
            $_SESSION['success'] = 'Privacy settings updated successfully!';
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                'User updated their privacy settings',
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_SUCCESS
            );
        } else {
            $_SESSION['error'] = $result['error'];
            $logger->log(
                UserLogger::ACTION_UPDATE_PROFILE,
                'Failed to update privacy settings: ' . $result['error'],
                $user_id,
                null,
                'users',
                $user_id,
                UserLogger::STATUS_FAILED
            );
        }
        header("Location: profile.php");
        exit();
    } catch(Exception $e) {
        error_log("Privacy settings update error: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating your privacy settings.";
        header("Location: profile.php");
        exit();
    }
}

// Handle photo deletion
if (isset($_GET['delete_photo']) && is_numeric($_GET['delete_photo'])) {
    try {
        $pdo = getDBConnection();
        $delete_id = (int)$_GET['delete_photo'];
        
        $result = deletePhoto($pdo, $delete_id, $user_id);
        
        if ($result['success']) {
            $_SESSION['success'] = "Photo deleted successfully!";
            
            // Log successful deletion
            $logger->log(
                UserLogger::ACTION_DELETE_PHOTO,
                "User deleted their photo: '{$result['title']}' (ID: {$delete_id})",
                $user_id,
                null,
                'photos',
                $delete_id,
                UserLogger::STATUS_SUCCESS
            );
        } else {
            $_SESSION['error'] = "Photo not found or you don't have permission to delete it.";
            
            // Log failed deletion
            $logger->log(
                UserLogger::ACTION_DELETE_PHOTO,
                "Failed to delete photo - Not found or no permission (ID: {$delete_id})",
                $user_id,
                null,
                null,
                null,
                UserLogger::STATUS_FAILED
            );
        }
        
        header("Location: profile.php");
        exit();
    } catch(PDOException $e) {
        error_log("Delete photo error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete photo. Please try again.";
        
        // Log error
        $logger->log(
            UserLogger::ACTION_DELETE_PHOTO,
            "Database error while deleting photo: " . $e->getMessage(),
            $user_id,
            null,
            null,
            null,
            UserLogger::STATUS_FAILED
        );
        
        header("Location: profile.php");
        exit();
    }
}

// Handle photo update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_photo'])) {
    try {
        $pdo = getDBConnection();
        $photo_id = (int)($_POST['photo_id'] ?? 0);
        $title = sanitizeInput($_POST['photo_title'] ?? '');
        $description = sanitizeInput($_POST['photo_description'] ?? '');
        
        $result = updatePhoto($pdo, $photo_id, $user_id, $title, $description);
        
        if ($result['success']) {
            $_SESSION['success'] = "Photo updated successfully!";
            
            // Log successful update
            $logger->log(
                UserLogger::ACTION_UPDATE_PHOTO,
                "User updated their photo: '{$result['title']}' (ID: {$photo_id})",
                $user_id,
                null,
                'photos',
                $photo_id,
                UserLogger::STATUS_SUCCESS
            );
        } else {
            $_SESSION['error'] = $result['error'] ?? "Failed to update photo.";
            
            // Log failed update
            $logger->log(
                UserLogger::ACTION_UPDATE_PHOTO,
                "Failed to update photo (ID: {$photo_id}): " . ($result['error'] ?? "Unknown error"),
                $user_id,
                null,
                'photos',
                $photo_id,
                UserLogger::STATUS_FAILED
            );
        }
        
        header("Location: profile.php");
        exit();
    } catch(PDOException $e) {
        error_log("Update photo error: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update photo. Please try again.";
        
        // Log error
        $logger->log(
            UserLogger::ACTION_UPDATE_PHOTO,
            "Database error while updating photo: " . $e->getMessage(),
            $user_id,
            null,
            null,
            null,
            UserLogger::STATUS_FAILED
        );
        
        header("Location: profile.php");
        exit();
    }
}

// Get user info and photos
try {
    $pdo = getDBConnection();
    
    $user_info = getUserInfo($pdo, $user_id);
    $user_photos = getUserPhotos($pdo, $user_id);
    
    if (!$user_info) {
        session_destroy();
        header("Location: login.php");
        exit();
    }
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = "Failed to load profile. Please try again.";
    header("Location: index.php");
    exit();
}

// Handle session messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';

// Clear session messages
unset($_SESSION['success']);
unset($_SESSION['error']);

// Set page title for header
$page_title = htmlspecialchars($user_info['username']) . "'s Profile";
$page_css = 'profile.css';

// Include header
include 'header.php';
?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="messages">
            <div class="container">
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="messages">
            <div class="container">
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Profile Header -->
    <section class="profile-header">
        <?php if ($user_info['cover_photo']): ?>
            <div class="profile-cover">
                <img src="uploads/covers/<?php echo htmlspecialchars($user_info['cover_photo']); ?>" alt="Cover Photo" class="cover-image">
            </div>
        <?php else: ?>
            <div class="profile-cover">
                <img src="assets/img/default-cover.svg" alt="Default Cover Photo" class="cover-image">
            </div>
        <?php endif; ?>
        <div class="container">
            <div class="profile-avatar-container">
                <?php if ($user_info['profile_picture']): ?>
                    <img src="uploads/profiles/<?php echo htmlspecialchars($user_info['profile_picture']); ?>" alt="Profile Picture" class="profile-avatar profile-avatar-image">
                <?php else: ?>
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user_info['username'], 0, 2)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user_info['username']); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user_info['email']); ?></p>
            <?php if ($user_info['bio']): ?>
                <p class="profile-bio"><?php echo nl2br(htmlspecialchars($user_info['bio'])); ?></p>
            <?php endif; ?>
            <p class="profile-joined">Member since <?php echo date('F Y', strtotime($user_info['created_at'])); ?></p>
            <div class="profile-buttons">
                <button class="edit-profile-btn" id="editProfileBtn">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
                <div class="settings-dropdown">
                    <button class="settings-btn" id="settingsBtn">
                        <i class="fas fa-cog"></i> Settings
                    </button>
                    <div class="dropdown-menu" id="settingsDropdown">
                        <a href="#" data-setting="password" class="dropdown-item">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                        <a href="#" data-setting="username" class="dropdown-item">
                            <i class="fas fa-user"></i> Change Username
                        </a>
                        <a href="#" data-setting="personal" class="dropdown-item">
                            <i class="fas fa-info-circle"></i> Personal Info
                        </a>
                        <a href="#" data-setting="social" class="dropdown-item">
                            <i class="fas fa-share-alt"></i> Social Media
                        </a>
                        <a href="#" data-setting="privacy" class="dropdown-item">
                            <i class="fas fa-shield-alt"></i> Privacy
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="profile-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat">
                    <div class="stat-value"><?php echo (int)($user_info['photos_count'] ?? 0); ?></div>
                    <div class="stat-label">Photos</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo (int)($user_info['followers_count'] ?? 0); ?></div>
                    <div class="stat-label">Followers</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?php echo (int)($user_info['following_count'] ?? 0); ?></div>
                    <div class="stat-label">Following</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button class="close-btn" id="closeEditModal">&times;</button>
            </div>
            <div class="profile-edit-tabs">
                <button type="button" class="profile-edit-tab-btn active" data-tab="basic">Basic</button>
                <button type="button" class="profile-edit-tab-btn" data-tab="cover">Cover Photo</button>
            </div>
            
            <!-- Basic Tab -->
            <div class="profile-edit-tab-content active" id="basic-tab">
                <form method="POST" enctype="multipart/form-data" class="edit-profile-form">
                    <div class="form-group">
                        <label for="profile_picture">Profile Picture</label>
                        <div class="file-input">
                            <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                            <label for="profile_picture" class="file-input-label">
                                <i class="fas fa-camera"></i> Choose Photo
                            </label>
                        </div>
                        <p class="file-help">Max 5MB. Allowed: JPG, PNG, GIF</p>
                    </div>
                
                    <div class="form-group">
                        <label for="bio">Bio</label>
                        <textarea id="bio" name="bio" maxlength="500" placeholder="Tell us about yourself..." class="form-textarea"><?php echo htmlspecialchars($user_info['bio'] ?? ''); ?></textarea>
                        <p class="char-count"><span id="charCount">0</span>/500</p>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                        <button type="submit" name="update_profile" value="1" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
            
            <!-- Cover Photo Tab -->
            <div class="profile-edit-tab-content" id="cover-tab">
                <form method="POST" enctype="multipart/form-data" class="edit-profile-form">
                    <div class="form-group">
                        <label for="cover_photo">Cover Photo</label>
                        <div class="file-input">
                            <input type="file" id="cover_photo" name="cover_photo" accept="image/*">
                            <label for="cover_photo" class="file-input-label">
                                <i class="fas fa-images"></i> Choose Cover Photo
                            </label>
                        </div>
                        <p class="file-help">Max 10MB. Allowed: JPG, PNG, GIF. Recommended size: 1200x300px</p>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" id="cancelCoverBtn">Cancel</button>
                        <button type="submit" name="update_cover_photo" value="1" class="btn btn-primary">Upload Cover Photo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Password</h2>
                <button class="close-btn" id="closePasswordModal">&times;</button>
            </div>
            <form method="POST" class="settings-form">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-input" placeholder="Enter your current password">
                </div>
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-input" placeholder="Enter your new password">
                    <small>At least 6 characters</small>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Confirm your new password">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPasswordBtn">Cancel</button>
                    <button type="submit" name="change_password" value="1" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Username Modal -->
    <div id="usernameModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Username</h2>
                <button class="close-btn" id="closeUsernameModal">&times;</button>
            </div>
            <form method="POST" class="settings-form">
                <div class="form-group">
                    <label for="current_username">Current Username</label>
                    <input type="text" id="current_username" class="form-input" value="<?php echo htmlspecialchars($user_info['username']); ?>" disabled>
                </div>
                <div class="form-group">
                    <label for="new_username">New Username</label>
                    <input type="text" id="new_username" name="new_username" class="form-input" placeholder="Enter your new username" maxlength="50">
                    <small>3-50 characters. Letters, numbers, underscores, and hyphens only.</small>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelUsernameBtn">Cancel</button>
                    <button type="submit" name="update_username" value="1" class="btn btn-primary">Update Username</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Personal Info Modal -->
    <div id="personalModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Personal Info</h2>
                <button class="close-btn" id="closePersonalModal">&times;</button>
            </div>
            <form method="POST" class="settings-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-input" placeholder="Your first name" value="<?php echo htmlspecialchars($user_info['first_name'] ?? ''); ?>" maxlength="50">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-input" placeholder="Your last name" value="<?php echo htmlspecialchars($user_info['last_name'] ?? ''); ?>" maxlength="50">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" class="form-input" placeholder="+1 (555) 123-4567" value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>" maxlength="20">
                </div>
                
                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" name="location" class="form-input" placeholder="City, Country" value="<?php echo htmlspecialchars($user_info['location'] ?? ''); ?>" maxlength="100">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPersonalBtn">Cancel</button>
                    <button type="submit" name="update_personal_info" value="1" class="btn btn-primary">Save Personal Info</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Social Media Modal -->
    <div id="socialModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Social Media</h2>
                <button class="close-btn" id="closeSocialModal">&times;</button>
            </div>
            <form method="POST" class="settings-form">
                <div class="form-group">
                    <label for="instagram"><i class="fab fa-instagram"></i> Instagram</label>
                    <input type="text" id="instagram" name="instagram" class="form-input" placeholder="@username" value="<?php echo htmlspecialchars($user_info['instagram'] ?? ''); ?>" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="twitter"><i class="fab fa-twitter"></i> Twitter/X</label>
                    <input type="text" id="twitter" name="twitter" class="form-input" placeholder="@username" value="<?php echo htmlspecialchars($user_info['twitter'] ?? ''); ?>" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="facebook"><i class="fab fa-facebook"></i> Facebook</label>
                    <input type="text" id="facebook" name="facebook" class="form-input" placeholder="Profile name or URL" value="<?php echo htmlspecialchars($user_info['facebook'] ?? ''); ?>" maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="website"><i class="fas fa-globe"></i> Website</label>
                    <input type="url" id="website" name="website" class="form-input" placeholder="https://example.com" value="<?php echo htmlspecialchars($user_info['website'] ?? ''); ?>" maxlength="255">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelSocialBtn">Cancel</button>
                    <button type="submit" name="update_social_media" value="1" class="btn btn-primary">Save Social Media</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Privacy Modal -->
    <div id="privacyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Privacy Settings</h2>
                <button class="close-btn" id="closePrivacyModal">&times;</button>
            </div>
            <form method="POST" class="settings-form">
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_profile_public" value="1" <?php echo ($user_info['is_profile_public'] ? 'checked' : ''); ?>>
                        <span>Make my profile public</span>
                        <small>Allow others to view your profile and photos</small>
                    </label>
                </div>
                
                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_email" value="1" <?php echo ($user_info['show_email'] ? 'checked' : ''); ?>>
                        <span>Show my email on my profile</span>
                        <small>Display your email address publicly</small>
                    </label>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPrivacyBtn">Cancel</button>
                    <button type="submit" name="update_privacy" value="1" class="btn btn-primary">Save Privacy Settings</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Crop Profile Picture Modal -->
    <div id="cropProfileModal" class="modal">
        <div class="modal-content crop-modal">
            <div class="modal-header">
                <h2>Crop Profile Picture</h2>
                <button class="close-btn" id="closeCropProfileModal">&times;</button>
            </div>
            
            <div class="crop-container">
                <img id="profileImageToCrop" src="" alt="Image to crop" style="max-width: 100%; max-height: 400px;">
            </div>
            
            <div class="crop-controls">
                <button type="button" class="btn btn-sm" id="resetProfileCrop">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <button type="button" class="btn btn-sm" id="rotateLeftProfile">
                    <i class="fas fa-redo"></i> Rotate Left
                </button>
                <button type="button" class="btn btn-sm" id="rotateRightProfile">
                    <i class="fas fa-redo fa-flip-horizontal"></i> Rotate Right
                </button>
                <div class="zoom-slider">
                    <label for="zoomProfileSlider">Zoom:</label>
                    <input type="range" id="zoomProfileSlider" min="0.1" max="3" step="0.1" value="1">
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelProfileCrop">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmProfileCrop">Crop & Upload</button>
            </div>
        </div>
    </div>

    <!-- Crop Cover Photo Modal -->
    <div id="cropCoverModal" class="modal">
        <div class="modal-content crop-modal">
            <div class="modal-header">
                <h2>Crop Cover Photo</h2>
                <button class="close-btn" id="closeCropCoverModal">&times;</button>
            </div>
            
            <div class="crop-container">
                <img id="coverImageToCrop" src="" alt="Image to crop" style="max-width: 100%; max-height: 400px;">
            </div>
            
            <div class="crop-controls">
                <button type="button" class="btn btn-sm" id="resetCoverCrop">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <button type="button" class="btn btn-sm" id="rotateLeftCover">
                    <i class="fas fa-redo"></i> Rotate Left
                </button>
                <button type="button" class="btn btn-sm" id="rotateRightCover">
                    <i class="fas fa-redo fa-flip-horizontal"></i> Rotate Right
                </button>
                <div class="zoom-slider">
                    <label for="zoomCoverSlider">Zoom:</label>
                    <input type="range" id="zoomCoverSlider" min="0.1" max="3" step="0.1" value="1">
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelCoverCrop">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmCoverCrop">Crop & Upload</button>
            </div>
        </div>
    </div>

    <!-- Edit Photo Modal -->
    <div id="editPhotoModal" class="modal">
        <div class="modal-content edit-photo-modal">
            <div class="modal-header">
                <h2>Edit Photo</h2>
                <button class="close-btn" id="closeEditPhotoModal">&times;</button>
            </div>
            
            <form id="editPhotoForm" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="update_photo" value="1">
                    <input type="hidden" name="photo_id" id="editPhotoId">
                    
                    <div class="form-group">
                        <label for="editPhotoTitle">Photo Title <span class="required">*</span></label>
                        <input type="text" id="editPhotoTitle" name="photo_title" maxlength="100" required>
                        <div class="char-count"><span id="titleCharCount">0</span>/100</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="editPhotoDescription">Description</label>
                        <textarea id="editPhotoDescription" name="photo_description" maxlength="500" rows="5"></textarea>
                        <div class="char-count"><span id="descCharCount">0</span>/500</div>
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelEditPhotoBtn">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>


    <!-- Photos Section -->
    <section class="photos-section">
        <div class="container">
            <h2 class="section-title">My Photos</h2>
            
            <?php if (empty($user_photos)): ?>
                <div class="no-photos">
                    <h3>You haven't uploaded any photos yet!</h3>
                    <p>Start sharing your photography with the community.</p>
                    <a href="upload.php" class="upload-link">
                        <i class="fas fa-cloud-upload-alt"></i> Upload Your First Photo
                    </a>
                </div>
            <?php else: ?>
                <div class="photos-grid">
                    <?php foreach ($user_photos as $photo): ?>
                        <div class="photo-card">
                            <div class="photo-image-container">
                                <img src="uploads/<?php echo htmlspecialchars($photo['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($photo['title']); ?>" 
                                     class="photo-image">
                            </div>
                            <div class="photo-info">
                                <h3 class="photo-title"><?php echo htmlspecialchars($photo['title']); ?></h3>
                                <div class="photo-meta">
                                    <span><?php echo date('M j, Y', strtotime($photo['uploaded_at'])); ?></span>
                                    <span><?php echo strlen($photo['description']) > 50 ? substr(htmlspecialchars($photo['description']), 0, 47) . '...' : htmlspecialchars($photo['description']); ?></span>
                                </div>
                                <div class="photo-actions">
                                    <a href="like.php?id=<?php echo $photo['id']; ?>" class="action-btn">
                                        <i class="fas fa-heart"></i>
                                        <span><?php echo $photo['like_count']; ?></span>
                                    </a>
                                    <button type="button" class="action-btn edit-photo-btn" onclick="openEditPhotoModal(<?php echo $photo['id']; ?>, '<?php echo htmlspecialchars($photo['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($photo['description'], ENT_QUOTES); ?>')">
                                        <i class="fas fa-edit"></i>
                                        <span>Edit</span>
                                    </button>
                                    <a href="profile.php?delete_photo=<?php echo $photo['id']; ?>" 
                                       class="action-btn delete-btn" 
                                       onclick="return confirm('Are you sure you want to delete this photo? This action cannot be undone.');">
                                        <i class="fas fa-trash"></i>
                                        <span>Delete</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-bottom">
                <?php
                    $profile_settings = new SiteSettings();
                    $profile_site_name = $profile_settings->get('site_name', 'LensCraft');
                ?>
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($profile_site_name); ?> Photography Community. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        let profileCropper = null;
        let coverCropper = null;
        let currentFile = null;
        let currentCropType = null;
        
        const editProfileBtn = document.getElementById('editProfileBtn');
        const settingsBtn = document.getElementById('settingsBtn');
        const settingsDropdown = document.getElementById('settingsDropdown');
        const editProfileModal = document.getElementById('editProfileModal');
        const cropProfileModal = document.getElementById('cropProfileModal');
        const cropCoverModal = document.getElementById('cropCoverModal');
        const closeEditModal = document.getElementById('closeEditModal');
        const closeCropProfileModal = document.getElementById('closeCropProfileModal');
        const closeCropCoverModal = document.getElementById('closeCropCoverModal');
        const cancelEditBtn = document.getElementById('cancelEditBtn');
        const cancelCoverBtn = document.getElementById('cancelCoverBtn');
        
        const passwordModal = document.getElementById('passwordModal');
        const usernameModal = document.getElementById('usernameModal');
        const personalModal = document.getElementById('personalModal');
        const socialModal = document.getElementById('socialModal');
        const privacyModal = document.getElementById('privacyModal');
        
        const closePasswordModal = document.getElementById('closePasswordModal');
        const closeUsernameModal = document.getElementById('closeUsernameModal');
        const closePersonalModal = document.getElementById('closePersonalModal');
        const closeSocialModal = document.getElementById('closeSocialModal');
        const closePrivacyModal = document.getElementById('closePrivacyModal');
        
        const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
        const cancelUsernameBtn = document.getElementById('cancelUsernameBtn');
        const cancelPersonalBtn = document.getElementById('cancelPersonalBtn');
        const cancelSocialBtn = document.getElementById('cancelSocialBtn');
        const cancelPrivacyBtn = document.getElementById('cancelPrivacyBtn');
        const bioTextarea = document.getElementById('bio');
        const charCount = document.getElementById('charCount');
        const profilePictureInput = document.getElementById('profile_picture');
        const coverPhotoInput = document.getElementById('cover_photo');
        const editPhotoModal = document.getElementById('editPhotoModal');
        const closeEditPhotoModal = document.getElementById('closeEditPhotoModal');
        const cancelEditPhotoBtn = document.getElementById('cancelEditPhotoBtn');
        const editPhotoForm = document.getElementById('editPhotoForm');
        const editPhotoTitle = document.getElementById('editPhotoTitle');
        const editPhotoDescription = document.getElementById('editPhotoDescription');
        const titleCharCount = document.getElementById('titleCharCount');
        const descCharCount = document.getElementById('descCharCount');
        
        editProfileBtn.addEventListener('click', function() {
            editProfileModal.classList.add('active');
        });
        
        settingsBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            settingsDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.settings-dropdown')) {
                settingsDropdown.classList.remove('active');
            }
        });
        
        document.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const setting = this.getAttribute('data-setting');
                settingsDropdown.classList.remove('active');
                
                const modals = {
                    'password': passwordModal,
                    'username': usernameModal,
                    'personal': personalModal,
                    'social': socialModal,
                    'privacy': privacyModal
                };
                
                if (modals[setting]) {
                    modals[setting].classList.add('active');
                }
            });
        });
        
        closeEditModal.addEventListener('click', function() {
            editProfileModal.classList.remove('active');
        });
        
        closePasswordModal.addEventListener('click', function() {
            passwordModal.classList.remove('active');
        });
        
        closeUsernameModal.addEventListener('click', function() {
            usernameModal.classList.remove('active');
        });
        
        closePersonalModal.addEventListener('click', function() {
            personalModal.classList.remove('active');
        });
        
        closeSocialModal.addEventListener('click', function() {
            socialModal.classList.remove('active');
        });
        
        closePrivacyModal.addEventListener('click', function() {
            privacyModal.classList.remove('active');
        });
        
        cancelEditBtn.addEventListener('click', function() {
            editProfileModal.classList.remove('active');
        });
        
        cancelCoverBtn.addEventListener('click', function() {
            editProfileModal.classList.remove('active');
        });
        
        cancelPasswordBtn.addEventListener('click', function() {
            passwordModal.classList.remove('active');
        });
        
        cancelUsernameBtn.addEventListener('click', function() {
            usernameModal.classList.remove('active');
        });
        
        cancelPersonalBtn.addEventListener('click', function() {
            personalModal.classList.remove('active');
        });
        
        cancelSocialBtn.addEventListener('click', function() {
            socialModal.classList.remove('active');
        });
        
        cancelPrivacyBtn.addEventListener('click', function() {
            privacyModal.classList.remove('active');
        });
        
        closeEditPhotoModal.addEventListener('click', function() {
            editPhotoModal.classList.remove('active');
        });
        
        cancelEditPhotoBtn.addEventListener('click', function() {
            editPhotoModal.classList.remove('active');
        });
        
        editPhotoTitle.addEventListener('input', function() {
            titleCharCount.textContent = this.value.length;
        });
        
        editPhotoDescription.addEventListener('input', function() {
            descCharCount.textContent = this.value.length;
        });
        
        document.querySelectorAll('.profile-edit-tab-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const tabName = this.getAttribute('data-tab');
                
                document.querySelectorAll('.profile-edit-tab-btn').forEach(b => {
                    b.classList.remove('active');
                });
                document.querySelectorAll('.profile-edit-tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                
                this.classList.add('active');
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === editProfileModal) {
                editProfileModal.classList.remove('active');
            }
            if (event.target === passwordModal) {
                passwordModal.classList.remove('active');
            }
            if (event.target === usernameModal) {
                usernameModal.classList.remove('active');
            }
            if (event.target === personalModal) {
                personalModal.classList.remove('active');
            }
            if (event.target === socialModal) {
                socialModal.classList.remove('active');
            }
            if (event.target === privacyModal) {
                privacyModal.classList.remove('active');
            }
        });
        
        bioTextarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
        
        charCount.textContent = bioTextarea.value.length;
        
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    window.scrollTo({
                        top: target.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
        
        function handleImageSelect(file, cropType) {
            const reader = new FileReader();
            reader.onload = function(e) {
                currentFile = file;
                currentCropType = cropType;
                
                if (cropType === 'profile') {
                    const img = document.getElementById('profileImageToCrop');
                    img.src = e.target.result;
                    
                    if (profileCropper) {
                        profileCropper.destroy();
                    }
                    
                    setTimeout(() => {
                        profileCropper = new Cropper(img, {
                            aspectRatio: 1,
                            viewMode: 1,
                            autoCropArea: 1,
                            responsive: true,
                            guides: true,
                            highlight: true,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: true,
                        });
                        cropProfileModal.classList.add('active');
                    }, 100);
                } else if (cropType === 'cover') {
                    const img = document.getElementById('coverImageToCrop');
                    img.src = e.target.result;
                    
                    if (coverCropper) {
                        coverCropper.destroy();
                    }
                    
                    setTimeout(() => {
                        coverCropper = new Cropper(img, {
                            aspectRatio: 4 / 1,
                            viewMode: 1,
                            autoCropArea: 1,
                            responsive: true,
                            guides: true,
                            highlight: true,
                            cropBoxMovable: true,
                            cropBoxResizable: true,
                            toggleDragModeOnDblclick: true,
                        });
                        cropCoverModal.classList.add('active');
                    }, 100);
                }
            };
            reader.readAsDataURL(file);
        }
        
        profilePictureInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                handleImageSelect(this.files[0], 'profile');
            }
        });
        
        coverPhotoInput.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                handleImageSelect(this.files[0], 'cover');
            }
        });
        
        closeCropProfileModal.addEventListener('click', function() {
            cropProfileModal.classList.remove('active');
            if (profileCropper) profileCropper.destroy();
            profilePictureInput.value = '';
        });
        
        closeCropCoverModal.addEventListener('click', function() {
            cropCoverModal.classList.remove('active');
            if (coverCropper) coverCropper.destroy();
            coverPhotoInput.value = '';
        });
        
        document.getElementById('cancelProfileCrop').addEventListener('click', function() {
            cropProfileModal.classList.remove('active');
            if (profileCropper) profileCropper.destroy();
            profilePictureInput.value = '';
        });
        
        document.getElementById('cancelCoverCrop').addEventListener('click', function() {
            cropCoverModal.classList.remove('active');
            if (coverCropper) coverCropper.destroy();
            coverPhotoInput.value = '';
        });
        
        document.getElementById('resetProfileCrop').addEventListener('click', function() {
            if (profileCropper) profileCropper.reset();
        });
        
        document.getElementById('resetCoverCrop').addEventListener('click', function() {
            if (coverCropper) coverCropper.reset();
        });
        
        document.getElementById('rotateLeftProfile').addEventListener('click', function() {
            if (profileCropper) profileCropper.rotate(-45);
        });
        
        document.getElementById('rotateRightProfile').addEventListener('click', function() {
            if (profileCropper) profileCropper.rotate(45);
        });
        
        document.getElementById('rotateLeftCover').addEventListener('click', function() {
            if (coverCropper) coverCropper.rotate(-45);
        });
        
        document.getElementById('rotateRightCover').addEventListener('click', function() {
            if (coverCropper) coverCropper.rotate(45);
        });
        
        document.getElementById('zoomProfileSlider').addEventListener('input', function() {
            if (profileCropper) profileCropper.zoomTo(this.value);
        });
        
        document.getElementById('zoomCoverSlider').addEventListener('input', function() {
            if (coverCropper) coverCropper.zoomTo(this.value);
        });
        
        document.getElementById('confirmProfileCrop').addEventListener('click', function() {
            if (profileCropper) {
                const canvas = profileCropper.getCroppedCanvas({
                    maxWidth: 500,
                    maxHeight: 500,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                
                canvas.toBlob(function(blob) {
                    const formData = new FormData();
                    formData.append('profile_picture_crop', blob, 'profile_picture.jpg');
                    formData.append('update_profile', '1');
                    
                    fetch('profile.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (response.ok) {
                            location.reload();
                        } else {
                            alert('Error uploading image');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        alert('Error uploading image');
                    });
                }, 'image/jpeg', 0.9);
            }
        });
        
        document.getElementById('confirmCoverCrop').addEventListener('click', function() {
            if (coverCropper) {
                const canvas = coverCropper.getCroppedCanvas({
                    maxWidth: 1200,
                    maxHeight: 300,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                
                canvas.toBlob(function(blob) {
                    const formData = new FormData();
                    formData.append('cover_photo_crop', blob, 'cover_photo.jpg');
                    formData.append('update_cover_photo', '1');
                    
                    fetch('profile.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (response.ok) {
                            location.reload();
                        } else {
                            alert('Error uploading image');
                        }
                    }).catch(error => {
                        console.error('Error:', error);
                        alert('Error uploading image');
                    });
                }, 'image/jpeg', 0.9);
            }
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === cropProfileModal) {
                cropProfileModal.classList.remove('active');
                if (profileCropper) profileCropper.destroy();
                profilePictureInput.value = '';
            }
            if (event.target === cropCoverModal) {
                cropCoverModal.classList.remove('active');
                if (coverCropper) coverCropper.destroy();
                coverPhotoInput.value = '';
            }
            if (event.target === editPhotoModal) {
                editPhotoModal.classList.remove('active');
            }
        });
        
        function openEditPhotoModal(photoId, title, description) {
            document.getElementById('editPhotoId').value = photoId;
            editPhotoTitle.value = title;
            editPhotoDescription.value = description;
            titleCharCount.textContent = title.length;
            descCharCount.textContent = description.length;
            editPhotoModal.classList.add('active');
        }
    </script>
</body>
</html>