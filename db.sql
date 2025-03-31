CREATE DATABASE IF NOT EXISTS cupid_db;
USE cupid_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    interests TEXT,
    major VARCHAR(100),
    looking_for ENUM('friends', 'study_partner', 'romance') DEFAULT 'friends',
    profile_pic VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS menfess (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    message TEXT NOT NULL,
    is_anonymous BOOLEAN DEFAULT TRUE,
    is_revealed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS menfess_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    menfess_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (menfess_id) REFERENCES menfess(id) ON DELETE CASCADE,
    UNIQUE(user_id, menfess_id)
);

CREATE TABLE IF NOT EXISTS chat_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    is_blind BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user1_last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    user2_last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    sender_id INT NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS compatibility_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_text TEXT NOT NULL,
    option_1 VARCHAR(255) NOT NULL,
    option_2 VARCHAR(255) NOT NULL,
    option_3 VARCHAR(255) NOT NULL,
    option_4 VARCHAR(255) NOT NULL,
    option_5 VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS compatibility_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    personality_score DECIMAL(5,2),
    major VARCHAR(100),
    interests TEXT,
    answers JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO compatibility_questions (question_text, option_1, option_2, option_3, option_4, option_5) VALUES
('Bagaimana kamu menghabiskan waktu luang?', 'Membaca buku', 'Menonton film', 'Berolahraga', 'Bermain game', 'Menghabiskan waktu dengan teman'),
('Bagaimana kamu mengatasi stress?', 'Meditasi', 'Berolahraga', 'Tidur', 'Mengobrol dengan teman', 'Makan'),
('Apa yang kamu cari dalam hubungan?', 'Keamanan', 'Petualangan', 'Kesetiaan', 'Kebersamaan', 'Kemandirian'),
('Bagaimana kamu mengekspresikan kasih sayang?', 'Kata-kata manis', 'Sentuhan fisik', 'Memberikan hadiah', 'Melakukan sesuatu untuk orang lain', 'Menghabiskan waktu bersama'),
('Bagaimana pendapatmu tentang hubungan jarak jauh?', 'Sangat sulit', 'Bisa berhasil dengan usaha', 'Tergantung orangnya', 'Saya suka ruang pribadi', 'Tidak masalah bagi saya'),
('Seberapa penting kesamaan minat dalam hubungan?', 'Sangat penting', 'Penting', 'Netral', 'Tidak terlalu penting', 'Tidak penting sama sekali'),
('Seberapa penting aspek fisik dalam hubungan?', 'Sangat penting', 'Penting', 'Netral', 'Tidak terlalu penting', 'Tidak penting sama sekali'),
('Bagaimana kamu melihat peran pendidikan dalam hidup?', 'Sangat penting', 'Penting', 'Netral', 'Tidak terlalu penting', 'Tidak penting sama sekali'),
('Apa yang kamu harapkan dari partner dalam 5 tahun ke depan?', 'Sukses karir', 'Berkeluarga', 'Petualangan bersama', 'Stabilitas finansial', 'Belum tahu'),
('Apa pendapatmu tentang berbagi password sosial media?', 'Tidak masalah', 'Tergantung situasi', 'Hanya akun tertentu', 'Lebih baik tidak', 'Tidak setuju');

INSERT INTO users (name, email, password) VALUES
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'), -- password: password
('Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Alice Johnson', 'alice@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Bob Williams', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('Charlie Brown', 'charlie@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO profiles (user_id, bio, interests, major, looking_for, profile_pic) VALUES
(1, 'Saya mahasiswa Teknik Informatika yang senang coding dan bermain game.', 'Coding, Gaming, Hiking', 'Computer Science', 'study_partner', 'uploads/profiles/default.jpg'),
(2, 'Mahasiswa Kedokteran yang senang membaca novel dan menikmati kopi.', 'Reading, Coffee, Medical', 'Medicine', 'romance', 'uploads/profiles/default.jpg'),
(3, 'Suka seni dan desain. Sedang belajar fotografi.', 'Art, Design, Photography', 'Graphic Design', 'friends', 'uploads/profiles/default.jpg'),
(4, 'Suka olahraga terutama basket dan sepak bola.', 'Basketball, Football, Sports', 'Sports Science', 'study_partner', 'uploads/profiles/default.jpg'),
(5, 'Suka bermain musik. Bisa bermain gitar dan piano.', 'Music, Guitar, Piano', 'Music', 'romance', 'uploads/profiles/default.jpg');

INSERT INTO menfess (sender_id, receiver_id, message, is_anonymous, is_revealed) VALUES
(1, 2, 'Hai Jane, saya selalu melihatmu di perpustakaan. Kamu sangat cantik dan pintar.', 1, 0),
(2, 1, 'Halo John, saya suka caramu presentasi di kelas kemarin.', 1, 0),
(3, 4, 'Bob, kamu keren banget waktu main basket kemarin!', 1, 0),
(4, 3, 'Alice, desain postermu kemarin bagus banget. Aku suka gayamu.', 1, 0),
(5, 1, 'John, saya selalu kagum dengan koding kamu. Bisa ajarin aku kapan-kapan?', 1, 0);


CREATE TABLE profile_reveal_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    target_user_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE profile_view_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    target_user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (target_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, target_user_id)
);

ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN verification_token VARCHAR(64) NULL;

-- Script untuk Login dan Register

-- login.php
-- Untuk login gunakan:
-- $email = $_POST['email'];
-- $password = $_POST['password'];
-- $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
-- $stmt->bind_param("s", $email);
-- $stmt->execute();
-- $result = $stmt->get_result();
-- if ($result->num_rows === 1) {
--     $user = $result->fetch_assoc();
--     if (password_verify($password, $user['password'])) {
--         $_SESSION['user_id'] = $user['id'];
--         header('Location: dashboard.php');
--         exit();
--     }
-- }

-- register.php
-- Untuk register gunakan:
-- $name = $_POST['name'];
-- $email = $_POST['email'];
-- $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
-- $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
-- $stmt->bind_param("sss", $name, $email, $password);
-- if ($stmt->execute()) {
--     $_SESSION['user_id'] = $conn->insert_id;
--     header('Location: dashboard.php');
--     exit();
-- }