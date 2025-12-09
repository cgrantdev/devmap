<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Page;
use App\Models\Setting;

class PagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create static pages with comprehensive AI-generated content
        $pages = [
            [
                'slug' => 'about',
                'title' => 'About PeptideSync',
                'content' => '<h2>Our Mission</h2>
<p>PeptideSync is dedicated to advancing peptide research by connecting scientists, researchers, and laboratory professionals with trusted peptide suppliers. Our platform serves as a comprehensive resource hub, providing access to high-quality peptides, educational content, and research tools to support the scientific community in their pursuit of groundbreaking discoveries.</p>

<h2>What We Do</h2>
<p>PeptideSync operates as an innovative aggregator platform that brings together multiple verified peptide suppliers, making it easier for researchers to:</p>
<ul>
<li>Compare products and prices from various suppliers in one centralized location</li>
<li>Access comprehensive educational resources about peptide research, protocols, and best practices</li>
<li>Find detailed information about different peptide compounds, their properties, and research applications</li>
<li>Connect with reputable vendors and suppliers who meet rigorous quality standards</li>
<li>Stay informed about the latest research developments, publications, and industry news</li>
</ul>

<h2>Our Commitment to Research</h2>
<p>All products listed on PeptideSync are intended exclusively for research purposes in laboratory settings. We are committed to supporting legitimate scientific research and do not promote or endorse the use of peptides for human consumption or therapeutic purposes. Our platform strictly adheres to research-only guidelines and works exclusively with suppliers who share this commitment to scientific integrity.</p>

<h2>Quality and Verification</h2>
<p>We work diligently to ensure that all suppliers featured on our platform meet high standards of quality and reliability. While we provide a platform for comparison and information, we encourage researchers to conduct their own due diligence when selecting suppliers and to verify product specifications, purity, and documentation before making purchases. Our goal is to facilitate connections between researchers and quality suppliers while maintaining transparency throughout the process.</p>

<h2>Educational Resources</h2>
<p>Beyond product listings, PeptideSync provides extensive educational content including research articles, dosage guidelines, storage protocols, and safety information. Our goal is to empower researchers with the knowledge they need to conduct safe and effective peptide research. We believe that well-informed researchers make better decisions and contribute more meaningfully to scientific advancement.</p>

<h2>Contact Us</h2>
<p>If you have questions about our platform, need assistance, or are interested in becoming a supplier partner, please visit our <a href="/contact">Contact</a> page. We\'re here to support the research community and welcome your feedback, suggestions, and collaboration opportunities.</p>',
                'meta_title' => 'About Us - PeptideSync | Connecting Researchers with Trusted Peptide Suppliers',
                'meta_description' => 'Learn about PeptideSync and our mission to connect researchers with trusted peptide suppliers. Discover our commitment to advancing peptide research through quality products and educational resources.',
            ],
            [
                'slug' => 'disclaimer',
                'title' => 'Disclaimer',
                'content' => '<div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; margin-bottom: 2rem;">
<p style="font-weight: 600; color: #92400e; margin: 0;"><strong>IMPORTANT:</strong> All products listed on PeptideSync are intended for research purposes only in laboratory settings. These products are NOT intended for human consumption, diagnostic use, or therapeutic purposes.</p>
</div>

<h2>Research Use Only</h2>
<p>All peptides and related products featured on PeptideSync are sold for research purposes exclusively. These products are intended for use by qualified researchers, scientists, and laboratory professionals in controlled research environments. They are NOT approved by the FDA or any regulatory agency for human consumption, medical treatment, or diagnostic purposes.</p>

<h2>No Medical Claims</h2>
<p>PeptideSync does not make any medical claims, health claims, or therapeutic claims regarding any products listed on our platform. Any information provided about peptides is for educational and research purposes only. We do not diagnose, treat, cure, or prevent any disease or medical condition. All content on this platform is intended to support scientific research and should not be construed as medical advice.</p>

<h2>User Responsibility</h2>
<p>Users of this platform are solely responsible for:</p>
<ul>
<li>Ensuring compliance with all applicable laws and regulations in their jurisdiction</li>
<li>Verifying the suitability of products for their specific research needs</li>
<li>Conducting proper due diligence on suppliers and products before making purchases</li>
<li>Handling and storing products according to safety protocols and laboratory best practices</li>
<li>Obtaining necessary permits, licenses, or approvals for their research activities</li>
<li>Following all institutional and regulatory guidelines for research involving peptides</li>
</ul>

<h2>Third-Party Suppliers</h2>
<p>PeptideSync serves as an aggregator platform that provides information and links to third-party suppliers. We do not manufacture, sell, or directly supply any products. All transactions are conducted directly between users and suppliers. PeptideSync is not responsible for the quality, purity, accuracy of product descriptions, shipping, or any other aspects of transactions between users and suppliers. Users should verify all product information directly with suppliers before making purchases.</p>

<h2>No Warranties</h2>
<p>PeptideSync provides this platform "as is" without warranties of any kind, either express or implied. We do not warrant the accuracy, completeness, or usefulness of any information on this platform. We are not liable for any errors, omissions, or inaccuracies in the content provided. Users should independently verify all information before relying on it for research purposes.</p>

<h2>Limitation of Liability</h2>
<p>PeptideSync, its owners, operators, and affiliates shall not be liable for any direct, indirect, incidental, special, consequential, or punitive damages resulting from the use of this platform, including but not limited to damages from product purchases, research outcomes, or reliance on information provided. This limitation applies to the fullest extent permitted by law.</p>

<h2>Regulatory Compliance</h2>
<p>It is the responsibility of users to ensure that their purchase and use of any products complies with all applicable local, state, federal, and international laws and regulations. Some peptides may be subject to restrictions or require special permits in certain jurisdictions. Users must verify regulatory requirements in their location before purchasing or using any products.</p>

<h2>Educational Content</h2>
<p>All educational content, articles, and information provided on PeptideSync are for informational purposes only. This content should not be construed as medical advice, treatment recommendations, or professional guidance. Always consult with qualified professionals for specific research protocols and safety procedures. The information provided is general in nature and may not apply to specific research situations.</p>

<h2>Acceptance of Terms</h2>
<p>By using this platform, you acknowledge that you have read, understood, and agree to be bound by this disclaimer. If you do not agree with any part of this disclaimer, you must not use this platform. Continued use of the platform constitutes acceptance of these terms.</p>

<div style="background-color: #f3f4f6; padding: 1.5rem; border-radius: 0.5rem; margin-top: 2rem;">
<p style="font-weight: 600; margin-bottom: 0.5rem;">Last Updated:</p>
<p style="margin: 0;">December 2024</p>
<p style="margin-top: 1rem; margin-bottom: 0;">This disclaimer may be updated from time to time. We encourage users to review this page periodically to stay informed of any changes.</p>
</div>',
                'meta_title' => 'Disclaimer - PeptideSync | Research Use Only Policy',
                'meta_description' => 'Read the PeptideSync disclaimer. All products are for research purposes only. Important legal information about product use, liability, and regulatory compliance.',
            ],
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'content' => '<h2>Introduction</h2>
<p>At PeptideSync, we are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services. By using our platform, you consent to the data practices described in this policy.</p>

<h2>Information We Collect</h2>
<h3>Information You Provide</h3>
<p>We may collect information that you voluntarily provide to us when you:</p>
<ul>
<li>Register for an account or create a profile</li>
<li>Contact us through our contact forms or email</li>
<li>Subscribe to our newsletter or updates</li>
<li>Submit reviews, feedback, or participate in surveys</li>
<li>Participate in research collaborations or partnerships</li>
</ul>
<p>This information may include your name, email address, phone number, organization affiliation, research interests, and any other information you choose to provide.</p>

<h3>Automatically Collected Information</h3>
<p>When you visit our website, we automatically collect certain information about your device and browsing behavior, including:</p>
<ul>
<li>IP address and approximate location data</li>
<li>Browser type and version</li>
<li>Operating system information</li>
<li>Pages visited and time spent on pages</li>
<li>Referring website addresses</li>
<li>Search terms used to find our site</li>
<li>Device identifiers and technical information</li>
</ul>

<h2>How We Use Your Information</h2>
<p>We use the information we collect for various purposes, including:</p>
<ul>
<li>To provide, maintain, and improve our services and platform functionality</li>
<li>To process your requests, transactions, and account management</li>
<li>To send you updates, newsletters, and promotional materials (with your consent)</li>
<li>To respond to your inquiries and provide customer support</li>
<li>To analyze website usage patterns and improve user experience</li>
<li>To detect, prevent, and address technical issues or security threats</li>
<li>To comply with legal obligations and enforce our terms of service</li>
<li>To conduct research and analytics to improve our services</li>
</ul>

<h2>Information Sharing and Disclosure</h2>
<p>We do not sell, trade, or rent your personal information to third parties. We may share your information only in the following circumstances:</p>
<ul>
<li><strong>Service Providers:</strong> We may share information with trusted third-party service providers who assist us in operating our website, conducting business, or serving our users, provided they agree to keep this information confidential and use it only for specified purposes.</li>
<li><strong>Legal Requirements:</strong> We may disclose information if required by law, court order, or government regulation, or to protect our rights, property, or safety, or that of our users or others.</li>
<li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction, with notice provided to users.</li>
<li><strong>With Your Consent:</strong> We may share your information with your explicit consent for specific purposes.</li>
</ul>

<h2>Cookies and Tracking Technologies</h2>
<p>We use cookies and similar tracking technologies to enhance your browsing experience, analyze website traffic, and understand user preferences. Cookies are small data files stored on your device. You can control cookie preferences through your browser settings, though disabling cookies may affect website functionality. We use both session cookies (which expire when you close your browser) and persistent cookies (which remain until deleted or expired).</p>

<h2>Data Security</h2>
<p>We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. These measures include encryption, secure servers, access controls, and regular security assessments. However, no method of transmission over the Internet or electronic storage is 100% secure, and we cannot guarantee absolute security.</p>

<h2>Your Rights and Choices</h2>
<p>Depending on your location, you may have certain rights regarding your personal information, including:</p>
<ul>
<li>The right to access and receive a copy of your personal data</li>
<li>The right to rectify inaccurate or incomplete information</li>
<li>The right to request deletion of your personal data</li>
<li>The right to object to processing of your personal data</li>
<li>The right to data portability</li>
<li>The right to withdraw consent at any time</li>
<li>The right to opt-out of marketing communications</li>
</ul>
<p>To exercise these rights, please contact us using the information provided in the Contact section below.</p>

<h2>Third-Party Links</h2>
<p>Our website may contain links to third-party websites, including supplier websites. We are not responsible for the privacy practices or content of these external sites. We encourage you to review the privacy policies of any third-party sites you visit. This privacy policy applies only to information collected by PeptideSync.</p>

<h2>Children\'s Privacy</h2>
<p>Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children. If we become aware that we have collected information from a child, we will take steps to delete such information promptly. If you believe we have collected information from a child, please contact us immediately.</p>

<h2>International Data Transfers</h2>
<p>Your information may be transferred to and processed in countries other than your country of residence. These countries may have data protection laws that differ from those in your country. By using our services, you consent to the transfer of your information to these countries. We take appropriate measures to ensure your data is protected in accordance with this privacy policy.</p>

<h2>Changes to This Privacy Policy</h2>
<p>We may update this Privacy Policy from time to time to reflect changes in our practices or for other operational, legal, or regulatory reasons. We will notify you of any material changes by posting the new Privacy Policy on this page and updating the "Last Updated" date. We encourage you to review this policy periodically to stay informed about how we protect your information.</p>

<h2>Contact Us</h2>
<p>If you have questions, concerns, or requests regarding this Privacy Policy or our data practices, please contact us through our <a href="/contact">Contact</a> page or email us at privacy@peptidesync.com.</p>

<div style="background-color: #f3f4f6; padding: 1.5rem; border-radius: 0.5rem; margin-top: 2rem;">
<p style="font-weight: 600; margin-bottom: 0.5rem;">Last Updated:</p>
<p style="margin: 0;">December 2024</p>
</div>',
                'meta_title' => 'Privacy Policy - PeptideSync | How We Protect Your Data',
                'meta_description' => 'Read PeptideSync\'s privacy policy. Learn how we collect, use, and protect your personal information. Your privacy is important to us.',
            ],
            [
                'slug' => 'contact',
                'title' => 'Contact Us',
                'content' => '<p>Get in touch with us for questions, support, partnership opportunities, or general inquiries. We\'re here to help and welcome your feedback.</p>

<h2>Contact Information</h2>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
<div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.5rem;">
<h3 style="margin-top: 0; color: #1f2937;">General Inquiries</h3>
<p style="color: #6b7280; margin-bottom: 0.5rem;">For general questions about our platform, services, or how to get started:</p>
<p style="font-weight: 600; color: #1f2937; margin: 0;">info@peptidesync.com</p>
</div>

<div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.5rem;">
<h3 style="margin-top: 0; color: #1f2937;">Supplier Partnerships</h3>
<p style="color: #6b7280; margin-bottom: 0.5rem;">Interested in becoming a featured supplier on our platform?</p>
<p style="font-weight: 600; color: #1f2937; margin: 0;">suppliers@peptidesync.com</p>
</div>

<div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.5rem;">
<h3 style="margin-top: 0; color: #1f2937;">Technical Support</h3>
<p style="color: #6b7280; margin-bottom: 0.5rem;">Need help with your account or experiencing technical issues?</p>
<p style="font-weight: 600; color: #1f2937; margin: 0;">support@peptidesync.com</p>
</div>

<div style="background-color: #f9fafb; padding: 1.5rem; border-radius: 0.5rem;">
<h3 style="margin-top: 0; color: #1f2937;">Research Collaboration</h3>
<p style="color: #6b7280; margin-bottom: 0.5rem;">For research partnerships or educational content inquiries:</p>
<p style="font-weight: 600; color: #1f2937; margin: 0;">research@peptidesync.com</p>
</div>
</div>

<h2>Response Time</h2>
<div style="background-color: #dbeafe; border-left: 4px solid #3b82f6; padding: 1rem; border-radius: 0.5rem; margin: 2rem 0;">
<p style="font-weight: 600; color: #1e40af; margin-bottom: 0.5rem;">We aim to respond to all inquiries within 24-48 hours during business days.</p>
<p style="color: #1e3a8a; margin: 0;">For urgent matters, please indicate this in your message subject line or contact us directly via email.</p>
</div>

<h2>Business Hours</h2>
<p>Our support team is available Monday through Friday, 9:00 AM to 5:00 PM EST. We monitor emails outside of business hours and will respond as soon as possible.</p>

<h2>Partnership Inquiries</h2>
<p>If you\'re interested in partnering with PeptideSync, whether as a supplier, research institution, or content collaborator, please reach out to us. We\'re always looking for opportunities to expand our network and support the research community.</p>

<h2>Feedback and Suggestions</h2>
<p>We value your feedback and suggestions for improving our platform. If you have ideas, concerns, or recommendations, please don\'t hesitate to contact us. Your input helps us better serve the research community.</p>',
                'meta_title' => 'Contact Us - PeptideSync | Get in Touch',
                'meta_description' => 'Contact PeptideSync for support, partnerships, or inquiries. We\'re here to help researchers and suppliers connect.',
            ],
            [
                'slug' => 'calculator',
                'title' => 'Peptide Research Calculator',
                'content' => '<p>Useful calculators for peptide dosage, reconstitution, and research calculations. These tools are designed to assist researchers in their laboratory work with peptides.</p>

<h2>Available Calculators</h2>
<p>Our calculator tools help researchers perform essential calculations for peptide research, including:</p>
<ul>
<li><strong>Dosage Calculations:</strong> Determine appropriate peptide dosages based on body weight and desired dosage per kilogram</li>
<li><strong>Reconstitution:</strong> Calculate the concentration of reconstituted peptide solutions</li>
<li><strong>Volume Calculations:</strong> Determine the volume needed to achieve a specific dosage from a reconstituted solution</li>
</ul>

<h2>Important Research Notes</h2>
<div style="background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 0.5rem; margin: 2rem 0;">
<p style="font-weight: 600; color: #92400e; margin-bottom: 0.5rem;">Research Use Only</p>
<ul style="color: #78350f; margin: 0.5rem 0;">
<li>These calculators are for research purposes only and should not be used for human consumption or therapeutic purposes</li>
<li>Always consult published research literature and protocols for appropriate dosages in your specific research context</li>
<li>Dosage requirements may vary significantly based on the specific peptide, research model, and experimental design</li>
<li>Proper storage and handling of reconstituted peptides is critical for maintaining stability and efficacy</li>
<li>These calculations are estimates and should be verified with qualified researchers and laboratory protocols</li>
</ul>
</div>

<h2>How to Use</h2>
<p>Each calculator is designed to be intuitive and easy to use. Simply enter the required values in the input fields, and the calculator will automatically compute the results. Always double-check your calculations and verify results against published protocols and laboratory guidelines.</p>

<h2>Disclaimer</h2>
<p>These calculators provide estimates based on standard formulas. Actual requirements may vary based on specific research conditions, peptide properties, and experimental protocols. Always consult with qualified researchers and follow institutional guidelines when conducting peptide research.</p>',
                'meta_title' => 'Peptide Calculator - PeptideSync | Research Calculation Tools',
                'meta_description' => 'Use PeptideSync\'s peptide research calculators for dosage, reconstitution, and volume calculations. Essential tools for laboratory research.',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }

        // Create default hero slides if none exist
        $heroSlidesSetting = Setting::where('key', 'hero_slides')->first();
        if (!$heroSlidesSetting) {
            $defaultHeroSlides = [
                [
                    'title' => 'Hello Hero',
                    'subtitle' => 'Want your brand to stand out? Advertise with us right here!',
                    'cta_text' => 'Contact Us to Advertise',
                    'cta_url' => '/contact',
                    'image' => null,
                    'order' => 0,
                    'is_active' => true,
                ],
                [
                    'title' => 'Discover Research Peptides',
                    'subtitle' => 'Explore our comprehensive collection of high-quality research peptides.',
                    'cta_text' => 'Browse Products',
                    'cta_url' => '/products',
                    'image' => null,
                    'order' => 1,
                    'is_active' => true,
                ],
                [
                    'title' => 'Trusted Vendors',
                    'subtitle' => 'Connect with verified vendors in the peptide research community.',
                    'cta_text' => 'View Vendors',
                    'cta_url' => '/brands',
                    'image' => null,
                    'order' => 2,
                    'is_active' => true,
                ],
                [
                    'title' => 'Research Education',
                    'subtitle' => 'Learn about peptides, research protocols, and best practices.',
                    'cta_text' => 'Learn More',
                    'cta_url' => '/education',
                    'image' => null,
                    'order' => 3,
                    'is_active' => true,
                ],
            ];

            Setting::create([
                'key' => 'hero_slides',
                'value' => json_encode($defaultHeroSlides),
            ]);
        }
    }
}
