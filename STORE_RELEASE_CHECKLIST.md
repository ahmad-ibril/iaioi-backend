# STORE RELEASE CHECKLIST

تاريخ الفحص: 2026-06-27

## المتطلبات الرسمية

- Google Play: التطبيقات الجديدة والتحديثات يجب أن تستهدف Android 15 / API 35 أو أعلى. المرجع: https://developer.android.com/google/play/requirements/target-sdk
- Apple App Store: منذ 2026-04-28 يجب رفع التطبيقات إلى App Store Connect باستخدام Xcode 26 أو أحدث و SDK iOS 26 أو أحدث. المرجع: https://developer.apple.com/news/upcoming-requirements/

## نتيجة الفحص السريعة

- Flutter version: 3.41.7 stable.
- App version: `1.0.1+2`.
- Production API default: `https://iaioi.com/api/v1`.
- Android package/applicationId لم يتغير: `com.arabrentals.arab_rentals_app`.
- iOS Bundle Identifier لم يتغير: `com.arabrentals.arabRentalsApp`.
- Android APK release تم بناؤه بنجاح.
- Android AAB release تم بناؤه بنجاح.
- iOS build لم يتم بناؤه محلياً لأن البيئة Windows ولا تحتوي subcommand الخاص ببناء iOS. يجب تشغيله على macOS/Xcode أو Codemagic.

## الملفات المعدلة

- `mobile_app/pubspec.yaml`
  - رفع الإصدار إلى `1.0.1+2`.
- `mobile_app/android/app/build.gradle.kts`
  - ضبط `compileSdk = 36`.
  - ضبط `targetSdk = 35`.
  - ضبط `minSdk = maxOf(23, flutter.minSdkVersion)`.
  - إضافة release signing config من `android/key.properties` عند توفره.
  - إبقاء fallback إلى debug signing محلياً فقط عند عدم وجود keystore.
- `mobile_app/android/app/src/main/AndroidManifest.xml`
  - تغيير اسم العرض إلى `@string/app_name`.
  - الإبقاء على الصلاحيات المستخدمة فقط: internet و location.
- `mobile_app/android/app/src/main/res/values/strings.xml`
  - إضافة اسم التطبيق `IAIOI`.
- `mobile_app/android/key.properties.example`
  - قالب آمن لإعداد keystore بدون تخزين أسرار.
- `mobile_app/ios/Runner/Info.plist`
  - تغيير Display Name و Bundle Name إلى `IAIOI`.
- `mobile_app/lib/main.dart`
  - تغيير عنوان التطبيق إلى `IAIOI`.
- `mobile_app/lib/src/core/network/api_client.dart`
  - جعل API logs تعمل في debug فقط، حتى لا تُطبع response bodies في release.
- `mobile_app/lib/src/modules/details/listing_details_page.dart`
  - إزالة علامة الاختبار المؤقتة `VERSION 2026-06-23 UPDATED`.

## Android

- applicationId: `com.arabrentals.arab_rentals_app`.
- namespace: `com.arabrentals.arab_rentals_app`.
- versionName/versionCode: من Flutter `1.0.1+2`.
- compileSdk: `36`.
- targetSdk: `35`.
- minSdk: `maxOf(23, flutter.minSdkVersion)`.
- signing:
  - مدعوم عبر `mobile_app/android/key.properties`.
  - حالياً لا يوجد release keystore داخل المشروع، لذلك build المحلي يستخدم debug fallback.
  - قبل رفع Google Play يجب إنشاء upload keystore حقيقي وتشغيل build من جديد.
- permissions:
  - `android.permission.INTERNET`
  - `android.permission.ACCESS_FINE_LOCATION`
  - `android.permission.ACCESS_COARSE_LOCATION`
- permissions غير موجودة لأنها غير مستخدمة حالياً:
  - camera
  - notifications
  - read/write external storage
- launcher icon:
  - ملفات launcher icon موجودة في `android/app/src/main/res/mipmap-*`.
  - يفضل استبدالها بأيقونة IAIOI النهائية قبل النشر إذا كانت ما زالت الافتراضية.
- Google Maps API key:
  - لم يتم العثور على key أو meta-data لـ Google Maps.
- Google Sign-In SHA:
  - لا يوجد release keystore بعد، لذلك SHA النهائي غير متاح.
  - Debug SHA الحالي:
    - SHA1: `A5:7B:DB:4D:7C:38:FF:0E:B1:A7:02:C0:06:07:B6:74:76:8A:24:D2`
    - SHA-256: `B2:40:CE:C7:FC:2B:5A:27:97:53:AF:66:58:E7:93:77:E9:E4:4B:3A:77:87:7C:EF:EE:F2:4D:C6:96:97:19:2B`
  - يجب استخراج SHA-1/SHA-256 من upload/release keystore وإضافته إلى Google Cloud/Firebase قبل النشر.

## iOS

- Bundle Identifier: `com.arabrentals.arabRentalsApp`.
- Display Name: `IAIOI`.
- Version/Build: من Flutter `1.0.1+2`.
- Deployment Target: `13.0`.
- Device family: iPhone + iPad (`1,2`).
- Info.plist permissions:
  - `NSLocationWhenInUseUsageDescription`
