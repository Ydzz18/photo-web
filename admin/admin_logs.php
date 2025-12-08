<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin'])) {
    header("Location: admin_login.php");
    exit();
}

require_once '../db_connect.php';
require_once '../logger.php';
require_once 'rbac.php';

// Check permission to view logs
requirePermission('view_logs');

$logger = new UserLogger();

// Pagination settings
$per_page = 50;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;
$filters = [];
if (isset($_GET['action_type']) && !empty($_GET['action_type'])) {
    $filters['action_type'] = $_GET['action_type'];
}
if (isset($_GET['status']) && !empty($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $filters['user_id'] = (int)$_GET['user_id'];
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'] . ' 00:00:00';
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'] . ' 23:59:59';
}

// Get logs and count
$logs = $logger->getAllLogs($filters, $per_page, $offset);
$total_logs = $logger->getLogsCount($filters);
$total_pages = ceil($total_logs / $per_page);

// Get all users for filter dropdown
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $all_users = [];
}

// Get activity statistics
$stats = $logger->getActivityStats(null, 7); // Last 7 days

// Handle session messages
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activity Logs - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="icon" type="image/png" href="assets/img/admin.png">
    <style>
        .filters-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .filters-form .form-group {
            margin: 0;
        }
        
        .filters-form label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .filters-form select,
        .filters-form input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filters-actions {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .filters-actions button {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }
        
        .btn-filter {
            background: #007bff;
            color: white;
        }
        
        .btn-reset {
            background: #6c757d;
            color: white;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .action-badge {
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 30px;
            padding: 20px 0;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-box h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
        }
        
        .stat-box .count {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }
        
        .log-description {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .ip-address {
            font-family: monospace;
            font-size: 12px;
            color: #666;
        }

        .btn-view-details {
            background: #17a2b8;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.3s;
        }

        .btn-view-details:hover {
            background: #138496;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            animation: slideIn 0.3s ease-in;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .modal-header h2 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            font-size: 14px;
            line-height: 1.6;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            font-size: 12px;
        }

        .detail-value {
            color: #333;
            word-break: break-word;
        }

        .detail-value.user-info {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .detail-value.badge-admin {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <header class="top-bar">
            <h1>User Activity Logs</h1>
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

        <!-- Activity Statistics -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-chart-bar"></i> Activity Overview (Last 7 Days)
            </h2>
            <div class="stats-grid">
                <?php
                $action_counts = [];
                foreach ($stats as $stat) {
                    if (!isset($action_counts[$stat['action_type']])) {
                        $action_counts[$stat['action_type']] = 0;
                    }
                    $action_counts[$stat['action_type']] += $stat['count'];
                }
                
                $priority_actions = ['login', 'upload_photo', 'delete_photo', 'like_photo', 'comment'];
                $display_actions = [];
                
                foreach ($priority_actions as $action) {
                    if (isset($action_counts[$action])) {
                        $display_actions[$action] = $action_counts[$action];
                    }
                }
                
                $remaining_actions = array_diff_key($action_counts, array_flip($priority_actions));
                $top_remaining = array_slice($remaining_actions, 0, (5 - count($display_actions)), true);
                $display_actions = array_merge($display_actions, $top_remaining);
                
                foreach ($display_actions as $action => $count):
                ?>
                <div class="stat-box">
                    <h4><?php echo ucwords(str_replace('_', ' ', $action)); ?></h4>
                    <div class="count"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Filters -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-filter"></i> Filter Logs
            </h2>
            <form method="GET" action="" class="filters-form">
                <div class="form-group">
                    <label for="action_type">Action Type</label>
                    <select name="action_type" id="action_type">
                        <option value="">All Actions</option>
                        <option value="login" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'login') ? 'selected' : ''; ?>>Login</option>
                        <option value="logout" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'logout') ? 'selected' : ''; ?>>Logout</option>
                        <option value="register" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'register') ? 'selected' : ''; ?>>Register</option>
                        <option value="upload_photo" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'upload_photo') ? 'selected' : ''; ?>>Upload Photo</option>
                        <option value="delete_photo" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'delete_photo') ? 'selected' : ''; ?>>Delete Photo</option>
                        <option value="like_photo" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'like_photo') ? 'selected' : ''; ?>>Like Photo</option>
                        <option value="comment" <?php echo (isset($filters['action_type']) && $filters['action_type'] == 'comment') ? 'selected' : ''; ?>>Comment</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="">All Statuses</option>
                        <option value="success" <?php echo (isset($filters['status']) && $filters['status'] == 'success') ? 'selected' : ''; ?>>Success</option>
                        <option value="failed" <?php echo (isset($filters['status']) && $filters['status'] == 'failed') ? 'selected' : ''; ?>>Failed</option>
                        <option value="warning" <?php echo (isset($filters['status']) && $filters['status'] == 'warning') ? 'selected' : ''; ?>>Warning</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="user_id">User</label>
                    <select name="user_id" id="user_id">
                        <option value="">All Users</option>
                        <?php foreach ($all_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" <?php echo (isset($filters['user_id']) && $filters['user_id'] == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="date_from">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="date_to">To Date</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                </div>
                
                <div class="filters-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="admin_logs.php" class="btn-reset" style="text-decoration: none; display: inline-block; line-height: 1.5;">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="card">
            <h2 class="card-title">
                <i class="fas fa-list"></i> Activity Logs (<?php echo number_format($total_logs); ?> total)
            </h2>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($logs)): ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo $log['id']; ?></td>
                                    <td><?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td>
                                        <?php if ($log['user_username']): ?>
                                            <strong><?php echo htmlspecialchars($log['user_username']); ?></strong>
                                        <?php elseif ($log['admin_username']): ?>
                                            <strong><?php echo htmlspecialchars($log['admin_username']); ?></strong> 
                                            <span class="badge badge-admin" style="font-size: 10px;">Admin</span>
                                        <?php else: ?>
                                            <span style="color: #999;">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="action-badge">
                                            <?php echo ucwords(str_replace('_', ' ', $log['action_type'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="log-description" title="<?php echo htmlspecialchars($log['action_description']); ?>">
                                            <?php echo htmlspecialchars($log['action_description']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="ip-address"><?php echo htmlspecialchars($log['ip_address']); ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $log['status']; ?>">
                                            <?php echo $log['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-view-details" onclick="openDetailModal(<?php echo htmlspecialchars(json_encode($log), ENT_QUOTES, 'UTF-8'); ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                    No logs found matching your criteria.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo ($page - 1); ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '']), '', '&'); ?>">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '']), '', '&'); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo ($page + 1); ?><?php echo http_build_query(array_diff_key($_GET, ['page' => '']), '', '&'); ?>">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Modal -->
    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Log Details</h2>
                <button class="modal-close" onclick="closeDetailModal()">&times;</button>
            </div>
            <div class="modal-body" id="modalBody">
            </div>
        </div>
    </div>

    <script>
        function openDetailModal(logData) {
            const modal = document.getElementById('detailModal');
            const modalBody = document.getElementById('modalBody');

            let userName = 'System';
            if (logData.user_username) {
                userName = logData.user_username;
            } else if (logData.admin_username) {
                userName = logData.admin_username + ' <span style="font-size: 10px; background: #ffc107; color: #000; padding: 2px 6px; border-radius: 3px; font-weight: 600;">Admin</span>';
            }

            const timestamp = new Date(logData.created_at).toLocaleString();
            const actionType = logData.action_type.replace(/_/g, ' ').toUpperCase();
            const statusBadge = `<span class="status-badge status-${logData.status}" style="display: inline-block;">${logData.status.toUpperCase()}</span>`;

            const detailsHTML = `
                <div class="detail-row">
                    <div class="detail-label">Log ID</div>
                    <div class="detail-value">${logData.id}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Timestamp</div>
                    <div class="detail-value">${timestamp}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">User</div>
                    <div class="detail-value">${userName}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Action</div>
                    <div class="detail-value"><span class="action-badge">${actionType}</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">${logData.action_description ? logData.action_description : '<em style="color: #999;">No description</em>'}</div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">IP Address</div>
                    <div class="detail-value"><span class="ip-address">${logData.ip_address}</span></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">${statusBadge}</div>
                </div>
            `;

            modalBody.innerHTML = detailsHTML;
            modal.style.display = 'block';
        }

        function closeDetailModal() {
            const modal = document.getElementById('detailModal');
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            const modal = document.getElementById('detailModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>