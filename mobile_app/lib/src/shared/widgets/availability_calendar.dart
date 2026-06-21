import 'package:flutter/material.dart';
import 'package:table_calendar/table_calendar.dart';

import '../../core/config/app_theme.dart';
import '../../data/models/availability_slot_model.dart';
import '../../data/models/calendar_date_model.dart';

class AvailabilityCalendar extends StatelessWidget {
  const AvailabilityCalendar({
    super.key,
    this.dates = const [],
    this.slots = const [],
    this.onDayTap,
    this.selectedDates = const {},
    this.compact = false,
  });

  final List<CalendarDateModel> dates;
  final List<AvailabilitySlotModel> slots;
  final ValueChanged<DateTime>? onDayTap;
  final Set<DateTime> selectedDates;
  final bool compact;

  @override
  Widget build(BuildContext context) {
    final map = _markersByDate();

    return DecoratedBox(
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border.all(color: AppTheme.border),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Padding(
        padding: EdgeInsets.all(compact ? 8 : 12),
        child: TableCalendar<void>(
          locale: 'ar',
          firstDay: DateTime.now().subtract(const Duration(days: 365)),
          lastDay: DateTime.now().add(const Duration(days: 730)),
          focusedDay: DateTime.now(),
          rowHeight: compact ? 46 : 54,
          headerStyle: const HeaderStyle(
            formatButtonVisible: false,
            titleCentered: true,
          ),
          daysOfWeekHeight: 24,
          selectedDayPredicate: (day) {
            return selectedDates.any((item) => DateUtils.isSameDay(item, day));
          },
          onDaySelected: onDayTap == null
              ? null
              : (selectedDay, focusedDay) =>
                    onDayTap!(DateUtils.dateOnly(selectedDay)),
          calendarBuilders: CalendarBuilders(
            defaultBuilder: (context, day, focusedDay) {
              return _dayCell(day, map[DateUtils.dateOnly(day)] ?? const []);
            },
            todayBuilder: (context, day, focusedDay) {
              return _dayCell(
                day,
                map[DateUtils.dateOnly(day)] ?? const [],
                today: true,
              );
            },
            selectedBuilder: (context, day, focusedDay) {
              return _dayCell(
                day,
                map[DateUtils.dateOnly(day)] ?? const [],
                selected: true,
              );
            },
          ),
        ),
      ),
    );
  }

  Map<DateTime, List<Color>> _markersByDate() {
    final map = <DateTime, List<Color>>{};

    for (final date in dates) {
      final key = DateUtils.dateOnly(date.date);
      map.putIfAbsent(key, () => []).add(_statusColor(date.status));
    }

    for (final slot in slots) {
      final key = DateUtils.dateOnly(slot.date);
      map.putIfAbsent(key, () => []).add(slot.statusColor);
    }

    return map;
  }

  Widget _dayCell(
    DateTime day,
    List<Color> markers, {
    bool today = false,
    bool selected = false,
  }) {
    final hasMarkers = markers.isNotEmpty;
    final singleMarker = markers.length == 1;
    final background = selected
        ? AppTheme.primary
        : singleMarker
        ? markers.first
        : hasMarkers
        ? const Color(0xFFF7FBFF)
        : const Color(0xFFE9EEF5);
    final textColor = selected || singleMarker
        ? Colors.white
        : AppTheme.textDark;

    return Center(
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: background,
          borderRadius: BorderRadius.circular(8),
          border: Border.all(
            color: today
                ? AppTheme.primaryDark
                : hasMarkers
                ? AppTheme.border
                : Colors.transparent,
            width: today ? 1.2 : 1,
          ),
        ),
        child: SizedBox(
          width: compact ? 36 : 42,
          height: compact ? 38 : 44,
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Text(
                '${day.day}',
                style: TextStyle(
                  color: textColor,
                  fontWeight: FontWeight.w800,
                  fontSize: compact ? 13 : 14,
                ),
              ),
              const SizedBox(height: 3),
              SizedBox(
                height: 6,
                child: Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    for (final color in markers.take(singleMarker ? 0 : 4))
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 1),
                        child: DecoratedBox(
                          decoration: BoxDecoration(
                            color: color,
                            shape: BoxShape.circle,
                          ),
                          child: const SizedBox(width: 5, height: 5),
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Color _statusColor(String status) {
    return switch (status) {
      'booked' || 'reserved' => Colors.red,
      'blocked' || 'unavailable' => Colors.grey,
      'pending' => Colors.orange,
      _ => AppTheme.green,
    };
  }
}
