<?php
include 'includes/conn.php';
include 'includes/functions.php';
requireRole('student');

$student_id = $_SESSION['user_id'];

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
$grades = $grades_stmt->fetchAll(PDO::FETCH_ASSOC);

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

$grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
foreach ($grades as $grade) {
    if (is_numeric($grade['grade'])) {
        $letter_grade = calculateGradePoint($grade['grade']);
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
    <meta name="robots" content="noindex, nofollow">
    <title>بوابة الطالب - نظام الدرجات</title>

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
                        success_bg: '#d4edda',
                        success_border: '#c3e6cb',
                        success_text: '#155724',
                        danger_bg: '#f8d7da',
                        danger_border: '#f5c6cb',
                        danger_text: '#721c24',
                    },
                    fontFamily: {
                        'cairo': ['Cairo', 'sans-serif'],
                    },
                     keyframes: {
                         fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        },
                         fadeIn: {
                            '0%': { opacity: '0'},
                            '100%': { opacity: '1'},
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.6s ease-out forwards',
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
        .admin-tabs button:hover {
            color: #3b82f6;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s ease-out; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
            text-align: center;
        }
        .stat-card-small {
            background: #fff; padding: 15px; border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.07); border: 1px solid #e5e7eb;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
         .stat-card-small:hover {
             transform: translateY(-5px);
             box-shadow: 0 8px 20px rgba(0,0,0,0.1);
         }
         .stat-card-small h3 { font-size: 1.8em; margin-bottom: 5px; color: #1e40af; }
         .stat-card-small small { color: #555; font-weight: 600; }

        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); border-radius: 8px; overflow: hidden;
        }
        .data-table th, .data-table td {
            border-bottom: 1px solid #ddd; padding: 12px 15px;
            text-align: right; white-space: nowrap;
        }
        .data-table th {
            background-color: #f9fafb; font-weight: 700; color: #374151;
            font-size: 0.875rem; text-transform: uppercase;
        }
        .data-table tr:last-child td { border-bottom: 0; }
        .data-table tbody tr:hover { background-color: #f3f4f6; }

        .badge { display: inline-block; padding: 4px 8px; font-size: 0.9em; font-weight: bold; border-radius: 999px; color: white; }
        .bg-success { background-color: #28a745; }
        .bg-info { background-color: #17a2b8; }
        .bg-warning { background-color: #ffc107; color: #333 !important; }
        .bg-danger { background-color: #dc3545; }
        .bg-dark { background-color: #343a40; }
        .bg-secondary { background-color: #6c757d; }

        .dist-item { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px dashed #eee; }
        .dist-item:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .dist-label { font-weight: bold; color: #374151; }
        .dist-bar-container { flex-grow: 1; margin: 0 15px; background-color: #e9ecef; border-radius: 999px; height: 10px; overflow: hidden;}
        .dist-bar { height: 100%; border-radius: 999px; transition: width 0.8s ease-in-out; }
        .dist-count { font-size: 14px; color: #555; min-width: 60px; text-align: left;}
        
        .grid-container {
             display: grid;
             grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
             gap: 30px;
         }
         .card-styled {
             background-color: #fff;
             border: 1px solid #e5e7eb;
             border-radius: 8px;
             padding: 1.5rem;
             box-shadow: 0 4px 10px rgba(0,0,0,0.05);
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
                        <i class="fas fa-user-graduate mr-3 text-bsu_medium_blue"></i> بوابة الطالب
                    </h2>
                    <span class="text-sm text-gray-600">مرحباً، <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?>! 
                        [<a href="logout.php" class="text-red-600 hover:underline">تسجيل الخروج</a>]
                    </span>
                </div>

                <div class="admin-tabs">
                    <button class="tab-button" onclick="openTab(event, 'dashboard')">
                        <i class="fas fa-tachometer-alt mr-2"></i> لوحة المعلومات
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'grades')">
                        <i class="fas fa-list-alt mr-2"></i> درجاتي بالتفصيل
                    </button>
                    <button class="tab-button" onclick="openTab(event, 'performance')">
                        <i class="fas fa-chart-line mr-2"></i> تحليل الأداء
                    </button>
                </div>

                <div id="dashboard" class="tab-content">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">نظرة عامة على أدائك</h3>
                    <div class="stats-grid">
                        <div class="stat-card-small">
                            <h3><?php echo $total_grades; ?></h3>
                            <small>إجمالي الدرجات المرصودة</small>
                        </div>
                        <div class="stat-card-small">
                             <h3 class="text-bsu_medium_blue"><?php echo number_format($average_grade, 1); ?></h3>
                            <small>متوسط الدرجات العام</small>
                        </div>
                        <div class="stat-card-small">
                             <h3 class="text-bsu_medium_blue"><?php echo calculateGradePoint($average_grade); ?></h3>
                            <small>التقدير العام</small>
                        </div>
                        <div class="stat-card-small">
                             <h3><?php echo $subject_count; ?></h3>
                            <small>عدد المواد</small>
                        </div>
                    </div>

                    <div class="mt-8">
                        <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4">آخر 5 درجات مرصودة</h3>
                        <?php if (empty($grades)): ?>
                             <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                                <i class="fas fa-info-circle mr-2"></i> لم يتم رصد أي درجات لك حتى الآن.
                             </div>
                        <?php else: ?>
                            <div class="overflow-x-auto bg-white rounded-lg shadow">
                                <table class="data-table min-w-full">
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
                                    <tbody class="bg-white">
                                        <?php foreach(array_slice($grades, 0, 5) as $grade):
                                            $letter_grade = calculateGradePoint($grade['grade'] ?? 0);
                                            $grade_color_class = getGradeColor($grade['grade'] ?? 0, 'tailwind');
                                        ?>
                                        <tr>
                                            <td class="font-medium text-gray-900"><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo $grade_color_class; ?>">
                                                    <?php echo htmlspecialchars(is_numeric($grade['grade']) ? number_format($grade['grade'], 1) : 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td class="font-semibold"><?php echo $letter_grade; ?></td>
                                            <td><span class="badge bg-secondary text-white"><?php echo htmlspecialchars(ucfirst($grade['grade_type'])); ?></span></td>
                                            <td><?php echo htmlspecialchars($grade['teacher_first'] . ' ' . $grade['teacher_last']); ?></td>
                                            <td><?php echo date('Y-m-d', strtotime($grade['graded_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (count($grades) > 5): ?>
                                <p class="mt-4 text-center"><a href="#" onclick="openTab(event, 'grades'); return false;" class="text-bsu_medium_blue hover:underline font-semibold">عرض كل الدرجات...</a></p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="grades" class="tab-content">
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">جميع درجاتي</h3>
                    <?php if (empty($grades)): ?>
                         <div class="p-4 text-center text-gray-600 bg-bsu_light_gray border border-gray-200 rounded-md">
                            <i class="fas fa-info-circle mr-2"></i> لم يتم رصد أي درجات لك حتى الآن.
                         </div>
                    <?php else: ?>
                         <div class="overflow-x-auto bg-white rounded-lg shadow">
                            <table class="data-table min-w-full">
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
                                <tbody class="bg-white">
                                    <?php foreach($grades as $grade):
                                        $letter_grade = calculateGradePoint($grade['grade'] ?? 0);
                                        $grade_color_class = getGradeColor($grade['grade'] ?? 0, 'tailwind');
                                    ?>
                                    <tr>
                                        <td class="font-medium text-gray-900"><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['classroom_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $grade_color_class; ?>">
                                                <?php echo htmlspecialchars(is_numeric($grade['grade']) ? number_format($grade['grade'], 1) : 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td class="font-semibold"><?php echo $letter_grade; ?></td>
                                        <td><span class="badge bg-secondary text-white"><?php echo htmlspecialchars(ucfirst($grade['grade_type'])); ?></span></td>
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
                    <h3 class="text-xl font-semibold text-bsu_dark_blue mb-4 mt-4">تحليل الأداء</h3>
                       <div class="grid-container">
                           <div class="card-styled">
                                <h4 class="text-lg font-semibold text-center text-bsu_dark_blue mb-4">ملخص الأداء</h4>
                                <div class="text-center mb-5">
                                    <h1 class="text-6xl font-bold text-bsu_medium_blue mb-0">
                                        <?php echo number_format($average_grade, 1); ?>
                                    </h1>
                                    <p class="text-lg font-semibold text-gray-600">المتوسط العام</p>
                                    <h2 class="text-4xl font-bold text-bsu_medium_blue mt-2"><?php echo calculateGradePoint($average_grade); ?></h2>
                                </div>
                                 <hr class="my-4">
                                <div class="stats-grid" style="grid-template-columns: 1fr 1fr; gap: 10px;">
                                    <div class="stat-card-small">
                                        <h3><?php echo $total_grades; ?></h3>
                                        <small>إجمالي الدرجات</small>
                                    </div>
                                    <div class="stat-card-small">
                                         <h3><?php echo $subject_count; ?></h3>
                                        <small>عدد المواد</small>
                                    </div>
                                </div>
                           </div>

                            <div class="card-styled">
                                <h4 class="text-lg font-semibold text-center text-bsu_dark_blue mb-4">توزيع التقديرات</h4>
                                <?php foreach($grade_distribution as $letter => $count):
                                    $percentage = $total_grades > 0 ? ($count / $total_grades) * 100 : 0;
                                    $color_class = '';
                                    switch($letter) {
                                        case 'A': $color_class = 'bg-success'; break;
                                        case 'B': $color_class = 'bg-info'; break;
                                        case 'C': $color_class = 'bg-warning'; break;
                                        case 'D': $color_class = 'bg-danger'; break;
                                        case 'F': $color_class = 'bg-dark'; break;
                                        default: $color_class = 'bg-secondary';
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
         
        // Helper function to get Tailwind grade colors
        function getGradeColor(grade) {
            if (!isFinite(grade)) return 'bg-gray-400 text-white';
            if (grade >= 90) return 'bg-success text-white';
            if (grade >= 80) return 'bg-info text-white';
            if (grade >= 70) return 'bg-warning text-black';
            if (grade >= 60) return 'bg-danger text-white';
            return 'bg-dark text-white';
        }
        
        document.querySelectorAll('.badge[data-grade]').forEach(badge => {
            const grade = parseFloat(badge.dataset.grade);
            const colorClasses = getGradeColor(grade).split(' ');
            badge.classList.add(...colorClasses);
        });
    </script>
</body>
</html>