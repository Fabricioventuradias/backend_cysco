<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Success</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
  :root{
    --backdrop:#10161a;
    --backdrop-2:#161d22;
    --paper:#faf9f5;
    --ink:#1c2321;
    --ink-muted:#6b756f;
    --seal:#0e6e4f;
    --seal-dark:#0a4f38;
    --gold:#c9a24b;
  }

  *{ box-sizing:border-box; }

  html,body{
    margin:0;
    min-height:100%;
    background: radial-gradient(120% 140% at 50% -10%, var(--backdrop-2), var(--backdrop));
    font-family:'Sora', sans-serif;
    color:var(--ink);
  }

  body{
    display:flex;
    align-items:center;
    justify-content:center;
    padding:32px 16px;
  }

  .stage{
    width:100%;
    max-width:380px;
  }

  .eyebrow{
    text-align:center;
    color:#8a938c;
    font-size:12px;
    letter-spacing:.14em;
    text-transform:uppercase;
    margin:0 0 14px;
  }

  .receipt{
    position:relative;
    background:var(--paper);
    color:var(--ink);
    box-shadow: 0 30px 60px -20px rgba(0,0,0,0.55);
  }

  .edge{
    height:11px;
    background-image:
      linear-gradient(135deg, transparent 50%, var(--paper) 50%),
      linear-gradient(45deg, var(--paper) 50%, transparent 50%);
    background-position:0 0, 11px 0;
    background-size:22px 22px;
    background-repeat:repeat-x;
  }
  .edge.bottom{ transform:scaleY(-1); }

  .body{
    padding:10px 30px 34px;
    text-align:center;
  }

  .seal-wrap{
    display:flex;
    justify-content:center;
    padding:26px 0 8px;
  }

  .seal{
    width:88px;
    height:88px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:
      radial-gradient(circle at 34% 30%, #12855f 0%, var(--seal) 55%, var(--seal-dark) 100%);
    box-shadow:
      0 0 0 3px var(--paper),
      0 0 0 5px rgba(14,110,79,0.35),
      0 10px 18px -6px rgba(10,79,56,0.55);
    transform: scale(0) rotate(-16deg);
    animation: stamp 640ms cubic-bezier(.34,1.56,.64,1) 120ms forwards;
  }

  @keyframes stamp{
    60%{ transform: scale(1.08) rotate(-6deg); }
    100%{ transform: scale(1) rotate(-8deg); }
  }

  @media (prefers-reduced-motion: reduce){
    .seal{ animation:none; transform:scale(1) rotate(-8deg); }
  }

  .seal svg{ width:40px; height:40px; }

  h1{
    font-size:23px;
    font-weight:700;
    letter-spacing:-0.01em;
    margin:16px 0 8px;
  }

  .sub{
    font-size:14.5px;
    color:var(--ink-muted);
    line-height:1.6;
    margin:0 0 30px;
  }

  .cta{
    display:block;
    width:100%;
    text-align:center;
    text-decoration:none;
    padding:14px 20px;
    border-radius:10px;
    background:var(--ink);
    color:var(--paper);
    font-weight:600;
    font-size:15px;
    letter-spacing:.01em;
    transition: transform 120ms ease, background 120ms ease;
  }
  .cta:hover{ background:#000; }
  .cta:focus-visible{ outline:2px solid var(--gold); outline-offset:3px; }
  .cta:active{ transform:scale(.98); }
</style>
</head>
<body>

  <div class="stage">
    <p class="eyebrow">{{ config('app.name', 'Success') }}</p>

    <div class="receipt">
      <div class="edge top"></div>

      <div class="body">
        <div class="seal-wrap">
          <div class="seal" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none">
              <path d="M4 12.5L9.5 18L20 6.5" stroke="#faf9f5" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
        </div>

        <h1>All done</h1>
        <p class="sub">Your {{ $serviceName ?? 'request' }} was completed successfully.</p>

        <a class="cta" href="{{ $continueUrl ?? url('/') }}">Continue</a>
      </div>

      <div class="edge bottom"></div>
    </div>
  </div>

</body>
</html>