<?php
// pages/admin/approvals.php - Account Approvals (mobile-responsive)

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../../login.php');
    exit();
}

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'trentians_gradehub';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$result = $conn->query("SELECT u.*, r.role_name FROM users u JOIN roles r ON u.role_id = r.role_id WHERE u.user_id = " . $_SESSION['user_id']);
$userData = $result->fetch_assoc();
$userAvatar = !empty($userData['profile_picture']) ? '../../assets/images/profiles/' . $userData['profile_picture'] : null;
if (!$userData) { die("User not found."); }
$fullname = $_SESSION['fullname'];
$email = $userData['email'];

if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $uid = intval($_GET['user_id']);
    $action = $_GET['action'];
    $admin_id = $_SESSION['user_id'];
    
    if ($action === 'approve') {
        $conn->query("UPDATE users SET account_status = 'Active', verified_by = $admin_id, verified_date = NOW() WHERE user_id = $uid");
        $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($uid, '✅ Account Approved', 'Your account has been approved! You can now log in and view your grades.', 'General')");
        header('Location: approvals.php?show_toast=' . urlencode('Student account has been approved successfully.') . '&toast_title=' . urlencode('Account Approved!') . '&toast_type=success');
        exit();
    } elseif ($action === 'reject') {
        $conn->query("UPDATE users SET account_status = 'Rejected', verified_by = $admin_id, verified_date = NOW() WHERE user_id = $uid");
        $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($uid, '❌ Account Rejected', 'Your registration was rejected. Please contact the administrator.', 'General')");
        header('Location: approvals.php?show_toast=' . urlencode('Student account has been rejected.') . '&toast_title=' . urlencode('Account Rejected') . '&toast_type=danger');
        exit();
    }
}

$pendingCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE account_status = 'Pending'")->fetch_assoc()['c'];
$approvedCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE account_status = 'Active' AND role_id = 1")->fetch_assoc()['c'];
$rejectedCount = $conn->query("SELECT COUNT(*) as c FROM users WHERE account_status = 'Rejected'")->fetch_assoc()['c'];

