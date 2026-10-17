<?php
// index.php - Admin Dashboard with professional modals

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'trentians_gradehub';

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$result = $conn->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = " . $_SESSION['user_id']);
$userData = $result->fetch_assoc();
$userAvatar = !empty($userData['profile_picture']) ? 'assets/images/profiles/' . $userData['profile_picture'] : null;
if (!$userData) { die("User not found."); }

$role = $_SESSION['role'];
$fullname = $_SESSION['fullname'];

if ($role !== 'Admin') {
    header('Location: login.php');
    exit();
}

$userCount = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
$studentCount = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$facultyCount = $conn->query("SELECT COUNT(*) as count FROM faculty")->fetch_assoc()['count'];
$courseCount = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$subjectCount = $conn->query("SELECT COUNT(*) as count FROM subjects")->fetch_assoc()['count'];
$sectionCount = $conn->query("SELECT COUNT(*) as count FROM sections")->fetch_assoc()['count'];
$pendingApprovals = $conn->query("SELECT COUNT(*) as count FROM users WHERE account_status = 'Pending'")->fetch_assoc()['count'];

$users = $conn->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id ORDER BY u.user_id");
$students = $conn->query("SELECT s.*, u.first_name, u.last_name, u.email, c.course_code FROM students s JOIN users u ON s.user_id = u.user_id LEFT JOIN courses c ON s.course_id = c.course_id ORDER BY s.student_id");
$faculty = $conn->query("SELECT f.*, u.first_name, u.last_name, u.email FROM faculty f JOIN users u ON f.user_id = u.user_id ORDER BY f.faculty_id");
$courses = $conn->query("SELECT * FROM courses ORDER BY course_code");
$subjects = $conn->query("SELECT s.*, c.course_code FROM subjects s LEFT JOIN courses c ON s.course_id = c.course_id ORDER BY s.subject_code");
$sections = $conn->query("SELECT sec.*, c.course_code FROM sections sec LEFT JOIN courses c ON sec.course_id = c.course_id ORDER BY sec.section_code");
$roles = $conn->query("SELECT * FROM roles");

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TRENTIANS GradeHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/modal-toast.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        html, body {
            height: 100vh;
            overflow: hidden;
            max-width: 100%;
        }
        
        :root {
            --navy-dark: #0f2b46;
            --navy: #1b3a5c;
            --navy-light: #2d5a7a;
            --gold: #ffc107;
            --bg: #f8fafc;
            --border: #e2e8f0;
            --text: #1e293b;
            --text-muted: #64748b;
            --text-light: #94a3b8;
        }
        
        body { 
            background: var(--bg); 
            font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif; 
            font-size: 14px;
            color: var(--text);
        }

        /* NAVBAR */
        .navbar-custom { 
            background: linear-gradient(135deg, var(--navy-dark), var(--navy)); 
            padding: 12px 0; 
            box-shadow: 0 2px 12px rgba(15,43,70,0.25);
            border-bottom: 3px solid var(--gold);
            height: 70px;
            position: relative;
            z-index: 100;
        }
        .navbar-custom .container { max-width: 1400px; }
        .navbar-brand { 
            color: white !important; 
            font-weight: 700; 
            font-size: 16px; 
            display: flex; 
            align-items: center;
        }
        .navbar-logo { 
            width: 42px; 
            height: 42px; 
            margin-right: 12px;
            background: white;
            border-radius: 50%;
            padding: 3px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 13px;
            color: rgba(255,255,255,0.9);
        }
        .role-badge { 
            background: var(--gold); 
            color: var(--navy); 
            padding: 4px 12px; 
            border-radius: 15px; 
            font-weight: 700; 
            font-size: 10px; 
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .btn-logout {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-logout:hover { background: rgba(255,255,255,0.25); color: white; }

        .nav-avatar {
            width: 38px; height: 38px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--gold);
            cursor: pointer;
        }
        .nav-avatar-initials {
            width: 38px; height: 38px;
            border-radius: 50%;
            background: var(--gold);
            color: var(--navy);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            border: 2px solid var(--gold);
            cursor: pointer;
            text-decoration: none;
        }
        .nav-avatar-initials:hover { color: var(--navy); }

        /* MAIN WRAPPER */
        .main-wrapper { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 24px; 
            display: flex;
            gap: 20px;
            height: calc(100vh - 70px);
            overflow: hidden;
        }

        /* SIDEBAR */
        .sidebar { 
            background: #ffffff; 
            border-radius: 12px; 
            padding: 12px 0; 
            border: 1px solid var(--border);
            width: 240px;
            min-width: 240px;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            box-shadow: 0 1px 4px rgba(15,43,70,0.04);
        }
        
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: transparent; }
        .sidebar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .sidebar .nav-item { margin: 2px 8px; }
        
        .sidebar .nav-link { 
            color: #475569; 
            padding: 10px 14px; 
            border-radius: 8px; 
            text-decoration: none; 
            display: flex; 
            align-items: center; 
            transition: background 0.15s, color 0.15s;
            font-size: 13px; 
            font-weight: 500;
            border: none !important;
            outline: none !important;
            position: relative;
        }
        
        .sidebar .nav-link::before,
        .sidebar .nav-link::after { display: none !important; content: none !important; }
        
        .sidebar .nav-link:hover { background: #f1f5f9; color: var(--navy); }
        
        .sidebar .nav-link.active { 
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: white; 
            box-shadow: 0 3px 10px rgba(27,58,92,0.25);
        }
        .sidebar .nav-link.active i { color: var(--gold); }
        
        .sidebar .nav-link i { 
            margin-right: 10px; 
            width: 18px; 
            font-size: 15px;
            color: var(--text-muted);
            flex-shrink: 0;
        }
        
        .sidebar .nav-link:hover i { color: var(--navy); }
        .sidebar .nav-link.active:hover i { color: var(--gold); }
        
        .sidebar .nav-link .count { 
            margin-left: auto; 
            font-size: 11px; 
            background: #f1f5f9;
            color: var(--text-muted);
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: 600;
        }
        .sidebar .nav-link.active .count { background: rgba(255,255,255,0.2); color: white; }
        .sidebar .nav-link .count.alert { background: #fee2e2; color: #991b1b; }

        /* CONTENT */
        .content { 
            flex: 1;
            min-width: 0;
            height: 100%;
            overflow-y: auto;
            overflow-x: hidden;
            padding-right: 10px;
        }
        
        .content::-webkit-scrollbar { width: 8px; }
        .content::-webkit-scrollbar-track { background: transparent; }
        .content::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .content::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 26px; font-weight: 700; color: var(--navy); margin: 0 0 4px; }
        .page-header p { color: var(--text-muted); font-size: 13px; margin: 0; }

        /* METRIC CARDS */
        .metric-card {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.2s;
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--navy);
            cursor: pointer;
        }
        .metric-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(27,58,92,0.1);
            border-left-color: var(--gold);
        }
        .metric-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; margin-bottom: 8px; }
        .metric-value { font-size: 32px; font-weight: 700; color: var(--navy); line-height: 1; }
        .metric-icon { position: absolute; top: 18px; right: 18px; font-size: 28px; opacity: 0.15; color: var(--navy); }
        .metric-card.gold { border-left-color: var(--gold); }
        .metric-card.teal { border-left-color: #14b8a6; }
        .metric-card.green { border-left-color: #10b981; }
        .metric-card.red { border-left-color: #ef4444; cursor: default; }
        .metric-card.purple { border-left-color: #8b5cf6; }

        /* PANELS */
        .panel {
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(15,43,70,0.04);
        }
        .panel-header {
            padding: 16px 22px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafbfc;
        }
        .panel-header h3 {
            font-size: 15px;
            font-weight: 700;
            color: var(--navy);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-header h3::before { content: ''; width: 3px; height: 16px; background: var(--gold); border-radius: 2px; }
        .panel-body { padding: 22px; }

        /* TABLES */
        .data-table { width: 100%; margin: 0; font-size: 13px; border-collapse: collapse; }
        .data-table thead th { 
            background: #f8fafc; 
            font-size: 11px; 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            color: var(--text-light); 
            font-weight: 700; 
            padding: 12px 22px; 
            border-bottom: 1px solid var(--border);
            text-align: left;
        }
        .data-table tbody td { 
            padding: 14px 22px; 
            vertical-align: middle; 
            border-bottom: 1px solid #f1f5f9; 
            color: var(--text);
        }
        .data-table tbody tr:last-child td { border-bottom: none; }
        .data-table tbody tr:hover { background: #f8fafc; }

        /* BADGES */
        .badge-soft { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .badge-soft.badge-admin { background: #fee2e2; color: #991b1b; }
        .badge-soft.badge-student { background: #dbeafe; color: #1e40af; }
        .badge-soft.badge-faculty { background: #dcfce7; color: #166534; }
        .badge-soft.badge-registrar { background: #fef3c7; color: #92400e; }
        .badge-soft.badge-active { background: #dcfce7; color: #166534; }
        .badge-soft.badge-inactive { background: #fee2e2; color: #991b1b; }
        .badge-soft.badge-protected { background: #f1f5f9; color: #475569; }
        .badge-soft.badge-pending { background: #fef3c7; color: #92400e; }

        /* BUTTONS */
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: white;
            border: none;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 3px 10px rgba(27,58,92,0.2);
            text-decoration: none;
        }
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--navy-dark), var(--navy));
            color: white;
            transform: translateY(-1px);
        }
        .btn-outline-custom {
            background: white;
            color: var(--text-muted);
            border: 1px solid var(--border);
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            text-decoration: none;
        }
        .btn-outline-custom:hover { border-color: var(--navy); color: var(--navy); background: #f8fafc; }
        .btn-danger-soft {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-danger-soft:hover { background: #fee2e2; color: #991b1b; }

        /* ============================================ */
        /* 🆕 PROFESSIONAL MODAL STYLING */
        /* ============================================ */
        .modal-content { 
            border: none; 
            border-radius: 16px; 
            box-shadow: 0 30px 80px rgba(15,43,70,0.3);
            overflow: hidden;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--navy-dark), var(--navy));
            color: white;
            border-bottom: none;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-header .modal-header-content {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        
        .modal-header .modal-header-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: rgba(255,193,7,0.15);
            color: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        
        .modal-header .modal-title-block {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .modal-header h5 { 
            font-size: 16px; 
            font-weight: 700; 
            color: white; 
            margin: 0; 
            letter-spacing: -0.2px;
        }
        
        .modal-header .modal-subtitle {
            font-size: 12px;
            color: rgba(255,255,255,0.65);
            font-weight: 400;
        }
        
        .modal-header .btn-close { 
            filter: invert(1);
            opacity: 0.7;
            transition: opacity 0.15s;
        }
        .modal-header .btn-close:hover { opacity: 1; }
        
        .modal-body { 
            padding: 0;
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .modal-body::-webkit-scrollbar { width: 8px; }
        .modal-body::-webkit-scrollbar-track { background: transparent; }
        .modal-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .modal-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        .modal-footer { 
            background: #f8fafc; 
            border-top: 1px solid var(--border); 
            padding: 14px 28px;
        }
        
        /* Professional Table in Modals */
        .modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .modal-table thead th {
            background: #f8fafc;
            font-size: 10.5px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: var(--text-light);
            font-weight: 700;
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(8px);
        }
        
        .modal-table tbody td {
            padding: 16px 24px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: var(--text);
        }
        
        .modal-table tbody tr:last-child td { border-bottom: none; }
        .modal-table tbody tr { transition: background 0.15s; }
        .modal-table tbody tr:hover { background: #f8fafc; }
        
        /* User Avatar in Table */
        .table-user {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .table-user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .table-user-avatar.admin { background: linear-gradient(135deg, #dc2626, #ef4444); }
        .table-user-avatar.student { background: linear-gradient(135deg, #2563eb, #3b82f6); }
        .table-user-avatar.faculty { background: linear-gradient(135deg, #10b981, #34d399); }
        .table-user-avatar.registrar { background: linear-gradient(135deg, #f59e0b, #fbbf24); }
        
        .table-user-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
            min-width: 0;
        }
        
        .table-user-name {
            font-weight: 600;
            color: var(--navy);
            font-size: 13px;
        }
        
        .table-user-username {
            font-size: 11px;
            color: var(--text-light);
            font-family: 'SF Mono', Monaco, Consolas, monospace;
        }
        
        /* Empty state in modal */
        .modal-empty {
            padding: 60px 30px;
            text-align: center;
        }
        
        .modal-empty-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #f1f5f9;
            color: var(--text-light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            margin-bottom: 16px;
        }
        
        .modal-empty h5 {
            font-size: 16px;
            font-weight: 700;
            color: var(--navy);
            margin-bottom: 6px;
        }
        
        .modal-empty p {
            font-size: 13px;
            color: var(--text-muted);
            margin: 0;
        }
        
        /* Modal count badge */
        .modal-count-badge {
            background: rgba(255,255,255,0.15);
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 20px;
            letter-spacing: 0.3px;
        }

        /* Form styles inside modals */
        .form-label { font-size: 11px; font-weight: 700; color: var(--text-muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control, .form-select { border: 1px solid var(--border); border-radius: 8px; padding: 10px 14px; font-size: 13px; }
        .form-control:focus, .form-select:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(27,58,92,0.1); }
        
        /* For form modals — restore padding */
        .modal-body.form-body { padding: 24px; max-height: none; overflow: visible; }

        /* ALERTS */
        .alert { border-radius: 8px; border: none; font-size: 13px; padding: 12px 16px; }
        .alert-success { background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a; }
        .alert-danger { background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626; }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            html, body { height: auto; overflow: auto; }
            .main-wrapper { flex-direction: column; height: auto; padding: 16px; }
            .sidebar { width: 100%; min-width: 100%; height: auto; max-height: none; }
            .content { height: auto; overflow: visible; padding-right: 0; }
            .modal-table thead th { padding: 12px 16px; }
            .modal-table tbody td { padding: 12px 16px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="assets/images/logo.png" alt="TIFTCI" class="navbar-logo" onerror="this.style.display='none'">
            TRENTIANS GradeHub <span style="color: var(--gold);"></span>
        </a>
        <div class="ms-auto d-flex align-items-center user-info">
            <a href="#" data-bs-toggle="modal" data-bs-target="#profileModal" title="My Profile">
                <?php if ($userAvatar): ?>
                    <img src="<?php echo $userAvatar; ?>" alt="Profile" class="nav-avatar">
                <?php else: ?>
                    <div class="nav-avatar-initials"><?php echo strtoupper(substr($fullname, 0, 2)); ?></div>
                <?php endif; ?>
            </a>
            <span><?php echo htmlspecialchars($fullname); ?></span>
            <span class="role-badge">ADMIN</span>
            <a href="logout.php" class="btn-logout"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
        </div>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-wrapper">
    
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="nav-item">
            <a class="nav-link active" href="index.php">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="pages/admin/approvals.php">
                <i class="bi bi-shield-check"></i> Account Approvals
                <?php if ($pendingApprovals > 0): ?>
                    <span class="count alert"><?php echo $pendingApprovals; ?></span>
                <?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill text-primary"></i> Add User
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="bi bi-plus-square-fill text-success"></i> Add Course
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                <i class="bi bi-plus-square-fill text-info"></i> Add Subject
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#addSectionModal">
                <i class="bi bi-plus-square-fill text-warning"></i> Add Section
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="content">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-3">
                <i class="bi bi-check-circle me-2"></i> <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-3">
                <i class="bi bi-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="page-header">
            <h1>Dashboard Overview</h1>
            <p>Welcome back, <strong><?php echo htmlspecialchars($fullname); ?></strong>. Here's what's happening today.</p>
        </div>

        <!-- Primary Stats (clickable to open modals) -->
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="metric-card" data-bs-toggle="modal" data-bs-target="#usersModal">
                    <div class="metric-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="metric-label">Total Users</div>
                    <div class="metric-value"><?php echo $userCount; ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card green" data-bs-toggle="modal" data-bs-target="#studentsModal">
                    <div class="metric-icon"><i class="bi bi-person-badge-fill"></i></div>
                    <div class="metric-label">Students</div>
                    <div class="metric-value"><?php echo $studentCount; ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card teal" data-bs-toggle="modal" data-bs-target="#facultyModal">
                    <div class="metric-icon"><i class="bi bi-person-workspace"></i></div>
                    <div class="metric-label">Faculty</div>
                    <div class="metric-value"><?php echo $facultyCount; ?></div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="metric-card gold" data-bs-toggle="modal" data-bs-target="#coursesModal">
                    <div class="metric-icon"><i class="bi bi-book-fill"></i></div>
                    <div class="metric-label">Courses</div>
                    <div class="metric-value"><?php echo $courseCount; ?></div>
                </div>
            </div>
        </div>

        <!-- Secondary Stats (clickable) -->
        <div class="row g-3">
            <div class="col-lg-4 col-md-4">
                <div class="metric-card purple" data-bs-toggle="modal" data-bs-target="#subjectsModal">
                    <div class="metric-icon"><i class="bi bi-journal-text"></i></div>
                    <div class="metric-label">Subjects</div>
                    <div class="metric-value"><?php echo $subjectCount; ?></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="metric-card" data-bs-toggle="modal" data-bs-target="#sectionsModal">
                    <div class="metric-icon"><i class="bi bi-layers-fill"></i></div>
                    <div class="metric-label">Sections</div>
                    <div class="metric-value"><?php echo $sectionCount; ?></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="metric-card red">
                    <div class="metric-icon"><i class="bi bi-shield-exclamation"></i></div>
                    <div class="metric-label">Pending Approvals</div>
                    <div class="metric-value"><?php echo $pendingApprovals; ?></div>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="panel">
            <div class="panel-header">
                <h3>Recent Users</h3>
                <button class="btn-primary-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus"></i> Add User
                </button>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $count = 0;
                        $users->data_seek(0);
                        while ($u = $users->fetch_assoc()): 
                            if ($count++ >= 6) break;
                            $roleClass = 'badge-' . strtolower($u['role_name']);
                            $acctStatus = $u['account_status'] ?? 'Active';
                        ?>
                        <tr>
                            <td><span style="color: var(--text-light); font-weight: 600;">#<?php echo $u['user_id']; ?></span></td>
                            <td><strong style="color: var(--navy);"><?php echo htmlspecialchars($u['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                            <td><span class="badge-soft <?php echo $roleClass; ?>"><?php echo strtoupper($u['role_name']); ?></span></td>
                            <td>
                                <?php if ($acctStatus === 'Pending'): ?>
                                    <span class="badge-soft badge-pending">Pending</span>
                                <?php elseif ($u['is_active']): ?>
                                    <span class="badge-soft badge-active">Active</span>
                                <?php else: ?>
                                    <span class="badge-soft badge-inactive">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['username'] !== 'admin'): ?>
                                    <a href="api/admin/delete_user.php?user_id=<?php echo $u['user_id']; ?>" 
                                       class="btn-danger-soft"
                                       onclick="return confirm('Delete user <?php echo htmlspecialchars($u['username']); ?>?')">
                                        Delete
                                    </a>
                                <?php else: ?>
                                    <span class="badge-soft badge-protected">Protected</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<!-- ============================================ -->
<!-- 🆕 PROFESSIONAL VIEW MODALS -->
<!-- ============================================ -->

<!-- Users Modal -->
<div class="modal fade" id="usersModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>All Users</h5>
                        <span class="modal-subtitle">Complete list of registered accounts</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-count-badge"><?php echo $userCount; ?> total</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($userCount == 0): ?>
                    <div class="modal-empty">
                        <div class="modal-empty-icon"><i class="bi bi-people"></i></div>
                        <h5>No Users Yet</h5>
                        <p>There are no registered users in the system.</p>
                    </div>
                <?php else: ?>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th style="width: 60px;">ID</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $users->data_seek(0);
                            while ($u = $users->fetch_assoc()): 
                                $roleKey = strtolower($u['role_name']);
                                $acctStatus = $u['account_status'] ?? 'Active';
                                $initials = strtoupper(substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1));
                            ?>
                            <tr>
                                <td><span style="color: var(--text-light); font-weight: 600;">#<?php echo $u['user_id']; ?></span></td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-user-avatar <?php echo $roleKey; ?>"><?php echo $initials; ?></div>
                                        <div class="table-user-info">
                                            <span class="table-user-name"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></span>
                                            <span class="table-user-username">@<?php echo htmlspecialchars($u['username']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-soft badge-<?php echo $roleKey; ?>"><?php echo strtoupper($u['role_name']); ?></span></td>
                                <td>
                                    <?php if ($acctStatus === 'Pending'): ?>
                                        <span class="badge-soft badge-pending"><i class="bi bi-clock-fill"></i> Pending</span>
                                    <?php elseif ($u['is_active']): ?>
                                        <span class="badge-soft badge-active"><i class="bi bi-check-circle-fill"></i> Active</span>
                                    <?php else: ?>
                                        <span class="badge-soft badge-inactive"><i class="bi bi-x-circle-fill"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Students Modal -->
<div class="modal fade" id="studentsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-person-badge-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>All Students</h5>
                        <span class="modal-subtitle">Enrolled students in the system</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-count-badge"><?php echo $studentCount; ?> total</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($studentCount == 0): ?>
                    <div class="modal-empty">
                        <div class="modal-empty-icon"><i class="bi bi-person-badge"></i></div>
                        <h5>No Students Yet</h5>
                        <p>There are no enrolled students at the moment.</p>
                    </div>
                <?php else: ?>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Student #</th>
                                <th>Student</th>
                                <th>Course</th>
                                <th style="text-align: center;">Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $students->data_seek(0);
                            while ($s = $students->fetch_assoc()): 
                                $initials = strtoupper(substr($s['first_name'] ?? '', 0, 1) . substr($s['last_name'] ?? '', 0, 1));
                            ?>
                            <tr>
                                <td><strong style="color: var(--navy); font-family: 'SF Mono', Monaco, Consolas, monospace;"><?php echo htmlspecialchars($s['student_number'] ?? 'N/A'); ?></strong></td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-user-avatar student"><?php echo $initials; ?></div>
                                        <div class="table-user-info">
                                            <span class="table-user-name"><?php echo htmlspecialchars(($s['first_name'] ?? '') . ' ' . ($s['last_name'] ?? '')); ?></span>
                                            <span class="table-user-username"><?php echo htmlspecialchars($s['email'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-soft badge-student"><?php echo htmlspecialchars($s['course_code'] ?? 'N/A'); ?></span></td>
                                <td style="text-align: center;"><strong style="color: var(--navy);"><?php echo $s['year_level'] ?? 'N/A'; ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Faculty Modal -->
<div class="modal fade" id="facultyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-person-workspace"></i></div>
                    <div class="modal-title-block">
                        <h5>All Faculty</h5>
                        <span class="modal-subtitle">Teaching staff and their specializations</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-count-badge"><?php echo $facultyCount; ?> total</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($facultyCount == 0): ?>
                    <div class="modal-empty">
                        <div class="modal-empty-icon"><i class="bi bi-person-workspace"></i></div>
                        <h5>No Faculty Yet</h5>
                        <p>There are no registered faculty members.</p>
                    </div>
                <?php else: ?>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th>Faculty</th>
                                <th>Department</th>
                                <th>Specialization</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $faculty->data_seek(0);
                            while ($f = $faculty->fetch_assoc()): 
                                $initials = strtoupper(substr($f['first_name'] ?? '', 0, 1) . substr($f['last_name'] ?? '', 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="table-user">
                                        <div class="table-user-avatar faculty"><?php echo $initials; ?></div>
                                        <div class="table-user-info">
                                            <span class="table-user-name"><?php echo htmlspecialchars(($f['first_name'] ?? '') . ' ' . ($f['last_name'] ?? '')); ?></span>
                                            <span class="table-user-username"><?php echo htmlspecialchars($f['email'] ?? ''); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-soft badge-faculty"><?php echo htmlspecialchars($f['department'] ?? 'N/A'); ?></span></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($f['specialization'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Courses Modal -->
<div class="modal fade" id="coursesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-book-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>All Courses</h5>
                        <span class="modal-subtitle">Degree programs offered by the institution</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-count-badge"><?php echo $courseCount; ?> total</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($courseCount == 0): ?>
                    <div class="modal-empty">
                        <div class="modal-empty-icon"><i class="bi bi-book"></i></div>
                        <h5>No Courses Yet</h5>
                        <p>No courses have been added to the system.</p>
                    </div>
                <?php else: ?>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Code</th>
                                <th>Course Name</th>
                                <th style="width: 130px;">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $courses->data_seek(0);
                            while ($c = $courses->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><span class="badge-soft badge-student" style="font-size: 12px;"><?php echo htmlspecialchars($c['course_code']); ?></span></td>
                                <td><strong style="color: var(--navy);"><?php echo htmlspecialchars($c['course_name']); ?></strong></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($c['duration'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Subjects Modal -->
<div class="modal fade" id="subjectsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-journal-text"></i></div>
                    <div class="modal-title-block">
                        <h5>All Subjects</h5>
                        <span class="modal-subtitle">Subjects offered across all courses</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-count-badge"><?php echo $subjectCount; ?> total</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($subjectCount == 0): ?>
                    <div class="modal-empty">
                        <div class="modal-empty-icon"><i class="bi bi-journal-text"></i></div>
                        <h5>No Subjects Yet</h5>
                        <p>No subjects have been added to the system.</p>
                    </div>
                <?php else: ?>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Code</th>
                                <th>Subject Name</th>
                                <th style="width: 80px; text-align: center;">Units</th>
                                <th style="width: 120px;">Course</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $subjects->data_seek(0);
                            while ($s = $subjects->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><span class="badge-soft badge-faculty" style="font-size: 12px;"><?php echo htmlspecialchars($s['subject_code']); ?></span></td>
                                <td><strong style="color: var(--navy);"><?php echo htmlspecialchars($s['subject_name']); ?></strong></td>
                                <td style="text-align: center;"><strong style="color: var(--navy); font-size: 14px;"><?php echo $s['units']; ?></strong></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($s['course_code'] ?? 'All'); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Sections Modal -->
<div class="modal fade" id="sectionsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-layers-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>All Sections</h5>
                        <span class="modal-subtitle">Class sections and their details</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="modal-count-badge"><?php echo $sectionCount; ?> total</span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body">
                <?php if ($sectionCount == 0): ?>
                    <div class="modal-empty">
                        <div class="modal-empty-icon"><i class="bi bi-layers"></i></div>
                        <h5>No Sections Yet</h5>
                        <p>No sections have been added to the system.</p>
                    </div>
                <?php else: ?>
                    <table class="modal-table">
                        <thead>
                            <tr>
                                <th style="width: 130px;">Code</th>
                                <th>Section Name</th>
                                <th style="width: 120px;">Course</th>
                                <th style="width: 80px; text-align: center;">Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $sections->data_seek(0);
                            while ($s = $sections->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><span class="badge-soft badge-registrar" style="font-size: 12px;"><?php echo htmlspecialchars($s['section_code']); ?></span></td>
                                <td><strong style="color: var(--navy);"><?php echo htmlspecialchars($s['section_name']); ?></strong></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($s['course_code'] ?? 'N/A'); ?></td>
                                <td style="text-align: center;"><strong style="color: var(--navy); font-size: 14px;"><?php echo $s['year_level']; ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================ -->
<!-- FORM MODALS (Add User, Course, Subject, Section, Profile) -->
<!-- ============================================ -->

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-person-circle"></i></div>
                    <div class="modal-title-block">
                        <h5>My Profile</h5>
                        <span class="modal-subtitle">Manage your account</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/upload_profile.php" enctype="multipart/form-data">
                <div class="modal-body form-body text-center">
                    <div class="mb-4">
                        <?php if ($userAvatar): ?>
                            <img src="<?php echo $userAvatar; ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--border);">
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--gold), #ffcc33); color: var(--navy); display: inline-flex; align-items: center; justify-content: center; font-size: 40px; font-weight: 700; border: 4px solid var(--border);">
                                <?php echo strtoupper(substr($fullname, 0, 2)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" disabled>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                    </div>
                    <div class="mb-3 text-start">
                        <label class="form-label">Upload New Picture</label>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*" required>
                        <small class="text-muted">Max 2MB. JPG, PNG, or GIF only.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-upload"></i> Upload Picture</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-person-plus-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>Add New User</h5>
                        <span class="modal-subtitle">Create a new account</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/admin/add_user.php">
                <div class="modal-body form-body">
                    <div class="row g-3 mb-3">
                        <div class="col-6"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" required></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Email Address</label><input type="email" name="email" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role_id" class="form-select" id="roleSelect" required>
                            <option value="">Select Role...</option>
                            <?php $roles->data_seek(0); while ($r = $roles->fetch_assoc()): ?>
                                <option value="<?php echo $r['role_id']; ?>"><?php echo $r['role_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div id="studentFields" style="display:none;">
                        <div class="mb-3"><label class="form-label">Student Number</label><input type="text" name="student_number" class="form-control"></div>
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Course</label>
                                <select name="course_id" class="form-select">
                                    <option value="">Select...</option>
                                    <?php $courses->data_seek(0); while ($c = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_code']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Year Level</label>
                                <select name="year_level" class="form-select">
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="facultyFields" style="display:none;">
                        <div class="mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
                        <div class="mb-3"><label class="form-label">Specialization</label><input type="text" name="specialization" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-check-circle"></i> Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Course Modal -->
<div class="modal fade" id="addCourseModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-book-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>Add Course</h5>
                        <span class="modal-subtitle">Add a new degree program</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/admin/add_course.php">
                <div class="modal-body form-body">
                    <div class="mb-3"><label class="form-label">Course Code</label><input type="text" name="course_code" class="form-control" required placeholder="e.g., BSIT"></div>
                    <div class="mb-3"><label class="form-label">Course Name</label><input type="text" name="course_name" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" placeholder="e.g., 4 Years"></div>
                    <div class="mb-3"><label class="form-label">Department</label><input type="text" name="department" class="form-control"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-check-circle"></i> Add Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Subject Modal -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-journal-plus"></i></div>
                    <div class="modal-title-block">
                        <h5>Add Subject</h5>
                        <span class="modal-subtitle">Add a new subject to the curriculum</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/admin/add_subject.php">
                <div class="modal-body form-body">
                    <div class="mb-3"><label class="form-label">Subject Code</label><input type="text" name="subject_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Subject Name</label><input type="text" name="subject_name" class="form-control" required></div>
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">Units</label><input type="number" name="units" class="form-control" value="3" step="0.5"></div>
                        <div class="col-6">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-select">
                                <option value="">All Courses</option>
                                <?php $courses->data_seek(0); while ($c = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_code']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-check-circle"></i> Add Subject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Section Modal -->
<div class="modal fade" id="addSectionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-content">
                    <div class="modal-header-icon"><i class="bi bi-plus-square-fill"></i></div>
                    <div class="modal-title-block">
                        <h5>Add Section</h5>
                        <span class="modal-subtitle">Create a new class section</span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="api/admin/add_section.php">
                <div class="modal-body form-body">
                    <div class="mb-3"><label class="form-label">Section Code</label><input type="text" name="section_code" class="form-control" required></div>
                    <div class="mb-3"><label class="form-label">Section Name</label><input type="text" name="section_name" class="form-control" required></div>
                    <div class="mb-3">
                        <label class="form-label">Course</label>
                        <select name="course_id" class="form-select" required>
                            <option value="">Select Course...</option>
                            <?php $courses->data_seek(0); while ($c = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $c['course_id']; ?>"><?php echo $c['course_code']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-6"><label class="form-label">Year Level</label><input type="number" name="year_level" class="form-control" value="1" min="1" max="4"></div>
                        <div class="col-6"><label class="form-label">Max Students</label><input type="number" name="max_students" class="form-control" value="40"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-primary-custom"><i class="bi bi-check-circle"></i> Add Section</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/confirm_modal.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/modal-toast.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('roleSelect');
    const studentFields = document.getElementById('studentFields');
    const facultyFields = document.getElementById('facultyFields');
    
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const roleId = parseInt(this.value);
            studentFields.style.display = (roleId === 1) ? 'block' : 'none';
            facultyFields.style.display = (roleId === 2) ? 'block' : 'none';
        });
    }
});
</script>
</body>
</html>