<?php
// admin_dashboard.php
require_once 'config.php';
require_once 'admin_functions.php';

// Make sure user is logged in and is admin
requireLogin();
requireAdmin();

// Get stats
$stats = getAdminDashboardStats($conn);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Cupid</title>
    <?php include 'admin_header_includes.php'; ?>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    
    <div class="admin-container">
        <div class="container">
            <?php include 'admin_sidebar.php'; ?>
            
            <div class="main-content">
                <div class="page-header">
                    <h1>Admin Dashboard</h1>
                </div>
                
                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $stats['new_users_today']; ?></div>
                        <div class="stat-label">New Users Today</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-value"><?php echo 'Rp ' . number_format($stats['total_revenue'], 0, ',', '.'); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Activity</h2>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($stats['recent_activity'] as $activity): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($activity['user_name']); ?></td>
                                <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($activity['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'admin_footer.php'; ?>
</body>
</html>