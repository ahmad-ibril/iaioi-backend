import 'dart:convert';

import 'package:dio/dio.dart';
import 'package:flutter/foundation.dart';

import '../config/app_config.dart';

class ApiClient {
  ApiClient()
    : dio = Dio(
        BaseOptions(
          baseUrl: AppConfig.apiBaseUrl,
          connectTimeout: const Duration(seconds: 15),
          receiveTimeout: const Duration(seconds: 20),
          headers: {'Accept': 'application/json'},
        ),
      ) {
    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) {
          _log('API REQUEST ${options.method} ${options.uri}');
          handler.next(options);
        },
        onResponse: (response, handler) {
          _log(
            'API RESPONSE ${response.statusCode} ${response.requestOptions.uri}',
          );
          _log('API RESPONSE BODY ${_bodyForLog(response.data)}');
          handler.next(response);
        },
        onError: (error, handler) {
          final response = error.response;

          _log(
            'API ERROR ${error.requestOptions.method} ${error.requestOptions.uri}',
          );
          _log('API ERROR MESSAGE ${error.message ?? error.toString()}');

          if (response != null) {
            _log(
              'API ERROR STATUS ${response.statusCode} ${response.requestOptions.uri}',
            );
            _log('API ERROR BODY ${_bodyForLog(response.data)}');
          }

          handler.next(error);
        },
      ),
    );
  }

  final Dio dio;
  String? _authToken;

  void setAuthToken(String? token) {
    _authToken = token;

    if (token == null || token.isEmpty) {
      dio.options.headers.remove('Authorization');
      return;
    }

    dio.options.headers['Authorization'] = 'Bearer $token';
  }

  Future<Map<String, dynamic>> getMap(
    String path, {
    Map<String, dynamic>? query,
  }) async {
    final response = await dio.get(path, queryParameters: query);
    return _asMap(response.data);
  }

  Future<Map<String, dynamic>> postMap(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await dio.post(path, data: data);
    return _asMap(response.data);
  }

  Future<Map<String, dynamic>> postForm(String path, FormData data) async {
    final response = await dio.post(path, data: data);
    return _asMap(response.data);
  }

  Future<Map<String, dynamic>> putMap(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await dio.put(path, data: data);
    return _asMap(response.data);
  }

  Future<Map<String, dynamic>> patchMap(
    String path, {
    Map<String, dynamic>? data,
  }) async {
    final response = await dio.patch(path, data: data);
    return _asMap(response.data);
  }

  Future<void> delete(String path) async {
    await dio.delete(path);
  }

  Future<Map<String, dynamic>> deleteMap(String path) async {
    final response = await dio.delete(path);
    return _asMap(response.data);
  }

  bool get hasToken => _authToken != null && _authToken!.isNotEmpty;

  Map<String, dynamic> _asMap(dynamic data) {
    if (data is Map<String, dynamic>) return data;
    if (data is Map) return Map<String, dynamic>.from(data);
    if (data is String && data.trim().isNotEmpty) {
      try {
        final decoded = jsonDecode(data);
        if (decoded is Map<String, dynamic>) return decoded;
        if (decoded is Map) return Map<String, dynamic>.from(decoded);
      } catch (_) {
        return {'data': data};
      }
    }

    return {'data': data};
  }

  static String _bodyForLog(dynamic data) {
    if (data == null) return '<empty>';
    if (data is String) return data;

    try {
      return jsonEncode(data);
    } catch (_) {
      return data.toString();
    }
  }

  static void _log(String message) {
    if (!kDebugMode) return;

    const chunkSize = 900;

    if (message.length <= chunkSize) {
      debugPrint(message);
      return;
    }

    for (var index = 0; index < message.length; index += chunkSize) {
      final end = index + chunkSize < message.length
          ? index + chunkSize
          : message.length;
      debugPrint(message.substring(index, end));
    }
  }
}
