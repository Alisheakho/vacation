<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <title>شاشة المدير - طلبات الإجازات</title>
    @vite(['resources/js/app.js'])
    <style>
        body { font-family: sans-serif; padding: 20px; }
        .status-box { padding: 10px; background: #eee; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: center; }
        th { background-color: #f2f2f2; }
        .new-row { background-color: #d4edda; animation: highlight 2s; }
        @keyframes highlight { from {background: #8fdfa8;} to {background: #d4edda;} }
    </style>
</head>
<body>
    <h1>📟 شاشة مراقبة الطلبات (Real-Time)</h1>
    
    <div id="connection-status" class="status-box">⏳ جاري الاتصال بالسيرفر...</div>

    <h3>الطلبات الواردة حديثاً:</h3>
    <table>
        <thead>
            <tr>
                <th>رقم الطلب</th>
                <th>اسم الموظف</th>
                <th>نوع الإجازة</th>
                <th>تاريخ البداية</th>
                <th>المدة</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody id="leaves-table">
            </tbody>
    </table>

    <script type="module">
        // هذا الآيدي هو "المدير" الذي سيستقبل الطلبات (مثلاً رقم 1)
        const managerId = "{{ $id }}"; 
        const token = "{{ $token }}";

        localStorage.setItem('jwt_token', token);

        setTimeout(() => {
            window.Echo.private('chat.' + managerId)
                .subscribed(() => {
                    document.getElementById('connection-status').innerText = "🟢 متصل بالنظام! بانتظار الطلبات...";
                    document.getElementById('connection-status').style.background = "#d4edda";
                })
                .listen('.leave.created', (e) => {
                    console.log("طلب جديد:", e);
                    
                    // إضافة صف جديد للجدول
                    const table = document.getElementById('leaves-table');
                    const newRow = `
                        <tr class="new-row">
                            <td>${e.id}</td>
                            <td>${e.employee}</td>
                            <td>${e.type}</td>
                            <td>${e.start_date}</td>
                            <td>${e.days} أيام</td>
                            <td>${e.status}</td>
                        </tr>
                    `;
                    table.innerHTML = newRow + table.innerHTML; // إضافة في الأعلى
                    
                    // تشغيل صوت تنبيه (اختياري)
                    alert("🔔 " + e.message);
                });
        }, 1000);
    </script>
</body>
</html>