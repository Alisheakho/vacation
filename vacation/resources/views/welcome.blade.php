<!DOCTYPE html>
<html>
<head>
    <title>Pusher Test</title>
    @vite(['resources/js/app.js'])
</head>
<body>
    <h1>اختبار الإشعارات</h1>
    
    @auth
        <p>مرحباً {{ auth()->user()->name }}، الآيدي تبعك: {{ auth()->id() }}</p>
    @endauth

    <script type="module">
        // نأخذ الآيدي الخاص بالمستخدم المسجل حالياً
        // (تأكد أنك عامل Login بمتصفحك)
        const userId = "{{ auth()->id() }}"; 

        if (userId) {
            console.log('Connecting to channel: chat.' + userId);

            window.Echo.private('chat.' + userId)
                .listen('.new.msg', (e) => {
                    console.log("وصلت الرسالة:", e);
                    alert("رسالة جديدة: " + e.message);
                });
        }
    </script>
</body>
</html>