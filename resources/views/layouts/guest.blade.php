<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <div class="text-sm text-gray-600">
                    <button type="button" class="text-gray-600 hover:text-gray-800 underline" onclick="openPrivacyModal()">Privacy Policy</button>
                    <span class="mx-2">•</span>
                    <button type="button" class="text-gray-600 hover:text-gray-800 underline" onclick="openTermsModal()">Terms of Service</button>
                    <span class="mx-2">•</span>
                    <button type="button" class="text-gray-600 hover:text-gray-800 underline" onclick="openSupportModal()">Contact Support</button>
                </div>
                <p class="mt-2 text-xs text-gray-500">&copy; {{ date('Y') }} Sincidentre. All rights reserved.</p>
            </div>
        </div>

        <!-- Privacy Policy Modal -->
        <div id="privacyModal" class="policy-modal" aria-hidden="true">
            <div class="policy-modal-backdrop" onclick="closePolicyModals()"></div>
            <div class="policy-modal-panel" role="dialog" aria-modal="true" aria-labelledby="privacyModalTitle">
                <div class="policy-modal-header">
                    <h3 id="privacyModalTitle">Privacy Policy</h3>
                    <button type="button" class="policy-modal-close" onclick="closePolicyModals()" aria-label="Close">&times;</button>
                </div>
                <div class="policy-modal-body">
                    <div class="policy-content">
                        <h4>Information We Collect</h4>
                        <p>We collect information you provide directly to us, such as when you create an account, submit reports, or contact us for support.</p>

                        <h4>How We Use Your Information</h4>
                        <p>We use the information we collect to:</p>
                        <ul>
                            <li>Provide, maintain, and improve our services</li>
                            <li>Process and manage incident reports</li>
                            <li>Send you technical notices and support messages</li>
                            <li>Respond to your comments and questions</li>
                        </ul>

                        <h4>Information Sharing</h4>
                        <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent, except as described in this policy.</p>

                        <h4>Data Security</h4>
                        <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.</p>

                        <h4>Contact Us</h4>
                        <p>If you have any questions about this Privacy Policy, please contact us through the Contact Support option.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms of Service Modal -->
        <div id="termsModal" class="policy-modal" aria-hidden="true">
            <div class="policy-modal-backdrop" onclick="closePolicyModals()"></div>
            <div class="policy-modal-panel" role="dialog" aria-modal="true" aria-labelledby="termsModalTitle">
                <div class="policy-modal-header">
                    <h3 id="termsModalTitle">Terms of Service</h3>
                    <button type="button" class="policy-modal-close" onclick="closePolicyModals()" aria-label="Close">&times;</button>
                </div>
                <div class="policy-modal-body">
                    <div class="policy-content">
                        <h4>Acceptance of Terms</h4>
                        <p>By accessing and using Sincidentre, you accept and agree to be bound by the terms and provision of this agreement.</p>

                        <h4>Use License</h4>
                        <p>Permission is granted to temporarily access Sincidentre for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.</p>

                        <h4>User Account</h4>
                        <p>When you create an account, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding your account credentials.</p>

                        <h4>Prohibited Uses</h4>
                        <p>You may not use Sincidentre:</p>
                        <ul>
                            <li>For any unlawful purpose or to solicit others to perform unlawful acts</li>
                            <li>To violate any international, federal, provincial, or state regulations, rules, laws, or local ordinances</li>
                            <li>To submit false or misleading information</li>
                            <li>To interfere with or circumvent the security features of the service</li>
                        </ul>

                        <h4>Report Content</h4>
                        <p>You are responsible for the content of reports you submit. All reports should be truthful, accurate, and submitted in good faith.</p>

                        <h4>Termination</h4>
                        <p>We may terminate or suspend your account and access to the service immediately, without prior notice, for conduct that we believe violates these Terms of Service.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Support Modal -->
        <div id="supportModal" class="policy-modal" aria-hidden="true">
            <div class="policy-modal-backdrop" onclick="closePolicyModals()"></div>
            <div class="policy-modal-panel" role="dialog" aria-modal="true" aria-labelledby="supportModalTitle">
                <div class="policy-modal-header">
                    <h3 id="supportModalTitle">Contact Support</h3>
                    <button type="button" class="policy-modal-close" onclick="closePolicyModals()" aria-label="Close">&times;</button>
                </div>
                <div class="policy-modal-body">
                    <div class="policy-content">
                        <h4>Get Help</h4>
                        <p>We're here to help you with any questions or issues you may have with Sincidentre.</p>

                        <div class="support-options">
                            <div class="support-option">
                                <h5>📧 Email Support</h5>
                                <p>For general inquiries and technical support:</p>
                                <p><strong>support@sincidentre.edu</strong></p>
                            </div>

                            <div class="support-option">
                                <h5>📞 Phone Support</h5>
                                <p>For urgent matters during business hours:</p>
                                <p><strong>(555) 123-4567</strong></p>
                                <p><small>Monday - Friday, 8:00 AM - 5:00 PM</small></p>
                            </div>

                            <div class="support-option">
                                <h5>🏢 In-Person Support</h5>
                                <p>Visit the IT Help Desk:</p>
                                <p><strong>Administration Building, Room 101</strong></p>
                                <p><small>Monday - Friday, 9:00 AM - 4:00 PM</small></p>
                            </div>

                            <div class="support-option">
                                <h5>📋 Report Issues</h5>
                                <p>For technical issues with the system:</p>
                                <p><strong>ithelp@sincidentre.edu</strong></p>
                            </div>
                        </div>

                        <div class="support-note">
                            <p><strong>Note:</strong> For incident-related questions, please contact the Student Discipline Office directly at <strong>discipline@sincidentre.edu</strong></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
        /* Footer link buttons */
        .footer-links button {
            background: none;
            border: none;
            cursor: pointer;
            text-decoration: underline;
        }

        /* Policy Modal Styles */
        .policy-modal {
            position: fixed;
            inset: 0;
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 4000;
            padding: 1rem;
        }

        .policy-modal.show {
            display: flex;
        }

        .policy-modal-backdrop {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
            cursor: pointer;
        }

        .policy-modal-panel {
            position: relative;
            z-index: 1;
            width: min(700px, 100%);
            max-height: 80vh;
            border-radius: 8px;
            background: white;
            color: #1f2937;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            display: flex;
            flex-direction: column;
        }

        .policy-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }

        .policy-modal-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #1f2937;
        }

        .policy-modal-close {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: all 0.2s ease;
            line-height: 1;
        }

        .policy-modal-close:hover {
            color: #1f2937;
            background: #f3f4f6;
        }

        .policy-modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .policy-content h4 {
            color: #1f2937;
            font-size: 1.1rem;
            margin: 1.5rem 0 0.75rem 0;
            padding-bottom: 0.25rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .policy-content h4:first-child {
            margin-top: 0;
        }

        .policy-content h5 {
            color: #1f2937;
            font-size: 1rem;
            margin: 1rem 0 0.5rem 0;
        }

        .policy-content p {
            color: #4b5563;
            line-height: 1.6;
            margin: 0.75rem 0;
        }

        .policy-content ul {
            color: #4b5563;
            line-height: 1.6;
            margin: 0.75rem 0;
            padding-left: 1.5rem;
        }

        .policy-content li {
            margin: 0.25rem 0;
        }

        .support-options {
            margin: 1.5rem 0;
        }

        .support-option {
            background: #f9fafb;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .support-option h5 {
            margin-top: 0;
            color: #1f2937;
            font-size: 1rem;
        }

        .support-option p {
            margin: 0.5rem 0;
        }

        .support-option strong {
            color: #1f2937;
        }

        .support-note {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1.5rem;
        }

        .support-note p {
            margin: 0;
            color: #92400e;
        }
        </style>

        <script>
        // Policy Modal Functions
        function openPrivacyModal() {
            const modal = document.getElementById('privacyModal');
            if (modal) {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        }

        function openTermsModal() {
            const modal = document.getElementById('termsModal');
            if (modal) {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        }

        function openSupportModal() {
            const modal = document.getElementById('supportModal');
            if (modal) {
                modal.classList.add('show');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }
        }

        function closePolicyModals() {
            const modals = ['privacyModal', 'termsModal', 'supportModal'];
            modals.forEach(function(modalId) {
                const modal = document.getElementById(modalId);
                if (modal && modal.classList.contains('show')) {
                    modal.classList.remove('show');
                    modal.setAttribute('aria-hidden', 'true');
                }
            });
            document.body.style.overflow = '';
        }

        // Close modals on escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const policyModals = ['privacyModal', 'termsModal', 'supportModal'];
                const openPolicyModal = policyModals.find(function(modalId) {
                    const modal = document.getElementById(modalId);
                    return modal && modal.classList.contains('show');
                });

                if (openPolicyModal) {
                    closePolicyModals();
                    return;
                }
            }
        });
        </script>
    </body>
</html>
