<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Privacy Policy - MedAssist</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="antialiased bg-slate-50 text-slate-800">
        <div class="relative min-h-screen bg-slate-50">
            <!-- Navigation -->
            <div class="fixed top-0 right-0 px-6 py-4 z-50 w-full flex justify-between items-center bg-white/90 backdrop-blur border-b border-slate-200 shadow-sm">
                <a href="{{ url('/') }}" class="font-bold text-blue-900 text-lg flex items-center gap-2">
                    <i class="fa-solid fa-user-doctor"></i> MedAssist
                </a>
                <div class="space-x-4">
                    <a href="{{ route('contact') }}" class="text-sm text-slate-700 hover:text-blue-900 underline font-semibold">Contact Us</a>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="text-sm text-slate-700 hover:text-blue-900 underline">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm text-slate-700 hover:text-blue-900 underline font-semibold">Log in</a>
                    @endauth
                </div>
            </div>

            <!-- Content -->
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-24 pb-12">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8 sm:p-12">
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-blue-900 mb-2">Privacy Policy</h1>
                    <p class="text-sm text-slate-500 mb-8">Last Updated: {{ date('F d, Y') }}</p>

                    <div class="prose prose-slate max-w-none">
                        <p class="mb-4">
                            <strong>MedAssist</strong> ("we," "our," or "us") is committed to protecting the privacy and security of your personal information and sensitive medical data. This Privacy Policy outlines our practices regarding the collection, storage, usage, and disclosure of information when you use our website, mobile application, and services (collectively, the "Platform").
                        </p>
                        <p class="mb-6">
                            This Privacy Policy is published in compliance with the <strong>Information Technology Act, 2000</strong>, the <strong>Information Technology (Reasonable Security Practices and Procedures and Sensitive Personal Data or Information) Rules, 2011</strong> ("SPDI Rules"), and the <strong>Telemedicine Practice Guidelines, 2020</strong> issued by the Board of Governors in supersession of the Medical Council of India.
                        </p>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">1. Information We Collect</h2>
                        <p class="mb-4">We collect the following types of information to provide and improve our healthcare services:</p>
                        <ul class="list-disc pl-5 space-y-2 mb-6">
                            <li><strong>Personal Information (PI):</strong> Name, age, gender, date of birth, address, phone number, email address, and other contact details.</li>
                            <li><strong>Sensitive Personal Data or Information (SPDI):</strong>
                                <ul class="list-circle pl-5 mt-2 space-y-1">
                                    <li>Physical, physiological, and mental health condition.</li>
                                    <li>Sexual orientation.</li>
                                    <li>Medical records and history (prescriptions, lab reports, diagnosis details).</li>
                                    <li>Biometric information.</li>
                                    <li>Financial information (bank account or credit card details) for payment processing.</li>
                                </ul>
                            </li>
                            <li><strong>Technical Information:</strong> IP address, device type, operating system, browser type, and usage patterns.</li>
                        </ul>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">2. Purpose of Collection and Usage</h2>
                        <p class="mb-4">We use your information for the following purposes:</p>
                        <ul class="list-disc pl-5 space-y-2 mb-6">
                            <li>To provide medical consultation, diagnosis, and treatment services via the Platform.</li>
                            <li>To maintain electronic health records (EHR) as per Indian medical standards.</li>
                            <li>To facilitate communication between patients, doctors, pharmacists, and diagnostic labs.</li>
                            <li>To process payments and billing.</li>
                            <li>To improve our services, user experience, and Platform security.</li>
                            <li>To comply with legal obligations, court orders, or government requests.</li>
                        </ul>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">3. Consent</h2>
                        <p class="mb-6">
                            By using our Platform and providing your information, you explicitly consent to the collection, use, and processing of your Personal Information and SPDI in accordance with this Privacy Policy. You have the right to withdraw your consent at any time by writing to our Grievance Officer. However, withdrawal of consent may result in our inability to provide you with certain services.
                        </p>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">4. Disclosure and Transfer of Information</h2>
                        <p class="mb-4">We may share your information in the following circumstances:</p>
                        <ul class="list-disc pl-5 space-y-2 mb-6">
                            <li><strong>Healthcare Providers:</strong> With doctors, pharmacists, and labs registered on the Platform to facilitate your treatment and care.</li>
                            <li><strong>Service Providers:</strong> With third-party vendors who assist us in operating the Platform (e.g., payment gateways, cloud storage providers), subject to strict confidentiality agreements.</li>
                            <li><strong>Legal Requirements:</strong> When required by law, court order, or government authority, or to protect our rights and safety.</li>
                        </ul>
                        <p class="mb-6">We do not sell or rent your personal information to third parties for marketing purposes.</p>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">5. Data Security</h2>
                        <p class="mb-6">
                            We implement reasonable security practices and procedures as mandated by the SPDI Rules, 2011, including encryption, access controls, and secure data storage, to protect your information from unauthorized access, loss, or misuse. However, no method of transmission over the internet is completely secure, and we cannot guarantee absolute security.
                        </p>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">6. Retention of Information</h2>
                        <p class="mb-6">
                            We retain your medical records and personal information for as long as necessary to provide services to you or as required by applicable laws (e.g., the Telemedicine Practice Guidelines require retention of records for a specific period).
                        </p>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">7. User Rights</h2>
                        <p class="mb-6">
                            You have the right to review the information you have provided and request corrections or amendments if it is inaccurate or deficient. You may contact us at the details provided below to exercise these rights.
                        </p>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">8. Grievance Officer</h2>
                        <p class="mb-4">
                            In accordance with the Information Technology Act, 2000 and Rules made thereunder, the contact details of the Grievance Officer are provided below:
                        </p>
                        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                            <p><strong>Name:</strong> MedAssist Compliance Team</p>
                            <p><strong>Email:</strong> sultan@niufin.cloud</p>
                            <p><strong>Address:</strong> Niufin Cloud Headquarters, Niu Aurora Tech Innovations Private Limited, Ward No 1, Sorbhog, Barpeta, Assam, India - 781317</p>
                        </div>

                        <h2 class="text-xl font-bold text-slate-800 mt-8 mb-4">9. Changes to this Policy</h2>
                        <p class="mb-6">
                            We may update this Privacy Policy from time to time. We will notify you of any significant changes by posting the new Privacy Policy on this page. You are advised to review this Privacy Policy periodically for any changes.
                        </p>

                        <div class="mt-12 pt-8 border-t border-slate-200 text-center">
                            <p class="text-slate-500 text-sm">
                                &copy; {{ date('Y') }} MedAssist / NiuFin Cloud. All rights reserved.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
