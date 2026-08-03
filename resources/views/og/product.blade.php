<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $product->display_name }} OG</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body { width: 1200px; height: 630px; overflow: hidden; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    color: #ffffff;
    position: relative;
    background:
      radial-gradient(900px 700px at 82% 30%, rgba(56,189,248,0.22) 0%, transparent 55%),
      radial-gradient(900px 700px at 18% 85%, rgba(34,197,94,0.20) 0%, transparent 55%),
      linear-gradient(180deg, #04060B 0%, #0B1424 55%, #061019 100%);
  }
  body::before {
    content: '';
    position: absolute; inset: 0;
    background-image: radial-gradient(rgba(255,255,255,0.05) 1px, transparent 1px);
    background-size: 26px 26px;
    pointer-events: none;
  }
  .mark-bg {
    position: absolute;
    top: -110px; right: -160px;
    width: 780px; height: 780px;
    background-image: url('{{ asset('images/logo.png?v=2') }}');
    background-repeat: no-repeat;
    background-size: 3260px auto;
    background-position: -60px center;
    opacity: 0.08;
    transform: rotate(-6deg);
    pointer-events: none;
  }
  .card {
    position: relative;
    width: 100%; height: 100%;
    padding: 56px 64px;
    display: grid;
    grid-template-columns: 1fr 460px;
    grid-template-rows: auto 1fr auto;
    grid-template-areas:
      "header header"
      "content tile"
      "footer footer";
    gap: 40px;
    z-index: 2;
  }

  /* header */
  .header { grid-area: header; display: flex; align-items: center; gap: 18px; }
  .logo-mark {
    width: 44px; height: 44px;
    background-image: url('{{ asset('images/logo.png?v=2') }}');
    background-repeat: no-repeat;
    background-size: 180px auto;
    background-position: -4px center;
  }
  .wordmark {
    display: inline-flex; align-items: baseline;
    font-size: 30px; line-height: 1; letter-spacing: -0.03em; color: #fff;
  }
  .wordmark .peptide { font-weight: 300; }
  .wordmark .map     { font-weight: 800; letter-spacing: -0.035em; }
  .brand-tag {
    margin-left: 12px;
    padding: 6px 12px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 10px; font-weight: 600; letter-spacing: 0.18em; text-transform: uppercase;
    color: rgba(134,239,172,0.95);
    background: rgba(20,184,166,0.10);
    border: 1px solid rgba(134,239,172,0.32);
    border-radius: 999px;
  }

  /* content */
  .content { grid-area: content; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
  .vendor-row {
    display: inline-flex; align-items: center; gap: 12px;
    margin-bottom: 20px;
  }
  .vendor-logo {
    width: 44px; height: 44px; border-radius: 10px;
    background: rgba(255,255,255,0.94);
    padding: 6px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px -4px rgba(0,0,0,0.6);
    flex-shrink: 0;
  }
  .vendor-logo img { max-width: 100%; max-height: 100%; object-fit: contain; display: block; }
  .vendor-logo-fallback {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 800; color: #0B1424;
    background: linear-gradient(120deg, rgba(56,189,248,0.25) 0%, rgba(52,211,153,0.25) 100%);
    border-radius: 6px;
  }
  .vendor-label {
    display: inline-flex; flex-direction: column; gap: 3px;
  }
  .vendor-eyebrow {
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 10px; letter-spacing: 0.18em; text-transform: uppercase;
    color: rgba(134,239,172,0.85);
  }
  .vendor-name {
    font-size: 18px; font-weight: 600; letter-spacing: -0.01em; color: #fff;
    line-height: 1;
  }
  .product-name {
    font-size: 62px; font-weight: 700; letter-spacing: -0.035em; line-height: 1.02;
    color: #fff;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .price-row {
    display: flex; align-items: baseline; gap: 14px;
    margin-top: 26px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
  }
  .price {
    font-size: 42px; font-weight: 700; letter-spacing: -0.02em;
    background: linear-gradient(120deg, #38bdf8 0%, #34d399 55%, #a7f3d0 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
  }
  .price-strike {
    font-size: 20px; color: rgba(255,255,255,0.45);
    text-decoration: line-through;
  }
  .price-badge {
    font-size: 11px; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    padding: 5px 10px;
    background: rgba(52,211,153,0.15);
    border: 1px solid rgba(52,211,153,0.4);
    border-radius: 6px;
    color: #6ee7b7;
  }

  /* Prominent conversion chip — coupon savings when we have them,
     falls back to compare-shopping badge otherwise. */
  .cta-chip {
    display: inline-flex; align-items: center; gap: 12px;
    margin-top: 24px;
    padding: 12px 20px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(52,211,153,0.22) 0%, rgba(56,189,248,0.14) 100%);
    border: 1px solid rgba(134,239,172,0.45);
    box-shadow: 0 8px 24px -12px rgba(52,211,153,0.5);
    align-self: flex-start;
  }
  .cta-chip .savings {
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 18px; font-weight: 800; letter-spacing: 0.02em;
    color: #6ee7b7;
    text-transform: uppercase;
  }
  .cta-chip .divider {
    width: 1px; height: 20px; background: rgba(134,239,172,0.35);
  }
  .cta-chip .code-label {
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 10px; font-weight: 700; letter-spacing: 0.18em;
    color: rgba(134,239,172,0.85);
    text-transform: uppercase;
  }
  .cta-chip .code {
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 18px; font-weight: 800; letter-spacing: 0.08em;
    color: #fff;
    padding: 4px 10px;
    background: rgba(0,0,0,0.35);
    border: 1px dashed rgba(134,239,172,0.55);
    border-radius: 6px;
  }
  .cta-chip.compare {
    background: linear-gradient(135deg, rgba(56,189,248,0.16) 0%, rgba(52,211,153,0.10) 100%);
    border-color: rgba(56,189,248,0.42);
  }
  .cta-chip.compare .savings { color: #7dd3fc; }

  /* product tile — subtle dark card with soft glow instead of stark white */
  .tile {
    grid-area: tile;
    width: 460px; height: 460px;
    border-radius: 24px;
    background:
      radial-gradient(circle at 30% 30%, rgba(56,189,248,0.10) 0%, transparent 55%),
      linear-gradient(180deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%);
    border: 1px solid rgba(255,255,255,0.08);
    box-shadow:
      0 24px 60px -20px rgba(0,0,0,0.75),
      inset 0 1px 0 rgba(255,255,255,0.06);
    padding: 44px;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    overflow: hidden;
  }
  /* soft radial "spotlight" behind the product image so white-bg product PNGs
     don't feel like a rectangle floating in a rectangle */
  .tile::before {
    content: '';
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 340px; height: 340px;
    background: radial-gradient(circle, rgba(255,255,255,0.10) 0%, transparent 70%);
    filter: blur(20px);
    pointer-events: none;
  }
  .tile img {
    max-width: 100%; max-height: 100%; object-fit: contain;
    position: relative; z-index: 1;
    filter: drop-shadow(0 20px 40px rgba(0,0,0,0.6));
  }
  .tile-fallback {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    font-size: 96px; font-weight: 800; letter-spacing: -0.05em;
    color: rgba(255,255,255,0.9);
    background: linear-gradient(135deg, rgba(56,189,248,0.18) 0%, rgba(52,211,153,0.18) 100%);
    border-radius: 16px;
    position: relative; z-index: 1;
  }

  /* footer */
  .footer {
    grid-area: footer;
    display: flex; align-items: center; justify-content: space-between; gap: 24px;
    padding-top: 16px;
  }
  .footer-meta {
    display: flex; align-items: center; gap: 16px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 12px; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(255,255,255,0.6);
  }
  .footer-meta .sep { color: rgba(255,255,255,0.2); }
  .footer-meta strong { color: #fff; font-weight: 700; }
  /* CTA button-styled URL */
  .url {
    display: flex; align-items: center; gap: 10px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 15px; color: #fff; font-weight: 600;
    padding: 12px 20px; border-radius: 12px;
    background: linear-gradient(135deg, #5B5FE8 0%, #4338CA 100%);
    box-shadow: 0 8px 20px -8px rgba(79,70,229,0.55), inset 0 1px 0 rgba(255,255,255,0.2);
  }
  .url .arrow { font-size: 18px; font-weight: 700; }
</style>
</head>
<body>
  <div class="mark-bg"></div>
  <div class="card">
    <div class="header">
      <div class="logo-mark"></div>
      <div class="wordmark"><span class="peptide">peptide</span><span class="map">map</span></div>
      <div class="brand-tag">Peptide Directory</div>
    </div>

    <div class="content">
      @if($product->brand)
      <div class="vendor-row">
        <div class="vendor-logo">
          @if($vendorLogo)
            <img src="{{ $vendorLogo }}" alt="">
          @else
            <div class="vendor-logo-fallback">{{ strtoupper(substr($product->brand->name, 0, 2)) }}</div>
          @endif
        </div>
        <div class="vendor-label">
          <span class="vendor-eyebrow">Sold by</span>
          <span class="vendor-name">{{ $product->brand->name }}</span>
        </div>
      </div>
      @endif
      <div class="product-name">{{ $product->display_name ?? $product->name }}</div>
      @if($displayPrice)
      <div class="price-row">
        <span class="price">${{ number_format($displayPrice, 2) }}</span>
        @if($strikePrice)
        <span class="price-strike">${{ number_format($strikePrice, 2) }}</span>
        <span class="price-badge">{{ $discountPct }}% Off</span>
        @endif
      </div>
      @endif

      {{-- Prominent CTA — coupon savings if we have them, else compare-shopping badge --}}
      @if($couponCode && $couponPct)
      <div class="cta-chip">
        <span class="savings">Save {{ $couponPctLabel }}%</span>
        <span class="divider"></span>
        <span class="code-label">Code</span>
        <span class="code">{{ strtoupper($couponCode) }}</span>
      </div>
      @elseif($vendorCount && $vendorCount >= 2)
      <div class="cta-chip compare">
        <span class="savings">Compare {{ $vendorCount }} Vendors</span>
      </div>
      @endif
    </div>

    <div class="tile">
      @if($productImage)
        <img src="{{ $productImage }}" alt="">
      @else
        <div class="tile-fallback">{{ strtoupper(substr($product->display_name ?? $product->name, 0, 2)) }}</div>
      @endif
    </div>

    <div class="footer">
      <div class="footer-meta">
        <span><strong>Compare vendors</strong></span>
        <span class="sep">·</span>
        <span>PMAP coupons</span>
        <span class="sep">·</span>
        <span>Lab verified</span>
      </div>
      <div class="url">View on Peptidemap <span class="arrow">→</span></div>
    </div>
  </div>
</body>
</html>
