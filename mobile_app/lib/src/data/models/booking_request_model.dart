import 'availability_slot_model.dart';
import 'listing_model.dart';

class BookingRequestModel {
  const BookingRequestModel({
    required this.id,
    required this.status,
    required this.statusLabel,
    required this.quantity,
    this.dateFrom,
    this.dateTo,
    this.contactName,
    this.contactPhone,
    this.notes,
    this.adminNotes,
    this.availabilitySlot,
    this.customer,
    this.listing,
  });

  final int id;
  final String status;
  final String statusLabel;
  final DateTime? dateFrom;
  final DateTime? dateTo;
  final int quantity;
  final String? contactName;
  final String? contactPhone;
  final String? notes;
  final String? adminNotes;
  final AvailabilitySlotModel? availabilitySlot;
  final BookingCustomerModel? customer;
  final ListingModel? listing;

  bool get canCancel =>
      status == 'new' || status == 'in_review' || status == 'pending';

  String get dateRangeText {
    if (dateFrom == null) return 'لم يتم تحديد التاريخ';
    if (availabilitySlot != null) {
      return '${_dateText(availabilitySlot!.date)} - ${availabilitySlot!.slotName} (${availabilitySlot!.timeText})';
    }
    final from = _dateText(dateFrom!);
    if (dateTo == null || _isSameDay(dateFrom, dateTo)) return from;
    return '$from - ${_dateText(dateTo!)}';
  }

  factory BookingRequestModel.fromJson(Map<String, dynamic> json) {
    return BookingRequestModel(
      id: json['id'] ?? 0,
      status: json['status'] ?? 'new',
      statusLabel: json['status_label'] ?? 'جديد',
      dateFrom: DateTime.tryParse('${json['date_from'] ?? ''}'),
      dateTo: DateTime.tryParse('${json['date_to'] ?? ''}'),
      quantity: int.tryParse('${json['quantity'] ?? ''}') ?? 1,
      contactName: json['contact_name'] ?? json['customer_name'],
      contactPhone: json['contact_phone'] ?? json['customer_phone'],
      notes: json['notes'],
      adminNotes: json['admin_notes'],
      availabilitySlot: json['availability_slot'] is Map<String, dynamic>
          ? AvailabilitySlotModel.fromJson(json['availability_slot'])
          : null,
      customer: json['user'] is Map<String, dynamic>
          ? BookingCustomerModel.fromJson(json['user'])
          : null,
      listing: json['listing'] is Map<String, dynamic>
          ? ListingModel.fromJson(json['listing'])
          : null,
    );
  }
}

class BookingCustomerModel {
  const BookingCustomerModel({
    required this.id,
    required this.name,
    this.phone,
    this.whatsapp,
    this.email,
  });

  final int id;
  final String name;
  final String? phone;
  final String? whatsapp;
  final String? email;

  factory BookingCustomerModel.fromJson(Map<String, dynamic> json) {
    return BookingCustomerModel(
      id: json['id'] ?? 0,
      name: json['name'] ?? '',
      phone: json['phone'],
      whatsapp: json['whatsapp'],
      email: json['email'],
    );
  }
}

String _dateText(DateTime date) {
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '${date.year}-$month-$day';
}

bool _isSameDay(DateTime? a, DateTime? b) {
  if (a == null || b == null) return false;
  return a.year == b.year && a.month == b.month && a.day == b.day;
}
