# MedAssist v1.4 Release Notes

## 🚀 Overview
Version 1.4 introduces a major rebranding from "Dr. AI" to "**MedAssist**" to better reflect our comprehensive healthcare management capabilities. This release also enhances user support with a dedicated Contact Us page and direct WhatsApp integration.

## ✨ Key Features & Improvements

### 🎨 Rebranding to MedAssist
- **Unified Identity**: The application has been renamed to **MedAssist** across all platforms (Web, Android, Reports).
- **Visual Updates**: Updated logos, page titles, and navigation headers to reflect the new brand identity.
- **Documentation**: All legal documents and user guides now reference MedAssist.

### 📞 Enhanced Support
- **Contact Us Page**: Added a new dedicated support page (`/contact-us`) accessible from the footer and privacy policy.
- **WhatsApp Integration**: Direct one-click chat support via WhatsApp (**+91 33690 28316**) for instant assistance.
- **Quick Help**: Added a help icon in the main dashboard navigation for logged-in users.

### 🛠️ Technical Improvements
- **Route Updates**: Registered new routes for support pages.
- **View Enhancements**: Refined layouts for better accessibility and mobile responsiveness.

## 📂 Technical Details
- **Updated Files**:
  - `laravel_app/config/clinic.php`: Updated global configuration for branding.
  - `laravel_app/resources/views/layouts/navigation_blue.blade.php`: Branding and new help link.
  - `laravel_app/routes/web.php`: Added contact routes.
  - `android_app/PLAY_STORE_LISTING.md`: Updated app name and descriptions.

## 📦 Deployment
1. Pull the latest changes.
2. Clear application cache: `php artisan optimize:clear`.
3. No database migrations required for this release.
