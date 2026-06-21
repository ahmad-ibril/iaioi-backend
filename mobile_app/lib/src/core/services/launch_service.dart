import 'package:get/get.dart';
import 'package:url_launcher/url_launcher.dart';

class LaunchService extends GetxService {
  Future<void> callPhone(String? phone) async {
    if (phone == null || phone.trim().isEmpty) return;
    final uri = Uri(scheme: 'tel', path: phone.trim());
    await _launch(uri);
  }

  Future<void> openWhatsapp(
    String? phone, {
    String message = 'مرحبا، أريد الاستفسار عن الإعلان',
  }) async {
    if (phone == null || phone.trim().isEmpty) return;
    final normalized = phone.replaceAll(RegExp(r'[^0-9+]'), '');
    final uri = Uri.parse(
      'https://wa.me/$normalized?text=${Uri.encodeComponent(message)}',
    );
    await _launch(uri);
  }

  Future<void> openUrl(String url) async {
    if (url.trim().isEmpty) return;
    await _launch(Uri.parse(url.trim()));
  }

  Future<void> openDirections({
    required double latitude,
    required double longitude,
  }) async {
    final uri = Uri.parse(
      'https://www.google.com/maps/dir/?api=1&destination=$latitude,$longitude',
    );
    await _launch(uri);
  }

  Future<void> _launch(Uri uri) async {
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      Get.snackbar('تنبيه', 'تعذر فتح الرابط المطلوب');
    }
  }
}
