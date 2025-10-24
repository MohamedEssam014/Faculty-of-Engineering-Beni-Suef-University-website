<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('teacher');

$teacher_id = $_SESSION['user_id'];

$classrooms_stmt = $pdo->prepare("SELECT id, name, description FROM classrooms WHERE teacher_id = ? ORDER BY name");
$classrooms_stmt->execute([$teacher_id]);
$teacher_classrooms = $classrooms_stmt->fetchAll(PDO::FETCH_ASSOC);

$students_stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.first_name, u.last_name, u.username, c.name as classroom_name
    FROM users u
    JOIN classroom_students cs ON u.id = cs.student_id
    JOIN classrooms c ON cs.classroom_id = c.id
    WHERE c.teacher_id = ? AND u.role = 'student'
    ORDER BY c.name, u.first_name
");
$students_stmt->execute([$teacher_id]);
$students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

$grades_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM grades WHERE teacher_id = ?");
$grades_count_stmt->execute([$teacher_id]);
$total_grades_assigned = $grades_count_stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>لوحة تحكم المدرس - نظام الدرجات</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bsu_dark_blue: '#1e40af',
                        bsu_light_gray: '#f3f4f6',
                        bsu_medium_blue: '#3b82f6',
                        bsu_gold: '#FFC107',
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                    keyframes: {
                         fadeIn: {
                            '0%': { opacity: '0'},
                            '100%': { opacity: '1'},
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out forwards',
                    }
                },
            },
        }
    </script>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'cairo', sans-serif;
        }
        nav ul li .dropdown-content {
            display: none; position: absolute; background: white;
            min-width: 200px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px; padding: 0.5rem 0; z-index: 50;
            opacity: 0; transform: translateY(10px);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        nav ul li:hover > .dropdown-content { display: block; opacity: 1; transform: translateY(0); }
        .dropdown-content li a {
            display: block; padding: 10px 15px; color: #374151;
            transition: background-color 0.2s ease, padding-right 0.2s ease, color 0.2s ease; white-space: nowrap;
        }
        .dropdown-content li a:hover {
            background-color: #f3f4f6; padding-right: 20px; color: #1e40af;
        }
        header { border-bottom: 3px solid #FFC107; }
        #mobile-menu a:hover { background-color: #e5e7eb; }

        .admin-tabs {
            display: flex; flex-wrap: wrap;
            margin-bottom: 25px;
            border-bottom: 2px solid #eee;
        }
        .admin-tabs button {
            background-color: transparent;
            border: none;
            padding: 12px 18px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #555;
            transition: color 0.3s, border-bottom 0.3s;
            border-bottom: 3px solid transparent;
            margin-left: 5px;
        }
        .admin-tabs button.active {
            color: #1e40af;
            border-bottom-color: #1e40af;
        }
         .admin-tabs button:hover:not(.active) {
            color: #3b82f6;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease-out; }

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
            box-shadow: 0 4px 12px rgba(0,0,0,0.07);
            text-align: center;
            border: 1px solid #e5e7eb;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        .stat-card i {
            font-size: 2.5em;
            margin-bottom: 10px;
            color: #3b82f6;
        }
        .stat-card h4 {
            font-size: 2.25em;
            margin-bottom: 5px;
            color: #111827;
        }
        .stat-card p { margin: 0; color: #4b5563; font-weight: 600; }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        .data-table th, .data-table td {
            border-bottom: 1px solid #ddd;
            padding: 12px 15px;
            text-align: right;
            white-space: nowrap;
        }
        .data-table th {
            background-color: #f9fafb;
            font-weight: 700;
            color: #374151;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .data-table tr:last-child td { border-bottom: 0; }
        .data-table tbody tr:hover { background-color: #f3f4f6; }
        .data-table .actions a {
            display: inline-flex; align-items: center;
            margin: 2px; padding: 5px 10px; font-size: 13px;
            text-decoration: none; border: 1px solid; border-radius: 6px;
            cursor: pointer; transition: all 0.2s ease;
        }
         .data-table .actions .btn-view { border-color: #3b82f6; color: #3b82f6; }
         .data-table .actions .btn-view:hover { background-color: #3b82f6; color: white; }
         .data-table .actions .btn-add { border-color: #28a745; color: #28a745; }
         .data-table .actions .btn-add:hover { background-color: #28a745; color: white; }
        
        .classroom-card {
             background-color: #fff;
             border: 1px solid #e5e7eb;
             border-radius: 8px;
             padding: 1rem;
             margin-bottom: 15px;
             box-shadow: 0 2px 4px rgba(0,0,0,0.05);
             transition: transform 0.3s ease, box-shadow 0.3s ease;
         }
         .classroom-card:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 20px rgba(0,0,0,0.08);
         }
         .classroom-card h5 { margin-top: 0; color: #1e40af; font-size: 1.1rem; font-weight: 700;}
         .classroom-card p { color: #555; font-size: 0.9rem; margin-bottom: 1rem;}
         .classroom-card .manage-btn {
             display: inline-flex;
             padding: 8px 15px;
             background-color: #1e40af;
             color: #fff;
             border: none;
             border-radius: 6px;
             font-size: 14px;
             font-weight: 600;
             text-decoration: none;
             transition: background-color 0.3s ease;
         }
         .classroom-card .manage-btn:hover { background-color: #3b82f6; }
         .classroom-grid {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
             gap: 20px;
         }
    </style>
</head>
<body class="bg-bsu_light_gray text-gray-800">

    <header id="main-header" class="bg-white shadow sticky top-0 z-50">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <div class="logo">
                <a href="../../index.html" class="flex items-center space-x-3 rtl:space-x-reverse group">
                    <img src="https://www2.0zz0.com/2025/10/08/14/393349642.png" alt="شعار كلية الهندسة جامعة بني سويف" class="w-12 h-12 rounded-full shadow transition-transform duration-300 group-hover:scale-110">
                    <h1 class="text-xl md:text-2xl font-bold text-bsu_dark_blue group-hover:text-bsu_medium_blue transition-colors duration-200">كلية الهندسة</h1>
                </a>
            </div>

            <nav class="hidden md:flex">
                <ul class="flex space-x-5 rtl:space-x-reverse items-center">
                    <li class="relative group">
                        <a href="../../index.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الرئيسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../../dean-word.html">كلمة عميد الكلية</a></li>
                            <li><a href="../../vision-mission.html">الرؤية والرسالة والأهداف</a></li>
                            <li><a href="../../regulations.html">اللائحة الداخلية</a></li>
                            <li><a href="../../laws-regulations.html">القوانين واللوائح</a></li>
                        </ul>
                    </li>
                    <li><a href="../../about.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">عن الكلية</a></li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الأقسام العلمية</a>
                        <ul class="dropdown-content">
                            <li><a href="../../civil-engineering.html">قسم الهندسة المدنية</a></li>
                            <li><a href="../../architectural-engineering.html">قسم الهندسة المعمارية</a></li>
                            <li><a href="../../electrical-engineering.html">قسم الهندسة الكهربية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">البرامج الدراسية</a>
                        <ul class="dropdown-content">
                            <li><a href="../../structural-engineering.html">برنامج الهندسة الإنشائية</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الطلاب</a>
                        <ul class="dropdown-content w-56">
                            <li><a href="http://www.email.bsu.edu.eg/_BSU_Std.aspx" target="_blank" rel="noopener noreferrer">الحصول على البريد الإلكتروني</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=284" target="_blank" rel="noopener noreferrer">منتدى الطلاب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=286" target="_blank" rel="noopener noreferrer">المدن الجامعية</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=277" target="_blank" rel="noopener noreferrer">رعاية الشباب</a></li>
                            <li><a href="https://www.bsu.edu.eg/Sector_Home.aspx?cat_id=53" target="_blank" rel="noopener noreferrer">إدارة الوافدين</a></li>
                            <li><a href="http://www.results.bsu.edu.eg/" target="_blank" rel="noopener noreferrer">نتائج الكليات</a></li>
                            <li><a href="http://www.bsu.edu.eg/Sector_Home.aspx?cat_id=283" target="_blank" rel="noopener noreferrer">التربية العسكرية</a></li>
                            <li><a href="http://www.payment.bsu.edu.eg/services/" target="_blank" rel="noopener noreferrer">خدمات الدفع الإلكتروني</a></li>
                        </ul>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">الخدمات</a>
                        <ul class="dropdown-content">
                            <li><a href="../../services/certificates.php">البحث عن الشهادات</a></li>
                            <li><a href="../../services/lectures.php">المحاضرات الدراسية</a></li>
                        </ul>
                    </li>
                    <li><a href="index.php" class="text-bsu_dark_blue font-semibold transition-colors duration-200">درجات الطلاب</a></li>
                    <li><a href="../../contact.html" class="text-gray-700 hover:text-bsu_dark_blue font-semibold transition-colors duration-200">اتصل بنا</a></li>
                </ul>
            </nav>
            <button class="md:hidden text-bsu_dark_blue text-2xl" id="menu-toggle" aria-controls="mobile-menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <div class="md:hidden bg-white shadow-lg absolute w-full top-full right-0 z-40 hidden" id="mobile-menu">
            <ul class="px-4 py-2 space-y-1">
                 <li><a href="../../index.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">الرئيسية</a></li>
                <li><a href="../../about.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">عن الكلية</a></li>
                <li><a href="index.php" class="block py-2 px-3 rounded bg-bsu_light_gray font-semibold text-bsu_dark_blue">درجات الطلاب</a></li>
                <li><a href="../../contact.html" class="block py-2 px-3 rounded hover:bg-bsu_light_gray">اتصل بنا</a></li>
            </ul>
        </div>
    </header>

    <main class="py-12 md:py-16">
        <div class="container mx-auto px-4">
            <section class="page-content max-w-7xl mx-auto bg-white p-6 md:p-8 rounded-lg shadow-md animate-fade-in-up">
                <div class="flex flex-col md:flex-row justify-between md:items-center mb-6 gap-4">
                    <h2 class="text-2xl md:text-3xl font-bold text-bsu_dark_blue flex items-center">
                        <i class="fas fa-chalkboard-teacher mr-3 text-bsu_medium_blue"></i> لوحة تحكم المدرس
                    </h2>
                    <span class="text-sm text-gray-600">مرحباً، <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! 
                        [<a href="logout.php" class="text-red-600 hover:underline">تسجيل الخروج</a>]
                    </span>
                </div>

                <div class="admin-tabs">
                    <button class="tab-button active" onclick="openTab(event, 'dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i> لوحة المعلومات
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'students')">
                        <i class="fas fa-user-graduate mr-2"></i> طلابي
                    </button>
                    <button type="button" onclick="window.location.href='grades.php'" class="hover:text-bsu_medium_blue">
                        <i class="fas fa-edit mr-2"></i> إدارة الدرجات
                    </button>
                    <button type="button" onclick="window.location.href='classroom.php'" class="hover:text-bsu_medium_blue">
                         <i class="fas fa-chalkboard mr-2"></i> فصولي الدراسية
                    </button>
                </div>

                <div id="dashboard" class="tab-content active">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">نظرة عامة</h3>
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

                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-8">الوصول السريع: فصولي</h3>
                    <div class="classroom-grid">
                       <?php if (empty($teacher_classrooms)): ?>
                            <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                                <i class="fas fa-info-circle mr-2"></i> لم يتم تعيين أي فصول دراسية لك بعد.
                            </div>
                       <?php else: ?>
                           <?php foreach($teacher_classrooms as $classroom): ?>
                           <div class="classroom-card">
                                <h5><?php echo htmlspecialchars($classroom['name']); ?></h5>
                               <p><?php echo htmlspecialchars($classroom['description'] ?: 'لا يوجد وصف'); ?></p>
                               <a href="classroom.php?id=<?php echo $classroom['id']; ?>" class="manage-btn">
                                   <i class="fas fa-cog mr-2"></i> إدارة الفصل
                               </a>
                           </div>
                           <?php endforeach; ?>
                       <?php endif; ?>
                    </div>
                </div>

                <div id="students" class="tab-content">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">قائمة طلابي</h3>
                    <?php if (empty($students)): ?>
                         <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                            <i class="fas fa-info-circle mr-2"></i> لا يوجد طلاب مسجلين في فصولك الدراسية حالياً.
                         </div>
                    <?php else: ?>
                        <div class="overflow-x-auto bg-white rounded-lg shadow">
                            <table class="data-table min-w-full">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>اسم الطالب</th>
                                        <th>اسم المستخدم</th>
                                        <th>الفصل الدراسي</th>
                                        <th>الإجراءات</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white">
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td class="text-gray-500"><?php echo $student['id']; ?></td>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['username']); ?></td>
                                        <td><?php echo htmlspecialchars($student['classroom_name']); ?></td>
                                        <td class="actions">
                                            <a href="grades.php?student_id=<?php echo $student['id']; ?>" class="btn-view">
                                                <i class="fas fa-eye mr-1"></i> عرض الدرجات
                                            </a>
                                            <a href="grades.php?action=add&student_id=<?php echo $student['id']; ?>" class="btn-add">
                                                <i class="fas fa-plus mr-1"></i> إضافة درجة
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

            </section>
        </div>
    </main>

    <footer class="bg-gray-800 text-white py-8 mt-16">
        <div class="container mx-auto px-4 text-center">
            <p>&copy; <span id="currentYear"></span> كلية الهندسة، جامعة بني سويف. جميع الحقوق محفوظة.</p>
        </div>
    </footer>

    <script src="../../script.js"></script>
    <script>
        document.getElementById('currentYear').textContent = new Date().getFullYear();
        
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
            if (selectedTab) {
                selectedTab.style.display = "block";
                selectedTab.classList.add("active");
            }
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add("active");
            }
        }
         document.addEventListener('DOMContentLoaded', function() {
              var dashboardTab = document.getElementById('dashboard');
              if (dashboardTab) {
                 dashboardTab.style.display = 'block';
                 dashboardTab.classList.add('active');
              }
              var firstTabButton = document.querySelector('.admin-tabs .tab-button');
              if (firstTabButton) {
                  firstTabButton.classList.add('active');
              }
         });
    </script>
</body>
</html>