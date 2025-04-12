<?php
// Sertakan file konfigurasi

require_once 'config.php';

// Pastikan user sudah login
requireLogin();

// Lanjutkan dengan kode dashboard

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get profile data if exists
$profile_sql = "SELECT * FROM profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Check if profile is complete
$profile_complete = ($profile && !empty($profile['interests']) && !empty($profile['bio']));

// Handle profile update
$profile_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $bio = $_POST['bio'];
    $interests = $_POST['interests'];
    $looking_for = $_POST['looking_for'];
    $major = $_POST['major'];
    
    // Upload profile picture
    $profile_pic = '';
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_pic']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $newname = 'profile_' . $user_id . '.' . $filetype;
            $upload_dir = 'uploads/profiles/';
            
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $newname)) {
                $profile_pic = $upload_dir . $newname;
            }
        }
    }
    
    if ($profile) {
        // Update existing profile
        $update_sql = "UPDATE profiles SET bio = ?, interests = ?, looking_for = ?, major = ?";
        $params = "ssss";
        $param_values = [$bio, $interests, $looking_for, $major];
        
        if (!empty($profile_pic)) {
            $update_sql .= ", profile_pic = ?";
            $params .= "s";
            $param_values[] = $profile_pic;
        }
        
        $update_sql .= " WHERE user_id = ?";
        $params .= "i";
        $param_values[] = $user_id;
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param($params, ...$param_values);
        
        if ($update_stmt->execute()) {
            $profile_message = 'Profile updated successfully!';
            // Refresh profile data
            $profile_stmt->execute();
            $profile_result = $profile_stmt->get_result();
            $profile = $profile_result->fetch_assoc();
            $profile_complete = true;
        } else {
            $profile_message = 'Error updating profile: ' . $conn->error;
        }
    } else {
        // Create new profile
        $insert_sql = "INSERT INTO profiles (user_id, bio, interests, looking_for, major, profile_pic) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("isssss", $user_id, $bio, $interests, $looking_for, $major, $profile_pic);
        
        if ($insert_stmt->execute()) {
            $profile_message = 'Profile created successfully!';
            // Refresh profile data
            $profile_stmt->execute();
            $profile_result = $profile_stmt->get_result();
            $profile = $profile_result->fetch_assoc();
            $profile_complete = true;
        } else {
            $profile_message = 'Error creating profile: ' . $conn->error;
        }
    }
}

// Get received menfess
$menfess_sql = "SELECT m.*, 
                CASE WHEN m.sender_id = ? THEN 'sent' ELSE 'received' END as type,
                CASE 
                    WHEN (SELECT COUNT(*) FROM menfess_likes WHERE user_id = ? AND menfess_id = m.id) > 0 
                    THEN 1 ELSE 0 
                END as liked
                FROM menfess m
                WHERE m.receiver_id = ? OR m.sender_id = ?
                ORDER BY m.created_at DESC";
$menfess_stmt = $conn->prepare($menfess_sql);
$menfess_stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
$menfess_stmt->execute();
$menfess_result = $menfess_stmt->get_result();
$menfess_messages = [];
while ($row = $menfess_result->fetch_assoc()) {
    $menfess_messages[] = $row;
}

// Get matches (mutual likes)
$matches_sql = "SELECT DISTINCT u.id, u.name, p.profile_pic, p.bio
                FROM users u
                JOIN profiles p ON u.id = p.user_id
                JOIN menfess m ON (m.sender_id = u.id OR m.receiver_id = u.id)
                JOIN menfess_likes ml1 ON m.id = ml1.menfess_id AND ml1.user_id = ?
                JOIN menfess_likes ml2 ON m.id = ml2.menfess_id 
                WHERE 
                  ((m.sender_id = ? AND m.receiver_id = u.id AND ml2.user_id = u.id) OR
                   (m.receiver_id = ? AND m.sender_id = u.id AND ml2.user_id = u.id))";
$matches_stmt = $conn->prepare($matches_sql);
$matches_stmt->bind_param("iii", $user_id, $user_id, $user_id);
$matches_stmt->execute();
$matches_result = $matches_stmt->get_result();
$matches = [];
while ($row = $matches_result->fetch_assoc()) {
    $matches[] = $row;
}

// Handle new menfess submission
$menfess_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_menfess'])) {
    $crush_id = $_POST['crush_id'];
    $message = $_POST['message'];
    
    $insert_menfess_sql = "INSERT INTO menfess (sender_id, receiver_id, message, is_anonymous) VALUES (?, ?, ?, 1)";
    $insert_menfess_stmt = $conn->prepare($insert_menfess_sql);
    $insert_menfess_stmt->bind_param("iis", $user_id, $crush_id, $message);
    
    if ($insert_menfess_stmt->execute()) {
        $menfess_message = 'Menfess sent successfully!';
        // Refresh menfess data
        $menfess_stmt->execute();
        $menfess_result = $menfess_stmt->get_result();
        $menfess_messages = [];
        while ($row = $menfess_result->fetch_assoc()) {
            $menfess_messages[] = $row;
        }
    } else {
        $menfess_message = 'Error sending menfess: ' . $conn->error;
    }
}

// Handle menfess like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_menfess'])) {
    $menfess_id = $_POST['menfess_id'];
    
    // Check if already liked
    $check_like_sql = "SELECT * FROM menfess_likes WHERE user_id = ? AND menfess_id = ?";
    $check_like_stmt = $conn->prepare($check_like_sql);
    $check_like_stmt->bind_param("ii", $user_id, $menfess_id);
    $check_like_stmt->execute();
    $check_like_result = $check_like_stmt->get_result();
    
    if ($check_like_result->num_rows > 0) {
        // Unlike
        $unlike_sql = "DELETE FROM menfess_likes WHERE user_id = ? AND menfess_id = ?";
        $unlike_stmt = $conn->prepare($unlike_sql);
        $unlike_stmt->bind_param("ii", $user_id, $menfess_id);
        $unlike_stmt->execute();
    } else {
        // Like
        $like_sql = "INSERT INTO menfess_likes (user_id, menfess_id) VALUES (?, ?)";
        $like_stmt = $conn->prepare($like_sql);
        $like_stmt->bind_param("ii", $user_id, $menfess_id);
        $like_stmt->execute();
    }
    
    // Refresh menfess data
    $menfess_stmt->execute();
    $menfess_result = $menfess_stmt->get_result();
    $menfess_messages = [];
    while ($row = $menfess_result->fetch_assoc()) {
        $menfess_messages[] = $row;
    }
    
    // Refresh matches
    $matches_stmt->execute();
    $matches_result = $matches_stmt->get_result();
    $matches = [];
    while ($row = $matches_result->fetch_assoc()) {
        $matches[] = $row;
    }
}

// Get all users for crush selection
$users_sql = "SELECT u.id, u.name, p.profile_pic, p.bio 
              FROM users u
              LEFT JOIN profiles p ON u.id = p.user_id
              WHERE u.id != ?";
