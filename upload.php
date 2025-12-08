<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database connection, logger, and settings
require_once 'db_connect.php';
require_once 'logger.php';
require_once 'settings.php';

$logger = new UserLogger();
$settings = new SiteSettings();

// Set page-specific variables
$page_title = 'Upload Photo';
$page_css = 'upload.css';

// Handle photo upload
$upload_dir = 'uploads/';
$upload_errors = [];
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate title
    $title = sanitizeInput($_POST['title']);
    if (empty($title)) {
        $upload_errors[] = "Photo title is required.";
    } elseif (strlen($title) > 100) {
        $upload_errors[] = "Photo title is too long (max 100 characters).";
    }
    
    // Validate description
    $description = sanitizeInput($_POST['description']);
    if (strlen($description) > 500) {
        $upload_errors[] = "Description is too long (max 500 characters).";
    }
    
    // Check if edited photo data exists
    $has_edited_photo = !empty($_POST['edited_photo_data']);
    
    // Handle file upload
    if (empty($_FILES['photo']['name']) && !$has_edited_photo) {
        $upload_errors[] = "Please select a photo to upload.";
    } else if (!empty($_FILES['photo']['name'])) {
        $file_name = $_FILES['photo']['name'];
        $file_tmp = $_FILES['photo']['tmp_name'];
        $file_size = $_FILES['photo']['size'];
        $file_error = $_FILES['photo']['error'];
        
        // Get file extension
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            $upload_errors[] = "Invalid file type. Only JPG, JPEG, PNG, GIF, and WebP files are allowed.";
        }
        
        if ($file_size > 10000000) { // 10MB limit
            $upload_errors[] = "File size too large. Maximum file size is 10MB.";
        }
        
        if ($file_error !== UPLOAD_ERR_OK) {
            $upload_errors[] = "File upload error occurred.";
        }
    }
    
    // If no errors, proceed with upload
    if (empty($upload_errors)) {
        try {
            // Create uploads directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $new_file_name = null;
            $upload_path = null;
            
            // Handle edited photo data (converted from base64)
            if ($has_edited_photo) {
                $new_file_name = uniqid() . '_' . time() . '.jpg';
                $upload_path = $upload_dir . $new_file_name;
                
                // Decode base64 data
                $image_data = $_POST['edited_photo_data'];
                if (strpos($image_data, 'data:image') === 0) {
                    $image_data = substr($image_data, strpos($image_data, ',') + 1);
                }
                
                // Decode and save
                if (file_put_contents($upload_path, base64_decode($image_data))) {
                    // File saved successfully
                } else {
                    $upload_errors[] = "Failed to save edited photo.";
                }
            }
            // Handle original file upload
            else if (!empty($_FILES['photo']['name']) && !isset($upload_errors[0])) {
                $file_name = $_FILES['photo']['name'];
                $file_tmp = $_FILES['photo']['tmp_name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;
                
                if (!move_uploaded_file($file_tmp, $upload_path)) {
                    $upload_errors[] = "Failed to move uploaded file.";
                }
            }
            
            // If no errors, insert into database
            if (empty($upload_errors) && $new_file_name) {
                // Connect to database
                $pdo = getDBConnection();
                
                // Insert photo record
                $stmt = $pdo->prepare("INSERT INTO photos (user_id, title, description, image_path, uploaded_at) VALUES (:user_id, :title, :description, :image_path, NOW())");
                $stmt->bindParam(':user_id', $_SESSION['user_id']);
                $stmt->bindParam(':title', $title);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':image_path', $new_file_name);
                
                if ($stmt->execute()) {
                    $photo_id = $pdo->lastInsertId();
                    $success_message = "Photo uploaded successfully!";
                    
                    // Log successful upload
                    $logger->log(
                        UserLogger::ACTION_UPLOAD_PHOTO,
                        "Uploaded photo: '{$title}' (File: {$new_file_name})" . ($has_edited_photo ? " [Edited]" : ""),
                        $_SESSION['user_id'],
                        null,
                        'photos',
                        $photo_id,
                        UserLogger::STATUS_SUCCESS
                    );
                    
                    // Clear form data
                    $title = '';
                    $description = '';
                } else {
                    $upload_errors[] = "Failed to save photo to database.";
                    
                    // Log failure
                    $logger->log(
                        UserLogger::ACTION_UPLOAD_PHOTO,
                        "Failed to save photo to database: {$title}",
                        $_SESSION['user_id'],
                        null,
                        null,
                        null,
                        UserLogger::STATUS_FAILED
                    );
                    
                    // Clean up uploaded file
                    if (isset($upload_path) && file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                }
            }
        } catch(PDOException $e) {
            $upload_errors[] = "Database error: " . $e->getMessage();
            
            // Log error
            $logger->log(
                UserLogger::ACTION_UPLOAD_PHOTO,
                "Database error during upload: " . $e->getMessage(),
                $_SESSION['user_id'],
                null,
                null,
                null,
                UserLogger::STATUS_FAILED
            );
            
            // Clean up uploaded file if it exists
            if (isset($upload_path) && file_exists($upload_path)) {
                unlink($upload_path);
            }
        }
    } else {
        // Log validation errors
        $logger->log(
            UserLogger::ACTION_UPLOAD_PHOTO,
            "Upload validation failed: " . implode(', ', $upload_errors),
            $_SESSION['user_id'],
            null,
            null,
            null,
            UserLogger::STATUS_WARNING
        );
    }
}

