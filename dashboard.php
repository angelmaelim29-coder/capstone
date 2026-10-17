<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Student') { header("Location: ../../login.php"); exit(); }

$host = 'localhost'; $user = 'root'; $pass = ''; $dbname = 'trentians_gradehub';
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

$user_id = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];

// ==========================================
// FEATURE 5: CHANGE PASSWORD LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    if ($new_pass !== $confirm_pass) {
        $_SESSION['swal_error'] = "New passwords do not match!";
    } else {
        $res = $conn->query("SELECT password_hash FROM users WHERE user_id = $user_id");
        $userData = $res->fetch_assoc();

        // Check kung password123 ang ginamit (plain text) o naka-hash
        if (password_verify($old_pass, $userData['password_hash']) || $old_pass === 'password123') {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password_hash = '$new_hash' WHERE user_id = $user_id");
            $_SESSION['swal_success'] = "Password changed successfully!";
        } else {
            $_SESSION['swal_error'] = "Current password is incorrect!";
        }
    }
    header("Location: dashboard.php");
    exit();
}

// ==========================================
// FEATURE 3: FACULTY EVALUATION LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_eval') {
    $subject_id = intval($_POST['subject_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);

    $check = $conn->query("SELECT * FROM evaluations WHERE student_id = $user_id AND subject_id = $subject_id");
    if ($check->num_rows > 0) {
        $_SESSION['swal_error'] = "You have already evaluated this subject.";
    } else {
        $conn->query("INSERT INTO evaluations (student_id, subject_id, rating, comment) VALUES ($user_id, $subject_id, $rating, '$comment')");
        $_SESSION['swal_success'] = "Evaluation submitted! Thank you for your feedback.";
    }
    header("Location: dashboard.php");
    exit();
}

// Fetch Student Info (TINANGGAL ANG section_id DITO)
$studentQuery = $conn->query("
    SELECT s.student_number, s.year_level, c.course_code, u.email, u.profile_picture 
    FROM students s 
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN courses c ON s.course_id = c.course_id 
    WHERE s.user_id = $user_id
");
$studentInfo = $studentQuery->fetch_assoc();
$userAvatar = !empty($studentInfo['profile_picture']) ? '../../assets/images/profiles/' . $studentInfo['profile_picture'] : null;

// Fetch Grades
$gradesQuery = $conn->query("
    SELECT sub.subject_code, sub.subject_name, sec.section_name, sub.subject_id,
           g.prelim_grade, g.midterm_grade, g.prefinal_grade, g.final_grade, g.status 
    FROM enrollments e 
    JOIN subjects sub ON e.subject_id = sub.subject_id 
    JOIN sections sec ON e.section_id = sec.section_id 
    LEFT JOIN grades g ON e.enrollment_id = g.enrollment_id 
    WHERE e.user_id = $user_id
");

$totalSubjects = 0; $totalUnits = 0; $totalGrades = 0; $gradedCount = 0; $gradesList = []; $enrolledSubjects = [];
if($gradesQuery) {
    while($row = $gradesQuery->fetch_assoc()) {
        $gradesList[] = $row;
        $enrolledSubjects[] = $row;
        $totalSubjects++; $totalUnits += 3; 
        if (!empty($row['final_grade']) && is_numeric($row['final_grade'])) { $totalGrades += floatval($row['final_grade']); $gradedCount++; }
    }
}
$gwa = ($gradedCount > 0) ? round($totalGrades / $gradedCount, 2) : '--';

// Fetch Schedule (INAYOS: Naka-join sa enrollments para makita ang schedule ng mga kinuhang subjects)
$scheduleQuery = $conn->query("
    SELECT sc.day_of_week, sc.start_time, sc.end_time, sc.room, sub.subject_code, sub.subject_name 
    FROM schedules sc
    JOIN subjects sub ON sc.subject_id = sub.subject_id
    JOIN enrollments e ON e.section_id = sc.section_id AND e.subject_id = sc.subject_id
    WHERE e.user_id = $user_id
    ORDER BY FIELD(sc.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'), sc.start_time
");

$announcementsQuery = $conn->query("SELECT * FROM announcements ORDER BY date_posted DESC LIMIT 3");
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Portal - TRENTIANS GradeHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* (Pareho ang CSS sa binigay ko kanina, huwag mo nang palitan para umayos agad) */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100vh; overflow: hidden; max-width: 100%; }
        :root { --navy-dark: #0f2b46; --navy: #1b3a5c; --navy-light: #2d5a7a; --gold: #ffc107; --bg: #f8fafc; --border: #e2e8f0; --text: #1e293b; --text-muted: #64748b; }
        body { background: var(--bg); font-family: -apple-system, BlinkMacSystemFont, 'Inter', 'Segoe UI', sans-serif; font-size: 14px; color: var(--text); }

        .navbar-custom { background: linear-gradient(135deg, var(--navy-dark), var(--navy)); padding: 12px 0; box-shadow: 0 2px 12px rgba(15,43,70,.25); border-bottom: 3px solid var(--gold); height: 70px; position: relative; z-index: 100; }
        .navbar-brand { color: #fff !important; font-weight: 700; font-size: 16px; display: flex; align-items: center; }
        .navbar-logo { width: 42px; height: 42px; margin-right: 12px; background: #fff; border-radius: 50%; padding: 3px; }
        .user-info { display: flex; align-items: center; gap: 12px; font-size: 13px; color: rgba(255,255,255,.9); }
        .role-badge { background: var(--gold); color: var(--navy); padding: 4px 12px; border-radius: 15px; font-weight: 700; font-size: 10px; text-transform: uppercase; }
        .btn-logout { background: rgba(255,255,255,.15); color: #fff; border: 1px solid rgba(255,255,255,.2); padding: 6px 16px; border-radius: 8px; font-size: 12px; font-weight: 500; text-decoration: none; }
        .btn-logout:hover { background: rgba(255,255,255,.25); color: #fff; }
        .nav-avatar { width: 38px; height: 38px; border-radius: 50%; object-fit: cover; border: 2px solid var(--gold); }
        .nav-avatar-initials { width: 38px; height: 38px; border-radius: 50%; background: var(--gold); color: var(--navy); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; border: 2px solid var(--gold); }

        .main-wrapper { max-width: 1400px; margin: 0 auto; padding: 24px; display: flex; gap: 24px; height: calc(100vh - 70px); overflow: hidden; }
        .sidebar { background: #ffffff; border-radius: 12px; padding: 16px 12px; border: 1px solid var(--border); width: 250px; min-width: 250px; height: 100%; overflow-y: auto; box-shadow: 0 1px 4px rgba(15,43,70,0.04); }
        .menu-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; margin: 16px 0 8px 12px; }
        .nav-item { margin-bottom: 4px; }
        .nav-link { color: #475569; padding: 10px 14px; border-radius: 8px; text-decoration: none; display: flex; align-items: center; font-size: 13.5px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; border-left: 3px solid transparent; }
        .nav-link:hover { background: #f1f5f9; color: var(--navy); }
        .nav-link.active { background: #eff6ff; color: var(--navy); font-weight: 600; border-left: 3px solid var(--navy); }
        .nav-link i { margin-right: 12px; width: 18px; font-size: 16px; color: #64748b; transition: color 0.2s; }
        .nav-link:hover i, .nav-link.active i { color: var(--navy); }

        .content { flex: 1; min-width: 0; height: 100%; overflow-y: auto; padding-right: 10px; }
        .page-header h1 { font-size: 26px; font-weight: 700; color: var(--navy); margin: 0 0 4px; }
        .page-header p { color: var(--text-muted); font-size: 13px; margin-bottom: 24px; }

        .metric-card { background: #fff; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 16px; position: relative; border-left: 4px solid var(--navy); transition: all .2s; }
        .metric-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(27,58,92,.1); border-left-color: var(--gold); }
        .metric-label { font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700; margin-bottom: 8px; }
        .metric-value { font-size: 32px; font-weight: 700; color: var(--navy); line-height: 1; }
        .metric-icon { position: absolute; top: 18px; right: 18px; font-size: 28px; opacity: .15; }
        .metric-card.gold { border-left-color: var(--gold); }
        .metric-card.green { border-left-color: #10b981; }

        .panel { background: #fff; border: 1px solid var(--border); border-radius: 12px; margin-bottom: 20px; overflow: hidden; }
        .panel-header { padding: 16px 22px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; background: #fafbfc; }
        .panel-header h3 { font-size: 15px; font-weight: 700; color: var(--navy); margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-header h3::before { content: ''; width: 3px; height: 16px; background: var(--gold); border-radius: 2px; }

        .data-table { width: 100%; margin: 0; font-size: 13px; border-collapse: collapse; }
        .data-table thead th { background: #f8fafc; font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; padding: 15px 22px; border-bottom: 2px solid var(--border); text-align: left; }
        .data-table tbody td { padding: 15px 22px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: var(--text); }
        
        .grade-val { font-size: 15px; font-weight: 700; color: var(--navy); text-align: center; }
        .grade-empty { color: #cbd5e1; text-align: center; font-weight: normal; font-size: 14px; }
        .badge-soft { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; background: #dbeafe; color: #1e40af; }

        .announcement-card { background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; border-radius: 8px; padding: 18px; margin-bottom: 12px; }
        .announcement-title { font-weight: 700; color: #92400e; font-size: 15px; margin-bottom: 6px; }
        .announcement-text { color: #78350f; font-size: 13px; line-height: 1.5; }

        .content-section { display: none; animation: fadeIn 0.3s ease; }
        .content-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .profile-card { background: white; border-radius: 12px; padding: 40px; border: 1px solid var(--border); text-align: center; }
        .profile-avatar-large { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid var(--border); margin-bottom: 20px; background: var(--navy); color: var(--gold); display: inline-flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: 700; }

        .star-rating { display: flex; flex-direction: row-reverse; justify-content: center; gap: 10px; }
        .star-rating input { display: none; }
        .star-rating label { cursor: pointer; font-size: 30px; color: #cbd5e1; transition: color 0.2s; }
        .star-rating input:checked ~ label, .star-rating label:hover, .star-rating label:hover ~ label { color: #fbbf24; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <img src="../../assets/images/logo.png" alt="TIFTCI" class="navbar-logo" onerror="this.style.display='none'">
                TRENTIANS GradeHub <span style="color: var(--gold); margin-left:5px;">Student</span>
            </a>
            <div class="ms-auto d-flex align-items-center user-info">
                <?php if ($userAvatar): ?><img src="<?php echo $userAvatar; ?>" class="nav-avatar" alt="Profile">
                <?php else: ?><div class="nav-avatar-initials"><?php echo strtoupper(substr($fullname,0,2)); ?></div><?php endif; ?>
                
                <span><?php echo htmlspecialchars($fullname); ?></span>
                <span class="role-badge">STUDENT</span>
                <a href="../../logout.php" class="btn-logout"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-wrapper">
        <aside class="sidebar">
            <div class="menu-label">Main Menu</div>
            <div class="nav-item"><a class="nav-link active" onclick="showSection('dashboard', this)"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a></div>
            <div class="nav-item"><a class="nav-link" onclick="showSection('grades', this)"><i class="bi bi-card-checklist"></i> My Grades</a></div>
            <div class="nav-item"><a class="nav-link" onclick="showSection('schedule', this)"><i class="bi bi-calendar3"></i> My Schedule</a></div>
            <div class="nav-item"><a class="nav-link" onclick="showSection('eval', this)"><i class="bi bi-star-fill"></i> Evaluate Faculty</a></div>
            <div class="menu-label" style="margin-top: 24px;">Account</div>
            <div class="nav-item"><a class="nav-link" onclick="showSection('profile', this)"><i class="bi bi-person-circle"></i> My Profile</a></div>
        </aside>

        <main class="content">
            
            <!-- DASHBOARD -->
            <div id="section-dashboard" class="content-section active">
                <div class="page-header"><h1>Welcome back, <?php echo htmlspecialchars($fullname); ?>!</h1><p>Here is your academic summary.</p></div>
                <div class="row g-3 mb-4">
                    <div class="col-lg-4"><div class="metric-card"><div class="metric-icon"><i class="bi bi-journal-bookmark-fill"></i></div><div class="metric-label">Enrolled Subjects</div><div class="metric-value"><?php echo $totalSubjects; ?></div></div></div>
                    <div class="col-lg-4"><div class="metric-card green"><div class="metric-icon"><i class="bi bi-layers-fill"></i></div><div class="metric-label">Total Units</div><div class="metric-value"><?php echo $totalUnits; ?></div></div></div>
                    <div class="col-lg-4"><div class="metric-card gold"><div class="metric-icon"><i class="bi bi-award-fill"></i></div><div class="metric-label">General Weighted Average</div><div class="metric-value"><?php echo $gwa; ?></div></div></div>
                </div>
                <?php if ($announcementsQuery && $announcementsQuery->num_rows > 0): ?>
                <div class="row mb-4"><div class="col-12"><h4 class="fw-bold mb-3" style="color:var(--navy); font-size: 18px;"><i class="bi bi-megaphone-fill text-warning me-2"></i> Latest Announcements</h4>
                    <?php while($a = $announcementsQuery->fetch_assoc()): ?>
                        <div class="announcement-card"><div class="announcement-title"><?= htmlspecialchars($a['title']) ?></div><div class="announcement-text"><?= nl2br(htmlspecialchars($a['content'])) ?></div><div class="mt-2 text-muted" style="font-size:11px;"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($a['posted_by']) ?> &bull; <?= date('M d, Y', strtotime($a['date_posted'])) ?></div></div>
                    <?php endwhile; ?>
                </div></div>
                <?php endif; ?>
            </div>

            <!-- MY GRADES -->
            <div id="section-grades" class="content-section">
                <div class="page-header"><h1>My Grades</h1></div>
                <div class="panel">
                    <div class="panel-header"><h3>Academic Grades</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Code</th><th>Title</th><th>Section</th><th class="text-center">Prelim</th><th class="text-center">Midterm</th><th class="text-center">Prefinal</th><th class="text-center">Final</th><th class="text-center">Status</th></tr></thead>
                            <tbody>
                                <?php if (count($gradesList) > 0): foreach ($gradesList as $g): ?>
                                    <tr>
                                        <td><strong style="color:var(--navy);"><?= htmlspecialchars($g['subject_code']) ?></strong></td>
                                        <td><?= htmlspecialchars($g['subject_name']) ?></td>
                                        <td><span class="badge-soft"><?= htmlspecialchars($g['section_name']) ?></span></td>
                                        <td class="grade-val <?= empty($g['prelim_grade']) ? 'grade-empty' : '' ?>"><?= $g['prelim_grade'] ?: '--' ?></td>
                                        <td class="grade-val <?= empty($g['midterm_grade']) ? 'grade-empty' : '' ?>"><?= $g['midterm_grade'] ?: '--' ?></td>
                                        <td class="grade-val <?= empty($g['prefinal_grade']) ? 'grade-empty' : '' ?>"><?= $g['prefinal_grade'] ?: '--' ?></td>
                                        <td class="grade-val <?= empty($g['final_grade']) ? 'grade-empty' : '' ?>" style="color: #166534; font-size: 16px;"><?= $g['final_grade'] ?: '--' ?></td>
                                        <td class="text-center"><?php if ($g['status'] === 'Submitted'): ?><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Finalized</span><?php else: ?><span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> In Progress</span><?php endif; ?></td>
                                    </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="8" class="text-center py-5 text-muted">No Subjects Yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- MY SCHEDULE -->
            <div id="section-schedule" class="content-section">
                <div class="page-header"><h1>My Class Schedule</h1></div>
                <div class="panel">
                    <div class="panel-header"><h3>Weekly Timetable</h3></div>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Room</th></tr></thead>
                            <tbody>
                                <?php if ($scheduleQuery && $scheduleQuery->num_rows > 0): foreach ($scheduleQuery as $s): ?>
                                <tr>
                                    <td><strong style="color:var(--navy);"><?= htmlspecialchars($s['day_of_week']) ?></strong></td>
                                    <td><?= date('h:i A', strtotime($s['start_time'])) ?> - <?= date('h:i A', strtotime($s['end_time'])) ?></td>
                                    <td>
                                        <span class="badge-soft mb-1"><?= htmlspecialchars($s['subject_code']) ?></span><br>
                                        <small class="text-muted"><?= htmlspecialchars($s['subject_name']) ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($s['room']) ?></td>
                                </tr>
                                <?php endforeach; else: ?>
                                <tr><td colspan="4" class="text-center py-5 text-muted"><i class="bi bi-calendar-x mb-2" style="font-size: 40px; display: block; color: #cbd5e1;"></i> No schedule available yet.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- EVALUATE FACULTY -->
            <div id="section-eval" class="content-section">
                <div class="page-header"><h1>Faculty Evaluation</h1></div>
                <div class="panel">
                    <div class="panel-header"><h3>Submit Evaluation</h3></div>
                    <div class="p-4">
                        <form method="POST">
                            <input type="hidden" name="action" value="submit_eval">
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small">SELECT SUBJECT / INSTRUCTOR</label>
                                <select name="subject_id" class="form-select" required>
                                    <option value="">Select subject to evaluate...</option>
                                    <?php foreach ($enrolledSubjects as $es): ?>
                                        <option value="<?= $es['subject_id'] ?>"><?= htmlspecialchars($es['subject_code'] . ' - ' . $es['subject_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-4 text-center">
                                <label class="form-label fw-bold text-muted small d-block mb-3">OVERALL RATING</label>
                                <div class="star-rating">
                                    <input type="radio" id="star5" name="rating" value="5" required/><label for="star5" class="bi bi-star-fill"></label>
                                    <input type="radio" id="star4" name="rating" value="4"/><label for="star4" class="bi bi-star-fill"></label>
                                    <input type="radio" id="star3" name="rating" value="3"/><label for="star3" class="bi bi-star-fill"></label>
                                    <input type="radio" id="star2" name="rating" value="2"/><label for="star2" class="bi bi-star-fill"></label>
                                    <input type="radio" id="star1" name="rating" value="1"/><label for="star1" class="bi bi-star-fill"></label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-muted small">COMMENTS (Optional)</label>
                                <textarea name="comment" class="form-control" rows="4" placeholder="Share your feedback..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100 py-2" style="background:var(--navy); border:none; font-weight:600;"><i class="bi bi-send-fill me-2"></i> Submit Evaluation</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- MY PROFILE -->
            <div id="section-profile" class="content-section">
                <div class="page-header"><h1>My Profile</h1></div>
                <div class="profile-card">
                    <?php if ($userAvatar): ?><img src="<?php echo $userAvatar; ?>" class="profile-avatar-large" alt="Profile Picture">
                    <?php else: ?><div class="profile-avatar-large"><?php echo strtoupper(substr($fullname, 0, 1)); ?></div><?php endif; ?>
                    
                    <h2 class="fw-bold text-primary mb-1"><?= htmlspecialchars($fullname) ?></h2>
                    <p class="text-muted mb-4"><i class="bi bi-person-badge"></i> Student Account</p>

                    <div class="row justify-content-center">
                        <div class="col-md-10">
                            <div class="bg-light p-4 rounded border mb-4 text-start">
                                <div class="row mb-3"><div class="col-sm-4 text-muted fw-bold">Student Number</div><div class="col-sm-8 fw-semibold"><?= htmlspecialchars($studentInfo['student_number'] ?? 'N/A') ?></div></div>
                                <div class="row mb-3"><div class="col-sm-4 text-muted fw-bold">Email Address</div><div class="col-sm-8 fw-semibold"><?= htmlspecialchars($studentInfo['email'] ?? 'N/A') ?></div></div>
                                <div class="row mb-3"><div class="col-sm-4 text-muted fw-bold">Strand / Course</div><div class="col-sm-8 fw-semibold"><?= htmlspecialchars($studentInfo['course_code'] ?? 'N/A') ?></div></div>
                                <div class="row"><div class="col-sm-4 text-muted fw-bold">Year Level</div><div class="col-sm-8 fw-semibold"><?= htmlspecialchars($studentInfo['year_level'] ?? 'N/A') ?> Year</div></div>
                            </div>

                            <h5 class="fw-bold mb-3 text-start" style="color:var(--navy);">Update Profile Picture</h5>
                            <form method="POST" action="../../api/upload_profile.php" enctype="multipart/form-data" class="mb-5">
                                <div class="input-group">
                                    <input type="file" name="profile_picture" class="form-control" accept="image/jpeg, image/png" required>
                                    <button type="submit" class="btn btn-primary" style="background:var(--navy); border:none;"><i class="bi bi-upload"></i> Upload</button>
                                </div>
                            </form>

                            <h5 class="fw-bold mb-3 text-start" style="color:var(--navy); border-top: 1px solid var(--border); padding-top: 20px;">Change Password</h5>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label text-muted small fw-bold text-start d-block">Current Password</label>
                                        <input type="password" name="old_password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold text-start d-block">New Password</label>
                                        <input type="password" name="new_password" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small fw-bold text-start d-block">Confirm New Password</label>
                                        <input type="password" name="confirm_password" class="form-control" required>
                                    </div>
                                    <div class="col-md-12 mt-4">
                                        <button type="submit" class="btn btn-primary w-100" style="background:var(--navy); border:none;"><i class="bi bi-shield-lock-fill me-2"></i> Update Password</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        function showSection(sectionName, element) {
            document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
            document.querySelectorAll('.sidebar .nav-link').forEach(link => link.classList.remove('active'));
            document.getElementById('section-' + sectionName).classList.add('active');
            element.classList.add('active');
        }

        <?php if (isset($_SESSION['swal_success'])): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?php echo $_SESSION['swal_success']; ?>', timer: 2500, showConfirmButton: false });
            <?php unset($_SESSION['swal_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['swal_error'])): ?>
            Swal.fire({ icon: 'error', title: 'Oops...', text: '<?php echo $_SESSION['swal_error']; ?>' });
            <?php unset($_SESSION['swal_error']); ?>
        <?php endif; ?>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>