$users_stmt = $conn->prepare($users_sql);
$users_stmt->bind_param("i", $user_id);
$users_stmt->execute();
$users_result = $users_stmt->get_result();
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Handle blind chat request
$blind_chat_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_blind_chat'])) {
    // Find a random user for blind chat
    $random_user_sql = "SELECT id FROM users WHERE id != ? ORDER BY RAND() LIMIT 1";
    $random_user_stmt = $conn->prepare($random_user_sql);
    $random_user_stmt->bind_param("i", $user_id);
    $random_user_stmt->execute();
    $random_user_result = $random_user_stmt->get_result();
    
    if ($random_user_result->num_rows > 0) {
        $random_user = $random_user_result->fetch_assoc();
        $random_user_id = $random_user['id'];
        
        // Create a new chat session
        $chat_sql = "INSERT INTO chat_sessions (user1_id, user2_id, is_blind) VALUES (?, ?, 1)";
        $chat_stmt = $conn->prepare($chat_sql);
        $chat_stmt->bind_param("ii", $user_id, $random_user_id);
        
        if ($chat_stmt->execute()) {
            $chat_id = $conn->insert_id;
            $blind_chat_message = 'Blind chat started! Redirecting...';
            header("Location: chat.php?session_id=" . $chat_id);
            exit();
        } else {
            $blind_chat_message = 'Error starting blind chat: ' . $conn->error;
        }
    } else {
        $blind_chat_message = 'No users available for blind chat right now.';
    }
}

// Get active chat sessions
// First check if hidden_chats table exists
$table_check_sql = "SHOW TABLES LIKE 'hidden_chats'";
$table_exists = $conn->query($table_check_sql)->num_rows > 0;

// Create the hidden_chats table if it doesn't exist
if (!$table_exists) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS hidden_chats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id INT NOT NULL,
        hidden_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_session (user_id, session_id)
    )";
    $conn->query($create_table_sql);
    $table_exists = true;
}

// Get active chat sessions (excluding hidden chats)
if ($table_exists) {
    $chat_sessions_sql = "SELECT cs.*, 
                      u1.name as user1_name, 
                      u2.name as user2_name,
                      CASE WHEN cs.user1_id = ? THEN u2.name ELSE u1.name END as partner_name,
                      CASE WHEN cs.user1_id = ? THEN u2.id ELSE u1.id END as partner_id,
                      (SELECT p.profile_pic FROM profiles p WHERE p.user_id = CASE WHEN cs.user1_id = ? THEN u2.id ELSE u1.id END) as profile_pic,
                      (SELECT MAX(created_at) FROM chat_messages WHERE session_id = cs.id) as last_message_time
                      FROM chat_sessions cs
                      JOIN users u1 ON cs.user1_id = u1.id
                      JOIN users u2 ON cs.user2_id = u2.id
                      LEFT JOIN hidden_chats hc ON cs.id = hc.session_id AND hc.user_id = ?
                      WHERE (cs.user1_id = ? OR cs.user2_id = ?) AND hc.id IS NULL
                      ORDER BY CASE WHEN (SELECT MAX(created_at) FROM chat_messages WHERE session_id = cs.id) IS NULL THEN 0 ELSE 1 END DESC, 
                               (SELECT MAX(created_at) FROM chat_messages WHERE session_id = cs.id) DESC";
    $chat_sessions_stmt = $conn->prepare($chat_sessions_sql);
    $chat_sessions_stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
} else {
    // Fallback query without hidden_chats table (shouldn't be used since we create the table above)
    $chat_sessions_sql = "SELECT cs.*, 
                      u1.name as user1_name, 
                      u2.name as user2_name,
                      CASE WHEN cs.user1_id = ? THEN u2.name ELSE u1.name END as partner_name,
                      CASE WHEN cs.user1_id = ? THEN u2.id ELSE u1.id END as partner_id,
                      (SELECT p.profile_pic FROM profiles p WHERE p.user_id = CASE WHEN cs.user1_id = ? THEN u2.id ELSE u1.id END) as profile_pic,
                      (SELECT MAX(created_at) FROM chat_messages WHERE session_id = cs.id) as last_message_time
                      FROM chat_sessions cs
                      JOIN users u1 ON cs.user1_id = u1.id
                      JOIN users u2 ON cs.user2_id = u2.id
                      WHERE (cs.user1_id = ? OR cs.user2_id = ?)
                      ORDER BY last_message_time DESC";
    $chat_sessions_stmt = $conn->prepare($chat_sessions_sql);
    $chat_sessions_stmt->bind_param("iiiii", $user_id, $user_id, $user_id, $user_id, $user_id);
}

$chat_sessions_stmt->execute();
$chat_sessions_result = $chat_sessions_stmt->get_result();
$chat_sessions = [];
while ($row = $chat_sessions_result->fetch_assoc()) {
    $chat_sessions[] = $row;
}

// Get compatibility test questions if not yet taken
$test_taken_sql = "SELECT * FROM compatibility_results WHERE user_id = ?";
$test_taken_stmt = $conn->prepare($test_taken_sql);
$test_taken_stmt->bind_param("i", $user_id);
$test_taken_stmt->execute();
$test_taken_result = $test_taken_stmt->get_result();
$test_taken = ($test_taken_result->num_rows > 0);

$questions_sql = "SELECT * FROM compatibility_questions";
$questions_result = $conn->query($questions_sql);
$questions = [];
while ($row = $questions_result->fetch_assoc()) {
    $questions[] = $row;
}

// Handle compatibility test submission
$test_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
    $answers = [];
    $personality_score = 0;
    
    foreach ($questions as $question) {
        $q_id = $question['id'];
        if (isset($_POST['q_'.$q_id])) {
            $answer = $_POST['q_'.$q_id];
            $answers[$q_id] = $answer;
            
            // Calculate personality score based on answers
            $personality_score += intval($answer);
        }
    }
    
    // Normalize personality score to a 0-100 scale
    $max_possible = count($questions) * 5; // assuming 5 is max score per question
    $personality_score = ($personality_score / $max_possible) * 100;
    
    // Get major and interests from profile
    $major = $profile['major'] ?? '';
    $interests = $profile['interests'] ?? '';
    
    // Save test results
    $test_sql = "INSERT INTO compatibility_results (user_id, personality_score, major, interests, answers) 
                VALUES (?, ?, ?, ?, ?)";
    $test_stmt = $conn->prepare($test_sql);
    $answers_json = json_encode($answers);
    $test_stmt->bind_param("idsss", $user_id, $personality_score, $major, $interests, $answers_json);
    
    if ($test_stmt->execute()) {
        $test_message = 'Compatibility test completed! Finding matches...';
        $test_taken = true;
        
        // Find compatible matches
        header("Location: dashboard.php");
        exit();
    } else {
        $test_message = 'Error saving test results: ' . $conn->error;
    }
}

