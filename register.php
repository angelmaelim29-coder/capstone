<?php
// register.php - Student Self-Registration with Password Toggle

session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$courses = $conn->query("SELECT * FROM courses WHERE is_active = 1 ORDER BY course_code");
$coursesData = [];
while ($c = $courses->fetch_assoc()) {
    $coursesData[] = $c;
}

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
$old = $_GET;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - TRENTIANS GradeHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            min-height: 100vh;
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
        
        .register-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            max-width: 1050px;
            width: 100%;
            display: flex;
            position: relative;
            z-index: 1;
            max-height: calc(100vh - 32px);
        }
        
        .register-branding {
            background: linear-gradient(160deg, #0a1e33 0%, #122f4d 50%, #1e4a70 100%);
            padding: 40px 30px;
            color: white;
            flex: 0.9;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            position: relative;
        }
        
        .register-branding::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, transparent, #f5b400, transparent);
        }
        
        .school-logo {
            width: 120px; height: 120px;
            margin-bottom: 20px;
            background: white;
            border-radius: 50%;
            padding: 10px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
        }
        
        .school-name {
            font-size: 16px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 6px;
        }
        
        .school-subtitle {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 22px;
        }
        
        .portal-badge {
            background: linear-gradient(135deg, #f5b400, #ffcc33);
            color: #0a1e33;
            padding: 9px 22px;
            border-radius: 30px;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 2px;
            box-shadow: 0 8px 20px rgba(245, 180, 0, 0.3);
        }
        
        .register-form-side {
            padding: 35px 40px;
            flex: 1.3;
            overflow-y: auto;
        }
        
        .register-title {
            color: #0a1e33;
            font-size: 24px;
            font-weight: 800;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        
        .register-subtitle {
            color: #64748b;
            font-size: 13px;
            margin-bottom: 22px;
        }
        
        .section-title {
            font-size: 10.5px;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin: 18px 0 10px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .form-label-custom {
            font-size: 10.5px;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 6px;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 13px;
            transition: all 0.2s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #1e4a70;
            box-shadow: 0 0 0 3px rgba(30, 74, 112, 0.08);
        }
        
        /* 🆕 Password field with eye icon */
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper .form-control {
            padding-right: 48px;
        }
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 16px;
            cursor: pointer;
            padding: 4px 8px;
            z-index: 10;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .toggle-password:hover { color: #1e4a70; }
        .toggle-password:focus { outline: none; }
        
        .info-box {
            background: #f0f9ff;
            border-left: 3px solid #1e4a70;
            border-radius: 8px;
            padding: 12px 14px;
            font-size: 12px;
            color: #0f2b46;
            margin-bottom: 15px;
        }
        
        .info-box i { color: #1e4a70; margin-right: 6px; }
        
        .btn-register {
            background: linear-gradient(135deg, #122f4d, #1e4a70);
            color: white;
            border: none;
            padding: 13px;
            border-radius: 11px;
            font-weight: 700;
            font-size: 13.5px;
            width: 100%;
            transition: all 0.3s;
            box-shadow: 0 8px 20px rgba(18, 47, 77, 0.25);
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(18, 47, 77, 0.35);
            color: white;
        }
        
        .login-link {
            text-align: center;
            margin-top: 18px;
            font-size: 12.5px;
            color: #64748b;
        }
        
        .login-link a {
            color: #1e4a70;
            font-weight: 700;
            text-decoration: none;
        }
        
        .login-link a:hover { color: #f5b400; }
        
        .alert-custom {
            border-radius: 11px;
            border: none;
            font-size: 12.5px;
            padding: 12px 14px;
            margin-bottom: 16px;
            font-weight: 500;
        }
        
        @media (max-width: 900px) {
            .register-container { flex-direction: column; max-width: 500px; }
            .register-branding { padding: 30px 20px; }
            .school-logo { width: 90px; height: 90px; margin-bottom: 15px; }
            .register-form-side { padding: 25px 22px; }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-branding">
        <img src="assets/images/logo.png" alt="TIFTCI Logo" class="school-logo" onerror="this.style.display='none'">
        <div class="school-name">Trent Information First<br>Technical Career Institute</div>
        <div class="school-subtitle">Est. 2007</div>
        <div class="portal-badge">TRENTIANS GRADEHUB</div>
    </div>
    
    <div class="register-form-side">
        <h2 class="register-title">Create Your Account</h2>
        <p class="register-subtitle">Register as a student to access your grades.</p>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-custom">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-custom">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="api/register_student.php">
            <!-- Personal Information -->
            <div class="section-title">Personal Information</div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label-custom">First Name *</label>
                    <input type="text" name="first_name" class="form-control" 
                           value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Last Name *</label>
                    <input type="text" name="last_name" class="form-control" 
                           value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="mt-2">
                <label class="form-label-custom">Email Address *</label>
                <input type="email" name="email" class="form-control" 
                       value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
            </div>
            
            <!-- Login Credentials -->
            <div class="section-title">Login Credentials</div>
            
            <div class="info-box">
                <i class="bi bi-info-circle-fill"></i>
                <strong>Important:</strong> Your <strong>Student Number</strong> will be your username when you log in.
            </div>
            
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label-custom">Student Number *</label>
                    <input type="text" name="student_number" class="form-control" 
                           placeholder="e.g., 2024-001"
                           value="<?php echo htmlspecialchars($old['student_number'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Year Level *</label>
                    <select name="year_level" class="form-select" required>
                        <option value="1" <?php echo (isset($old['year_level']) && $old['year_level'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                        <option value="2" <?php echo (isset($old['year_level']) && $old['year_level'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                        <option value="3" <?php echo (isset($old['year_level']) && $old['year_level'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                        <option value="4" <?php echo (isset($old['year_level']) && $old['year_level'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                    </select>
                </div>
            </div>
            
            <div class="row g-2 mt-2">
                <div class="col-md-6">
                    <label class="form-label-custom">Password *</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="passwordInput" class="form-control" 
                               minlength="6" required placeholder="Min. 6 characters">
                        <button type="button" class="toggle-password" onclick="togglePassword('passwordInput', 'eyeIcon1')" title="Show/Hide Password">
                            <i class="bi bi-eye-fill" id="eyeIcon1"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label-custom">Confirm Password *</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirmPasswordInput" class="form-control" 
                               minlength="6" required placeholder="Re-enter password">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirmPasswordInput', 'eyeIcon2')" title="Show/Hide Password">
                            <i class="bi bi-eye-fill" id="eyeIcon2"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Academic Info -->
            <div class="section-title">Academic Information</div>
            <div class="mb-2">
                <label class="form-label-custom">Course / Program *</label>
                <select name="course_id" class="form-select" required>
                    <option value="">Select Course...</option>
                    <?php foreach ($coursesData as $c): ?>
                        <option value="<?php echo $c['course_id']; ?>"
                                <?php echo (isset($old['course_id']) && $old['course_id'] == $c['course_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['course_code'] . ' - ' . $c['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-check mt-3">
                <input type="checkbox" class="form-check-input" id="terms" required>
                <label class="form-check-label" for="terms" style="font-size: 12px; color: #64748b;">
                    I confirm the information provided is correct and I agree to the terms of use.
                </label>
            </div>
            
            <button type="submit" class="btn-register mt-3">
                <i class="bi bi-person-plus me-2"></i>Create My Account
            </button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in here</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function togglePassword(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(iconId);
        
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