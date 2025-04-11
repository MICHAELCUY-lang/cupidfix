<?php
// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: cupid.php');
    exit();
}

// Check if session_id is provided
if (!isset($_GET['session_id'])) {
    header('Location: dashboard.php?page=chat');
    exit();
}

$session_id = $_GET['session_id'];

// Database connection
$servername = "localhost";
$username = "u287442801_cupid";
$password = "Cupid1234!";
$dbname = "u287442801_cupid";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get chat session data with approval status
$session_sql = "SELECT cs.*, 
                u1.name as user1_name, 
                u2.name as user2_name,
                CASE WHEN cs.user1_id = ? THEN u2.name ELSE u1.name END as partner_name,
                CASE WHEN cs.user1_id = ? THEN u2.id ELSE u1.id END as partner_id,
                p.profile_pic,
                cs.user1_approved, cs.user2_approved, cs.is_approved
                FROM chat_sessions cs
                JOIN users u1 ON cs.user1_id = u1.id
                JOIN users u2 ON cs.user2_id = u2.id
                LEFT JOIN profiles p ON (CASE WHEN cs.user1_id = ? THEN u2.id ELSE u1.id END) = p.user_id
                WHERE cs.id = ? AND (cs.user1_id = ? OR cs.user2_id = ?)";
$session_stmt = $conn->prepare($session_sql);
$session_stmt->bind_param("iiiiii", $user_id, $user_id, $user_id, $session_id, $user_id, $user_id);
$session_stmt->execute();
$session_result = $session_stmt->get_result();

if ($session_result->num_rows === 0) {
    header('Location: dashboard.php?page=chat');
    exit();
}

$chat_session = $session_result->fetch_assoc();
$partner_id = $chat_session['partner_id'];
$is_blind = $chat_session['is_blind'];

// Tentukan status persetujuan
$user_approved = false;
$partner_approved = false;

if ($chat_session['user1_id'] == $user_id) {
    $user_approved = $chat_session['user1_approved'] == 1;
    $partner_approved = $chat_session['user2_approved'] == 1;
} else {
    $user_approved = $chat_session['user2_approved'] == 1;
    $partner_approved = $chat_session['user1_approved'] == 1;
}

$both_approved = $user_approved && $partner_approved;

// Proses persetujuan chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_chat'])) {
    // Tentukan field mana yang akan diupdate berdasarkan user
    if ($chat_session['user1_id'] == $user_id) {
        $update_field = 'user1_approved';
    } else {
        $update_field = 'user2_approved';
    }
    
    // Update status persetujuan
    $approve_sql = "UPDATE chat_sessions SET $update_field = 1 WHERE id = ?";
    $approve_stmt = $conn->prepare($approve_sql);
    $approve_stmt->bind_param("i", $session_id);
    
    if ($approve_stmt->execute()) {
        // Periksa apakah kedua user sudah menyetujui
        $check_both_sql = "SELECT user1_approved, user2_approved FROM chat_sessions WHERE id = ?";
        $check_both_stmt = $conn->prepare($check_both_sql);
        $check_both_stmt->bind_param("i", $session_id);
        $check_both_stmt->execute();
        $approval_result = $check_both_stmt->get_result()->fetch_assoc();
        
        if ($approval_result['user1_approved'] == 1 && $approval_result['user2_approved'] == 1) {
            // Kedua user sudah menyetujui, update is_approved
            $update_approved_sql = "UPDATE chat_sessions SET is_approved = 1 WHERE id = ?";
            $update_approved_stmt = $conn->prepare($update_approved_sql);
            $update_approved_stmt->bind_param("i", $session_id);
            $update_approved_stmt->execute();
        }
        
        // Refresh halaman
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Handle new message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    // Jika blind chat, pastikan keduanya sudah setuju
    if ($is_blind && !$both_approved) {
        $error_message = "Kedua pengguna harus menyetujui chat ini sebelum dapat bertukar pesan.";
    } else {
        $message = $_POST['message'];
        
        $insert_sql = "INSERT INTO chat_messages (session_id, sender_id, message) VALUES (?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("iis", $session_id, $user_id, $message);
        
        if ($insert_stmt->execute()) {
            // Success
        } else {
            $error_message = "Error sending message: " . $conn->error;
        }
    }
}

