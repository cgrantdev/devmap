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
                                        ✓ Application Approved
                                    </td>
                                </tr>
                            </table>

                            <h1 style="margin:0 0 16px;font-size:26px;font-weight:600;color:#0A0B0E;letter-spacing:-0.01em;">
                                You're in, {{ $companyName }} 🎉
                            </h1>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#52525B;">
                                Your PeptideMap vendor account is now active. We've verified your store and your products are being imported in the background. Sign in to your dashboard to manage your listing, view analytics, and respond to reviews.
                            </p>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" style="margin-bottom:32px;">
                                <tr>
                                    <td style="background-color:#0F172A;border-radius:8px;">
                                        <a href="{{ $loginUrl }}" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">
                                            Sign in to your dashboard →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 8px;font-size:13px;color:#71717A;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Account</p>
                            <p style="margin:0 0 24px;font-size:14px;color:#52525B;">
                                Sign in with <strong style="color:#0A0B0E;">{{ $email }}</strong> and the password you chose during registration.
                            </p>

                            <h2 style="margin:32px 0 12px;font-size:16px;font-weight:600;color:#0A0B0E;">A few things you can do now:</h2>
                            <ul style="margin:0 0 24px;padding-left:20px;font-size:15px;line-height:1.7;color:#52525B;">
                                <li style="margin-bottom:8px;">Upload a logo and customize your storefront branding</li>
                                <li style="margin-bottom:8px;">Verify your imported product catalog and set featured items</li>
                                <li style="margin-bottom:8px;">Review your storefront analytics for clicks and comparisons</li>
                                <li style="margin-bottom:8px;">Reply to customer reviews as they come in</li>
                            </ul>

                            <p style="margin:0;font-size:15px;line-height:1.7;color:#52525B;">
                                Anything you need, reach us at <a href="mailto:info@peptidemap.com" style="color:#4338CA;text-decoration:none;">info@peptidemap.com</a>. Welcome aboard.
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
