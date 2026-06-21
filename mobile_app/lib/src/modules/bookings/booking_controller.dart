import 'package:dio/dio.dart';
import 'package:get/get.dart';

import '../../data/models/booking_request_model.dart';
import '../../data/repositories/booking_repository.dart';
import '../auth/auth_controller.dart';

class BookingController extends GetxController {
  BookingController(this._repository, this._authController);

  final BookingRepository _repository;
  final UserAuthController _authController;

  final requests = <BookingRequestModel>[].obs;
  final ownerRequests = <BookingRequestModel>[].obs;
  final ownerDashboard = Rxn<OwnerDashboardModel>();
  final isLoading = false.obs;
  final isSubmitting = false.obs;
  final isOwnerLoading = false.obs;
  final error = RxnString();
  final ownerError = RxnString();

  Future<void> loadRequests() async {
    if (!_authController.isAuthenticated) {
      requests.clear();
      return;
    }

    isLoading.value = true;
    error.value = null;

    try {
      requests.assignAll(await _repository.fetchBookingRequests());
    } catch (_) {
      error.value = 'تعذر تحميل الطلبات.';
    } finally {
      isLoading.value = false;
    }
  }

  Future<void> loadOwnerDashboard() async {
    if (!_authController.isAuthenticated) {
      ownerRequests.clear();
      ownerDashboard.value = null;
      return;
    }

    isOwnerLoading.value = true;
    ownerError.value = null;

    try {
      final results = await Future.wait([
        _repository.fetchOwnerDashboard(),
        _repository.fetchOwnerBookingRequests(),
      ]);
      ownerDashboard.value = results[0] as OwnerDashboardModel;
      ownerRequests.assignAll(results[1] as List<BookingRequestModel>);
    } on DioException catch (exception) {
      ownerError.value = _messageFromDio(exception);
    } catch (_) {
      ownerError.value = 'تعذر تحميل لوحة الإدارة.';
    } finally {
      isOwnerLoading.value = false;
    }
  }

  Future<bool> create({
    required int listingId,
    int? availabilitySlotId,
    DateTime? dateFrom,
    DateTime? dateTo,
    int quantity = 1,
    String? contactName,
    String? contactPhone,
    String? notes,
  }) async {
    if (!_authController.isAuthenticated) {
      error.value = 'سجل الدخول أولاً لإرسال الطلب.';
      return false;
    }

    isSubmitting.value = true;
    error.value = null;

    try {
      final request = await _repository.createBookingRequest(
        listingId: listingId,
        availabilitySlotId: availabilitySlotId,
        dateFrom: dateFrom,
        dateTo: dateTo,
        quantity: quantity,
        contactName: contactName,
        contactPhone: contactPhone,
        notes: notes,
      );
      requests.insert(0, request);
      Get.snackbar('تم الإرسال', 'وصل طلبك وسيتم التواصل معك.');
      return true;
    } on DioException catch (exception) {
      error.value = _messageFromDio(exception);
      return false;
    } catch (_) {
      error.value = 'تعذر إرسال الطلب. حاول مرة أخرى.';
      return false;
    } finally {
      isSubmitting.value = false;
    }
  }

  Future<void> cancel(BookingRequestModel request) async {
    try {
      await _repository.cancelBookingRequest(request.id);
      await loadRequests();
      Get.snackbar('تم الإلغاء', 'تم إلغاء الطلب.');
    } catch (_) {
      Get.snackbar('تنبيه', 'تعذر إلغاء الطلب.');
    }
  }

  Future<void> updateOwnerRequestStatus(
    BookingRequestModel request,
    String status, {
    String? adminNotes,
  }) async {
    isOwnerLoading.value = true;
    ownerError.value = null;

    try {
      final updated = await _repository.updateOwnerBookingRequest(
        id: request.id,
        status: status,
        adminNotes: adminNotes,
      );
      final index = ownerRequests.indexWhere((item) => item.id == request.id);
      if (index >= 0) {
        ownerRequests[index] = updated;
      }
      await loadOwnerDashboard();
      Get.snackbar('تم التحديث', 'تم تحديث حالة الطلب.');
    } on DioException catch (exception) {
      ownerError.value = _messageFromDio(exception);
      Get.snackbar('تنبيه', ownerError.value ?? 'تعذر تحديث الطلب.');
    } catch (_) {
      ownerError.value = 'تعذر تحديث الطلب.';
      Get.snackbar('تنبيه', ownerError.value!);
    } finally {
      isOwnerLoading.value = false;
    }
  }

  String _messageFromDio(DioException exception) {
    final data = exception.response?.data;

    if (data is Map<String, dynamic>) {
      final message = data['message'];
      if (message is String && message.isNotEmpty) return message;

      final errors = data['errors'];
      if (errors is Map && errors.isNotEmpty) {
        final first = errors.values.first;
        if (first is List && first.isNotEmpty) return '${first.first}';
      }
    }

    return 'تعذر الاتصال بالخادم.';
  }
}