// Get chat messages
$messages_sql = "SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC";
$messages_stmt = $conn->prepare($messages_sql);
$messages_stmt->bind_param("i", $session_id);
$messages_stmt->execute();
$messages_result = $messages_stmt->get_result();
$messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $messages[] = $row;
}

// Update last seen
$update_seen_sql = "UPDATE chat_sessions SET 
                    user1_last_seen = CASE WHEN user1_id = ? THEN NOW() ELSE user1_last_seen END,
                    user2_last_seen = CASE WHEN user2_id = ? THEN NOW() ELSE user2_last_seen END
                    WHERE id = ?";
$update_seen_stmt = $conn->prepare($update_seen_sql);
$update_seen_stmt->bind_param("iii", $user_id, $user_id, $session_id);
$update_seen_stmt->execute();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - Cupid</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
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
        
        .chat-container {
            padding-top: 100px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background-color: var(--light);
            border-radius: 10px 10px 0 0;
            padding: 20px;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 80px;
            z-index: 1;
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
        
        .chat-avatar a {
            display: block;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        
        .chat-avatar a:hover img {
            opacity: 0.9;
            transform: scale(1.03);
            transition: all 0.3s ease;
        }
        
        .chat-profile {
            flex: 1;
        }
        
        .chat-profile h2 {
            font-size: 20px;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
        }
        
        .chat-profile p {
            font-size: 14px;
            color: #666;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .chat-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chat-status {
            font-size: 12px;
            padding: 3px 8px;
            border-radius: 10px;
            margin-left: 8px;
        }
        
        .status-anonymous {
            background-color: var(--secondary);
            color: var(--primary);
        }
        
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        
        .chat-messages {
            background-color: var(--light);
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            max-height: calc(100vh - 300px);
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
        }
        
        .message {
            display: flex;
            margin-bottom: 20px;
        }
        
        .message.sent {
            justify-content: flex-end;
        }
        
        .message-content {
            max-width: 70%;
            padding: 15px;
            border-radius: 10px;
            position: relative;
        }
        
        .sent .message-content {
            background-color: var(--primary);
            color: var(--light);
            border-top-right-radius: 0;
        }
        
        .received .message-content {
            background-color: #f0f0f0;
            border-top-left-radius: 0;
        }
        
        .message-text {
            margin-bottom: 5px;
        }
        
        .message-time {
            font-size: 12px;
            opacity: 0.8;
            text-align: right;
        }
        
        .chat-input {
            background-color: var(--light);
            padding: 20px;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.05);
            border-top: 1px solid #eee;
        }
        
        .chat-form {
            display: flex;
            gap: 10px;
        }
        
        .chat-form input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .chat-form button {
            padding: 12px 20px;
            background-color: var(--primary);
            color: var(--light);
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .blind-chat-notice {
            background-color: var(--secondary);
            color: var(--primary);
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .inline-form {
            display: inline;
        }
        
        .approved-badge {
            color: #0caa0c;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            margin-left: 5px;
        }
        
        .approval-required-message {
            text-align: center;
            padding: 40px 0;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #666;
        }
        
        .approval-required-message i {
            font-size: 40px;
            color: var(--primary);
            margin-bottom: 15px;
            opacity: 0.6;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        @media (max-width: 767px) {
            .message-content {
                max-width: 85%;
            }
            
            .chat-header {
                flex-wrap: wrap;
            }
            
            .chat-actions {
                margin-top: 10px;
                width: 100%;
                justify-content: flex-end;
            }
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
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li>
                            <a href="dashboard.php?page=chat" class="btn btn-outline">Kembali ke Chat</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Chat Section -->
    <section class="chat-container">
        <div class="container">
            <!-- Chat Header -->
            <div class="chat-header">
                <div class="chat-avatar">
                    <?php if (!$is_blind || $both_approved): ?>
                    <a href="<?php echo (!$is_blind) ? 'view_profile.php?id=' . $partner_id : '#'; ?>" style="display: block; cursor: pointer;">
                        <img src="<?php echo !empty($chat_session['profile_pic']) ? htmlspecialchars($chat_session['profile_pic']) : '../assets/images/user_profile.png'; ?>" alt="<?php echo htmlspecialchars($chat_session['partner_name']); ?>">
                    </a>
                    <?php else: ?>
                    <img src="../assets/images/user_profile.png" alt="Anonymous User">
                    <?php endif; ?>
                </div>
                <div class="chat-profile">
                    <h2>
                        <?php if ($is_blind && !$both_approved): ?>
                            Anonymous User
                            <span class="chat-status status-anonymous">Anonymous</span>
                        <?php else: ?>
                            <?php echo htmlspecialchars($chat_session['partner_name']); ?>
                            <?php if ($is_blind && $both_approved): ?>
                                <span class="chat-status status-approved">Disetujui</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </h2>
                    <?php if ($is_blind): ?>
                        <p>
                            <i class="fas fa-mask"></i> 
                            Anonymous Chat
                            <?php if ($user_approved): ?>
                                <span class="approved-badge"><i class="fas fa-check-circle"></i> Disetujui</span>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="chat-actions">
                    <?php if ($is_blind && !$both_approved): ?>
                        <!-- Tombol approve untuk blind chat -->
                        <form method="post" class="inline-form">
                            <?php if (!$user_approved): ?>
                                <button type="submit" name="approve_chat" class="btn btn-sm" title="Setujui Chat">
                                    <i class="fas fa-check"></i> Setujui Chat
                                </button>
                            <?php endif; ?>
                        </form>
                    <?php elseif ($is_blind && $both_approved): ?>
                        <!-- Tombol lihat profil berbayar -->
                        <a href="create_profile_payment.php?chat_id=<?php echo $session_id; ?>&partner_id=<?php echo $partner_id; ?>" class="btn btn-sm" title="Lihat Profil">
                            <i class="fas fa-eye"></i> Lihat Profil (Rp15.000)
                        </a>
                    <?php elseif (!$is_blind): ?>
                        <!-- Chat biasa bisa langsung lihat profil -->
                        <a href="view_profile.php?id=<?php echo $partner_id; ?>" title="Lihat Profil" class="btn btn-sm">
                            <i class="fas fa-user"></i> Profil
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($is_blind && !$both_approved): ?>
                <div class="blind-chat-notice">
                    <i class="fas fa-info-circle"></i> 
                    <?php if ($partner_approved && !$user_approved): ?>
                        Lawan bicara telah menyetujui chat ini. Silakan klik tombol 'Setujui Chat' untuk mulai berkomunikasi.
                    <?php elseif ($user_approved && !$partner_approved): ?>
                        Anda telah menyetujui chat ini. Menunggu lawan bicara untuk menyetujui.
                    <?php else: ?>
                        Ini adalah blind chat. Kedua pengguna harus menyetujui untuk dapat saling berkomunikasi.
                    <?php endif; ?>
                </div>
                
                <!-- Jika belum disetujui keduanya, batasi interaksi -->
                <div class="approval-required-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Kedua pengguna harus menyetujui chat ini sebelum dapat bertukar pesan.</p>
                </div>
            <?php else: ?>
                <!-- Tampilkan chat jika sudah disetujui keduanya atau bukan blind chat -->
                <div class="chat-messages" id="chat-messages">
                    <?php if (empty($messages)): ?>
                        <div style="text-align: center; padding: 20px; color: #999;">
                            <p>Belum ada pesan. Mulai percakapan sekarang!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                            <div class="message <?php echo $message['sender_id'] === $user_id ? 'sent' : 'received'; ?>">
                                <div class="message-content">
                                    <div class="message-text">
                                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                    </div>
                                    <div class="message-time">
                                        <?php echo date('H:i', strtotime($message['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input">
                    <form method="post" class="chat-form">
                        <input type="text" name="message" placeholder="Ketik pesan..." required autofocus>
                        <button type="submit" name="send_message"><i class="fas fa-paper-plane"></i></button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Auto scroll to bottom of chat
        const chatMessages = document.getElementById('chat-messages');
        if (chatMessages) {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
    </script>
</body>
</html>