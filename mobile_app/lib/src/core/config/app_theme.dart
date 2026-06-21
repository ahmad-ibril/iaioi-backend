import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppTheme {
  const AppTheme._();

  static const background = Color(0xFFF8FAFC);
  static const surface = Color(0xFFFFFFFF);
  static const surfaceWarm = Color(0xFFFFF3F1);
  static const primary = Color(0xFFE34D3F);
  static const primaryDark = Color(0xFF9F2F26);
  static const secondary = Color(0xFF0F766E);
  static const secondarySoft = Color(0xFFE6F5F2);
  static const warning = Color(0xFFF59E0B);
  static const danger = Color(0xFFDC2626);
  static const textDark = Color(0xFF1F2933);
  static const textMuted = Color(0xFF667085);
  static const border = Color(0xFFE4E7EC);
  static const green = Color(0xFF12805C);

  static final _textTheme = GoogleFonts.cairoTextTheme(
    const TextTheme(
      headlineSmall: TextStyle(
        fontWeight: FontWeight.w800,
        color: textDark,
        height: 1.25,
      ),
      titleLarge: TextStyle(fontWeight: FontWeight.w800, color: textDark),
      titleMedium: TextStyle(fontWeight: FontWeight.w700, color: textDark),
      titleSmall: TextStyle(fontWeight: FontWeight.w700, color: textDark),
      bodyLarge: TextStyle(color: textDark, height: 1.5),
      bodyMedium: TextStyle(color: textDark, height: 1.45),
      bodySmall: TextStyle(color: textMuted, height: 1.35),
    ),
  );

  static final light = ThemeData(
    useMaterial3: true,
    fontFamily: GoogleFonts.cairo().fontFamily,
    scaffoldBackgroundColor: background,
    colorScheme: ColorScheme.fromSeed(
      seedColor: primary,
      primary: primary,
      onPrimary: Colors.white,
      secondary: secondary,
      tertiary: warning,
      surface: surface,
      onSurface: textDark,
      error: danger,
    ),
    appBarTheme: const AppBarTheme(
      backgroundColor: background,
      foregroundColor: textDark,
      elevation: 0,
      centerTitle: true,
    ),
    cardTheme: CardThemeData(
      elevation: 0,
      margin: const EdgeInsets.only(bottom: 12),
      shadowColor: const Color(0x1A101828),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(8),
        side: const BorderSide(color: border),
      ),
      color: surface,
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: surface,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(8)),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: border),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(8),
        borderSide: const BorderSide(color: primary, width: 1.4),
      ),
    ),
    chipTheme: ChipThemeData(
      backgroundColor: surface,
      selectedColor: primary,
      labelStyle: const TextStyle(color: textDark),
      secondaryLabelStyle: const TextStyle(color: textDark),
      side: const BorderSide(color: border),
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
    ),
    filledButtonTheme: FilledButtonThemeData(
      style: FilledButton.styleFrom(
        backgroundColor: primary,
        foregroundColor: Colors.white,
        minimumSize: const Size(48, 48),
        textStyle: const TextStyle(fontWeight: FontWeight.w800),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    ),
    outlinedButtonTheme: OutlinedButtonThemeData(
      style: OutlinedButton.styleFrom(
        foregroundColor: primaryDark,
        side: const BorderSide(color: border),
        minimumSize: const Size(48, 46),
        textStyle: const TextStyle(fontWeight: FontWeight.w800),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
      ),
    ),
    textButtonTheme: TextButtonThemeData(
      style: TextButton.styleFrom(foregroundColor: primaryDark),
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Colors.white,
      indicatorColor: primary.withValues(alpha: 0.12),
      height: 74,
      labelTextStyle: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return TextStyle(
          fontSize: 12,
          color: selected ? primaryDark : textMuted,
          fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
        );
      }),
      iconTheme: WidgetStateProperty.resolveWith((states) {
        final selected = states.contains(WidgetState.selected);
        return IconThemeData(color: selected ? primaryDark : textMuted);
      }),
    ),
    floatingActionButtonTheme: const FloatingActionButtonThemeData(
      backgroundColor: primary,
      foregroundColor: Colors.white,
    ),
    progressIndicatorTheme: const ProgressIndicatorThemeData(color: primary),
    sliderTheme: SliderThemeData(
      activeTrackColor: primary,
      inactiveTrackColor: secondary.withValues(alpha: 0.24),
      thumbColor: primary,
      overlayColor: primary.withValues(alpha: 0.12),
    ),
    switchTheme: SwitchThemeData(
      thumbColor: WidgetStateProperty.resolveWith((states) {
        return states.contains(WidgetState.selected) ? primary : Colors.white;
      }),
      trackColor: WidgetStateProperty.resolveWith((states) {
        return states.contains(WidgetState.selected)
            ? primary.withValues(alpha: 0.45)
            : const Color(0xFFE2E3E5);
      }),
    ),
    tabBarTheme: const TabBarThemeData(
      labelColor: primaryDark,
      unselectedLabelColor: textMuted,
      indicatorColor: primary,
    ),
    iconTheme: const IconThemeData(color: textDark),
    dividerTheme: const DividerThemeData(color: border, thickness: 1, space: 1),
    listTileTheme: const ListTileThemeData(
      iconColor: primaryDark,
      textColor: textDark,
      tileColor: Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.all(Radius.circular(8)),
      ),
    ),
    textTheme: _textTheme,
  );
}
