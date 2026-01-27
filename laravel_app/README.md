# MedAssist (Laravel App)

MedAssist is a hospital management system designed to streamline workflows by connecting Doctors, Pharmacists, Lab Assistants, Patients, and Hospital Admins in a single platform.

## Key Features

- **Role-Based Dashboards**: tailored interfaces for Doctors, Pharmacists, Lab Assistants, and Patients.
- **Unified Navigation**: Consistent, accessible navigation bar across all authorized pages.
- **Pharmacy Management**:
  - Real-time Inventory Tracking.
  - **In-Stock Medicines View**: Filtered list showing only available stock with detailed columns for Composition, Expiry Date, Price, and Quantity.
  - Dispensing Workflow: Seamless prescription fulfillment.
  - Stock Alerts: Low stock and near-expiry notifications.
- **Doctor Consultation**: Digital prescription generation and patient history tracking.
- **Lab Integration**: Management of lab reports and investigations.

## Recent Updates

- **Pharmacist Dashboard**: Updated layout to display stock summary cards (Medicines In Stock, Low Stock, Near Expiry, Stock Value) in a clean, single-line horizontal view.
- **Inventory Management**: Enhanced the "In-Stock Medicines" page to include detailed information such as generic composition, expiry dates, and pricing, filtering out out-of-stock items for better usability.
- **Navigation**: Standardized the blue navigation bar across all dashboard pages for a consistent user experience.

## Tech Stack

- **Framework**: Laravel
- **Frontend**: Blade Templates, Tailwind CSS
- **Database**: MySQL

## Setup

See the root README for the full installation steps: [README.md](../README.md)

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
