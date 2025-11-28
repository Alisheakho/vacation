import 'package:flutter/material.dart';

class LeaveHistoryPage extends StatefulWidget {
  const LeaveHistoryPage({super.key});

  @override
  State<LeaveHistoryPage> createState() => _LeaveHistoryPageState();
}

class _LeaveHistoryPageState extends State<LeaveHistoryPage> {
  // colors
  final Color darkColor = const Color(0xFF06332E);
  final Color background = const Color(0xFFF5F7FA);

  // Dummy Data: List of Leave Requests
  final List<Map<String, dynamic>> _leaves = [
    // {
    //   'type': 'إجازة سنوية',
    //   'startDate': DateTime(2025, 12, 1),
    //   'endDate': DateTime(2025, 12, 5),
    //   'status': 'approved',
    //   'reason': ' السفر  ',
    // },
    // {
    //   'type': 'إجازة مرضية',
    //   'startDate': DateTime(2025, 12, 1),
    //   'endDate': DateTime(2025, 12, 5),
    //   'status': 'pending',
    //   'reason': ' مافيني شي بس بدي غيب  ',
    // },
    // {
    //   'type': 'إجازة سنوية',
    //   'startDate': DateTime(2025, 12, 1),
    //   'endDate': DateTime(2025, 12, 5),
    //   'status': 'rejected',
    //   'reason': ' تجريب ',
    // },
  ];

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        backgroundColor: background,
        appBar: AppBar(
          backgroundColor: Colors.white,
          elevation: 0,
          centerTitle: true,
          title: const Text(
            'سجل الإجازات',
            style: TextStyle(
              color: Colors.black87,
              fontWeight: FontWeight.bold,
              fontSize: 18,
            ),
          ),
          leading: IconButton(
            icon: const Icon(Icons.arrow_back_ios_new, color: Colors.black87),
            onPressed: () => Navigator.pop(context),
          ),
        ),
        body: _leaves.isEmpty
            ? _buildEmptyState()
            : ListView.builder(
                padding: const EdgeInsets.all(20),
                itemCount: _leaves.length,
                itemBuilder: (context, index) {
                  return _buildLeaveCard(_leaves[index]);
                },
              ),
      ),
    );
  }

  Widget _buildLeaveCard(Map<String, dynamic> leave) {
    // duration
    final DateTime start = leave['startDate'];
    final DateTime end = leave['endDate'];
    final int days = end.difference(start).inDays + 1;
    final String status = leave['status'];

    Color statusColor;
    String statusText;
    Color statusBg;

    switch (status) {
      case 'approved':
        statusColor = Colors.green[700]!;
        statusBg = Colors.green[50]!;
        statusText = 'مقبولة';
        break;
      case 'rejected':
        statusColor = Colors.red[700]!;
        statusBg = Colors.red[50]!;
        statusText = 'مرفوضة';
        break;
      default:
        statusColor = Colors.orange[800]!;
        statusBg = Colors.orange[50]!;
        statusText = 'قيد الانتظار';
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.grey.withOpacity(0.06),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(14),
        child: IntrinsicHeight(
          child: Row(
            children: [
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: 14,
                    vertical: 12,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Text(
                            leave['type'],
                            style: TextStyle(
                              fontSize: 15,
                              fontWeight: FontWeight.bold,
                              color: darkColor,
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 3,
                            ),
                            decoration: BoxDecoration(
                              color: statusBg,
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Text(
                              statusText,
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: statusColor,
                              ),
                            ),
                          ),
                        ],
                      ),

                      const SizedBox(height: 10),

                      Row(
                        children: [
                          Icon(
                            Icons.calendar_month_outlined,
                            size: 14,
                            color: Colors.grey[400],
                          ),
                          const SizedBox(width: 4),
                          Text(
                            '${start.day}/${start.month}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[700],
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                          Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 4),
                            child: Icon(
                              Icons.arrow_back,
                              size: 10,
                              color: Colors.grey[300],
                            ),
                          ),
                          Text(
                            '${end.day}/${end.month}/${end.year}',
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey[700],
                              fontWeight: FontWeight.w500,
                            ),
                          ),

                          const Spacer(),
                          Container(
                            padding: const EdgeInsets.symmetric(
                              horizontal: 8,
                              vertical: 2,
                            ),
                            decoration: BoxDecoration(
                              color: Colors.grey[100],
                              borderRadius: BorderRadius.circular(4),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  Icons.access_time,
                                  size: 12,
                                  color: Colors.grey[600],
                                ),
                                const SizedBox(width: 4),
                                Text(
                                  '$days أيام',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.w600,
                                    color: Colors.grey[800],
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),

                      // --- Bottom Row: Reason (Only if exists) ---
                      if (leave['reason'] != null &&
                          leave['reason'].toString().trim().isNotEmpty) ...[
                        const SizedBox(height: 10),
                        Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8F9FA),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Row(
                            children: [
                              Icon(
                                Icons.notes_rounded,
                                size: 14,
                                color: Colors.grey[400],
                              ),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  leave['reason'],
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey[600],
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.history, size: 80, color: Colors.grey[300]),
          const SizedBox(height: 16),
          Text(
            ' ليس لديك أي طلبات إجازة سابقة ',
            style: TextStyle(
              fontSize: 16,
              color: Colors.grey[500],
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }
}
