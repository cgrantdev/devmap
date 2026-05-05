<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to PeptideMap</title>
</head>
<body style="margin:0;padding:0;background-color:#f8f9fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0A0B0E;padding:32px 40px;text-align:center;">
                            <span style="color:#ffffff;font-size:22px;font-weight:700;letter-spacing:-0.01em;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">PeptideMap</span>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <!-- Success badge -->
                            <table cellpadding="0" cellspacing="0" style="margin:0 0 20px;">
                                <tr>
                                    <td style="background-color:#ECFDF5;color:#065F46;border-radius:9999px;padding:6px 12px;font-size:11px;font-weight:600;letter-spacing:0.08em;text-transform:uppercase;">
                                        ✓ Approved for Launch
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:0 0 16px;font-size:26px;font-weight:600;color:#0A0B0E;letter-spacing:-0.01em;">
                                You're in, {{ $companyName }} 🎉
                            </h1>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#52525B;">
                                Welcome aboard. Your application has been reviewed and approved — you're officially part of the PeptideMap launch cohort.
                            </p>

                            <!-- Launch date callout -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F4F5;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 6px;font-size:11px;color:#71717A;text-transform:uppercase;letter-spacing:0.08em;font-weight:600;">🚀 Launch Date</p>
                                        <p style="margin:0 0 8px;font-size:18px;font-weight:600;color:#0A0B0E;letter-spacing:-0.01em;">
                                            May 11, 2026
                                        </p>
                                        <p style="margin:0;font-size:14px;line-height:1.6;color:#52525B;">
                                            That's when PeptideMap goes live and your storefront, products, and pricing become visible to researchers. Upon launch, you'll receive a separate email with sign-in details for your vendor dashboard.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:32px 0 12px;font-size:16px;font-weight:600;color:#0A0B0E;">Between now and launch</h2>
                            <p style="margin:0 0 12px;font-size:15px;line-height:1.7;color:#52525B;">
                                We're working in the background to:
                            </p>
                            <ul style="margin:0 0 24px;padding-left:20px;font-size:15px;line-height:1.7;color:#52525B;">
                                <li style="margin-bottom:8px;">Import your full product catalog and match items to compound listings</li>
                                <li style="margin-bottom:8px;">Verify your COAs and apply your verified-vendor badge</li>
                                <li style="margin-bottom:8px;">Polish your storefront page on the directory</li>
                            </ul>

                            <h2 style="margin:32px 0 12px;font-size:16px;font-weight:600;color:#0A0B0E;">When we launch, you'll be able to:</h2>
                            <ul style="margin:0 0 24px;padding-left:20px;font-size:15px;line-height:1.7;color:#52525B;">
                                <li style="margin-bottom:8px;">Sign in at <strong>peptidemap.com/login</strong> with the email + password you chose during registration</li>
                                <li style="margin-bottom:8px;">Upload a logo and customize your storefront branding</li>
                                <li style="margin-bottom:8px;">Verify your imported catalog and feature your best products</li>
                                <li style="margin-bottom:8px;">View storefront analytics — clicks, comparisons, and reviews</li>
                            </ul>

                            <!-- Demo CTA -->
                            <table cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="background-color:#0F172A;border-radius:8px;">
                                        <a href="https://demo.peptidemap.com" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">
                                            Preview the directory →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 24px;font-size:13px;color:#71717A;line-height:1.6;">
                                Same look and feel as the real site, with sample vendors, so you can see your future home before launch day.
                            </p>

                            <p style="margin:0;font-size:15px;line-height:1.7;color:#52525B;">
                                Anything you need in the meantime, reach us at <a href="mailto:info@peptidemap.com" style="color:#4338CA;text-decoration:none;">info@peptidemap.com</a>. We'll be in touch on launch day.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;border-top:1px solid #E4E4E7;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#A1A1AA;">
                                © {{ date('Y') }} PeptideMap. For research use only.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
