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
                            <h1 style="margin:0 0 16px;font-size:24px;font-weight:600;color:#0A0B0E;">Thanks, {{ $companyName }}!</h1>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#52525B;">
                                We've received your application. Our team is reviewing it now and we'll send you a separate email once your account has been activated — usually within 1–2 business days.
                            </p>

                            <!-- Account info box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F4F5;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#71717A;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Application Summary</p>
                                        <p style="margin:0 0 4px;font-size:15px;color:#0A0B0E;">
                                            <strong>Company:</strong> {{ $companyName }}
                                        </p>
                                        <p style="margin:0;font-size:15px;color:#0A0B0E;">
                                            <strong>Email:</strong> {{ $email }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:32px 0 12px;font-size:16px;font-weight:600;color:#0A0B0E;">What happens next?</h2>
                            <ol style="margin:0 0 24px;padding-left:20px;font-size:15px;line-height:1.7;color:#52525B;">
                                <li style="margin-bottom:8px;"><strong>Review</strong> — our team reviews your application and tests your REST API connection</li>
                                <li style="margin-bottom:8px;"><strong>Approval</strong> — once your store is verified, we'll activate your account and email you with your dashboard access</li>
                                <li style="margin-bottom:8px;"><strong>Go live</strong> — your products are imported and you're listed on PeptideMap</li>
                            </ol>

                            <p style="margin:0;font-size:14px;line-height:1.7;color:#71717A;">
                                There's nothing you need to do right now. We'll be in touch shortly.
                            </p>

                            <p style="margin:16px 0 0;font-size:15px;line-height:1.7;color:#52525B;">
                                Questions? Reach us at <a href="mailto:info@peptidemap.com" style="color:#4338CA;text-decoration:none;">info@peptidemap.com</a>
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
