<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('student'); // التأكد من أن المستخدم طالب

$student_id = $_SESSION['user_id'];

// --- (الكود الخاص بجلب البيانات يبقى كما هو) ---
// Get student's grades
$grades_stmt = $pdo->prepare("
    SELECT g.*, s.name as subject_name, c.name as classroom_name,
           u.first_name as teacher_first, u.last_name as teacher_last
    FROM grades g
    JOIN subjects s ON g.subject_id = s.id
    JOIN classrooms c ON g.classroom_id = c.id
    JOIN users u ON g.teacher_id = u.id
    WHERE g.student_id = ?
    ORDER BY g.graded_at DESC
");
$grades_stmt->execute([$student_id]);
$grades = $grades_stmt->fetchAll();

// --- (الكود الخاص بحساب الإحصائيات يبقى كما هو) ---
$total_grades = count($grades);
$average_grade = 0;
$subject_count = 0;
if ($total_grades > 0) {
    $sum = 0;
    $subject_ids = [];
    foreach ($grades as $grade) {
         if (is_numeric($grade['grade'])) {
            $sum += $grade['grade'];
            $subject_ids[] = $grade['subject_id'];
         }
    }
    $average_grade = $sum / $total_grades;
    $subject_count = count(array_unique($subject_ids));
}

// Get grade distribution
$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
foreach ($grades as $grade) {
    if (is_numeric($grade['grade'])) {
        $letter_grade = calculateGradePoint($grade['grade']); // Assuming this returns 'A', 'B', etc.
        if (array_key_exists($letter_grade, $grade_distribution)) {
             $grade_distribution[$letter_grade]++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بوابة الطالب - نظام الدرجات</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* استايلات مشابهة للصفحات الأخرى */
        .admin-tabs { /* Using same class name */
            display: flex; margin-bottom: 25px; border-bottom: 2px solid #eee;
            background-color: #f9f9f9; padding: 5px; border-radius: 8px 8px 0 0; flex-wrap: wrap;
        }
        .admin-tabs button {
            background: transparent; border: none; padding: 12px 20px; cursor: pointer;
            font-size: 16px; font-weight: bold; color: #555; transition: color 0.3s, border-bottom 0.3s;
            border-bottom: 3px solid transparent; margin-left: 5px;
        }
        .admin-tabs button.active { color: #00447C; border-bottom-color: #00447C; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px; margin-bottom: 30px; text-align: center;
        }
        .stat-card-small {
            background: #fff; padding: 15px; border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08); border: 1px solid #eee;
        }
         .stat-card-small h3 { font-size: 1.8em; margin-bottom: 5px; color: #00447C; }
         .stat-card-small small { color: #555; font-weight: bold; }

        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); overflow-x: auto;
        }
        .data-table th, .data-table td { border: 1px solid #ddd; padding: 10px; text-align: right; white-space: nowrap; }
        .data-table th { background-color: #f2f2f2; font-weight: bold; color: #333; }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }

        .badge { display: inline-block; padding: 4px 8px; font-size: 0.9em; font-weight: bold; border-radius: 4px; color: white; }
        .bg-success { background-color: #28a745; }
        .bg-info { background-color: #17a2b8; }
        .bg-warning { background-color: #ffc107; color: #333 !important; }
        .bg-danger { background-color: #dc3545; }
        .bg-dark { background-color: #343a40; }
        .bg-secondary { background-color: #6c757d; }

        /* Grade distribution styling */
        .dist-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
        .dist-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .dist-label { font-weight: bold; }
        .dist-bar-container { flex-grow: 1; margin: 0 15px; background-color: #e9ecef; border-radius: 5px; height: 10px; overflow: hidden;}
        .dist-bar { height: 100%; border-radius: 5px; transition: width 0.5s ease-in-out;}
        .dist-count { font-size: 14px; color: #555; min-width: 60px; text-align: left;}

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
                    <h2><i class="fas fa-user-graduate"></i> بوابة الطالب</h2>
                    <span>مرحباً، <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! [<a href="logout.php" style="color: red; text-decoration: none;">تسجيل الخروج</a>]</span>
                </div>

                <div class="admin-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'dashboard')">
                        <i class="fas fa-tachometer-alt me-2"></i> لوحة المعلومات
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'grades')">
                        <i class="fas fa-list-alt me-2"></i> درجاتي بالتفصيل
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'performance')">
                        <i class="fas fa-chart-line me-2"></i> تحليل الأداء
                    </button>
                </div>

                <div id="dashboard" class="tab-content active">
                    <h3>نظرة عامة على أدائك</h3>
                    <div class="stats-grid">
                        <div class="stat-card-small">
                            <h3><?php echo $total_grades; ?></h3>
                            <small>إجمالي الدرجات المرصودة</small>
                        </div>
                        <div class="stat-card-small">
                             <h3 style="color: <?php echo getGradeColor($average_grade); ?>;"><?php echo number_format($average_grade, 1); ?></h3>
                            <small>متوسط الدرجات العام</small>
                        </div>
                        <div class="stat-card-small">
                             <h3 style="color: <?php echo getGradeColor($average_grade); ?>;"><?php echo calculateGradePoint($average_grade); ?></h3>
                            <small>التقدير العام</small>
                        </div>
                        <div class="stat-card-small">
                             <h3><?php echo $subject_count; ?></h3>
                            <small>عدد المواد</small>
                        </div>
                    </div>

                    <div style="margin-top: 30px;">
                        <h3>آخر الدرجات المرصودة</h3>
                        <?php if (empty($grades)): ?>
                             <p>لم يتم رصد أي درجات لك حتى الآن.</p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>المادة</th>
                                            <th>الدرجة</th>
                                            <th>التقدير</th>
                                            <th>النوع</th>
                                            <th>المدرس</th>
                                            <th>التاريخ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach(array_slice($grades, 0, 5) as $grade): // Show only the latest 5
                                            $letter_grade = calculateGradePoint($grade['grade'] ?? 0);
                                            $grade_color = getGradeColor($grade['grade'] ?? 0);
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $grade_color; ?>">
                                                    <?php echo htmlspecialchars(is_numeric($grade['grade']) ? number_format($grade['grade'], 1) : 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td><strong><?php echo $letter_grade; ?></strong></td>
                                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($grade['grade_type'])); ?></span></td>
                                            <td><?php echo htmlspecialchars($grade['teacher_first'] . ' ' . $grade['teacher_last']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($grade['graded_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($grades) > 5): ?>
                                <p style="margin-top: 15px; text-align: center;"><a href="#" onclick="openTab(event, 'grades'); return false;">عرض كل الدرجات...</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="grades" class="tab-content">
                    <h3>جميع درجاتي</h3>
                    <?php if (empty($grades)): ?>
                         <p>لم يتم رصد أي درجات لك حتى الآن.</p>
                    <?php else: ?>
                         <div style="overflow-x: auto;">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>المادة</th>
                                        <th>الفصل</th>
                                        <th>الدرجة</th>
                                        <th>التقدير</th>
                                        <th>النوع</th>
                                        <th>المدرس</th>
                                        <th>الملاحظات</th>
                                        <th>تاريخ الرصد</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($grades as $grade):
                                        $letter_grade = calculateGradePoint($grade['grade'] ?? 0);
                                        $grade_color = getGradeColor($grade['grade'] ?? 0);
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['classroom_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $grade_color; ?>">
                                                <?php echo htmlspecialchars(is_numeric($grade['grade']) ? number_format($grade['grade'], 1) : 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td><strong><?php echo $letter_grade; ?></strong></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($grade['grade_type'])); ?></span></td>
                                        <td><?php echo htmlspecialchars($grade['teacher_first'] . ' ' . $grade['teacher_last']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['remarks']); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($grade['graded_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="performance" class="tab-content">
                    <h3>تحليل الأداء</h3>
                     <div class="grid-container">
                         <div class="contact-form"> <h4 style="text-align: center; margin-bottom: 20px;">ملخص الأداء</h4>
                            <div style="text-align: center; margin-bottom: 25px;">
                                <h1 style="font-size: 3.5em; color: <?php echo getGradeColor($average_grade); ?>; margin-bottom: 0;">
                                    <?php echo number_format($average_grade, 1); ?>
                                </h1>
                                <p style="font-size: 1.2em; font-weight: bold; color: #555;">المتوسط العام</p>
                                <h2 style="color: <?php echo getGradeColor($average_grade); ?>;"><?php echo calculateGradePoint($average_grade); ?></h2>
                            </div>
                             <hr style="margin: 20px 0;">
                            <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;"> <div class="stat-card-small">
                                    <h3><?php echo $total_grades; ?></h3>
                                    <small>إجمالي الدرجات</small>
                                </div>
                                <div class="stat-card-small">
                                     <h3><?php echo $subject_count; ?></h3>
                                    <small>عدد المواد</small>
                                </div>
                            </div>
                         </div>

                        <div class="contact-form">
                            <h4 style="text-align: center; margin-bottom: 20px;">توزيع التقديرات</h4>
                            <?php foreach($grade_distribution as $letter => $count):
                                $percentage = $total_grades > 0 ? ($count / $total_grades) * 100 : 0;
                                $color_class = '';
                                switch($letter) {
                                    case 'A': $color_class = 'bg-success'; break;
                                    case 'B': $color_class = 'bg-info'; break;
                                    case 'C': $color_class = 'bg-warning'; break;
                                    case 'D': $color_class = 'bg-danger'; break;
                                    case 'F': $color_class = 'bg-dark'; break;
                                }
                            ?>
                            <div class="dist-item">
                                <span class="dist-label">تقدير <?php echo $letter; ?></span>
                                <div class="dist-bar-container">
                                    <div class="dist-bar <?php echo $color_class; ?>" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span class="dist-count"><?php echo $count; ?> (<?php echo round($percentage); ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
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

    <script>
        // --- وظيفة فتح التبويبات ---
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }
            var selectedTab = document.getElementById(tabName);
            if (selectedTab) { // Check if element exists
                selectedTab.style.display = "block";
                selectedTab.classList.add("active");
            }
            if (evt && evt.currentTarget) { // Check if event and target exist
                evt.currentTarget.classList.add("active");
            }
        }
        // إظهار التبويب الأول افتراضياً
         document.addEventListener('DOMContentLoaded', function() {
             var dashboardTab = document.getElementById('dashboard');
             if (dashboardTab) {
                dashboardTab.style.display = 'block';
             }
             // Activate first tab button visually
             var firstTabButton = document.querySelector('.admin-tabs .tab-button');
             if (firstTabButton) {
                 firstTabButton.classList.add('active');
             }
         });
    </script>

</body>
</html>
