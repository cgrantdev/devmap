<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $brand->name }} OG</title>
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
    grid-template-columns: 1fr 360px;
    grid-template-rows: auto 1fr auto;
    grid-template-areas:
      "header header"
      "content logo"
      "footer footer";
    gap: 40px;
    z-index: 2;
  }

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

  .content { grid-area: content; display: flex; flex-direction: column; justify-content: center; min-width: 0; }
  .eyebrow {
    display: inline-flex; align-items: center; gap: 8px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 12px; letter-spacing: 0.18em; text-transform: uppercase;
    color: rgba(134,239,172,0.9);
    margin-bottom: 20px;
  }
  .eyebrow .dot { width: 6px; height: 6px; border-radius: 50%; background: #34d399; box-shadow: 0 0 8px 2px rgba(52,211,153,0.55); }
  .brand-name {
    font-size: 74px; font-weight: 700; letter-spacing: -0.035em; line-height: 1;
    color: #fff;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .tagline {
    margin-top: 20px;
    font-size: 20px; line-height: 1.35; color: rgba(226,232,240,0.78); font-weight: 400;
    max-width: 640px;
    display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical;
    overflow: hidden;
  }

  /* vendor logo tile (subject of the card — bigger than product OG) */
  .logo-tile {
    grid-area: logo;
    width: 360px; height: 360px;
    border-radius: 24px;
    background:
      radial-gradient(circle at 30% 30%, rgba(56,189,248,0.12) 0%, transparent 55%),
      linear-gradient(180deg, rgba(255,255,255,0.94) 0%, #f5f7fa 100%);
    box-shadow:
      0 24px 60px -20px rgba(0,0,0,0.75),
      inset 0 1px 0 rgba(255,255,255,1);
    padding: 60px;
    display: flex; align-items: center; justify-content: center;
    position: relative;
    overflow: hidden;
  }
  .logo-tile img {
    max-width: 100%; max-height: 100%; object-fit: contain;
    position: relative; z-index: 1;
  }
  .logo-fallback {
    font-size: 96px; font-weight: 800; letter-spacing: -0.05em; color: #0B1424;
    background: linear-gradient(135deg, rgba(56,189,248,0.2) 0%, rgba(52,211,153,0.2) 100%);
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
    border-radius: 16px;
  }

  .footer {
    grid-area: footer;
    display: flex; align-items: center; justify-content: space-between; gap: 24px;
    padding-top: 16px;
  }
  .stats {
    display: flex; align-items: center; gap: 22px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(255,255,255,0.7);
  }
  .stats .num { color: #fff; font-weight: 700; margin-right: 6px; letter-spacing: 0.05em; }
  .stats .sep { color: rgba(255,255,255,0.22); }
  .stats .coupon {
    padding: 6px 10px; border-radius: 8px;
    background: rgba(52,211,153,0.14); border: 1px solid rgba(52,211,153,0.4);
    color: #6ee7b7;
  }
  .url {
    display: flex; align-items: center; gap: 8px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 14px; color: rgba(255,255,255,0.9); font-weight: 500;
    padding: 8px 14px; border-radius: 10px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
  }
  .url .dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; box-shadow: 0 0 10px 2px rgba(16,185,129,0.55); }
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
      <div class="eyebrow"><span class="dot"></span><span>Vendor Storefront</span></div>
      <div class="brand-name">{{ $brand->name }}</div>
      @if($tagline)
      <div class="tagline">{{ $tagline }}</div>
      @endif
    </div>

    <div class="logo-tile">
      @if($vendorLogo)
        <img src="{{ $vendorLogo }}" alt="">
      @else
        <div class="logo-fallback">{{ strtoupper(substr($brand->name, 0, 2)) }}</div>
      @endif
    </div>

    <div class="footer">
      <div class="stats">
        @if($productCount)
        <span><span class="num">{{ $productCount }}</span>Products</span>
        @endif
        @if($fromPrice)
        <span class="sep">·</span>
        <span>From <span class="num">${{ number_format($fromPrice, 2) }}</span></span>
        @endif
        @if($couponCode)
        <span class="sep">·</span>
        <span class="coupon">Code {{ strtoupper($couponCode) }}</span>
        @endif
      </div>
      <div class="url"><span class="dot"></span>peptidemap.com</div>
    </div>
  </div>
</body>
</html>
