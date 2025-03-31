<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cupid - Temukan Pasanganmu</title>
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
            padding: 10px 0;
            position: relative;
        }
        
        .logo {
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .logo img {
            height: 80px;
            max-height: 80px;
            width: auto;
            transition: height 0.3s ease;
        }
        
        nav ul {
            display: flex;
            list-style: none;
            gap: 15px;
            align-items: center;
        }
        
        nav ul li a {
            text-decoration: none;
            color: var(--dark);
            font-weight: 500;
            transition: color 0.3s;
            font-size: 16px;
            padding: 8px 12px;
        }
        
        nav ul li a:hover {
            color: var(--primary);
        }
        
        .menu-toggle {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 30px;
            height: 21px;
            cursor: pointer;
            margin-left: auto; /* Push to the right */
        }
        
        .menu-toggle span {
            height: 3px;
            width: 100%;
            background-color: var(--dark);
            border-radius: 3px;
            transition: all 0.3s ease;
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
            transition: all 0.3s;
            font-size: 16px;
            text-align: center;
        }
        
        .btn:hover {
            background-color: #e63e5c;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
        
        .hero {
            min-height: 80vh;
            display: flex;
            align-items: center;
            padding: 120px 0 60px;  /* Increased top padding to accommodate larger logo */
            background: linear-gradient(135deg, var(--secondary) 0%, #fff1f3 100%);
        }
        
        .hero-content {
            width: 100%;
            max-width: 700px;
            margin: 0 auto;
            text-align: left;
            padding: 0 20px;
        }
        
        .hero-image {
            position: absolute;
            right: 5%;
            top: 50%;
            transform: translateY(-50%);
            z-index: 0;
            opacity: 0.3;
            display: block;
        }
        
        @media (max-width: 991px) {
            .hero-image {
                display: none;
            }
        }
        
        .hero-content h1 {
            display: block;
            line-height: 1.3;
        }
        
        .hero-title {
            margin-bottom: 20px;
        }
        
        .hero-title h1 {
            margin-bottom: 5px;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .hero-logo {
            height: 60px;
            max-width: 150px;
            object-fit: contain;
        }
        
        h1 span {
            color: var(--primary);
        }
        
        p {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #666;
        }
        
        .features {
            padding: 100px 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-header h2 {
            font-size: 36px;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .section-header p {
            font-size: 18px;
            color: #666;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: var(--light);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            text-align: center;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            font-size: 50px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 15px;
            color: var(--dark);
        }
        
        .feature-card p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
            flex-grow: 1;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        
        .modal-content {
            background-color: var(--light);
            padding: 30px;
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .close-btn {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #666;
            z-index: 10;
        }
        
        .modal h2 {
            margin-bottom: 20px;
            font-size: 24px;
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
        
        footer {
            background-color: var(--dark);
            color: var(--light);
            padding: 60px 0 30px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .footer-logo {
            font-size: 24px;
            font-weight: bold;
            color: var(--light);
            margin-bottom: 15px;
            display: block;
        }
        
        .footer-about p {
            color: #aaa;
            margin-bottom: 20px;
        }
        
        .footer-heading {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--light);
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 10px;
        }
        
        .footer-links a {
            color: #aaa;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-links a:hover {
            color: var(--primary);
        }
        
        .footer-bottom {
            border-top: 1px solid #444;
            padding-top: 30px;
            text-align: center;
            font-size: 14px;
            color: #aaa;
        }
        
        /* Media Queries */
        @media (max-width: 1024px) {
            h1 {
                font-size: 2.5rem;
            }
            
            .hero-content h1 img {
                height: 60px;
            }
        }
        
        @media (max-width: 991px) {
            .hero {
                padding: 120px 0 50px;
            }
            
            .footer-content {
                grid-template-columns: 1fr 1fr;
                row-gap: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .logo img {
                height: 60px;
                max-height: 60px;
            }
            
            .logo-container {
                font-size: 1.8rem;
                justify-content: center;
                margin-top: 10px;
            }
            
            .hero-logo {
                height: 50px;
            }
            
            .hero-title, .hero-content {
                text-align: center;
            }
            
            /* Mobile menu styles - RIGHT SIDE */
            .menu-toggle {
                display: flex;
                z-index: 120;
                position: absolute;
                right: 0;
            }
            
            nav {
                position: fixed;
                top: 0;
                right: -100%;
                width: 80%;
                max-width: 300px;
                height: 100vh;
                background-color: var(--light);
                box-shadow: -5px 0 15px rgba(0, 0, 0, 0.1);
                transition: right 0.3s ease;
                z-index: 110;
                padding: 80px 20px 20px;
            }
            
            nav.active {
                right: 0;
            }
            
            nav ul {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            nav ul li {
                width: 100%;
            }
            
            nav ul li a, nav ul li .btn {
                display: block;
                width: 100%;
                padding: 12px 15px;
            }
            
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 100;
            }
            
            .overlay.active {
                display: block;
            }
            
            .close-menu {
                position: absolute;
                top: 20px;
                right: 20px;
                font-size: 28px;
                cursor: pointer;
                display: block;
            }
        }
        @media (min-width: 769px) {
            /* Desktop styles - No hamburger */
            .menu-toggle {
                display: none;
            }
            
            nav {
                display: block;
                position: static;
                width: auto;
                height: auto;
                background-color: transparent;
                box-shadow: none;
                padding: 0;
            }
            
            nav ul {
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 20px;
            }
            
            .close-menu {
                display: none;
            }
            
            .overlay {
                display: none;
            }
        }
            
            .section-header h2 {
                font-size: 28px;
            }
        
        @media (max-width: 576px) {
            .logo img {
                height: 50px;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .logo-container {
                font-size: 1.5rem;
                flex-direction: column;
                gap: 5px;
            }
            
            .hero-logo {
                height: 45px;
            }
            
            .footer-content {
                grid-template-columns: 1fr;
            }
            
            .hero {
                padding-top: 100px;
            }
            
            .feature-card {
                padding: 20px;
            }
            
            .feature-icon {
                font-size: 40px;
            }
            
            .feature-card h3 {
                font-size: 20px;
            }
            
            .section-header h2 {
                font-size: 24px;
            }
            
            .modal-content {
                padding: 20px;
            }
        }
         /*Overlay for mobile menu*/
             @media (max-width: 480px) {
            .logo-container {
                display: block;
                margin-top: 15px;
            }
            .logo-container span {
                display: none;
            }
            
            .hero-logo {
                height: 50px;
                margin-top: 5px;
            }
        }        h1 {
            font-size: 2.5rem;
            color: var(--dark);
            line-height: 1.3;
            margin: 0;
        }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="#" class="logo">
                    <img src="../assets/images/cupid_nobg.png" alt="Cupid" id="logo-img" style="height: 80px;">
                </a>
                <div class="menu-toggle" id="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
                <nav id="nav">
                    <div class="close-menu" id="close-menu">&times;</div>
                    <ul>
                        <li><a href="#features">Fitur</a></li>
                        <li><a href="#" id="login-btn">Masuk</a></li>
                        <li><a href="#" id="register-btn" class="btn">Daftar</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Temukan <span>Pasanganmu</span> Di <img src="../assets/images/cupid_nobg.png" alt="Cupid" style="height: 160px; vertical-align: middle; display: inline-block;"></h1>
                <p>Platform dimana kamu dapat menemukan pasangan yang cocok berdasarkan ketertarikan, hobi, dan tujuan yang sama. Apakah kamu mencari teman, partner belajar, atau romansa, Cupid membantu kamu terhubung dengan orang yang tepat.</p>
                <a href="#" class="btn" id="get-started-btn">Mulai Sekarang</a>
            </div>
            <div class="hero-image">
                    <!--<img src="../assets/images/cupid_nobg.png" alt="Cupid Dating App Preview" style="max-width: 200px; width: 100%; opacity: 0.2;">-->
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header">
                <h2>Fitur Utama</h2>
                <p>Cupid menawarkan berbagai fitur menarik untuk membantu kamu menemukan pasangan yang cocok.</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <h3>Profile Creation</h3>
                    <p>Buat profil dengan minat, hobi, dan apa yang kamu cari (teman, partner belajar, atau romansa).</p>
                    <a href="#" class="btn btn-outline">Buat Profil</a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mask"></i>
                    </div>
                    <h3>Anonymous Crush Menfess</h3>
                    <p>Kirim pesan anonim ke crush kamu. Jika keduanya saling suka, nama akan terungkap!</p>
                    <a href="#" class="btn btn-outline">Kirim Menfess</a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Blind Chat</h3>
                    <p>Chat dengan mahasiswa acak tanpa melihat profil mereka terlebih dahulu.</p>
                    <a href="#" class="btn btn-outline">Mulai Chat</a>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <h3>Compatibility Test</h3>
                    <p>Kuis untuk mencocokkan mahasiswa berdasarkan kepribadian, jurusan, dan minat.</p>
                    <a href="#" class="btn btn-outline">Ikuti Tes</a>
                </div>
            </div>
        </div>
    </section>

   <!-- Login Modal (NO LONGER USED) -->
    <div class="modal" id="login-modal">
        <div class="modal-content">
            <span class="close-btn" id="close-login">&times;</span>
            <h2>Masuk ke Cupid</h2>
            <form id="login-form" action="login.php" method="post">
                <div class="form-group">
                    <label for="login-email">Email</label>
                    <input type="email" id="login-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Password</label>
                    <input type="password" id="login-password" name="password" required>
                </div>
                <button type="submit" class="btn">Masuk</button>
                <p style="text-align: center; margin-top: 20px;">
                    Belum punya akun? <a href="register.php" id="switch-to-register">Daftar</a>
                </p>
            </form>
        </div>
    </div>

    <!-- Register Modal (NO LONGER USED) -->
    <div class="modal" id="register-modal">
        <div class="modal-content">
            <span class="close-btn" id="close-register">&times;</span>
            <h2>Daftar di Cupid</h2>
            <form id="register-form" action="register.php" method="post">
                <div class="form-group">
                    <label for="register-name">Nama Lengkap</label>
                    <input type="text" id="register-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="register-email">Email</label>
                    <input type="email" id="register-email" name="email" required placeholder="email@student.president.ac.id">
                    <small style="color: #666; display: block; margin-top: 5px;">Gunakan email dengan domain student.president.ac.id</small>
                </div>
                <div class="form-group">
                    <label for="register-password">Password</label>
                    <input type="password" id="register-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="register-confirm">Konfirmasi Password</label>
                    <input type="password" id="register-confirm" name="confirm_password" required>
                </div>
                <button type="submit" class="btn">Daftar</button>
                <p style="text-align: center; margin-top: 20px;">
                    Sudah punya akun? <a href="login.php" id="switch-to-login">Masuk</a>
                </p>
            </form>
        </div>
    </div>
    
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-about">
                    <a href="#" class="footer-logo">Cupid</a>
                    <p>Platform untuk menemukan pasangan yang cocok berdasarkan minat, hobi, dan tujuan yang sama.</p>
                </div>
                <div class="footer-links-section">
                    <h3 class="footer-heading">Fitur</h3>
                    <ul class="footer-links">
                        <li><a href="#">Profile Creation</a></li>
                        <li><a href="#">Anonymous Crush Menfess</a></li>
                        <li><a href="#">Blind Chat</a></li>
                        <li><a href="#">Compatibility Test</a></li>
                    </ul>
                </div>
                <div class="footer-links-section">
                    <h3 class="footer-heading">Perusahaan</h3>
                    <ul class="footer-links">
                        <li><a href="#">Tentang Kami</a></li>
                        <li><a href="#">Kontak</a></li>
                        <li><a href="#">Karir</a></li>
                        <li><a href="#">Blog</a></li>
                    </ul>
                </div>
                <div class="footer-links-section">
                    <h3 class="footer-heading">Bantuan</h3>
                    <ul class="footer-links">
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">Kebijakan Privasi</a></li>
                        <li><a href="#">Syarat & Ketentuan</a></li>
                        <li><a href="#">Dukungan</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Cupid. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu functionality
        const menuToggle = document.getElementById('menu-toggle');
        const nav = document.getElementById('nav');
        const overlay = document.getElementById('overlay');
        const closeMenu = document.getElementById('close-menu');
        
        menuToggle.addEventListener('click', function() {
            nav.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        });
        
        function closeNavMenu() {
            nav.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
        
        closeMenu.addEventListener('click', closeNavMenu);
        overlay.addEventListener('click', closeNavMenu);
        
        // Close menu when clicking on a nav link
        const navLinks = document.querySelectorAll('nav ul li a');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    closeNavMenu();
                }
            });
        });

        // Modal functionality
        const loginBtn = document.getElementById('login-btn');
        const registerBtn = document.getElementById('register-btn');
        const getStartedBtn = document.getElementById('get-started-btn');
        const loginModal = document.getElementById('login-modal');
        const registerModal = document.getElementById('register-modal');
        const closeLogin = document.getElementById('close-login');
        const closeRegister = document.getElementById('close-register');
        const switchToRegister = document.getElementById('switch-to-register');
        const switchToLogin = document.getElementById('switch-to-login');

        // Open login modal
        loginBtn.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        // Open register modal
        registerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        // Get started button
        getStartedBtn.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        });

        // Close login modal
        closeLogin.addEventListener('click', function() {
            loginModal.style.display = 'none';
            document.body.style.overflow = '';
        });

        // Close register modal
        closeRegister.addEventListener('click', function() {
            registerModal.style.display = 'none';
            document.body.style.overflow = '';
        });

        // Switch to register form
        switchToRegister.addEventListener('click', function(e) {
            e.preventDefault();
            loginModal.style.display = 'none';
            registerModal.style.display = 'flex';
        });

        // Switch to login form
        switchToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            registerModal.style.display = 'none';
            loginModal.style.display = 'flex';
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target == loginModal) {
                loginModal.style.display = 'none';
                document.body.style.overflow = '';
            }
            if (e.target == registerModal) {
                registerModal.style.display = 'none';
                document.body.style.overflow = '';
            }
        });

        // Form submissions
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');

        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('login-email').value;
            const password = document.getElementById('login-password').value;
            
            // Here you would normally send an AJAX request to your PHP backend
            console.log('Login attempt:', email, password);
            
            // Simulate successful login
            alert('Login successful! Redirecting to dashboard...');
            window.location.href = 'dashboard.php';
        });

        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const name = document.getElementById('register-name').value;
            const email = document.getElementById('register-email').value;
            const password = document.getElementById('register-password').value;
            const confirm = document.getElementById('register-confirm').value;
            
            if (password !== confirm) {
                alert('Passwords do not match!');
                return;
            }
            
            // Here you would normally send an AJAX request to your PHP backend
            console.log('Register attempt:', name, email, password);
            
            // Simulate successful registration
            alert('Registration successful! Please check your email to verify your account.');
            registerModal.style.display = 'none';
            document.body.style.overflow = '';
        });
        
        // Responsive adjustments
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && nav.classList.contains('active')) {
                closeNavMenu();
            }
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.getAttribute('href') !== '#' && this.getAttribute('id') !== 'login-btn' && 
                    this.getAttribute('id') !== 'register-btn' && this.getAttribute('id') !== 'get-started-btn' &&
                    this.getAttribute('id') !== 'switch-to-register' && this.getAttribute('id') !== 'switch-to-login') {
                    e.preventDefault();
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        window.scrollTo({
                            top: targetElement.offsetTop - 80,
                            behavior: 'smooth'
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>