// Set default values for form
$title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
$description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';

// Include header
include 'header.php';
?>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

    <div class="container">
        <div class="upload-container">
            <h1 class="upload-title">Upload Your Photo</h1>
            
            <?php if ($success_message): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($upload_errors)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul class="error-list">
                        <?php foreach ($upload_errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data" id="uploadForm">
                <div class="form-group">
                    <label for="photo-title">Photo Title *</label>
                    <input type="text" id="photo-title" name="title" required 
                           value="<?php echo htmlspecialchars($title); ?>"
                           placeholder="Give your photo a descriptive title">
                </div>
                
                <div class="form-group">
                    <label for="photo-description">Description</label>
                    <textarea id="photo-description" name="description" 
                              placeholder="Describe your photo, location, camera settings, or story behind the shot..."><?php echo htmlspecialchars($description); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="photo-file">Choose Photo *</label>
                    <div class="file-input">
                        <span class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i> Click to select photo (JPG, PNG, GIF, WebP - Max 10MB)
                        </span>
                        <input type="file" id="photo-file" name="photo" accept="image/*" required>
                    </div>
                    <p class="file-hint">Select a photo to preview and edit before uploading</p>
                </div>
                
                <input type="hidden" id="editedPhotoData" name="edited_photo_data">
                
                <button type="submit" class="submit-btn">Upload Photo</button>
            </form>
            
            <a href="home.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Gallery
            </a>
        </div>
    </div>

    <!-- Image Editor Modal -->
    <div id="editImageModal" class="modal">
        <div class="modal-content edit-image-modal">
            <div class="modal-header">
                <h2>Edit Your Photo</h2>
                <button type="button" class="close-btn" id="closeEditImageModal">&times;</button>
            </div>
            
            <div class="editor-container">
                <div class="editor-preview">
                    <img id="imageToEdit" src="" alt="Photo to edit" style="max-width: 100%; max-height: 500px;">
                </div>
                
                <div class="editor-controls">
                    <div class="control-group">
                        <label>Crop & Rotate</label>
                        <div class="button-group">
                            <button type="button" class="btn btn-sm" id="resetEditBtn" title="Reset to original">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                            <button type="button" class="btn btn-sm" id="rotateLeftBtn" title="Rotate left 90°">
                                <i class="fas fa-redo"></i> Rotate Left
                            </button>
                            <button type="button" class="btn btn-sm" id="rotateRightBtn" title="Rotate right 90°">
                                <i class="fas fa-redo fa-flip-horizontal"></i> Rotate Right
                            </button>
                        </div>
                    </div>
                    
                    <div class="control-group">
                        <label for="zoomSlider">Zoom</label>
                        <div class="slider-container">
                            <input type="range" id="zoomSlider" min="0.1" max="3" step="0.1" value="1">
                            <span id="zoomValue" class="slider-value">1x</span>
                        </div>
                    </div>

                    <div class="control-group">
                        <label for="brightnessSlider">Brightness</label>
                        <div class="slider-container">
                            <input type="range" id="brightnessSlider" min="0.5" max="2" step="0.1" value="1">
                            <span id="brightnessValue" class="slider-value">1x</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmEditBtn">Apply & Continue</button>
            </div>
        </div>
    </div>

    <script>
        let cropper = null;
        let currentImageFile = null;
        let brightness = 1;
        
        const photoFileInput = document.getElementById('photo-file');
        const editImageModal = document.getElementById('editImageModal');
        const imageToEdit = document.getElementById('imageToEdit');
        const zoomSlider = document.getElementById('zoomSlider');
        const zoomValue = document.getElementById('zoomValue');
        const brightnessSlider = document.getElementById('brightnessSlider');
        const brightnessValue = document.getElementById('brightnessValue');
        
        photoFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const fileName = file.name;
                const label = document.querySelector('.file-input-label');
                label.innerHTML = '<i class="fas fa-file-image"></i> ' + 
                    (fileName.length > 30 ? fileName.substring(0, 27) + '...' : fileName);
                
                currentImageFile = file;
                showImageEditor(file);
            }
        });
        
        function showImageEditor(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                imageToEdit.src = e.target.result;
                
                if (cropper) {
                    cropper.destroy();
                }
                
                setTimeout(() => {
                    cropper = new Cropper(imageToEdit, {
                        viewMode: 1,
                        autoCropArea: 1,
                        responsive: true,
                        guides: true,
                        highlight: true,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: true,
                    });
                    editImageModal.classList.add('active');
                }, 100);
            };
            reader.readAsDataURL(file);
        }
        
        document.getElementById('closeEditImageModal').addEventListener('click', function() {
            editImageModal.classList.remove('active');
            if (cropper) cropper.destroy();
            cropper = null;
            photoFileInput.value = '';
        });
        
        document.getElementById('cancelEditBtn').addEventListener('click', function() {
            editImageModal.classList.remove('active');
            if (cropper) cropper.destroy();
            cropper = null;
            photoFileInput.value = '';
        });
        
        document.getElementById('resetEditBtn').addEventListener('click', function() {
            if (cropper) cropper.reset();
            brightness = 1;
            brightnessSlider.value = 1;
            brightnessValue.textContent = '1x';
            imageToEdit.style.filter = 'brightness(1)';
        });
        
        document.getElementById('rotateLeftBtn').addEventListener('click', function() {
            if (cropper) cropper.rotate(-90);
        });
        
        document.getElementById('rotateRightBtn').addEventListener('click', function() {
            if (cropper) cropper.rotate(90);
        });
        
        zoomSlider.addEventListener('input', function() {
            if (cropper) {
                cropper.zoomTo(this.value);
                zoomValue.textContent = parseFloat(this.value).toFixed(1) + 'x';
            }
        });
        
        brightnessSlider.addEventListener('input', function() {
            brightness = parseFloat(this.value);
            imageToEdit.style.filter = 'brightness(' + brightness + ')';
            brightnessValue.textContent = brightness.toFixed(1) + 'x';
        });
        
        document.getElementById('confirmEditBtn').addEventListener('click', function() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    maxWidth: 4096,
                    maxHeight: 4096,
                    fillColor: '#fff',
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high',
                });
                
                if (brightness !== 1) {
                    const ctx = canvas.getContext('2d');
                    ctx.filter = 'brightness(' + brightness + ')';
                    ctx.drawImage(canvas, 0, 0);
                }
                
                canvas.toBlob(function(blob) {
                    const formData = new FormData();
                    formData.append('edited_photo', blob, 'edited_photo.jpg');
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        document.getElementById('editedPhotoData').value = e.target.result;
                        editImageModal.classList.remove('active');
                        if (cropper) cropper.destroy();
                        cropper = null;
                    };
                    reader.readAsDataURL(blob);
                }, 'image/jpeg', 0.9);
            }
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === editImageModal) {
                editImageModal.classList.remove('active');
                if (cropper) cropper.destroy();
                cropper = null;
                photoFileInput.value = '';
            }
        });
    </script>
</body>
</html>