$pending = $conn->query("
    SELECT u.*, s.student_number, s.year_level, c.course_code 
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN courses c ON s.course_id = c.course_id
    WHERE u.account_status = 'Pending'
    ORDER BY u.created_at ASC
");
$pendingList = [];
while ($p = $pending->fetch_assoc()) $pendingList[] = $p;

$history = $conn->query("
    SELECT u.*, s.student_number, s.year_level, c.course_code,
           CONCAT(v.first_name, ' ', v.last_name) as verified_by_name
    FROM users u
    LEFT JOIN students s ON s.user_id = u.user_id
    LEFT JOIN courses c ON s.course_id = c.course_id
    LEFT JOIN users v ON u.verified_by = v.user_id
    WHERE u.account_status IN ('Active', 'Rejected') AND u.role_id = 1
    ORDER BY u.verified_date DESC LIMIT 20
");
$historyList = [];
while ($h = $history->fetch_assoc()) $historyList[] = $h;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Account Approvals - TRENTIANS GradeHub</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../assets/css/modal-toast.css">
<link rel="stylesheet" href="../../assets/css/responsive.css">
<style>
*{box-sizing:border-box;margin:0;padding:0}
html,body{height:100vh;overflow:hidden;max-width:100%}
:root{--navy-dark:#0f2b46;--navy:#1b3a5c;--navy-light:#2d5a7a;--gold:#ffc107;--bg:#f8fafc;--border:#e2e8f0;--text:#1e293b;--text-muted:#64748b;--text-light:#94a3b8}
body{background:var(--bg);font-family:-apple-system,BlinkMacSystemFont,'Inter','Segoe UI',sans-serif;font-size:14px;color:var(--text)}

.navbar-custom{background:linear-gradient(135deg,var(--navy-dark),var(--navy));padding:12px 0;box-shadow:0 2px 12px rgba(15,43,70,.25);border-bottom:3px solid var(--gold);height:70px;position:relative;z-index:100}
.navbar-custom .container{max-width:1400px}
.navbar-brand{color:#fff!important;font-weight:700;font-size:16px;display:flex;align-items:center}
.navbar-logo{width:42px;height:42px;margin-right:12px;background:#fff;border-radius:50%;padding:3px}
.user-info{display:flex;align-items:center;gap:12px;font-size:13px;color:rgba(255,255,255,.9)}
.role-badge{background:var(--gold);color:var(--navy);padding:4px 12px;border-radius:15px;font-weight:700;font-size:10px;letter-spacing:.5px;text-transform:uppercase}
.btn-logout{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.2);padding:6px 16px;border-radius:8px;font-size:12px;font-weight:500;text-decoration:none}
.btn-logout:hover{background:rgba(255,255,255,.25);color:#fff}
.nav-avatar{width:38px;height:38px;border-radius:50%;object-fit:cover;border:2px solid var(--gold);cursor:pointer}
.nav-avatar-initials{width:38px;height:38px;border-radius:50%;background:var(--gold);color:var(--navy);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;border:2px solid var(--gold);cursor:pointer;text-decoration:none}
.nav-avatar-initials:hover{color:var(--navy)}

.main-wrapper{max-width:1400px;margin:0 auto;padding:24px;display:flex;gap:20px;height:calc(100vh - 70px);overflow:hidden}

.sidebar{background:#fff;border-radius:12px;padding:12px 0;border:1px solid var(--border);width:240px;min-width:240px;height:100%;overflow-y:auto;overflow-x:hidden;box-shadow:0 1px 4px rgba(15,43,70,.04)}
.sidebar::-webkit-scrollbar{width:6px}
.sidebar::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px}
.sidebar .nav-item{margin:2px 8px}
.sidebar .nav-link{color:#475569;padding:10px 14px;border-radius:8px;text-decoration:none;display:flex;align-items:center;transition:background .15s,color .15s;font-size:13px;font-weight:500;position:relative}
.sidebar .nav-link::before,.sidebar .nav-link::after{display:none!important;content:none!important}
.sidebar .nav-link:hover{background:#f1f5f9;color:var(--navy)}
.sidebar .nav-link.active{background:linear-gradient(135deg,var(--navy),var(--navy-light));color:#fff;box-shadow:0 3px 10px rgba(27,58,92,.25)}
.sidebar .nav-link.active i{color:var(--gold)}
.sidebar .nav-link i{margin-right:10px;width:18px;font-size:15px;color:var(--text-muted);flex-shrink:0}
.sidebar .nav-link:hover i{color:var(--navy)}
.sidebar .nav-link.active:hover i{color:var(--gold)}
.sidebar .nav-link .count{margin-left:auto;font-size:11px;background:#f1f5f9;color:var(--text-muted);padding:2px 8px;border-radius:10px;font-weight:600}
.sidebar .nav-link.active .count{background:rgba(255,255,255,.2);color:#fff}
.sidebar .nav-link .count.alert{background:#fee2e2;color:#991b1b}
.sidebar .nav-link.text-danger:hover{background:#fef2f2}

.content{flex:1;min-width:0;height:100%;overflow-y:auto;overflow-x:hidden;padding-right:10px}
.content::-webkit-scrollbar{width:8px}
.content::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:10px}

.page-header{margin-bottom:24px;padding-bottom:20px;border-bottom:2px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px}
.page-header h1{font-size:26px;font-weight:700;color:var(--navy);margin:0 0 4px}
.page-header p{color:var(--text-muted);font-size:13px;margin:0}
.btn-back{background:#fff;color:var(--text-muted);border:1px solid var(--border);padding:9px 18px;border-radius:8px;font-size:12px;font-weight:500;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:all .15s}
.btn-back:hover{background:#f8fafc;color:var(--navy);border-color:var(--navy)}

.metric-card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;margin-bottom:16px;transition:all .2s;position:relative;overflow:hidden;border-left:4px solid var(--navy)}
.metric-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(27,58,92,.1);border-left-color:var(--gold)}
.metric-label{font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.8px;font-weight:700;margin-bottom:8px}
.metric-value{font-size:32px;font-weight:700;color:var(--navy);line-height:1}
.metric-icon{position:absolute;top:18px;right:18px;font-size:28px;opacity:.15;color:var(--navy)}
.metric-card.gold{border-left-color:var(--gold)}
.metric-card.green{border-left-color:#10b981}
.metric-card.red{border-left-color:#ef4444}

.panel{background:#fff;border:1px solid var(--border);border-radius:12px;margin-bottom:20px;overflow:hidden;box-shadow:0 1px 4px rgba(15,43,70,.04)}
.panel-header{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:#fafbfc}
.panel-header h3{font-size:15px;font-weight:700;color:var(--navy);margin:0;display:flex;align-items:center;gap:8px}
.panel-header h3::before{content:'';width:3px;height:16px;background:var(--gold);border-radius:2px}
.panel-header .panel-sub{font-size:12px;color:var(--text-light);margin-top:2px}

.data-table{width:100%;margin:0;font-size:13px;border-collapse:collapse}
.data-table thead th{background:#f8fafc;font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:var(--text-light);font-weight:700;padding:12px 22px;border-bottom:1px solid var(--border);text-align:left}
.data-table tbody td{padding:14px 22px;vertical-align:middle;border-bottom:1px solid #f1f5f9;color:var(--text)}
.data-table tbody tr:last-child td{border-bottom:none}
.data-table tbody tr:hover{background:#f8fafc}

.badge-soft{padding:4px 10px;border-radius:6px;font-size:11px;font-weight:600;display:inline-flex;align-items:center;gap:4px}
.badge-soft.badge-approved{background:#dcfce7;color:#166534}
.badge-soft.badge-rejected{background:#fee2e2;color:#991b1b}

.btn-approve{background:linear-gradient(135deg,#10b981,#34d399);color:#fff;border:none;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:5px;cursor:pointer}
.btn-approve:hover{color:#fff;background:linear-gradient(135deg,#059669,#10b981);transform:translateY(-1px)}
.btn-reject{background:#fef2f2;color:#dc2626;border:1px solid #fecaca;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:5px;cursor:pointer}
.btn-reject:hover{background:#fee2e2;color:#991b1b}

.empty-state{padding:60px 30px;text-align:center}
.empty-state-icon{width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg,#d1fae5,#a7f3d0);color:#059669;display:inline-flex;align-items:center;justify-content:center;font-size:42px;margin-bottom:20px;box-shadow:0 12px 30px rgba(16,185,129,.2)}
.empty-state h5{font-size:18px;font-weight:700;color:var(--navy);margin-bottom:6px}
.empty-state p{font-size:13px;color:var(--text-muted);margin:0}

.modal-content{border:none;border-radius:16px;box-shadow:0 30px 80px rgba(15,43,70,.3);overflow:hidden}
.modal-header{background:linear-gradient(135deg,var(--navy-dark),var(--navy));color:#fff;border-bottom:none;padding:20px 28px;display:flex;align-items:center;justify-content:space-between}
.modal-header .modal-header-content{display:flex;align-items:center;gap:14px}
.modal-header .modal-header-icon{width:44px;height:44px;border-radius:10px;background:rgba(255,193,7,.15);color:var(--gold);display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}
.modal-header .modal-title-block{display:flex;flex-direction:column;gap:2px}
.modal-title{font-size:16px;font-weight:700;color:#fff;margin:0}
.modal-header .modal-subtitle{font-size:12px;color:rgba(255,255,255,.65);font-weight:400}
.modal-header .btn-close{filter:invert(1);opacity:.7}
.modal-header .btn-close:hover{opacity:1}
.modal-body{padding:24px;max-height:70vh;overflow-y:auto}
.modal-footer{background:#f8fafc;border-top:1px solid var(--border);padding:14px 28px}
.form-label{font-size:11px;font-weight:700;color:var(--text-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px}
.form-control{border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:13px}
.btn-primary-custom{background:linear-gradient(135deg,var(--navy),var(--navy-light));color:#fff;border:none;padding:9px 18px;border-radius:8px;font-size:12px;font-weight:600;display:inline-flex;align-items:center;gap:6px;text-decoration:none;cursor:pointer}
.btn-primary-custom:hover{background:linear-gradient(135deg,var(--navy-dark),var(--navy));color:#fff}
.btn-outline-custom{background:#fff;color:var(--text-muted);border:1px solid var(--border);padding:9px 18px;border-radius:8px;font-size:12px;font-weight:500;text-decoration:none}
.btn-outline-custom:hover{background:#f8fafc;color:var(--navy)}

.alert{border-radius:8px;border:none;font-size:13px;padding:12px 16px}
.alert-success{background:#f0fdf4;color:#166534;border-left:4px solid #16a34a}
.alert-danger{background:#fef2f2;color:#991b1b;border-left:4px solid #dc2626}

@media(max-width:992px){.user-info .hide-mobile{display:none}}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <div class="container">
        <a class="navbar-brand" href="../../index.php">
            <img src="../../assets/images/logo.png" alt="TIFTCI" class="navbar-logo" onerror="this.style.display='none'">
            TRENTIANS GradeHub <span style="color: var(--gold);">Admin</span>
        </a>
        <div class="ms-auto d-flex align-items-center user-info">
            <a href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                <?php if ($userAvatar): ?><img src="<?php echo $userAvatar; ?>" class="nav-avatar">
                <?php else: ?><div class="nav-avatar-initials"><?php echo strtoupper(substr($fullname,0,2)); ?></div><?php endif; ?>
            </a>
            <span class="hide-mobile"><?php echo htmlspecialchars($fullname); ?></span>
            <span class="role-badge">ADMIN</span>
            <a href="../../logout.php" class="btn-logout"><i class="bi bi-box-arrow-right me-1"></i><span>Logout</span></a>
        </div>
    </div>
</nav>

<div class="main-wrapper">
    
    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="nav-item">
            <a class="nav-link" href="../../index.php">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link active" href="approvals.php">
                <i class="bi bi-shield-check"></i> Account Approvals
                <?php if ($pendingCount>0): ?><span class="count alert"><?php echo $pendingCount; ?></span><?php endif; ?>
            </a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="../../index.php"><i class="bi bi-people-fill"></i> Users</a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="../../index.php"><i class="bi bi-book-fill"></i> Courses</a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="../../index.php"><i class="bi bi-journal-text"></i> Subjects</a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="../../index.php"><i class="bi bi-layers-fill"></i> Sections</a>
        </div>
        <div class="nav-item">
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </div>
    </aside>

    <!-- CONTENT -->
    <main class="content">
        <div class="page-header">
            <div>
                <h1>Account Approvals</h1>
                <p>Review and verify new student registrations.</p>
            </div>
            <a href="../../index.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>

        <!-- Stats Row -->
        <div class="row g-3">
            <div class="col-lg-4 col-md-4">
                <div class="metric-card gold">
                    <div class="metric-icon"><i class="bi bi-clock-history"></i></div>
                    <div class="metric-label">Pending Approvals</div>
                    <div class="metric-value"><?php echo $pendingCount; ?></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="metric-card green">
                    <div class="metric-icon"><i class="bi bi-check-circle-fill"></i></div>
                    <div class="metric-label">Verified Students</div>
                    <div class="metric-value"><?php echo $approvedCount; ?></div>
                </div>
            </div>
            <div class="col-lg-4 col-md-4">
                <div class="metric-card red">
                    <div class="metric-icon"><i class="bi bi-x-circle-fill"></i></div>
                    <div class="metric-label">Rejected</div>
                    <div class="metric-value"><?php echo $rejectedCount; ?></div>
                </div>
            </div>
        </div>

        <!-- Pending Accounts -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3>Pending Account Approvals</h3>
                    <div class="panel-sub">Verify each registration before approving</div>
                </div>
                <?php if ($pendingCount > 0): ?>
                    <span style="background:#fef3c7;color:#92400e;font-size:11px;font-weight:700;padding:5px 14px;border-radius:20px;"><?php echo $pendingCount; ?> awaiting review</span>
                <?php endif; ?>
            </div>

            <?php if (empty($pendingList)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="bi bi-check-lg"></i></div>
                    <h5>All Caught Up!</h5>
                    <p>There are no pending registrations to review.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th style="text-align:center;">Year</th>
                                <th>Registered</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingList as $p): ?>
                            <tr>
                                <td><strong style="color:var(--navy);"><?php echo htmlspecialchars($p['student_number'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($p['email']); ?></td>
                                <td><?php echo htmlspecialchars($p['course_code'] ?? 'N/A'); ?></td>
                                <td style="text-align:center;"><?php echo $p['year_level'] ?? 'N/A'; ?></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?php echo date('M d, Y', strtotime($p['created_at'])); ?></td>
                                <td style="text-align:right;">
                                    <button class="btn-approve approve-btn"
                                            data-user-id="<?php echo $p['user_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>">
                                        <i class="bi bi-check-lg"></i> Approve
                                    </button>
                                    <button class="btn-reject reject-btn"
                                            data-user-id="<?php echo $p['user_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($p['first_name'] . ' ' . $p['last_name']); ?>">
                                        <i class="bi bi-x-lg"></i> Reject
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h3>Recent Activity</h3>
                    <div class="panel-sub">Recently approved and rejected accounts</div>
                </div>
            </div>
            <?php if (empty($historyList)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon" style="background:linear-gradient(135deg,#e0e7ff,#a5b4fc);color:#4338ca;"><i class="bi bi-clock-history"></i></div>
                    <h5>No Activity Yet</h5>
                    <p>Approved or rejected accounts will appear here.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Student Number</th>
                                <th>Full Name</th>
                                <th>Course</th>
                                <th>Status</th>
                                <th>Verified By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historyList as $h):
                                $statusClass = $h['account_status'] === 'Active' ? 'badge-approved' : 'badge-rejected';
                                $statusLabel = $h['account_status'] === 'Active' ? 'Approved' : 'Rejected';
                                $statusIcon = $h['account_status'] === 'Active' ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                            ?>
                            <tr>
                                <td><strong style="color:var(--navy);"><?php echo htmlspecialchars($h['student_number'] ?? 'N/A'); ?></strong></td>
                                <td><?php echo htmlspecialchars($h['first_name'] . ' ' . $h['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($h['course_code'] ?? 'N/A'); ?></td>
                                <td><span class="badge-soft <?php echo $statusClass; ?>"><i class="bi <?php echo $statusIcon; ?>"></i> <?php echo $statusLabel; ?></span></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?php echo htmlspecialchars($h['verified_by_name'] ?? 'System'); ?></td>
                                <td style="font-size:12px;color:var(--text-muted);"><?php echo isset($h['verified_date']) ? date('M d, Y', strtotime($h['verified_date'])) : 'N/A'; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- PROFILE MODAL -->
<div class="modal fade" id="profileModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
        <div class="modal-header-content">
            <div class="modal-header-icon"><i class="bi bi-person-circle"></i></div>
            <div class="modal-title-block">
                <h5 class="modal-title">My Profile</h5>
                <span class="modal-subtitle">Manage your account</span>
            </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form method="POST" action="../../api/upload_profile.php" enctype="multipart/form-data">
        <div class="modal-body text-center">
            <div class="mb-4">
                <?php if ($userAvatar): ?>
                    <img src="<?php echo $userAvatar; ?>" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid var(--navy);">
                <?php else: ?>
                    <div style="width:120px;height:120px;border-radius:50%;background:var(--gold);color:var(--navy);display:inline-flex;align-items:center;justify-content:center;font-size:40px;font-weight:700;border:4px solid var(--navy);">
                        <?php echo strtoupper(substr($fullname,0,2)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="mb-3 text-start"><label class="form-label">Full Name</label><input type="text" class="form-control" value="<?php echo htmlspecialchars($fullname); ?>" disabled></div>
            <div class="mb-3 text-start"><label class="form-label">Email</label><input type="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" disabled></div>
            <div class="mb-3 text-start"><label class="form-label">Upload New Picture</label><input type="file" name="profile_picture" class="form-control" accept="image/*" required></div>
        </div>
        <div class="modal-footer"><button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn-primary-custom"><i class="bi bi-upload"></i> Upload</button></div>
    </form>
</div></div></div>

<?php include '../../includes/confirm_modal.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/modal-toast.js"></script>
<script src="../../assets/js/mobile-menu.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.approve-btn').forEach(btn => btn.addEventListener('click', function(){
        const uid = this.dataset.userId, name = this.dataset.name;
        showConfirm({
            type: 'success', title: 'Approve Student Account?',
            message: 'You are about to approve the account for <strong>' + name + '</strong>.<br><br>The student will be able to log in and access the system.',
            confirmText: 'Yes, Approve',
            onConfirm: function() { window.location.href = 'approvals.php?action=approve&user_id=' + uid; }
        });
    }));
    document.querySelectorAll('.reject-btn').forEach(btn => btn.addEventListener('click', function(){
        const uid = this.dataset.userId, name = this.dataset.name;
        showConfirm({
            type: 'danger', title: 'Reject Student Account?',
            message: 'You are about to reject the account for <strong>' + name + '</strong>.<br><br>The student will not be able to access the system.',
            confirmText: 'Yes, Reject',
            onConfirm: function() { window.location.href = 'approvals.php?action=reject&user_id=' + uid; }
        });
    }));
});
</script>
</body>
</html>