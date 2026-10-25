<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your login details</title>
</head>
<body style="margin:0; padding:0; background:#f4f5f7; font-family:Arial, Helvetica, sans-serif; color:#222;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f5f7; padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,.08);">
                {{-- Header --}}
                <tr>
                    <td style="background:{{ $primary }}; color:{{ $primaryText }}; padding:22px 28px;">
                        <div style="font-size:20px; font-weight:bold;">{{ $institutionName }}</div>
                        <div style="font-size:13px; opacity:.9; margin-top:2px;">Lecturer Tracker</div>
                    </td>
                </tr>

                {{-- Body --}}
                <tr>
                    <td style="padding:28px;">
                        <p style="margin:0 0 14px; font-size:16px;">Hello <strong>{{ $userName }}</strong>,</p>
                        <p style="margin:0 0 20px; font-size:14px; line-height:1.6;">
                            An account has been created for you on the <strong>{{ $institutionName }}</strong> Lecturer Tracker.
                            Use the details below to log in.
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9fa; border:1px solid #e5e7eb; border-radius:8px; font-size:14px;">
                            <tr>
                                <td style="padding:12px 16px; color:#6b7280; width:38%; border-bottom:1px solid #e5e7eb;">Email (username)</td>
                                <td style="padding:12px 16px; font-weight:bold; border-bottom:1px solid #e5e7eb;">{{ $email }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px; color:#6b7280; border-bottom:1px solid #e5e7eb;">Password</td>
                                <td style="padding:12px 16px; font-weight:bold; font-family:Consolas, Menlo, monospace; border-bottom:1px solid #e5e7eb;">{{ $password }}</td>
                            </tr>
                            <tr>
                                <td style="padding:12px 16px; color:#6b7280;">Role</td>
                                <td style="padding:12px 16px; font-weight:bold;">{{ $role }}</td>
                            </tr>
                        </table>

                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:26px auto;">
                            <tr>
                                <td style="background:{{ $primary }}; border-radius:6px;">
                                    <a href="{{ $loginUrl }}" style="display:inline-block; padding:12px 28px; color:{{ $primaryText }}; text-decoration:none; font-weight:bold; font-size:15px;">Log in to Lecturer Tracker</a>
                                </td>
                            </tr>
                        </table>

                        <p style="margin:0 0 6px; font-size:13px; color:#6b7280;">Or open this link in your browser:</p>
                        <p style="margin:0 0 20px; font-size:13px; word-break:break-all;"><a href="{{ $loginUrl }}" style="color:{{ $primary }};">{{ $loginUrl }}</a></p>

                        <p style="margin:0; font-size:13px; color:#6b7280; line-height:1.6;">
                            For your security, please change your password after you log in, and don't share these details with anyone.
                        </p>
                    </td>
                </tr>

                {{-- Footer --}}
                <tr>
                    <td style="background:#fafafa; border-top:1px solid #eee; padding:14px 28px; font-size:12px; color:#9ca3af; text-align:center;">
                        {{ $institutionName }} · Powered by <a href="https://nawema.co.ke" style="color:#9ca3af;">nawema.co.ke</a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
