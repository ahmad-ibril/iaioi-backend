import 'package:geolocator/geolocator.dart';
import 'package:get/get.dart';

class LocationService extends GetxService {
  final currentPosition = Rxn<Position>();
  final isLoading = false.obs;
  final error = RxnString();

  Future<Position?> requestCurrentLocation() async {
    isLoading.value = true;
    error.value = null;

    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        error.value = 'خدمة الموقع غير مفعلة.';
        return null;
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied ||
          permission == LocationPermission.deniedForever) {
        error.value = 'لم يتم منح صلاحية الموقع.';
        return null;
      }

      final position = await Geolocator.getCurrentPosition();
      currentPosition.value = position;
      return position;
    } catch (e) {
      error.value = 'تعذر تحديد الموقع.';
      return null;
    } finally {
      isLoading.value = false;
    }
  }
}
