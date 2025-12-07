import 'package:flutter/material.dart';
import 'package:leave_app/pages/login.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatelessWidget {
  const MyApp({super.key});
  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      locale: const Locale('ar'),
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        primarySwatch: Colors.blue,
        fontFamily: 'Cairo',
        primaryColor: const Color(0xFF1B5E55),
        useMaterial3: true,
      ),
      home: const SignInPage(),
    );
  }
}
