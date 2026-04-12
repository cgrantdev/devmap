<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to PeptideMaps</title>
</head>
<body style="margin:0;padding:0;background-color:#f8f9fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0A0B0E;padding:32px 40px;text-align:center;">
                            <img src="{{ url('/images/logo.png') }}" alt="PeptideMaps" style="height:40px;filter:brightness(0) invert(1);" />
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:40px;">
                            <h1 style="margin:0 0 16px;font-size:24px;font-weight:600;color:#0A0B0E;">Welcome to PeptideMaps, {{ $companyName }}!</h1>

                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#52525B;">
                                Your vendor account has been created. Here are your login credentials:
                            </p>

                            <!-- Credentials box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F4F5;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <p style="margin:0 0 8px;font-size:13px;color:#71717A;text-transform:uppercase;letter-spacing:0.05em;font-weight:600;">Login Details</p>
                                        <p style="margin:0 0 4px;font-size:15px;color:#0A0B0E;">
                                            <strong>Email:</strong> {{ $email }}
                                        </p>
                                        <p style="margin:0 0 4px;font-size:15px;color:#0A0B0E;">
                                            <strong>Password:</strong> <code style="background:#E4E4E7;padding:2px 6px;border-radius:4px;font-family:monospace;">{{ $password }}</code>
                                        </p>
                                        <p style="margin:12px 0 0;font-size:13px;color:#A1A1AA;">
                                            Please change your password after first login.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" style="margin-bottom:24px;">
                                <tr>
                                    <td style="background-color:#4338CA;border-radius:6px;">
                                        <a href="{{ $loginUrl }}" style="display:inline-block;padding:12px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">
                                            Sign in to your dashboard →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <h2 style="margin:32px 0 12px;font-size:16px;font-weight:600;color:#0A0B0E;">What happens next?</h2>
                            <ol style="margin:0 0 24px;padding-left:20px;font-size:15px;line-height:1.7;color:#52525B;">
                                <li style="margin-bottom:8px;"><strong>Verify your email</strong> — check your inbox for a verification link</li>
                                <li style="margin-bottom:8px;"><strong>Account review</strong> — our team will review your application (usually within 24 hours)</li>
                                <li style="margin-bottom:8px;"><strong>Go live</strong> — once approved, your products will appear on PeptideMaps</li>
                            </ol>

                            <p style="margin:0;font-size:15px;line-height:1.7;color:#52525B;">
                                Questions? Reach us at <a href="mailto:info@peptidemap.com" style="color:#4338CA;text-decoration:none;">info@peptidemap.com</a>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:24px 40px;border-top:1px solid #E4E4E7;text-align:center;">
                            <p style="margin:0;font-size:12px;color:#A1A1AA;">
                                © {{ date('Y') }} PeptideMaps. For research use only.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
