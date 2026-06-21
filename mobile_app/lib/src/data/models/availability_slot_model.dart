import 'package:flutter/material.dart';

import '../../core/config/app_theme.dart';

class AvailabilitySlotModel {
  const AvailabilitySlotModel({
    required this.id,
    required this.listingId,
    required this.date,
    required this.slotName,
    required this.status,
    this.startTime,
    this.endTime,
    this.price,
  });

  final int id;
  final int listingId;
  final DateTime date;
  final String slotName;
  final String? startTime;
  final String? endTime;
  final String? price;
  final String status;

  bool get isAvailable => status == 'available';
  bool get isPending => status == 'pending';
  bool get isReserved => status == 'reserved' || status == 'booked';
  bool get isUnavailable => status == 'unavailable' || status == 'blocked';

  String get timeText {
    if ((startTime ?? '').isEmpty && (endTime ?? '').isEmpty) {
      return 'يوم كامل';
    }
    return '${startTime ?? ''} - ${endTime ?? ''}'.trim();
  }

  String get statusLabel {
    return switch (status) {
      'reserved' || 'booked' => 'محجوز',
      'pending' => 'قيد المراجعة',
      'unavailable' || 'blocked' => 'غير متاح',
      _ => 'متاح',
    };
  }

  Color get statusColor {
    return switch (status) {
      'reserved' || 'booked' => Colors.red,
      'pending' => Colors.orange,
      'unavailable' || 'blocked' => Colors.grey,
      _ => AppTheme.green,
    };
  }

  AvailabilitySlotModel copyWith({
    int? id,
    int? listingId,
    DateTime? date,
    String? slotName,
    String? startTime,
    String? endTime,
    String? price,
    String? status,
  }) {
    return AvailabilitySlotModel(
      id: id ?? this.id,
      listingId: listingId ?? this.listingId,
      date: date ?? this.date,
      slotName: slotName ?? this.slotName,
      startTime: startTime ?? this.startTime,
      endTime: endTime ?? this.endTime,
      price: price ?? this.price,
      status: status ?? this.status,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      if (id > 0) 'id': id,
      if (listingId > 0) 'listing_id': listingId,
      'date': formatDate(date),
      'slot_name': slotName,
      if ((startTime ?? '').isNotEmpty) 'start_time': startTime,
      if ((endTime ?? '').isNotEmpty) 'end_time': endTime,
      if ((price ?? '').isNotEmpty) 'price': price,
      'status': status,
    };
  }

  factory AvailabilitySlotModel.fromJson(Map<String, dynamic> json) {
    return AvailabilitySlotModel(
      id: int.tryParse('${json['id'] ?? 0}') ?? 0,
      listingId: int.tryParse('${json['listing_id'] ?? 0}') ?? 0,
      date: DateTime.tryParse('${json['date'] ?? ''}') ?? DateTime.now(),
      slotName: json['slot_name'] ?? 'يوم كامل',
      startTime: _cleanTime(json['start_time']),
      endTime: _cleanTime(json['end_time']),
      price: json['price']?.toString(),
      status: json['status'] ?? 'available',
    );
  }

  static String formatDate(DateTime date) {
    final month = date.month.toString().padLeft(2, '0');
    final day = date.day.toString().padLeft(2, '0');
    return '${date.year}-$month-$day';
  }
}

String formatDate(DateTime date) => AvailabilitySlotModel.formatDate(date);

String? _cleanTime(dynamic value) {
  if (value == null) return null;
  final text = '$value';
  if (text.isEmpty) return null;
  return text.length >= 5 ? text.substring(0, 5) : text;
}