// Get compatible matches if test taken
$compatible_matches = [];
if ($test_taken) {
    $matches_sql = "SELECT u.id, u.name, p.profile_pic, p.bio, p.major, p.interests,
               ABS(IFNULL(cr.personality_score, 0) - ?) as personality_diff,
               CASE WHEN cr.major = ? THEN 30 ELSE 0 END as major_match,
               CASE WHEN INSTR(LOWER(IFNULL(cr.interests, '')), LOWER(IFNULL(?, ''))) > 0 THEN 40 ELSE 0 END as interests_match,
               (100 - ABS(IFNULL(cr.personality_score, 0) - ?) * 0.3 + 
               CASE WHEN cr.major = ? THEN 30 ELSE 0 END + 
               CASE WHEN INSTR(LOWER(IFNULL(cr.interests, '')), LOWER(IFNULL(?, ''))) > 0 THEN 40 ELSE 0 END) as compatibility_score
               FROM compatibility_results cr
               JOIN users u ON cr.user_id = u.id
               LEFT JOIN profiles p ON u.id = p.user_id
               WHERE cr.user_id != ?
               ORDER BY compatibility_score DESC
               LIMIT 15";
    $matches_stmt = $conn->prepare($matches_sql);
    $matches_stmt->bind_param("iiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
    $matches_stmt->execute();
    $compatible_matches_result = $matches_stmt->get_result();
    while ($row = $compatible_matches_result->fetch_assoc()) {
        $compatible_matches[] = $row;
    }
}

// Current page for navigation
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupid - Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ff4b6e;
            --secondary: #ffd9e0;
            --dark: #333333;
            --light: #ffffff;
            --accent: #ff8fa3;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: var(--dark);
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background-color: var(--light);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }
        
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo i {
            margin-right: 10px;
            font-size: 24px;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary);
            color: var(--light);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .btn:hover {
            background-color: #e63e5c;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }
        
        .btn-outline:hover {
            background-color: var(--primary);
            color: var(--light);
        }
        
        .dashboard {
            padding-top: 100px;
            min-height: 100vh;
            background-color: #f9f9f9;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background-color: var(--light);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .sidebar-menu {
            list-style: none;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 15px;
            color: var(--dark);
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: var(--secondary);
            color: var(--primary);
        }
        
        .sidebar-menu i {
            margin-right: 10px;
        }
        
        .main-content {
            padding-bottom: 50px;
        }
        
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h2 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .page-header h3 {
            font-size: 22px;
        }
        
        .card {
            background-color: var(--light);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .card-header {
            margin-bottom: 20px;
        }
        
        .card-header h3 {
            font-size: 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 20px;
        }
        
        .profile-pic img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-info h3 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .profile-info p {
            color: #666;
        }
        
        .menfess-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .menfess-card {
            background-color: #f0f0f0;
            border-radius: 10px;
            padding: 20px;
            position: relative;
        }
        
        .menfess-card.sent {
            background-color: var(--secondary);
            align-self: flex-end;
            max-width: 80%;
        }
        
        .menfess-card.received {
            background-color: #e4e6eb;
            align-self: flex-start;
            max-width: 80%;
        }
        
        .menfess-content {
            margin-bottom: 10px;
        }
        
        .menfess-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            color: #777;
        }
        
        .menfess-like {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .menfess-like i {
            margin-right: 5px;
            color: var(--primary);
        }
        
        .menfess-time {
            font-size: 12px;
        }
        
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .user-card {
            background-color: var(--light);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .user-card-img {
            height: 200px;
            overflow: hidden;
        }
        
        .user-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-card-info {
            padding: 20px;
        }
        
        .user-card-info h3 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .user-card-bio {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            height: 60px;
            overflow: hidden;
        }
        
        .chat-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .chat-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background-color: var(--light);
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s;
            text-decoration: none;
            color: inherit;
        }
        
        .chat-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .chat-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            overflow: hidden;
            margin-right: 15px;
            background-color: #f0f0f0;
        }
        
        .chat-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .chat-info {
            flex: 1;
        }
        
        .chat-name {
            font-size: 16px;
            font-weight: 500;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .chat-last-msg {
            font-size: 14px;
            color: #666;
        }
        
        .chat-time {
            font-size: 12px;
            color: #999;
        }
        
        .compatibility-score {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 20px;
            margin-right: 10px;
        }
        
        .compatibility-details {
            flex: 1;
        }
        
        .question {
            margin-bottom: 25px;
        }
        
        .question h4 {
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .options {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .option {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .option:hover {
            background-color: var(--secondary);
            border-color: var(--primary);
        }
        
        .option input {
            margin-right: 10px;
        }
        
        .lock-icon {
            margin-left: 5px;
            color: var(--primary);
        }
        
        /* Media Queries */
        @media (max-width: 991px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
                margin-bottom: 30px;
            }
        }
        
        @media (max-width: 767px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }
            
            .profile-pic {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }
        
            /* Light Theme (Default) */
    :root {
        --primary: #ff4b6e;
        --secondary: #ffd9e0;
        --dark: #333333;
        --light: #ffffff;
        --accent: #ff8fa3;
        --text-color: #333333;
        --bg-color: #f9f9f9;
        --card-bg: #ffffff;
        --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        --border-color: #eeeeee;
        --input-bg: #ffffff;
        --input-border: #dddddd;
    }

    /* Dark Theme */
    [data-theme="dark"] {
        --primary: #ff6b8a; /* slightly lighter for better contrast */
        --secondary: #662d39;
        --dark: #f5f5f5;
        --light: #222222;
        --accent: #ff8fa3;
        --text-color: #f5f5f5;
        --bg-color: #121212;
        --card-bg: #1e1e1e;
        --card-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        --border-color: #333333;
        --input-bg: #2a2a2a;
        --input-border: #444444;
    }

    /* Apply theme variables to elements */
    body {
        background-color: var(--bg-color);
        color: var(--text-color);
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    .card, .profile-section, .chat-messages, .chat-header, .chat-input, header {
        background-color: var(--card-bg);
        box-shadow: var(--card-shadow);
        transition: background-color 0.3s ease, box-shadow 0.3s ease;
    }

    input, textarea, select {
        background-color: var(--input-bg);
        border-color: var(--input-border);
        color: var(--text-color);
        transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
    }

    header, .logo {
        color: var(--primary);
    }

    nav ul li a {
        color: var(--text-color);
    }

    /* Theme Toggle Button */
    .theme-toggle {
        margin-left: 15px;
        display: flex;
        align-items: center;
    }

    #theme-toggle-btn {
        background: none;
        border: none;
        font-size: 20px;
        cursor: pointer;
        color: var(--primary);
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    #theme-toggle-btn:hover {
        background-color: rgba(255, 75, 110, 0.1);
    }

    /* Show/hide sun and moon icons based on theme */
    #theme-toggle-btn .fa-moon {
        display: block;
        position: absolute;
        transform: translateY(0);
        opacity: 1;
        transition: all 0.3s ease;
    }

    #theme-toggle-btn .fa-sun {
        display: block;
        position: absolute;
        transform: translateY(30px);
        opacity: 0;
        transition: all 0.3s ease;
    }

    [data-theme="dark"] #theme-toggle-btn .fa-moon {
        transform: translateY(-30px);
        opacity: 0;
    }

    [data-theme="dark"] #theme-toggle-btn .fa-sun {
        transform: translateY(0);
        opacity: 1;
    }

    /* Additional dark mode adjustments */
    [data-theme="dark"] .btn-outline {
        border-color: var(--primary);
    }

    [data-theme="dark"] .menfess-card,
    [data-theme="dark"] .option,
    [data-theme="dark"] .user-preview {
        background-color: var(--card-bg);
    }

    [data-theme="dark"] .message.received .message-content {
        background-color: #2a2a2a;
    }

    [data-theme="dark"] .menfess-card.received {
        background-color: #252525;
    }
    
    [data-theme="dark"] .sidebar {
    background-color: var(--card-bg);
    }
    
    [data-theme="dark"] .feature-box {
        background-color: var(--card-bg) !important;
    }
    
    [data-theme="dark"] .menfess-card.sent {
        background-color: var(--secondary);
    }
    
    [data-theme="dark"] .score-details {
        background-color: var(--card-bg) !important;
    }
    [data-theme="dark"] body {
    background-color: var(--bg-color);
    color: var(--text-color);
        [data-theme="dark"] body {
    background-color: var(--bg-color);
    color: var(--text-color);
    }
    
    [data-theme="dark"] .sidebar,
    [data-theme="dark"] .card,
    [data-theme="dark"] .feature-box,
    [data-theme="dark"] .dashboard,
    [data-theme="dark"] .chat-item,
    [data-theme="dark"] .user-card {
        background-color: var(--card-bg);
    }
    
    [data-theme="dark"] li, 
    [data-theme="dark"] p, 
    [data-theme="dark"] span, 
    [data-theme="dark"] div {
        color: var(--text-color);
    }
    </style>
</head>
<body>
    
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="cupid.php" class="logo">
                    <i class="fas fa-heart"></i> Cupid
                </a>
                <nav>
                    <ul>
                            <div class="theme-toggle">
    <button id="theme-toggle-btn" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
        <i class="fas fa-sun"></i>
    </button>
</div>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li>
                            <a href="logout.php" class="btn btn-outline">Keluar</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Dashboard Section -->
    <section class="dashboard">
        <div class="container">
            <div class="dashboard-container">
                <!-- Sidebar -->
                <div class="sidebar">
                    <ul class="sidebar-menu">
                        <li>
                            <a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li>
                            <a href="?page=profile" class="<?php echo $page === 'profile' ? 'active' : ''; ?>">
                                <i class="fas fa-user"></i> Profil
                            </a>
                        </li>
                        <li>
                            <a href="?page=menfess" class="<?php echo $page === 'menfess' ? 'active' : ''; ?>">
                                <i class="fas fa-mask"></i> Crush Menfess
                            </a>
                        </li>
                        <li>
                            <a href="?page=chat" class="<?php echo $page === 'chat' ? 'active' : ''; ?>">
                                <i class="fas fa-comments"></i> Chat
                            </a>
                        </li>
                        <li>
                            <a href="?page=compatibility" class="<?php echo $page === 'compatibility' ? 'active' : ''; ?>">
                                <i class="fas fa-clipboard-check"></i> Tes Kecocokan
                            </a>
                        </li>
                        <li>
                            <a href="?page=matches" class="<?php echo $page === 'matches' ? 'active' : ''; ?>">
                                <i class="fas fa-heart"></i> Pasangan
                            </a>
                        </li>
                        <li>
                            <a href="?page=payments" class="<?php echo $page === 'payments' ? 'active' : ''; ?>">
                                <i class="fas fa-credit-card"></i> Pembayaran
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Main Content -->
                <div class="main-content">
                    <?php if ($page === 'dashboard'): ?>
                        <div class="dashboard-header">
                            <h2>Dashboard</h2>
                            <p>Selamat datang, <?php echo htmlspecialchars($user['name']); ?>!</p>
                        </div>
                        
                        <?php if (!$profile_complete): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>Lengkapi Profil Anda</h3>
                            </div>
                            <p>Lengkapi profil Anda untuk meningkatkan peluang menemukan pasangan yang cocok!</p>
                            <a href="?page=profile" class="btn" style="margin-top: 15px;">Lengkapi Profil</a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Aktivitas Terbaru</h3>
                            </div>
                            <div class="recent-activity">
                                <?php if (empty($menfess_messages) && empty($chat_sessions)): ?>
                                    <p>Belum ada aktivitas baru.</p>
                                <?php else: ?>
                                    <ul style="list-style: none; padding: 0;">
                                        <?php 
                                        $count = 0;
                                        foreach ($menfess_messages as $message) {
                                            if ($count >= 3) break;
                                            $type = $message['type'] === 'sent' ? 'mengirim' : 'menerima';
                                            echo '<li style="padding: 10px 0; border-bottom: 1px solid #eee;">';
                                            echo '<i class="fas fa-mask" style="margin-right: 10px; color: var(--primary);"></i>';
                                            echo 'Anda ' . $type . ' pesan menfess. ';
                                            echo '<span style="color: #999; font-size: 12px;">' . date('d M Y H:i', strtotime($message['created_at'])) . '</span>';
                                            echo '</li>';
                                            $count++;
                                        }
                                        
                                        foreach ($chat_sessions as $session) {
                                            if ($count >= 3) break;
                                            echo '<li style="padding: 10px 0; border-bottom: 1px solid #eee;">';
                                            echo '<i class="fas fa-comments" style="margin-right: 10px; color: var(--primary);"></i>';
                                            
                                            // Check if blind chat and if permission exists
                                            $has_permission = false;
                                            if ($session['is_blind']) {
                                                $partner_id = $session['partner_id'];
                                                $permission_sql = "SELECT * FROM profile_view_permissions 
                                                                WHERE user_id = ? AND target_user_id = ?";
                                                $permission_stmt = $conn->prepare($permission_sql);
                                                $permission_stmt->bind_param("ii", $user_id, $partner_id);
                                                $permission_stmt->execute();
                                                $permission_result = $permission_stmt->get_result();
                                                $has_permission = ($permission_result->num_rows > 0);
                                                
                                                if (!$has_permission) {
                                                    echo 'Chat dengan Anonymous User';
                                                } else {
                                                    echo 'Chat dengan ' . htmlspecialchars($session['partner_name']);
                                                }
                                            } else {
                                                echo 'Chat dengan ' . htmlspecialchars($session['partner_name']);
                                            }
                                            
                                            if ($session['is_blind']) {
                                                echo ' (Blind Chat)';
                                            }
                                            echo ' <span style="color: #999; font-size: 12px;">' . 
                                                (isset($session['last_message_time']) && !empty($session['last_message_time']) 
                                                ? date('d M Y H:i', strtotime($session['last_message_time'])) 
                                                : 'Belum ada pesan') . 
                                                '</span>';
                                            echo '</li>';
                                            $count++;
                                        }
                                        ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Fitur Utama</h3>
                            </div>
                            <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                                <div class="feature-box" style="text-align: center; padding: 20px; background-color: var(--bg-color); border-radius: 10px;">
                                    <i class="fas fa-mask" style="font-size: 40px; color: var(--primary); margin-bottom: 15px;"></i>
                                    <h4>Anonymous Crush Menfess</h4>
                                    <p style="margin-bottom: 15px;">Kirim pesan anonim ke crush kamu!</p>
                                    <a href="?page=menfess" class="btn btn-outline">Kirim Menfess</a>
                                </div>
                                <div class="feature-box" style="text-align: center; padding: 20px; background-color: var(--bg-color); border-radius: 10px;">
                                    <i class="fas fa-comments" style="font-size: 40px; color: var(--primary); margin-bottom: 15px;"></i>
                                    <h4>Blind Chat</h4>
                                    <p style="margin-bottom: 15px;">Chat dengan mahasiswa acak!</p>
                                    <a href="?page=chat" class="btn btn-outline">Mulai Chat</a>
                                </div>
                                <div class="feature-box" style="text-align: center; padding: 20px; background-color: var(--bg-color); border-radius: 10px;">
                                    <i class="fas fa-clipboard-check" style="font-size: 40px; color: var(--primary); margin-bottom: 15px;"></i>
                                    <h4>Compatibility Test</h4>
                                    <p style="margin-bottom: 15px;">Temukan kecocokan berdasarkan kepribadian!</p>
                                    <a href="?page=compatibility" class="btn btn-outline">Ikuti Tes</a>
                                </div>
                            </div>
                        </div>
                    
<?php elseif ($page === 'profile'): ?>
    <div class="dashboard-header">
        <h2>Profil</h2>
        <p>Kelola informasi profil Anda untuk meningkatkan peluang menemukan pasangan yang cocok.</p>
    </div>
    
    <?php if (!empty($profile_message)): ?>
    <div class="alert <?php echo strpos($profile_message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
        <i class="<?php echo strpos($profile_message, 'success') !== false ? 'fas fa-check-circle' : 'fas fa-exclamation-circle'; ?>"></i>
        <?php echo $profile_message; ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h3>Informasi Profil</h3>
        </div>
        
        <div class="profile-header">
            <div class="profile-pic">
                <img src="<?php echo !empty($profile['profile_pic']) ? htmlspecialchars($profile['profile_pic']) : '/api/placeholder/400/400'; ?>" alt="Profile Picture">
                <label for="profile_pic" class="edit-pic-button">
                    <i class="fas fa-camera"></i>
                </label>
            </div>
            <div class="profile-info">
                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php if(!empty($profile['major'])): ?>
                <p><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($profile['major']); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-completion">
            <?php
            // Calculate profile completion percentage
            $total_fields = 5; // Name, email, bio, interests, major
            $filled_fields = 2; // Name and email are always filled
            
            if (!empty($profile)) {
                if (!empty($profile['bio'])) $filled_fields++;
                if (!empty($profile['interests'])) $filled_fields++;
                if (!empty($profile['major'])) $filled_fields++;
            }
            
            $completion_percentage = round(($filled_fields / $total_fields) * 100);
            ?>
            <div class="completion-text">
                <span>Kelengkapan Profil</span>
                <span><?php echo $completion_percentage; ?>%</span>
            </div>
            <div class="completion-bar">
                <div class="completion-progress" style="width: <?php echo $completion_percentage; ?>%;"></div>
            </div>
        </div>
        
        <div class="profile-tabs">
            <div class="profile-tab active" data-tab="basic">Informasi Dasar</div>
            <div class="profile-tab" data-tab="details">Detail Diri</div>
            <div class="profile-tab" data-tab="privacy">Privasi</div>
        </div>
        
        <form method="post" enctype="multipart/form-data">
            <!-- Basic Information Tab -->
            <div class="tab-content active" id="basic-tab">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    <div class="help-text">Email tidak dapat diubah karena digunakan untuk verifikasi.</div>
                </div>
                
                <div class="form-group">
                    <label for="major">Jurusan</label>
                    <select id="major" name="major" class="form-control">
                        <option value="">-- Pilih Jurusan --</option>
                        <option value="Computer Science" <?php echo ($profile && $profile['major'] === 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                        <option value="Business" <?php echo ($profile && $profile['major'] === 'Business') ? 'selected' : ''; ?>>Business</option>
                        <option value="Law" <?php echo ($profile && $profile['major'] === 'Law') ? 'selected' : ''; ?>>Law</option>
                        <option value="Medicine" <?php echo ($profile && $profile['major'] === 'Medicine') ? 'selected' : ''; ?>>Medicine</option>
                        <option value="Engineering" <?php echo ($profile && $profile['major'] === 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                        <option value="Graphic Design" <?php echo ($profile && $profile['major'] === 'Graphic Design') ? 'selected' : ''; ?>>Graphic Design</option>
                        <option value="Psychology" <?php echo ($profile && $profile['major'] === 'Psychology') ? 'selected' : ''; ?>>Psychology</option>
                        <option value="Communication" <?php echo ($profile && $profile['major'] === 'Communication') ? 'selected' : ''; ?>>Communication</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="profile_pic">Foto Profil</label>
                    <div class="file-upload">
                        <input type="text" class="form-control" readonly placeholder="Pilih file foto..." id="file-name">
                        <label for="profile_pic" class="file-upload-btn">Browse</label>
                    </div>
                    <input type="file" id="profile_pic" name="profile_pic" style="display: none;">
                    <div class="help-text">Format yang didukung: JPG, PNG, GIF. Maksimal 2MB.</div>
                </div>
            </div>
            
            <!-- Details Tab -->
            <div class="tab-content" id="details-tab">
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" class="form-control" rows="5" placeholder="Ceritakan tentang dirimu..."><?php echo $profile ? htmlspecialchars($profile['bio']) : ''; ?></textarea>
                    <div class="help-text">Maksimal 500 karakter. Ceritakan tentang hobi, kesukaan, dan hal menarik tentang dirimu.</div>
                </div>
                
                <div class="form-group">
                    <label for="interests">Minat & Hobi</label>
                    <textarea id="interests" name="interests" class="form-control" rows="3" placeholder="Masukkan minat dan hobi (pisahkan dengan koma)"><?php echo $profile ? htmlspecialchars($profile['interests']) : ''; ?></textarea>
                    <div class="help-text">Contoh: Musik, Film, Fotografi, Hiking, Coding</div>
                </div>
                
                <div class="form-group">
                    <label>Minat yang Ditambahkan</label>
                    <div class="interests-container" id="interests-display">
                        <?php 
                        if ($profile && !empty($profile['interests'])) {
                            $interests_array = explode(',', $profile['interests']);
                            foreach ($interests_array as $interest) {
                                $interest = trim($interest);
                                if (!empty($interest)) {
                                    echo '<span class="interest-tag">' . htmlspecialchars($interest) . ' <i class="fas fa-times"></i></span>';
                                }
                            }
                        } else {
                            echo '<span class="text-muted">Belum ada minat yang ditambahkan</span>';
                        }
                        ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="looking_for">Mencari</label>
                    <select id="looking_for" name="looking_for" class="form-control">
                        <option value="friends" <?php echo ($profile && $profile['looking_for'] === 'friends') ? 'selected' : ''; ?>>Teman</option>
                        <option value="study_partner" <?php echo ($profile && $profile['looking_for'] === 'study_partner') ? 'selected' : ''; ?>>Partner Belajar</option>
                        <option value="romance" <?php echo ($profile && $profile['looking_for'] === 'romance') ? 'selected' : ''; ?>>Romansa</option>
                    </select>
                </div>
            </div>
            
            <!-- Privacy Tab -->
            <div class="tab-content" id="privacy-tab">
                <div class="privacy-option">
                    <h4>
                        Tampilkan Profil Dalam Pencarian
                        <label class="toggle">
                            <input type="checkbox" name="searchable" <?php echo ($profile && isset($profile['searchable']) && $profile['searchable'] == 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </h4>
                    <p>Izinkan pengguna lain menemukan profil Anda dalam hasil pencarian dan rekomendasi kecocokan.</p>
                </div>
                
                <div class="privacy-option">
                    <h4>
                        Tampilkan Status Online
                        <label class="toggle">
                            <input type="checkbox" name="show_online" <?php echo ($profile && isset($profile['show_online']) && $profile['show_online'] == 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </h4>
                    <p>Tampilkan status online Anda kepada pengguna lain.</p>
                </div>
                
                <div class="privacy-option">
                    <h4>
                        Terima Pesan dari Siapa Saja
                        <label class="toggle">
                            <input type="checkbox" name="allow_messages" <?php echo ($profile && isset($profile['allow_messages']) && $profile['allow_messages'] == 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </h4>
                    <p>Izinkan pesan dari pengguna yang belum terhubung dengan Anda.</p>
                </div>
                
                <div class="privacy-option">
                    <h4>
                        Tampilkan Jurusan
                        <label class="toggle">
                            <input type="checkbox" name="show_major" <?php echo ($profile && isset($profile['show_major']) && $profile['show_major'] == 1) ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </h4>
                    <p>Tampilkan informasi jurusan Anda kepada pengguna lain.</p>
                </div>
            </div>
            
            <div class="submit-wrapper">
                <button type="submit" name="update_profile" class="btn">
                    <i class="fas fa-save"></i> Simpan Profil
                </button>
            </div>
        </form>
    </div>
    
    <script>
        // Handle tab switching
        document.querySelectorAll('.profile-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab
                document.querySelectorAll('.profile-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Show corresponding content
                const tabName = this.getAttribute('data-tab');
                document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });
        
        // Handle file upload preview
        document.getElementById('profile_pic').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const fileName = this.files[0].name;
                document.getElementById('file-name').value = fileName;
                
                // Optional: Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    const profilePic = document.querySelector('.profile-pic img');
                    profilePic.src = e.target.result;
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
        
        // Handle dynamic interests display
        const interestsInput = document.getElementById('interests');
        const interestsDisplay = document.getElementById('interests-display');
        
        interestsInput.addEventListener('input', function() {
            const interests = this.value.split(',').filter(interest => interest.trim() !== '');
            
            if (interests.length > 0) {
                interestsDisplay.innerHTML = '';
                
                interests.forEach(interest => {
                    const tag = document.createElement('span');
                    tag.className = 'interest-tag';
                    tag.innerHTML = interest.trim() + ' <i class="fas fa-times"></i>';
                    interestsDisplay.appendChild(tag);
                    
                    // Add event listener to remove tag when clicked
                    tag.querySelector('i').addEventListener('click', function() {
                        const removedInterest = this.parentNode.textContent.trim().slice(0, -1).trim();
                        const currentInterests = interestsInput.value.split(',').map(i => i.trim());
                        const filteredInterests = currentInterests.filter(i => i !== removedInterest);
                        interestsInput.value = filteredInterests.join(', ');
                        this.parentNode.remove();
                        
                        if (interestsDisplay.children.length === 0) {
                            interestsDisplay.innerHTML = '<span class="text-muted">Belum ada minat yang ditambahkan</span>';
                        }
                    });
                });
            } else {
                interestsDisplay.innerHTML = '<span class="text-muted">Belum ada minat yang ditambahkan</span>';
            }
        });
        
        // Add click event to existing interest tags
        document.querySelectorAll('.interest-tag i').forEach(icon => {
            icon.addEventListener('click', function() {
                const removedInterest = this.parentNode.textContent.trim().slice(0, -1).trim();
                const currentInterests = interestsInput.value.split(',').map(i => i.trim());
                const filteredInterests = currentInterests.filter(i => i !== removedInterest);
                interestsInput.value = filteredInterests.join(', ');
                this.parentNode.remove();
                
                if (interestsDisplay.children.length === 0) {
                    interestsDisplay.innerHTML = '<span class="text-muted">Belum ada minat yang ditambahkan</span>';
                }
            });
        });
    </script>
                        
                    <?php elseif ($page === 'menfess'): ?>
                        <div class="dashboard-header">
                            <h2>Crush Menfess</h2>
                            <p>Kirim pesan anonim ke crush Anda. Jika keduanya saling suka, nama akan terungkap!</p>
                        </div>
                        
                        <?php if (!empty($menfess_message)): ?>
                        <div class="alert <?php echo strpos($menfess_message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $menfess_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Kirim Menfess</h3>
                            </div>
                            <form method="post">
                                <div class="form-group">
                                    <label for="crush_id">Pilih Crush</label>
                                    <select id="crush_id" name="crush_id" required>
                                        <option value="">-- Pilih Crush --</option>
                                        <?php foreach ($users as $user_item): ?>
                                            <option value="<?php echo $user_item['id']; ?>"><?php echo htmlspecialchars($user_item['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="message">Pesan</label>
                                    <textarea id="message" name="message" placeholder="Tulis pesan anonim Anda..." required></textarea>
                                </div>
                                <button type="submit" name="send_menfess" class="btn">Kirim Menfess</button>
                            </form>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Menfess Anda</h3>
                            </div>
                            <div class="menfess-list">
                                <?php if (empty($menfess_messages)): ?>
                                    <p>Belum ada pesan menfess.</p>
                                <?php else: ?>
                                    <?php foreach ($menfess_messages as $message): ?>
                                        <div class="menfess-card <?php echo $message['type']; ?>">
                                            <div class="menfess-content">
                                                <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            </div>
                                            <div class="menfess-actions">
                                                <?php if ($message['type'] === 'received'): ?>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="menfess_id" value="<?php echo $message['id']; ?>">
                                                    <button type="submit" name="like_menfess" class="menfess-like" style="background: none; border: none; cursor: pointer;">
                                                        <i class="<?php echo $message['liked'] ? 'fas' : 'far'; ?> fa-heart"></i> 
                                                        <?php echo $message['liked'] ? 'Liked' : 'Like'; ?>
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                                <span class="menfess-time">
                                                    <?php echo date('d M Y H:i', strtotime($message['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    
                    <?php elseif ($page === 'chat'): ?>
                        <div class="dashboard-header">
                            <h2>Chat</h2>
                            <p>Chat dengan mahasiswa lain atau mulai blind chat.</p>
                        </div>
                        
                        <?php if (!empty($blind_chat_message)): ?>
                        <div class="alert <?php echo strpos($blind_chat_message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $blind_chat_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Blind Chat</h3>
                            </div>
                            <p>Mulai chat dengan mahasiswa acak tanpa melihat profil mereka terlebih dahulu.</p>
                            <form method="post" style="margin-top: 20px;">
                                <button type="submit" name="start_blind_chat" class="btn">Mulai Blind Chat</button>
                            </form>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Chat Aktif</h3>
                            </div>
                            <div class="chat-list">
                                <?php if (empty($chat_sessions)): ?>
                                    <p>Belum ada chat aktif.</p>
                                <?php else: ?>
                                    <?php foreach ($chat_sessions as $session): ?>
                                        <a href="chat.php?session_id=<?php echo $session['id']; ?>" class="chat-item">
                                            <div class="chat-avatar">
                                                <?php 
                                                // Check if blind chat and if user has permission
                                                $is_blind = $session['is_blind'];
                                                $partner_id = $session['partner_id'];
                                                $has_permission = false;
                                                
                                                if ($is_blind) {
                                                    // Check permission
                                                    $permission_sql = "SELECT * FROM profile_view_permissions 
                                                                    WHERE user_id = ? AND target_user_id = ?";
                                                    $permission_stmt = $conn->prepare($permission_sql);
                                                    $permission_stmt->bind_param("ii", $user_id, $partner_id);
                                                    $permission_stmt->execute();
                                                    $permission_result = $permission_stmt->get_result();
                                                    $has_permission = ($permission_result->num_rows > 0);
                                                }
                                                
                                                if (!$is_blind || $has_permission): 
                                                ?>
                                                    <img src="<?php echo !empty($session['profile_pic']) ? htmlspecialchars($session['profile_pic']) : 'assets/images/user_profile.png'; ?>" alt="Avatar">
                                                <?php else: ?>
                                                    <img src="assets/images/user_profile.png" alt="Anonymous">
                                                <?php endif; ?>
                                            </div>
                                            <div class="chat-info">
                                                <div class="chat-name">
                                                    <?php 
                                                    if ($is_blind && !$has_permission) {
                                                        echo 'Anonymous User';
                                                        echo '<i class="fas fa-lock lock-icon" title="Profil Terkunci"></i>';
                                                    } else {
                                                        echo htmlspecialchars($session['partner_name']);
                                                        if ($is_blind && $has_permission) {
                                                            echo '<i class="fas fa-unlock lock-icon" title="Profil Terbuka"></i>';
                                                        }
                                                    }
                                                    ?>
                                                    <?php if ($is_blind): ?>
                                                        <span style="font-size: 12px; color: var(--primary); text-decoration: none; margin-left: 5px;">
                                                            (Blind Chat)
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="chat-last-msg">Klik untuk melihat percakapan</div>
                                            </div>
                                            <div class="chat-time">
                                            <?php 
                                            if (isset($session['last_message_time']) && !empty($session['last_message_time'])) {
                                                echo date('d M', strtotime($session['last_message_time'])); 
                                            } else {
                                                echo 'Baru';
                                            }
                                            ?>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    
                   <?php elseif ($page === 'compatibility'): ?>
                        <div class="dashboard-header">
                            <h2>Tes Kecocokan</h2>
                            <p>Ikuti tes untuk menemukan pasangan yang cocok berdasarkan kepribadian, jurusan, dan minat.</p>
                        </div>
                        
                        <?php if (!empty($test_message)): ?>
                        <div class="alert <?php echo strpos($test_message, 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $test_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!$test_taken): ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>Tes Kecocokan</h3>
                            </div>
                            <p>Jawab pertanyaan berikut dengan jujur untuk mendapatkan hasil yang paling akurat.</p>
                            
                            <?php if (empty($questions)): ?>
                                <div class="alert alert-danger">
                                    Tidak ada pertanyaan kompatibilitas yang tersedia. Silakan hubungi admin.
                                </div>
                            <?php else: ?>
                            <form id="compatibility-form" method="post">
                                <?php foreach ($questions as $index => $question): ?>
                                <div class="question">
                                    <h4><?php echo ($index + 1) . '. ' . htmlspecialchars($question['question_text']); ?></h4>
                                    <div class="options">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <label class="option">
                                            <input type="radio" name="q_<?php echo $question['id']; ?>" value="<?php echo $i; ?>" required>
                                            <?php echo htmlspecialchars($question['option_' . $i]); ?>
                                        </label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                
                                <button type="submit" name="submit_test" class="btn">Lihat Hasil</button>
                            </form>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="card">
                            <div class="card-header">
                                <h3>Hasil Tes Kecocokan</h3>
                            </div>
                            <p>Berdasarkan jawaban dan profil Anda, kami telah menemukan orang-orang yang cocok dengan Anda.</p>
                            
                            <div class="score-details" style="display: flex; justify-content: space-between; padding: 10px 15px; background-color: var(--card-bg); border-radius: 5px; margin-bottom: 15px;">
                                <div class="score-item" style="text-align: center;">
                                    <div class="score-value" style="font-size: 18px; font-weight: 500; color: var(--primary);"><?php echo isset($test_results['personality_score']) ? round($test_results['personality_score']) : '0'; ?></div>
                                    <div class="score-label" style="font-size: 12px; color: #666;">Skor Kepribadian</div>
                                </div>
                                <div class="score-item" style="text-align: center;">
                                    <div class="score-value" style="font-size: 18px; font-weight: 500; color: var(--primary);"><?php echo isset($test_results['major']) && !empty($test_results['major']) ? htmlspecialchars($test_results['major']) : 'Tidak ada'; ?></div>
                                    <div class="score-label" style="font-size: 12px; color: #666;">Jurusan</div>
                                </div>
                                <div class="score-item" style="text-align: center;">
                                    <div class="score-value" style="font-size: 18px; font-weight: 500; color: var(--primary);"><?php echo count($compatible_matches); ?></div>
                                    <div class="score-label" style="font-size: 12px; color: #666;">Kecocokan Ditemukan</div>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3>Pasangan Yang Cocok</h3>
                                <a href="compatibility.php?reset=true" class="btn btn-outline">Ambil Tes Ulang</a>
                            </div>
                            
                            <?php if (empty($compatible_matches)): ?>
                            <div style="text-align: center; padding: 40px 0;">
                                <i class="fas fa-search" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                                <h3 style="font-size: 20px; margin-bottom: 10px; color: #666;">Belum Ada Kecocokan</h3>
                                <p style="color: #999; margin-bottom: 20px;">Kami belum menemukan kecocokan berdasarkan hasil tes Anda. Silakan coba lagi nanti.</p>
                            </div>
                            <?php else: ?>
                            <div class="user-grid">
                                <?php foreach ($compatible_matches as $match): ?>
                                <div class="user-card">
                                    <div class="user-card-img">
                                        <img src="<?php echo isset($match['profile_pic']) && !empty($match['profile_pic']) ? htmlspecialchars($match['profile_pic']) : 'assets/images/user_profile.png'; ?>" alt="<?php echo htmlspecialchars($match['name']); ?>">
                                    </div>
                                    <div class="user-card-info">
                                        <h3>
                                            <?php echo htmlspecialchars($match['name']); ?>
                                            <span style="float: right; background-color: var(--primary); color: white; padding: 3px 8px; border-radius: 15px; font-size: 14px;"><?php echo round($match['compatibility_score']); ?>%</span>
                                        </h3>
                                        <p style="margin-bottom: 10px; color: #666; font-size: 14px;"><?php echo isset($match['major']) && !empty($match['major']) ? htmlspecialchars($match['major']) : 'Jurusan tidak diketahui'; ?></p>
                                        <div class="user-card-bio">
                                            <?php echo isset($match['bio']) && !empty($match['bio']) ? nl2br(htmlspecialchars(substr($match['bio'], 0, 100) . (strlen($match['bio']) > 100 ? '...' : ''))) : 'Belum ada bio.'; ?>
                                        </div>
                                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                                            <a href="view_profile.php?id=<?php echo $match['id']; ?>" class="btn btn-outline" style="flex: 1;">Profil</a>
                                            <a href="start_chat.php?user_id=<?php echo $match['id']; ?>" class="btn" style="flex: 1;">Chat</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Tombol Reset Tes -->
                            <div style="margin-top: 30px; text-align: center;">
                                <a href="compatibility.php?reset=true" class="btn" style="background-color: #dc3545; color: white;">Reset Tes & Mulai Ulang</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    
                    <?php elseif ($page === 'matches'): ?>
                        <div class="dashboard-header">
                            <h2>Pasangan</h2>
                            <p>Lihat orang-orang yang cocok dengan Anda berdasarkan menfess mutual.</p>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Mutual Menfess</h3>
                            </div>
                            <div class="user-grid">
                                <?php if (empty($matches)): ?>
                                    <p>Belum ada mutual menfess. Kirim menfess dan like untuk menemukan pasangan!</p>
                                <?php else: ?>
                                    <?php foreach ($matches as $match): ?>
                                        <div class="user-card">
                                            <div class="user-card-img">
                                                <a href="view_profile.php?id=<?php echo $match['id']; ?>" style="display: block; cursor: pointer;">
                                                    <img src="<?php echo !empty($match['profile_pic']) ? htmlspecialchars($match['profile_pic']) : 'assets/images/user_profile.png'; ?>" alt="<?php echo htmlspecialchars($match['name']); ?>">
                                                </a>
                                            </div>
                                            <div class="user-card-info">
                                                <h3><?php echo htmlspecialchars($match['name']); ?></h3>
                                                <div class="user-card-bio">
                                                    <?php echo nl2br(htmlspecialchars(substr($match['bio'], 0, 100) . (strlen($match['bio']) > 100 ? '...' : ''))); ?>
                                                </div>
                                                <div style="display: flex; gap: 10px; margin-top: 15px;">
                                                    <a href="view_profile.php?id=<?php echo $match['id']; ?>" class="btn btn-outline" style="flex: 1;">Profil</a>
                                                    <a href="start_chat.php?user_id=<?php echo $match['id']; ?>" class="btn" style="flex: 1;">Chat</a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif ($page === 'payments'): ?>
                        <div class="dashboard-header">
                            <h2>Riwayat Pembayaran</h2>
                            <p>Lihat riwayat pembayaran dan transaksi Anda.</p>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Pembayaran Saya</h3>
                            </div>
                            
                            <?php
                            // Get user's payment history
                            $payments_sql = "SELECT prp.*, u.name as target_user_name 
                                            FROM profile_reveal_payments prp
                                            JOIN users u ON prp.target_user_id = u.id
                                            WHERE prp.user_id = ?
                                            ORDER BY prp.created_at DESC";
                            $payments_stmt = $conn->prepare($payments_sql);
                            $payments_stmt->bind_param("i", $user_id);
                            $payments_stmt->execute();
                            $payments_result = $payments_stmt->get_result();
                            $payments = [];
                            while ($row = $payments_result->fetch_assoc()) {
                                $payments[] = $row;
                            }
                            ?>
                            
                            <?php if (empty($payments)): ?>
                                <div class="empty-state" style="text-align: center; padding: 40px 0;">
                                    <i class="fas fa-receipt" style="font-size: 50px; color: #ccc; margin-bottom: 20px;"></i>
                                    <h3>Belum Ada Pembayaran</h3>
                                    <p>Anda belum melakukan pembayaran apapun.</p>
                                </div>
                            <?php else: ?>
                                <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                                    <thead>
                                        <tr>
                                            <th style="text-align: left; padding: 12px; border-bottom: 1px solid #eee;">Order ID</th>
                                            <th style="text-align: left; padding: 12px; border-bottom: 1px solid #eee;">Profil</th>
                                            <th style="text-align: left; padding: 12px; border-bottom: 1px solid #eee;">Jumlah</th>
                                            <th style="text-align: left; padding: 12px; border-bottom: 1px solid #eee;">Status</th>
                                            <th style="text-align: left; padding: 12px; border-bottom: 1px solid #eee;">Tanggal</th>
                                            <th style="text-align: left; padding: 12px; border-bottom: 1px solid #eee;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td style="padding: 12px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($payment['order_id']); ?></td>
                                            <td style="padding: 12px; border-bottom: 1px solid #eee;"><?php echo htmlspecialchars($payment['target_user_name']); ?></td>
                                            <td style="padding: 12px; border-bottom: 1px solid #eee;">Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                            <td style="padding: 12px; border-bottom: 1px solid #eee;">
                                                <span style="
                                                    display: inline-block;
                                                    padding: 4px 8px;
                                                    border-radius: 12px;
                                                    font-size: 12px;
                                                    <?php
                                                    switch ($payment['status']) {
                                                        case 'completed':
                                                            echo 'background-color: #d4edda; color: #155724;';
                                                            break;
                                                        case 'pending':
                                                            echo 'background-color: #fff3cd; color: #856404;';
                                                            break;
                                                        case 'failed':
                                                            echo 'background-color: #f8d7da; color: #721c24;';
                                                            break;
                                                        case 'refunded':
                                                            echo 'background-color: #d1ecf1; color: #0c5460;';
                                                            break;
                                                    }
                                                    ?>
                                                ">
                                                    <?php
                                                    switch ($payment['status']) {
                                                        case 'completed':
                                                            echo 'Selesai';
                                                            break;
                                                        case 'pending':
                                                            echo 'Menunggu';
                                                            break;
                                                        case 'failed':
                                                            echo 'Gagal';
                                                            break;
                                                        case 'refunded':
                                                            echo 'Dikembalikan';
                                                            break;
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td style="padding: 12px; border-bottom: 1px solid #eee;"><?php echo date('d M Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td style="padding: 12px; border-bottom: 1px solid #eee;">
                                                <?php if ($payment['status'] === 'completed'): ?>
                                                    <a href="view_profile.php?id=<?php echo $payment['target_user_id']; ?>" class="btn btn-sm" style="padding: 5px 10px; font-size: 12px;">Lihat Profil</a>
                                                <?php elseif ($payment['status'] === 'pending'): ?>
                                                    <a href="payment_process.php?order_id=<?php echo $payment['order_id']; ?>" class="btn btn-sm" style="padding: 5px 10px; font-size: 12px;">Bayar</a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="dashboard-header">
                            <h2><?php echo ucfirst($page); ?></h2>
                            <p>Halaman ini sedang dalam pengembangan.</p>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h3>Coming Soon</h3>
                            </div>
                            <p>Fitur ini akan segera tersedia. Silakan coba fitur lain yang sudah aktif.</p>
                            <a href="?page=dashboard" class="btn" style="margin-top: 15px;">Kembali ke Dashboard</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script>
        // JavaScript untuk interaktivitas
        document.addEventListener('DOMContentLoaded', function() {
            // Highlight active sidebar menu based on page parameter
            const currentPage = '<?php echo $page; ?>';
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                const linkPage = link.getAttribute('href').split('=')[1];
                if (linkPage === currentPage) {
                    link.classList.add('active');
                }
            });
            
            // Make radio options more user-friendly for compatibility test
            if (currentPage === 'compatibility') {
                document.querySelectorAll('.option').forEach(option => {
                    option.addEventListener('click', function() {
                        const radio = this.querySelector('input[type="radio"]');
                        radio.checked = true;
                        
                        // Update visual selection
                        const questionDiv = this.closest('.question');
                        questionDiv.querySelectorAll('.option').forEach(op => {
                            op.classList.remove('selected');
                        });
                        this.classList.add('selected');
                    });
                });
            }
        });
        
    // Function to toggle between light and dark themes
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        // Set theme on document
        document.documentElement.setAttribute('data-theme', newTheme);
        
        // Save theme preference to localStorage
        localStorage.setItem('cupid-theme', newTheme);
    }
    
    // Initialize theme based on saved preference
    function initTheme() {
        const savedTheme = localStorage.getItem('cupid-theme');
        if (savedTheme) {
            document.documentElement.setAttribute('data-theme', savedTheme);
        }
    }
    
    // Add event listener to theme toggle button
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize theme
        initTheme();
        
        // Add event listener to theme toggle button
        const themeToggleBtn = document.getElementById('theme-toggle-btn');
        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', toggleTheme);
        }
    });
    </script>
</body>
</html>