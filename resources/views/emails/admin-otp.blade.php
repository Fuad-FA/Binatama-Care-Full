<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family:'Segoe UI',sans-serif; background:#f3f4f6; margin:0; padding:20px; }
        .container { max-width:520px; margin:0 auto; background:#fff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
        .header { background:#1565C0; padding:32px; text-align:center; }
        .header h1 { color:#fff; margin:0; font-size:22px; }
        .header p  { color:rgba(255,255,255,0.8); margin:4px 0 0; font-size:13px; }
        .body { padding:32px; }
        .greeting { font-size:15px; color:#374151; margin-bottom:16px; }
        .info { font-size:14px; color:#6B7280; margin-bottom:24px; line-height:1.6; }
        .otp-box { background:#F0F9FF; border:2px dashed #1565C0; border-radius:12px; padding:24px; text-align:center; margin:24px 0; }
        .otp-label { font-size:12px; color:#6B7280; text-transform:uppercase; letter-spacing:1px; margin-bottom:8px; }
        .otp-code { font-size:48px; font-weight:900; color:#1565C0; letter-spacing:8px; font-family:monospace; }
        .otp-expire { font-size:12px; color:#EF4444; margin-top:8px; }
        .warning { background:#FEF9C3; border-left:4px solid #F59E0B; border-radius:8px; padding:12px 16px; font-size:13px; color:#92400E; margin-top:24px; }
        .footer { background:#F9FAFB; padding:20px 32px; text-align:center; font-size:12px; color:#9CA3AF; border-top:1px solid #E5E7EB; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🏥 Binatama Care</h1>
        <p>SMK Kesehatan Binatama Yogyakarta</p>
    </div>
    <div class="body">
        <div class="greeting">Halo, <strong>{{ $namaAdmin }}</strong>!</div>
        <div class="info">
            Anda baru saja meminta perubahan password akun Administrator.
            Gunakan kode OTP berikut untuk melanjutkan:
        </div>
        <div class="otp-box">
            <div class="otp-label">Kode OTP Anda</div>
            <div class="otp-code">{{ $otp }}</div>
            <div class="otp-expire">⏱ Berlaku selama 10 menit</div>
        </div>
        <div class="warning">
            ⚠ <strong>Jangan bagikan kode ini kepada siapapun.</strong>
            Jika Anda tidak merasa meminta kode ini, segera amankan akun Anda.
        </div>
    </div>
    <div class="footer">
        Email ini dikirim otomatis oleh Binatama Care.<br>
        Jangan membalas email ini.
    </div>
</div>
</body>
</html>