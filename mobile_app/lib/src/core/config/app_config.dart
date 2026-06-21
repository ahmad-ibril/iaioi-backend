class AppConfig {
  const AppConfig._();

  static const apiBaseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'https://iaioi.com/api/v1',
  );

  static const googleClientId = String.fromEnvironment(
    'GOOGLE_CLIENT_ID',
    defaultValue: '',
  );

  static const googleServerClientId = String.fromEnvironment(
    'GOOGLE_SERVER_CLIENT_ID',
    defaultValue: '',
  );

  static const adMobEnabled = bool.fromEnvironment('ADMOB_ENABLED');
  static const androidAdUnitId = String.fromEnvironment(
    'ANDROID_AD_UNIT_ID',
    defaultValue: '',
  );
  static const iosAdUnitId = String.fromEnvironment(
    'IOS_AD_UNIT_ID',
    defaultValue: '',
  );
  static const webAdPlaceholder = String.fromEnvironment(
    'WEB_AD_PLACEHOLDER',
    defaultValue: 'مساحة إعلانية',
  );

  static String mediaUrl(String? url) {
    final rawUrl = url?.trim();

    if (rawUrl == null || rawUrl.isEmpty) return '';
    if (rawUrl.startsWith('http://') || rawUrl.startsWith('https://')) {
      return rawUrl;
    }

    final apiUri = Uri.tryParse(apiBaseUrl);
    if (apiUri == null) {
      return rawUrl.startsWith('/') ? rawUrl : '/$rawUrl';
    }

    final origin =
        '${apiUri.scheme}://${apiUri.host}${apiUri.hasPort ? ':${apiUri.port}' : ''}';
    final normalizedPath = rawUrl
        .replaceAll('\\', '/')
        .replaceFirst(RegExp(r'^/+'), '');

    if (normalizedPath.isEmpty) return '';

    final storagePath = normalizedPath.startsWith('storage/')
        ? normalizedPath
        : 'storage/$normalizedPath';

    return '$origin/$storagePath';
  }
}
