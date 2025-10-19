<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('teacher'); // التأكد من أن المستخدم هو مدرس

$teacher_id = $_SESSION['user_id'];

// --- جلب بيانات الفصول والطلاب الخاصة بالمدرس ---
// (الكود الخاص بجلب البيانات يبقى كما هو)
// Get teacher's classrooms
$classrooms_stmt = $pdo->prepare("SELECT * FROM classrooms WHERE teacher_id = ? ORDER BY name");
$classrooms_stmt->execute([$teacher_id]);
$teacher_classrooms = $classrooms_stmt->fetchAll();

// Get students in teacher's classrooms
$students_stmt = $pdo->prepare("
    SELECT u.id, u.first_name, u.last_name, u.username, c.name as classroom_name
    FROM users u
    JOIN classroom_students cs ON u.id = cs.student_id
    JOIN classrooms c ON cs.classroom_id = c.id
    WHERE c.teacher_id = ?
    ORDER BY c.name, u.first_name
");
$students_stmt->execute([$teacher_id]);
$students = $students_stmt->fetchAll();

// Get total grades assigned by this teacher (Example - you might need a more specific query)
$grades_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE teacher_id = ?");
$grades_count_stmt->execute([$teacher_id]);
$total_grades_assigned = $grades_count_stmt->fetchColumn();


?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدرس - نظام الدرجات</title>

    <link rel="stylesheet" href="../../style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        /* استايلات التبويبات */
        .admin-tabs { /* Using same class name for consistency */
            display: flex;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
            background-color: #f9f9f9;
            padding: 5px;
            border-radius: 8px 8px 0 0;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }
        .admin-tabs button {
            background-color: transparent;
            border: none;
            padding: 12px 20px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            color: #555;
            transition: color 0.3s, border-bottom 0.3s;
            border-bottom: 3px solid transparent;
            margin-left: 5px;
        }
        .admin-tabs button.active {
            color: #00447C;
            border-bottom-color: #00447C;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* استايلات بطاقات الإحصائيات */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08);
            text-align: center;
        }
        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #005A9E;
        }
        .stat-card h4 {
            font-size: 2em;
            margin-bottom: 5px;
            color: #333;
        }
        .stat-card p { margin: 0; color: #666; font-weight: bold; }

        /* استايلات الجداول (نفس كلاس الأدمن) */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow-x: auto; /* For responsiveness */
        }
        .data-table th, .data-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: right;
            white-space: nowrap; /* Prevent text wrapping */
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold; color: #333;
        }
        .data-table tr:nth-child(even) { background-color: #f9f9f9; }
        .data-table tr:hover { background-color: #f1f1f1; }
        .data-table .actions a, .data-table .actions button {
            margin: 0 3px; padding: 5px 8px; font-size: 13px;
            text-decoration: none; border: 1px solid; border-radius: 4px;
            cursor: pointer; display: inline-block; /* Ensure buttons align */
        }
         .data-table .actions .btn-view { border-color: #007bff; color: #007bff; background: transparent;}
         .data-table .actions .btn-add { border-color: #28a745; color: #28a745; background: transparent;}
         .data-table .actions a:hover, .data-table .actions button:hover { opacity: 0.7; }

        /* Card for classrooms */
        .classroom-card {
             background-color: #fff;
             border: 1px solid #ddd;
             border-radius: 8px;
             padding: 15px;
             margin-bottom: 15px;
             box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
         .classroom-card h5 { margin-top: 0; color: #00447C; }
         .classroom-card p { color: #555; font-size: 14px; margin-bottom: 10px;}

         /* Use submit-btn style for Manage button */
         .classroom-card .manage-btn {
             display: inline-block;
             padding: 8px 15px;
             background-color: #00447C;
             color: #fff;
             border: none;
             border-radius: 5px;
             font-size: 14px;
             text-decoration: none;
             transition: background-color 0.3s ease;
         }
         .classroom-card .manage-btn:hover { background-color: #005A9E; }

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
                    <h2>لوحة تحكم المدرس</h2>
                    <span>مرحباً، <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! [<a href="logout.php" style="color: red; text-decoration: none;">تسجيل الخروج</a>]</span>
                </div>

                <div class="admin-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'dashboard')">
                        <i class="fas fa-tachometer-alt me-2"></i> لوحة المعلومات
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'students')">
                        <i class="fas fa-user-graduate me-2"></i> طلابي
                    </button>
                    <button type="button" onclick="window.location.href='grades.php'">
                        <i class="fas fa-edit me-2"></i> إدارة الدرجات
                    </button>
                    <button type="button" onclick="window.location.href='classroom.php'">
                         <i class="fas fa-chalkboard me-2"></i> فصولي الدراسية
                    </button>
                </div>

                <div id="dashboard" class="tab-content active">
                    <h3>نظرة عامة</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <i class="fas fa-chalkboard"></i>
                            <h4><?php echo count($teacher_classrooms); ?></h4>
                            <p>فصولي الدراسية</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-user-graduate"></i>
                            <h4><?php echo count($students); ?></h4>
                            <p>إجمالي طلابي</p>
                        </div>
                         <div class="stat-card">
                             <i class="fas fa-edit"></i>
                             <h4><?php echo $total_grades_assigned; ?></h4>
                            <p>الدرجات المرصودة</p>
                        </div>
                    </div>

                    <h3>فصولي الدراسية</h3>
                    <div class="row"> <?php if (empty($teacher_classrooms)): ?>
                            <p>لم يتم تعيين أي فصول دراسية لك بعد.</p>
                       <?php else: ?>
                           <?php foreach($teacher_classrooms as $classroom): ?>
                           <div class="col-md-6 col-lg-4"> <div class="classroom-card">
                                   <h5><?php echo htmlspecialchars($classroom['name']); ?></h5>
                                   <p><?php echo htmlspecialchars($classroom['description'] ?: 'لا يوجد وصف'); ?></p>
                                   <a href="classroom.php?id=<?php echo $classroom['id']; ?>" class="manage-btn">إدارة الفصل</a>
                               </div>
                           </div>
                           <?php endforeach; ?>
                       <?php endif; ?>
                    </div>
                </div>

                <div id="students" class="tab-content">
                    <h3>قائمة طلابي</h3>
                    <?php if (empty($students)): ?>
                         <p>لا يوجد طلاب مسجلين في فصولك الدراسية حالياً.</p>
                    <?php else: ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>اسم الطالب</th>
                                    <th>اسم المستخدم</th>
                                    <th>الفصل الدراسي</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['username']); ?></td>
                                    <td><?php echo htmlspecialchars($student['classroom_name']); ?></td>
                                    <td class="actions">
                                        <a href="grades.php?student_id=<?php echo $student['id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> عرض الدرجات
                                        </a>
                                        <a href="grades.php?action=add&student_id=<?php echo $student['id']; ?>" class="btn-add">
                                            <i class="fas fa-plus"></i> إضافة درجة
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
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
        // --- وظيفة فتح التبويبات (نفس كود الأدمن) ---
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
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        // إظهار التبويب الأول افتراضياً
         document.addEventListener('DOMContentLoaded', function() {
             document.getElementById('dashboard').style.display = 'block';
         });
    </script>

</body>
</html>