- LSApplicationQueriesSchemes:
  - `tel`
  - `whatsapp`
- GoogleService-Info.plist:
  - غير موجود في المشروع.
- Google Sign-In URL Schemes:
  - غير موجودة لأن Google iOS client/reversed client id غير متوفرين.
  - يجب إضافتها على macOS/Xcode أو عبر plist بعد توفير Google iOS Client ID.
- Apple Sign-In:
  - غير موجود حالياً.
- App Store privacy-sensitive permissions:
  - الموقع Location مستخدم.
  - الصور/الكاميرا/الإشعارات غير مفعلة حالياً في native permissions.

## API وبيئة الإنتاج

- `mobile_app/lib/src/core/config/app_config.dart` يستخدم افتراضياً:
  - `https://iaioi.com/api/v1`
- تم البحث عن:
  - `localhost`
  - `127.0.0.1`
  - `10.0.2.2`
  - `toolsfb.com`
- لم يتم العثور على روابط API تجريبية.
- ظهور `http://` في نتائج البحث كان فقط داخل XML doctype أو منطق فحص روابط media، وليس Base URL.

## مقاومة الأعطال

- أغلب صفحات التحميل تستخدم `try/catch` و `EmptyState` عند فشل API أو عدم وجود بيانات.
- Splash ينتقل إلى الصفحة الرئيسية ولا يعتمد على طلب API قد يسبب شاشة بيضاء.
- API logs أصبحت debug-only في release.
- عند عدم وجود بيانات مواعيد في تفاصيل الإعلان يتم عرض حالة فارغة بدل انهيار الصفحة.
- انتهاء الجلسة لا يحتوي refresh token تلقائي حالياً؛ الطلبات المحمية تعرض رسائل خطأ أو تطلب تسجيل الدخول حسب الصفحة.

## أوامر تم تشغيلها

```bash
flutter clean
flutter pub get
flutter analyze
flutter test
flutter build apk --release
flutter build appbundle --release
flutter build ios --release --no-codesign
```

## نتائج الأوامر

- `flutter clean`: نجح، مع تحذير Windows بأن بعض مجلدات `.dart_tool/ephemeral` كانت مقفلة من عملية أخرى.
- `flutter pub get`: نجح.
- `flutter analyze`: نجح، `No issues found`.
- `flutter test`: نجح، `All tests passed`.
- `flutter build apk --release`: نجح.
  - output: `mobile_app/build/app/outputs/flutter-apk/app-release.apk`
- `flutter build appbundle --release`: نجح.
  - output: `mobile_app/build/app/outputs/bundle/release/app-release.aab`
- `flutter build ios --release --no-codesign`: لم يعمل على Windows لأن Flutter build في هذه البيئة يعرض subcommands Android/Web/Windows فقط. يجب تشغيل iOS build على macOS أو Codemagic.

## أوامر Android النهائية

قبل الرفع على Google Play، أنشئ upload keystore ثم أعد بناء AAB:

```bash
cd mobile_app/android
keytool -genkeypair -v -keystore upload-keystore.jks -keyalg RSA -keysize 2048 -validity 10000 -alias upload
copy key.properties.example key.properties
```

عدّل `android/key.properties` بالقيم الحقيقية، ثم:

```bash
cd mobile_app
flutter clean
flutter pub get
flutter build appbundle --release --dart-define=API_BASE_URL=https://iaioi.com/api/v1
```

إذا كان Google Sign-In مطلوباً في الإصدار:

```bash
flutter build appbundle --release ^
  --dart-define=API_BASE_URL=https://iaioi.com/api/v1 ^
  --dart-define=GOOGLE_CLIENT_ID=YOUR_ANDROID_OR_WEB_CLIENT_ID.apps.googleusercontent.com ^
  --dart-define=GOOGLE_SERVER_CLIENT_ID=YOUR_SERVER_CLIENT_ID.apps.googleusercontent.com
```

## أوامر iOS النهائية

على macOS أو Codemagic مع Xcode 26+:

```bash
cd mobile_app
flutter clean
flutter pub get
flutter build ios --release --no-codesign --dart-define=API_BASE_URL=https://iaioi.com/api/v1
```

للرفع إلى App Store Connect عبر Xcode:

```bash
open ios/Runner.xcworkspace
```

ثم اختر Team/Provisioning Profile/Signing، واضبط Google URL Schemes إذا كان Google Sign-In مطلوباً.

## أشياء تحتاج إدخالها يدوياً

- Android upload keystore وكلمات المرور داخل `android/key.properties`.
- Google Play App Signing setup.
- SHA-1 و SHA-256 الخاصان بالـ upload/release keystore في Google Cloud/Firebase.
- Google OAuth Client IDs:
  - Android client.
  - iOS client.
  - Web/server client إذا كان backend يتحقق من ID token.
- iOS Apple Developer Team.
- iOS provisioning profiles/certificates.
- `GoogleService-Info.plist` أو URL scheme الخاص بـ Google iOS Client إذا كان Google Sign-In مطلوباً.
- أيقونة IAIOI النهائية للـ launcher/app icon إذا كانت الأيقونات الحالية افتراضية.
- بيانات Privacy في Google Play و App Store Connect، خصوصاً Location وبيانات الحساب/الاتصال إن كانت تجمع من المستخدم.

