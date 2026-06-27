<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode Verifikasi Email</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        .content {
            text-align: center;
            margin: 30px 0;
        }
        .otp-box {
            background-color: #f8f9fa;
            border: 2px solid #007bff;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .otp-code {
            font-size: 36px;
            font-weight: bold;
            color: #007bff;
            letter-spacing: 5px;
            font-family: 'Courier New', monospace;
        }
        .expiry-warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #856404;
            font-size: 14px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #7f8c8d;
            font-size: 12px;
        }
        .security-note {
            background-color: #e8f4f8;
            border-left: 4px solid #17a2b8;
            padding: 12px 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #0c5460;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✓ Verifikasi Email Anda</h1>
        </div>

        <div class="content">
            <p>Halo, {{$userEmail}}</p>
            <p>Kami menerima permintaan verifikasi email Anda. Gunakan kode OTP di bawah ini untuk menyelesaikan verifikasi:</p>

            <div class="otp-box">
                <div class="otp-code">{{ $otpCode }}</div>
            </div>

            <div class="expiry-warning">
                ⏰ Kode ini berlaku selama <strong>5 menit</strong>. Segera masukkan kode sebelum kadaluarsa.
            </div>

            <div class="security-note">
                🔒 Jangan bagikan kode ini kepada siapa pun. Kami tidak akan pernah meminta kode OTP Anda melalui email atau pesan.
            </div>

            <p style="margin-top: 30px; color: #7f8c8d; font-size: 14px;">
                Jika Anda tidak melakukan permintaan verifikasi ini, abaikan email ini.
            </p>
        </div>

        <div class="footer">
            <p>&copy; 2026 Aska. Semua hak dilindungi.</p>
            <p>Email ini dikirim ke: <strong>{{ $userEmail }}</strong></p>
        </div>
    </div>
</body>
</html>
