<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Vendor Signup</title>
</head>
<body style="margin:0;padding:0;background-color:#f8f9fa;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f8f9fa;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0A0B0E;padding:24px 40px;">
                            <p style="margin:0;font-size:14px;color:#A1A1AA;">PeptideMaps Admin Notification</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px 40px;">
                            <h1 style="margin:0 0 8px;font-size:22px;font-weight:600;color:#0A0B0E;">New Vendor Signup</h1>
                            <p style="margin:0 0 24px;font-size:15px;color:#71717A;">A new vendor has registered on PeptideMaps and is awaiting review.</p>

                            <!-- Vendor details -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F4F5;border-radius:8px;margin-bottom:24px;">
                                <tr>
                                    <td style="padding:20px 24px;">
                                        <table width="100%" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:4px 0;font-size:13px;color:#71717A;width:120px;vertical-align:top;">Company</td>
                                                <td style="padding:4px 0;font-size:15px;color:#0A0B0E;font-weight:600;">{{ $brand->name }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-size:13px;color:#71717A;vertical-align:top;">Website</td>
                                                <td style="padding:4px 0;font-size:15px;color:#0A0B0E;">
                                                    <a href="{{ $website }}" style="color:#4338CA;text-decoration:none;">{{ $website }}</a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-size:13px;color:#71717A;vertical-align:top;">Email</td>
                                                <td style="padding:4px 0;font-size:15px;color:#0A0B0E;">{{ $contactEmail }}</td>
                                            </tr>
                                            @if($phone)
                                            <tr>
                                                <td style="padding:4px 0;font-size:13px;color:#71717A;vertical-align:top;">Phone</td>
                                                <td style="padding:4px 0;font-size:15px;color:#0A0B0E;">{{ $phone }}</td>
                                            </tr>
                                            @endif
                                            @if($country)
                                            <tr>
                                                <td style="padding:4px 0;font-size:13px;color:#71717A;vertical-align:top;">Country</td>
                                                <td style="padding:4px 0;font-size:15px;color:#0A0B0E;">{{ $country }}</td>
                                            </tr>
                                            @endif
                                            @if($description)
                                            <tr>
                                                <td style="padding:8px 0 4px;font-size:13px;color:#71717A;vertical-align:top;">Description</td>
                                                <td style="padding:8px 0 4px;font-size:14px;color:#52525B;line-height:1.6;">{{ \Illuminate\Support\Str::limit($description, 300) }}</td>
                                            </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA -->
                            <table cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background-color:#4338CA;border-radius:6px;">
                                        <a href="{{ url('/admin/vendors/' . $brand->id . '/edit') }}" style="display:inline-block;padding:12px 32px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;">
                                            Review in Admin →
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:20px 40px;border-top:1px solid #E4E4E7;">
                            <p style="margin:0;font-size:12px;color:#A1A1AA;">
                                This is an automated notification from PeptideMaps.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
