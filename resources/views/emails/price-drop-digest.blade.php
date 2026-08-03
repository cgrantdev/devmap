<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Price drops on your Peptidemap wishlist</title>
</head>
<body style="margin:0;padding:0;background:#f6f7f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0A0B0E;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f7f9;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
          <tr>
            <td style="padding:28px 32px 8px;">
              <div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;color:#6B7280;font-weight:600;margin-bottom:10px;">Peptidemap · Price drop alert</div>
              <h1 style="margin:0 0 8px;font-size:22px;line-height:1.25;color:#0A0B0E;">
                {{ count($drops) }} price drop{{ count($drops) === 1 ? '' : 's' }} on your wishlist
              </h1>
              <p style="margin:0 0 24px;font-size:14px;color:#52525B;line-height:1.5;">
                Hi {{ $user->name ?: 'there' }}, prices moved on {{ count($drops) === 1 ? 'a product you\'re' : 'products you\'re' }} watching. Grab them while they're down.
              </p>
            </td>
          </tr>

          <tr>
            <td style="padding:0 32px 8px;">
              @foreach ($drops as $d)
                @php
                  $p = $d['product'];
                  $productUrl = url('/product/' . ($p->brand->slug ?? 'unknown') . '/' . $p->slug . '/' . $p->id);
                  $buyUrl = url('/go/' . $p->id);
                  $couponCode = strtoupper($p->brand?->vendorSetting?->coupon_code ?: 'PMAP');
                @endphp
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #E4E4E7;border-radius:10px;margin-bottom:12px;">
                  <tr>
                    <td style="padding:14px 16px;">
                      <div style="font-size:14px;font-weight:600;color:#0A0B0E;line-height:1.35;">
                        <a href="{{ $productUrl }}" style="color:#0A0B0E;text-decoration:none;">{{ $p->display_name ?? $p->name }}</a>
                      </div>
                      <div style="font-size:12px;color:#6B7280;margin:2px 0 8px;">
                        {{ $p->brand?->name ?? 'Unknown vendor' }}
                      </div>
                      <div style="font-size:13px;color:#0A0B0E;">
                        <span style="color:#9CA3AF;text-decoration:line-through;">${{ number_format($d['old_price'], 2) }}</span>
                        &nbsp;→&nbsp;
                        <strong style="color:#047857;">${{ number_format($d['new_price'], 2) }}</strong>
                        &nbsp;
                        <span style="display:inline-block;background:#ECFDF5;border:1px solid #A7F3D0;color:#047857;font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;letter-spacing:0.04em;">▼ {{ $d['drop_pct'] }}%</span>
                      </div>
                      <div style="margin-top:10px;">
                        <a href="{{ $buyUrl }}" style="display:inline-block;padding:8px 16px;background:#4338CA;color:#ffffff;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px;">
                          Buy now →
                        </a>
                        <span style="margin-left:10px;font-size:11px;color:#6B7280;">Use code <strong style="color:#047857;">{{ $couponCode }}</strong> at checkout for extra savings</span>
                      </div>
                    </td>
                  </tr>
                </table>
              @endforeach
            </td>
          </tr>

          <tr>
            <td align="center" style="padding:8px 32px 24px;">
              <a href="{{ $wishlistUrl }}" style="display:inline-block;padding:11px 22px;background:#0A0B0E;color:#ffffff;font-size:13px;font-weight:600;text-decoration:none;border-radius:8px;">
                Manage your wishlist
              </a>
            </td>
          </tr>

          <tr>
            <td style="padding:16px 32px 28px;border-top:1px solid #F1F5F9;">
              <p style="margin:0;font-size:11px;color:#94A3B8;line-height:1.5;">
                You're receiving this because you added items to your Peptidemap wishlist and opted-in to weekly price alerts. Manage or turn off alerts from
                <a href="{{ $wishlistUrl }}" style="color:#4338CA;text-decoration:underline;">your wishlist</a>. Research use only.
              </p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
