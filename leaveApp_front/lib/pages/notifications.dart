import 'package:intl/intl.dart' hide TextDirection;
import 'package:flutter/material.dart';

class NotificationsPage extends StatelessWidget {
  const NotificationsPage({super.key});

  // theme colors
  final Color darkColor = const Color(0xFF1B5E55);
  final Color background = const Color(0xFFF5F7FA);

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: background,
        appBar: AppBar(
          backgroundColor: Colors.white,
          elevation: 0,
          title: const Text(
            'الإشعارات',
            style: TextStyle(
              color: Colors.black87,
              fontWeight: FontWeight.bold,
              fontSize: 18,
            ),
          ),
          centerTitle: true,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new, color: Colors.black87),
            onPressed: () => Navigator.pop(context),
          ),
          actions: [
            TextButton(
              onPressed: () {},
              child: Text(
                "حذف الكل",
                style: TextStyle(color: darkColor, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        body: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _buildSectionHeader("اليوم"),
            _buildNotificationItem(
              title: "تمت الموافقة على إجازتك",
              message: "وافق المدير على طلب الإجازة السنوية.",
              time: DateFormat('yyyy/MM/dd').format(DateTime.now()),
              type: NotificationType.success,
            ),
            const SizedBox(height: 20),
            _buildSectionHeader("الأمس"),
            _buildNotificationItem(
              title: "تم رفض الطلب",
              message: "تم رفض طلب الاجازة بسبب ضغط العمل.",
              time: DateFormat(
                'yyyy/MM/dd',
              ).format(DateTime.now().subtract(const Duration(days: 1))),
              type: NotificationType.error,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSectionHeader(String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12, right: 4),
      child: Text(
        title,
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.bold,
          color: Colors.grey[600],
        ),
      ),
    );
  }

  Widget _buildNotificationItem({
    required String title,
    required String message,
    required String time,
    required NotificationType type,
  }) {
    Color iconColor;
    Color bgColor;
    IconData icon;

    switch (type) {
      case NotificationType.success:
        iconColor = Colors.green;
        bgColor = Colors.green.withOpacity(0.1);
        icon = Icons.check_circle_outline;
        break;
      case NotificationType.error:
        iconColor = Colors.red;
        bgColor = Colors.red.withOpacity(0.1);
        icon = Icons.cancel_outlined;
        break;
      case NotificationType.warning:
        iconColor = Colors.orange;
        bgColor = Colors.orange.withOpacity(0.1);
        icon = Icons.info_outline;
        break;
      case NotificationType.info:
      default:
        iconColor = darkColor;
        bgColor = darkColor.withOpacity(0.1);
        icon = Icons.notifications_none;
        break;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: bgColor, shape: BoxShape.circle),
            child: Icon(icon, color: iconColor, size: 24),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: Colors.black87,
                      ),
                    ),
                    Text(
                      time,
                      style: TextStyle(fontSize: 12, color: Colors.grey[400]),
                    ),
                  ],
                ),
                const SizedBox(height: 6),
                Text(
                  message,
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey[600],
                    height: 1.4,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

enum NotificationType { success, error, warning, info }
