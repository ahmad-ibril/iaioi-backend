class CalendarDateModel {
  const CalendarDateModel({
    required this.date,
    required this.status,
    this.priceOverride,
    this.note,
  });

  final DateTime date;
  final String status;
  final String? priceOverride;
  final String? note;

  Map<String, dynamic> toJson() {
    return {
      'date': _formatDate(date),
      'status': status,
      if (priceOverride != null && priceOverride!.isNotEmpty)
        'price_override': priceOverride,
      if (note != null && note!.isNotEmpty) 'note': note,
    };
  }

  factory CalendarDateModel.fromJson(Map<String, dynamic> json) {
    return CalendarDateModel(
      date: DateTime.tryParse(json['date'] ?? '') ?? DateTime.now(),
      status: json['status'] ?? 'available',
      priceOverride: json['price_override']?.toString(),
      note: json['note'],
    );
  }
}

String _formatDate(DateTime date) {
  final month = date.month.toString().padLeft(2, '0');
  final day = date.day.toString().padLeft(2, '0');
  return '${date.year}-$month-$day';
}
