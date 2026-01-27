# Doctor App (Android)

This is a native Android application wrapper for the Doctor Niufin Cloud platform.

## Setup

1.  **Open in Android Studio**:
    *   Open Android Studio.
    *   Select "Open" and choose the `android_app` folder.
    *   Android Studio will automatically sync the project and download necessary Gradle dependencies.

2.  **Configuration**:
    *   The app is currently configured to load `https://doctor.niufin.cloud`.
    *   To change the URL, edit `app/src/main/java/in/niufin/doctor/MainActivity.kt`.

3.  **Build**:
    *   Connect your Android device or start an emulator.
    *   Click the "Run" button (Green Play icon) in Android Studio.

## Java and Kotlin version

*   The module is configured for **Java 11** (`sourceCompatibility`/`targetCompatibility` and `kotlinOptions.jvmTarget`).
*   Use a JDK that supports Java 11 or higher when building.

## Activity patterns

*   `MainActivity` uses the **Activity Result API** instead of `startActivityForResult` and `onActivityResult` for file picking.
*   Back navigation is handled via **`OnBackPressedDispatcher`** instead of overriding `onBackPressed`.
*   When adding new activities:
    *   Prefer `registerForActivityResult` for any external intents that return results.
    *   Use `onBackPressedDispatcher.addCallback` for custom back navigation logic.

## Structure
*   `app/src/main/java`: Kotlin source code.
*   `app/src/main/res`: UI layouts and resources.
*   `app/build.gradle.kts`: App dependencies and configuration.
