<?php
include 'includes/conn.php';
include 'includes/functions.php';

// التأكد من أن المستخدم هو مدرس
if (!isLoggedIn() || !hasRole('teacher')) {
    redirect('index.php');
}

$teacher_id = $_SESSION['user_id'];
$message = '';
$message_type = ''; // success or danger

// --- (الكود الخاص بمعالجة البيانات يبقى كما هو) ---
// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_grade'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $classroom_id = $_POST['classroom_id'];
    $grade = $_POST['grade'];
    $grade_type = $_POST['grade_type'];
    $remarks = $_POST['remarks'];

    // Basic validation
    if (!empty($student_id) && !empty($subject_id) && !empty($classroom_id) && is_numeric($grade) && $grade >= 0 && $grade <= 100) {
        $stmt = $pdo->prepare("INSERT INTO grades (student_id, subject_id, classroom_id, teacher_id, grade, grade_type, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $subject_id, $classroom_id, $teacher_id, $grade, $grade_type, $remarks]);
        $message = "تمت إضافة الدرجة بنجاح!";
        $message_type = "success";
    } else {
         $message = "بيانات غير صالحة. يرجى التحقق من المدخلات.";
         $message_type = "danger";
    }
}

// Handle grade deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete_grade' && isset($_GET['id'])) {
    $grade_id = $_GET['id'];

    $result = deleteGrade($pdo, $grade_id, $teacher_id); // deleteGrade function needs to verify teacher_id owns the grade
    if ($result === true) {
        $message = "تم حذف الدرجة بنجاح!";
        $message_type = "success";
    } else {
        $message = $result; // Contains error message
        $message_type = "danger";
    }
     // Redirect to avoid re-action on refresh
    header("Location: grades.php?msg=" . urlencode($message) . "&msg_type=" . $message_type);
    exit;
}
// Get message from URL
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
    $message_type = $_GET['msg_type'];
}

// --- (الكود الخاص بجلب البيانات يبقى كما هو) ---
// Get teacher's classrooms
$classrooms_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY name");
$classrooms_stmt->execute([$teacher_id]);
$teacher_classrooms = $classrooms_stmt->fetchAll();

// Get subjects
$subjects_stmt = $pdo->query("SELECT * FROM subjects ORDER BY name");
$subjects = $subjects_stmt->fetchAll();

// Get students assigned ONLY to this teacher's classrooms
$students_stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name
    FROM users u
    JOIN classroom_students cs ON u.id = cs.student_id
    JOIN classrooms c ON cs.classroom_id = c.id
    WHERE c.teacher_id = ? AND u.role = 'student'
    ORDER BY u.first_name, u.last_name
");
$students_stmt->execute([$teacher_id]);
$students = $students_stmt->fetchAll();


