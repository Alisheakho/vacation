<!DOCTYPE html>
<html>
<head>
    <title>Test JWT</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <h1>اختبار JWT</h1>
    <div id="log" style="padding: 10px; background: #eee; margin: 10px;">⏳ جاري الاتصال...</div>

    <script type="module">
        const token = "{{ $token }}";
        const userId = "{{ $id }}";
        
        // تخزين التوكن
        localStorage.setItem('jwt_token', token);
        console.log("User ID:", userId);

        setTimeout(() => {
            console.log("Requesting subscription to: chat." + userId);

            window.Echo.private('chat.' + userId)
                // 👇👇 1. هذه الدالة تعمل فوراً عند نجاح الاتصال 👇👇
                .subscribed(() => {
                    console.log("✅ Subscription Successful!");
                    document.getElementById('log').innerHTML = "🟢 متصل بالقناة بنجاح! (بانتظار الرسالة...)";
                    document.getElementById('log').style.background = "#d4edda"; // لون أخضر
                })
                // 👇👇 2. هذه الدالة تعمل عند وصول خطأ في التوكن 👇👇
                .error((err) => {
                    console.error("❌ Subscription Error:", err);
                    document.getElementById('log').innerHTML = "❌ فشل الاتصال (تأكد من الكونسول)";
                    document.getElementById('log').style.background = "#f8d7da"; // لون أحمر
                })
                // 👇👇 3. هذه الدالة تعمل لما توصل الرسالة 👇👇
                .listen('.new.msg', (e) => {
                    console.log("📩 Event Received:", e);
                    document.getElementById('log').innerHTML = "📩 وصلت رسالة: " + e.message;
                    alert(e.message);
                });
        }, 1000);
    </script>
</body>
</html>