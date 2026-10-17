<?php
// login.php - Professional Login (without student hint box)

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    
    if ($role === 'Student') {
        header('Location: pages/student/dashboard.php');
        exit();
    } elseif ($role === 'Admin') {
        header('Location: index.php');
        exit();
    } elseif ($role === 'Faculty') {
        header('Location: pages/faculty/dashboard.php');
        exit();
    } elseif ($role === 'Registrar') {
        header('Location: pages/registrar/dashboard.php');
        exit();
    }
}

$error = '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($identifier) || empty($password)) {
        $error = 'Please enter your Student Number (or Username) and password.';
    } else {
        $sql = "SELECT u.*, r.role_name FROM users u 
                JOIN roles r ON u.role_id = r.role_id 
                LEFT JOIN students s ON s.user_id = u.user_id
                WHERE u.username = ? 
                   OR u.email = ? 
                   OR s.student_number = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $identifier, $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        if ($userData) {
            $md5_match = md5($password) === $userData['password_hash'];
            $bcrypt_match = password_verify($password, $userData['password_hash']);
            
            if ($md5_match || $bcrypt_match) {
                $accountStatus = $userData['account_status'] ?? 'Active';
                
                if (!$userData['is_active']) {
                    $error = 'Account is deactivated. Please contact the administrator.';
                } elseif ($accountStatus === 'Pending') {
                    $error = '⏳ Your account is pending admin approval. Please wait for verification.';
                } elseif ($accountStatus === 'Rejected') {
                    $error = '❌ Your registration was rejected. Please contact the administrator.';
                } else {
                    $_SESSION['user_id'] = $userData['user_id'];
                    $_SESSION['role'] = $userData['role_name'];
                    $_SESSION['username'] = $userData['username'];
                    $_SESSION['fullname'] = $userData['first_name'] . ' ' . $userData['last_name'];
                    
                    $role = $userData['role_name'];
                    
                    if ($role === 'Student') {
                        header('Location: pages/student/dashboard.php');
                        exit();
                    } elseif ($role === 'Admin') {
                        header('Location: index.php');
                        exit();
                    } elseif ($role === 'Faculty') {
                        header('Location: pages/faculty/dashboard.php');
                        exit();
                    } elseif ($role === 'Registrar') {
                        header('Location: pages/registrar/dashboard.php');
                        exit();
                    } else {
                        header('Location: index.php');
                        exit();
                    }
                }
            } else {
                $error = 'Invalid Student Number or password.';
            }
        } else {
            $error = 'Invalid Student Number or password.';
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TRENTIANS GradeHub - Sign In</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100vh;
            overflow: hidden;
            max-width: 100%;
        }
        
        :root {
            --navy-dark: #0a1e33;
            --navy: #122f4d;
            --navy-light: #1e4a70;
            --gold: #f5b400;
            --gold-light: #ffcc33;
            --text: #1e293b;
            --text-muted: #64748b;
            --text-light: #94a3b8;
            --border: #e2e8f0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif;
            background: radial-gradient(ellipse at top left, #1e4a70 0%, #0a1e33 60%, #050f1c 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -300px; right: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(245, 180, 0, 0.08), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        body::after {
            content: '';
            position: fixed;
            bottom: -300px; left: -200px;
            width: 600px; height: 600px;
            background: radial-gradient(circle, rgba(30, 74, 112, 0.4), transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.05);
            overflow: hidden;
            max-width: 950px;
            width: 100%;
            display: flex;
            position: relative;
            z-index: 1;
            height: calc(100vh - 32px);
            max-height: 600px;
        }
        
        /* Left Branding Panel */
        .login-branding {
            background: linear-gradient(160deg, #0a1e33 0%, #122f4d 50%, #1e4a70 100%);
            padding: 32px 30px;
            color: white;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-branding::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, transparent, var(--gold), transparent);
        }
        
        .school-logo {
            width: 120px; height: 120px;
            margin-bottom: 18px;
            background: white;
            border-radius: 50%;
            padding: 8px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .school-name {
            font-size: 15px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 4px;
            color: #ffffff;
            letter-spacing: -0.2px;
        }
        
        .school-subtitle {
            font-size: 10.5px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 18px;
        }
        
        .portal-badge {
            background: linear-gradient(135deg, var(--gold), var(--gold-light));
            color: var(--navy-dark);
            padding: 8px 22px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 2px;
            box-shadow: 0 8px 20px rgba(245, 180, 0, 0.3);
        }
        
        /* Right Form Panel */
        .login-form-side {
            padding: 32px 40px;
            flex: 1.15;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-title {
            color: var(--navy-dark);
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 4px;
            letter-spacing: -0.5px;
        }
        
        .login-subtitle {
            color: var(--text-muted);
            font-size: 12.5px;
            margin-bottom: 24px;
        }
        
        .form-label-custom {
            font-size: 10px;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 6px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .input-group-custom {
            position: relative;
            margin-bottom: 14px;
        }
        
        .input-group-custom .left-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            font-size: 14px;
            z-index: 5;
            pointer-events: none;
        }
        
        .input-group-custom input {
            width: 100%;
            padding: 12px 46px 12px 42px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 13px;
            transition: all 0.25s;
            background: #f8fafc;
            color: var(--text);
            font-weight: 500;
        }
        
        .input-group-custom input:focus {
            outline: none;
            border-color: var(--navy-light);
            background: white;
            box-shadow: 0 0 0 4px rgba(30, 74, 112, 0.08);
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: var(--text-light);
            font-size: 15px;
            cursor: pointer;
            padding: 4px 8px;
            z-index: 10;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-password:hover { color: var(--navy-light); }
        .toggle-password:focus { outline: none; }
        
        .btn-login {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-light) 100%);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.5px;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(18, 47, 77, 0.25);
            margin-top: 4px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(18, 47, 77, 0.35);
            color: white;
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            font-size: 12px;
            padding: 10px 13px;
            margin-bottom: 14px;
            font-weight: 500;
        }
        
        .register-link-box {
            text-align: center;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }
        
        .register-link-box span {
            font-size: 12px;
            color: var(--text-muted);
        }
        
        .register-link-box a {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: var(--navy-light);
            font-weight: 700;
            font-size: 12px;
            text-decoration: none;
            margin-left: 5px;
        }
        
        .register-link-box a:hover { color: var(--gold); }
        
        .demo-creds {
            background: #f8fafc;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            margin-top: 16px;
        }
        
        .demo-creds h6 {
            color: var(--text-muted);
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .demo-creds h6 i { color: var(--gold); }
        
        .demo-creds .cred-item {
            padding: 1.5px 0;
            color: var(--text);
            font-size: 10.5px;
        }
        
        .demo-creds code {
            background: #e2e8f0;
            padding: 1px 5px;
            border-radius: 3px;
            color: var(--navy);
            font-weight: 600;
            font-size: 10px;
        }
        
        .footer-text {
            text-align: center;
            margin-top: 14px;
            font-size: 9.5px;
            color: var(--text-light);
        }
        
        @media (max-width: 900px) {
            .login-container { 
                flex-direction: column; 
                max-width: 440px;
                height: auto;
                max-height: calc(100vh - 32px);
            }
            .login-branding { padding: 25px 20px; }
            .school-logo { width: 80px; height: 80px; margin-bottom: 12px; }
            .school-name { font-size: 13px; }
            .login-form-side { padding: 25px 25px; }
            .login-title { font-size: 20px; }
        }
    </style>
</head>
<body>

    <div class="login-container">
        <!-- Left Branding Panel -->
        <div class="login-branding">
            <img src="assets/images/logo.png" alt="TIFTCI Logo" class="school-logo" onerror="this.style.display='none'">
            <div class="school-name">Trent Information First<br>Technical Career Institute</div>
            <div class="school-subtitle">Est. 2007</div>
            <div class="portal-badge">TRENTIANS GRADEHUB</div>
        </div>
        
        <!-- Right Form Panel -->
        <div class="login-form-side">
            <h2 class="login-title">Welcome Back</h2>
            <p class="login-subtitle">Sign in to access your grade portal</p>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-custom">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-custom">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div>
                    <label class="form-label-custom">Student Number / Username</label>
                    <div class="input-group-custom">
                        <input type="text" name="username" required autocomplete="username" 
                               placeholder="e.g., 2024-001 or admin">
                        <i class="bi bi-person-badge-fill left-icon"></i>
                    </div>
                </div>
                
                <div>
                    <label class="form-label-custom">Password</label>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="passwordInput" required autocomplete="current-password" placeholder="Enter your password">
                        <i class="bi bi-lock-fill left-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePassword()" title="Show/Hide Password">
                            <i class="bi bi-eye-fill" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            
            <!-- Register Link -->
            <div class="register-link-box">
                <span>Don't have an account?</span>
                <a href="register.php">
                    <i class="bi bi-person-plus-fill"></i> Register as Student
                </a>
            </div>
            
            <!-- Demo Credentials -->
            <div class="demo-creds">
                <h6><i class="bi bi-info-circle-fill"></i>Demo Credentials</h6>
                <div class="row g-1">
                    <div class="col-6">
                        <div class="cred-item">Admin: <code>admin</code></div>
                        <div class="cred-item">Student: <code>2024-001</code></div>
                        <div class="cred-item">Faculty: <code>faculty1</code></div>
                    </div>
                    <div class="col-6">
                        <div class="cred-item">Pass: <code>password123</code></div>
                        <div class="cred-item">Pass: <code>password123</code></div>
                        <div class="cred-item">Pass: <code>password123</code></div>
                    </div>
                </div>
            </div>
            
            <div class="footer-text">
                © <?php echo date('Y'); ?> TIFTCI TRENTIANS GradeHub • All Rights Reserved
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye-fill');
                eyeIcon.classList.add('bi-eye-slash-fill');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash-fill');
                eyeIcon.classList.add('bi-eye-fill');
            }
        }
    </script>
</body>
</html>