// Get grades entered by THIS teacher
$grades_stmt = $pdo->prepare("
    SELECT g.*, u.first_name, u.last_name, s.name as subject_name, c.name as classroom_name
    FROM grades g
    JOIN users u ON g.student_id = u.id
    JOIN subjects s ON g.subject_id = s.id
    JOIN classrooms c ON g.classroom_id = c.id
    WHERE g.teacher_id = ?
    ORDER BY g.graded_at DESC
");
$grades_stmt->execute([$teacher_id]);
$grades = $grades_stmt->fetchAll();

// Calculate statistics based ONLY on grades entered by this teacher
$total_grades = count($grades);
$average_grade = 0;
if ($total_grades > 0) {
    $sum = 0;
    foreach ($grades as $grade_item) { // Renamed variable to avoid conflict
        if (is_numeric($grade_item['grade'])) {
             $sum += $grade_item['grade'];
        }
    }
    $average_grade = $sum / $total_grades;
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الدرجات - نظام الدرجات</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* استايلات مشابهة لصفحة الأدمن و teacher.php */
         .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Smaller columns for stats */
            gap: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .stat-card-small { /* Renamed for clarity */
            background-color: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            border: 1px solid #eee;
        }
         .stat-card-small h3 { font-size: 1.8em; margin-bottom: 5px; color: #00447C; }
         .stat-card-small small { color: #555; font-weight: bold; }

         .grading-scale-table {
             width: 100%;
             font-size: 14px;
             border-collapse: collapse;
         }
         .grading-scale-table td { padding: 5px; border: 1px solid #eee; }
         .grading-scale-table span.badge { font-size: 1em; padding: 3px 6px; border-radius: 4px; color: white; }
         /* Define badge colors */
        .bg-success { background-color: #28a745; }
        .bg-info { background-color: #17a2b8; }
        .bg-warning { background-color: #ffc107; color: #333 !important; } /* Text color for yellow */
        .bg-danger { background-color: #dc3545; }
        .bg-dark { background-color: #343a40; }
        .bg-secondary { background-color: #6c757d; } /* For grade type */


        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto;
        }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 10px; text-align: right; white-space: nowrap; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; color: #333; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .data-table .actions a {
            margin: 0 3px; padding: 5px 8px; font-size: 13px; text-decoration: none;
            border: 1px solid #dc3545; color: #dc3545; border-radius: 4px; cursor: pointer;
        }
         .data-table .actions a:hover { opacity: 0.7; }
    </style>
</head>
<body>

    <header>
        <div class="container">
            <div class="logo">
                <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة جامعة بني سويف">
                <h1>كلية الهندسة</h1>
            </div>
            <nav>
                <ul>
                    <li class="dropdown">
                        <a href="index.html">الرئيسية</a>
                        <ul class="dropdown-content">
                            <li><a href="dean-word.html">كلمة عميد الكلية</a></li>
                            <li><a href="vision-mission.html">الرؤية والرسالة والأهداف</a></li>
                            <li><a href="regulations.html">اللائحة الداخلية</a></li>
                            <li><a href="laws-regulations.html">القوانين واللوائح</a></li>
                        </ul>
                    </li>
                    <li><a href="about.html">عن الكلية</a></li>
                    <li class="dropdown">
                        <a href="#">الأقسام العلمية</a>
                        <ul class="dropdown-content">
                            <li><a href="civil-engineering.html">قسم الهندسة المدنية</a></li>
                            <li><a href="architectural-engineering.html">قسم الهندسة المعمارية</a></li>
                            <li><a href="electrical-engineering.html">قسم الهندسة الكهربية</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">البرامج الدراسية</a>
                        <ul class="dropdown-content">
                           <li><a href="structural-engineering.html">برنامج الهندسة الإنشائية</a></li>
                        </ul>
                    </li>
                    <li class="dropdown">
                        <a href="#">الطلاب</a>
                        <ul class="dropdown-content">
                            <li><a href="http://www.email.bsu.edu.eg/_BSU_Std.aspx" target="_blank">الحصول على البريد الإلكتروني</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=284" target="_blank">منتدى الطلاب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=286" target="_blank">المدن الجامعية</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=277" target="_blank">رعاية الشباب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=53" target="_blank">إدارة الوافدين</a></li>
                            <li><a href="http://www.results.bsu.edu.eg/" target="_blank">نتائج الكليات</a></li>
                            <li><a href="http://www.bsu.edu.eg/Sector_Home.aspx?cat_id=283" target="_blank">التربية العسكرية</a></li>
                            <li><a href="http://www.payment.bsu.edu.eg/services/" target="_blank">خدمات الدفع الإلكتروني</a></li>
                        </ul>
                    </li>
                    
                    <li class="dropdown">
                    <a href="#">الخدمات</a>
                    <ul class="dropdown-content">
                        <li><a href="services/certificates.php">البحث عن الشهادات</a></li>
                        <li><a href="services/lectures.php">المحاضرات الدراسية</a></li>
                    </ul>
                </li>
                    <li><a href="student-grades/src/index.php">درجات الطلاب</a></li>
                    <li><a href="contact.html">اتصل بنا</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="container">
            <section class="page-content">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap;">
                    <h2><i class="fas fa-edit"></i> إدارة الدرجات</h2>
                    <span>مرحباً، <?php echo htmlspecialchars($_SESSION['first_name']); ?>! [<a href="logout.php" style="color: red; text-decoration: none;">تسجيل الخروج</a>]</span>
                </div>
                 <a href="teacher.php" class="submit-btn" style="display:inline-block; width: auto; margin-bottom: 20px; background-color:#6c757d; font-size: 14px; padding: 8px 15px;">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                 </a>

                <?php if ($message): ?>
                    <div class="message" style="padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; font-weight: bold; background-color: <?php echo $message_type === 'success' ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo $message_type === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>; color: <?php echo $message_type === 'success' ? '#155724' : '#721c24'; ?>;">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="grid-container">
                    <div class="contact-form">
                        <h3><i class="fas fa-plus-circle"></i> إضافة درجة جديدة</h3>
                        <form method="POST">
                            <div class="form-group">
                                <label>الطالب</label>
                                <select name="student_id" required>
                                    <option value="">-- اختر الطالب --</option>
                                    <?php foreach($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>" <?php echo (isset($_GET['student_id']) && $_GET['student_id'] == $student['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>المادة</label>
                                    <select name="subject_id" required>
                                        <option value="">-- اختر المادة --</option>
                                        <?php foreach($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>الفصل الدراسي</label>
                                    <select name="classroom_id" required>
                                        <option value="">-- اختر الفصل --</option>
                                        <?php foreach($teacher_classrooms as $classroom): ?>
                                        <option value="<?php echo $classroom['id']; ?>"><?php echo htmlspecialchars($classroom['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                             <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <div class="form-group">
                                    <label>الدرجة (من 100)</label>
                                    <input type="number" name="grade" min="0" max="100" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <label>نوع الدرجة</label>
                                    <select name="grade_type" required>
                                        <option value="">-- اختر النوع --</option>
                                        <option value="quiz">اختبار قصير</option>
                                        <option value="assignment">واجب</option>
                                        <option value="exam">امتحان</option>
                                        <option value="project">مشروع</option>
                                        <option value="participation">مشاركة</option>
                                        <option value="other">أخرى</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>ملاحظات (اختياري)</label>
                                <textarea name="remarks" rows="2"></textarea>
                            </div>
                            <button type="submit" name="add_grade" class="submit-btn">
                                <i class="fas fa-save"></i> إضافة الدرجة
                            </button>
                        </form>
                    </div>

                    <div>
                        <div class="contact-form" style="margin-bottom: 30px;">
                            <h3><i class="fas fa-chart-bar"></i> إحصائيات الدرجات (المرصودة بواسطتك)</h3>
                            <div class="stats-grid">
                                <div class="stat-card-small">
                                    <h3><?php echo $total_grades; ?></h3>
                                    <small>إجمالي الدرجات</small>
                                </div>
                                <div class="stat-card-small">
                                     <h3><?php echo number_format($average_grade, 1); ?></h3>
                                    <small>متوسط الدرجات</small>
                                </div>
                                <div class="stat-card-small">
                                    <h3><?php echo count($teacher_classrooms); ?></h3>
                                    <small>فصولي</small>
                                </div>
                            </div>
                        </div>

                         <div class="contact-form">
                             <h3><i class="fas fa-info-circle"></i> مقياس التقديرات</h3>
                             <table class="grading-scale-table">
                                 <tr><td>90-100</td><td><span class="badge bg-success">A</span></td><td>ممتاز</td></tr>
                                 <tr><td>80-89</td><td><span class="badge bg-info">B</span></td><td>جيد جداً</td></tr>
                                 <tr><td>70-79</td><td><span class="badge bg-warning">C</span></td><td>جيد</td></tr>
                                 <tr><td>60-69</td><td><span class="badge bg-danger">D</span></td><td>مقبول</td></tr>
                                 <tr><td>أقل من 60</td><td><span class="badge bg-dark">F</span></td><td>راسب</td></tr>
                             </table>
                         </div>
                    </div>
                </div>

                <div style="margin-top: 30px;">
                    <h3><i class="fas fa-list"></i> الدرجات المرصودة مؤخراً (بواسطتك)</h3>
                     <div style="overflow-x: auto;"> <table class="data-table">
                            <thead>
                                <tr>
                                    <th>الطالب</th>
                                    <th>المادة</th>
                                    <th>الفصل</th>
                                    <th>الدرجة</th>
                                    <th>النوع</th>
                                    <th>الملاحظات</th>
                                    <th>التاريخ</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($grades)): ?>
                                    <tr><td colspan="8" style="text-align: center;">لم تقم برصد أي درجات بعد.</td></tr>
                                <?php else: ?>
                                    <?php foreach($grades as $grade_item): // Use different variable name
                                        // Calculate grade letter/color (assuming functions exist)
                                        $grade_point = calculateGradePoint($grade_item['grade'] ?? 0); // Handle potential null grade
                                        $grade_color = getGradeColor($grade_item['grade'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade_item['first_name'] . ' ' . $grade_item['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade_item['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade_item['classroom_name']); ?></td>
                                        <td>
                                             <span class="badge bg-<?php echo $grade_color; ?>">
                                                <?php echo htmlspecialchars(is_numeric($grade_item['grade']) ? number_format($grade_item['grade'], 1) : 'N/A'); ?> (<?php echo $grade_point; ?>)
                                             </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($grade_item['grade_type'])); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade_item['remarks']); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($grade_item['graded_at'])); ?></td>
                                        <td class="actions">
                                            <a href="?action=delete_grade&id=<?php echo $grade_item['id']; ?>"
                                               onclick="return confirm('هل أنت متأكد من حذف هذه الدرجة؟')">
                                               <i class="fas fa-trash"></i> حذف
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2025 كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

</body>
</html>
