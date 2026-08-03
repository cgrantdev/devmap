<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $category->name }} OG</title>
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
    opacity: 0.06;
    transform: rotate(-6deg);
    pointer-events: none;
  }

  .card {
    position: relative;
    width: 100%; height: 100%;
    padding: 56px 72px;
    display: flex; flex-direction: column;
    justify-content: space-between;
    z-index: 2;
  }

  .header { display: flex; align-items: center; gap: 18px; }
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

  .content { display: flex; flex-direction: column; justify-content: center; flex: 1; padding: 20px 0; }
  .eyebrow {
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 12px; letter-spacing: 0.22em; text-transform: uppercase;
    color: rgba(134,239,172,0.9);
    margin-bottom: 18px;
  }
  .compound-name {
    font-size: 108px; font-weight: 800; letter-spacing: -0.045em; line-height: 0.95;
    background: linear-gradient(120deg, #38bdf8 0%, #34d399 55%, #a7f3d0 100%);
    -webkit-background-clip: text; background-clip: text;
    -webkit-text-fill-color: transparent;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .full-name {
    margin-top: 14px;
    font-size: 24px; font-weight: 500; color: rgba(226,232,240,0.7);
    letter-spacing: -0.005em;
    max-width: 900px;
    display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .tagline {
    margin-top: 20px;
    font-size: 18px; line-height: 1.4; color: rgba(226,232,240,0.72); font-weight: 400;
    max-width: 900px;
    display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .footer { display: flex; align-items: center; justify-content: space-between; gap: 24px; }
  .stats {
    display: flex; align-items: center; gap: 22px;
    font-family: "SF Mono", ui-monospace, Menlo, Consolas, monospace;
    font-size: 13px; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(255,255,255,0.7);
  }
  .stats .num { color: #fff; font-weight: 700; margin-right: 6px; letter-spacing: 0.05em; }
  .stats .sep { color: rgba(255,255,255,0.22); }
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
      <div class="brand-tag">Encyclopedia</div>
    </div>

    <div class="content">
      <div class="eyebrow">Peptide Compound Guide</div>
      <div class="compound-name">{{ $category->name }}</div>
      @if($fullName)
      <div class="full-name">{{ $fullName }}</div>
      @endif
      @if($tagline)
      <div class="tagline">{{ $tagline }}</div>
      @endif
    </div>

    <div class="footer">
      <div class="stats">
        @if($vendorCount)
        <span><span class="num">{{ $vendorCount }}</span>Vendors</span>
        @endif
        @if($fromPrice)
        <span class="sep">·</span>
        <span>From <span class="num">${{ number_format($fromPrice, 2) }}</span></span>
        @endif
        <span class="sep">·</span>
        <span>Lab verified</span>
      </div>
      <div class="url"><span class="dot"></span>peptidemap.com</div>
    </div>
  </div>
</body>
</html>
