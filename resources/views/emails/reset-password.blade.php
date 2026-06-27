<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
</head>

<body style="margin:0;padding:0;background:#f5f5f5;font-family:Arial, Helvetica, sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 0;">
        <tr>
            <td align="center">

                <table width="600" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:10px;padding:40px;">

                    <tr>
                        <td align="center">
                            <h2 style="margin:0;color:#333;">
                                Reset Password
                            </h2>

                            <p style="color:#666;margin-top:10px;">
                                Halo,
                                <strong>{{ $name }}</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:20px;color:#555;line-height:26px;">

                            Kami menerima permintaan untuk mereset password akun Anda.

                            <br><br>

                            Klik tombol di bawah untuk membuat password baru.

                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:35px 0;">

                            <a href="{{ $resetLink }}"
                                style="
                                    background:#2563eb;
                                    color:#fff;
                                    text-decoration:none;
                                    padding:14px 28px;
                                    border-radius:6px;
                                    display:inline-block;
                                    font-weight:bold;
                                ">
                                Reset Password
                            </a>

                        </td>
                    </tr>

                    <tr>
                        <td style="color:#666;line-height:26px;">

                            Link ini hanya berlaku selama <strong>15 menit</strong>.

                            <br><br>

                            Jika Anda tidak pernah meminta reset password, abaikan email ini. Password Anda tidak akan berubah sampai Anda membuat password baru melalui link di atas.

                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:40px;border-top:1px solid #eee;">

                            <small style="color:#999;">
                                Email ini dikirim secara otomatis oleh sistem Aska.
                                Mohon jangan membalas email ini.
                            </small>

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>