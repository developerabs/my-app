<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Created</title>
</head>

<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, sans-serif;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
        style="background-color:#f3f4f6; padding:20px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0"
                    style="background-color:#ffffff; border-radius:16px; box-shadow:0 4px 12px rgba(0,0,0,0.1); padding:40px; text-align:center;">

                    <!-- Logo Section -->
                    @php
                        $logoPath = public_path('landlord/images/logo.png');
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoMime = mime_content_type($logoPath); // যেমন: image/png
                    @endphp

                    <tr>
                        <td style="padding-bottom:24px;">
                            <img src="data:{{ $logoMime }};base64,{{ $logoData }}" alt="SheraziPOS Logo"
                                width="150" style="display:block; margin:0 auto;">
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <h2 style="margin:0 0 16px; font-size:24px; color:#111827;">Welcome to SheraziPOS Platform!
                            </h2>
                            <p style="margin:0 0 24px; color:#6b7280; font-size:16px;">Your tenant has been successfully
                                created. Below are your account details:</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f9fafb; border-radius:12px; padding:24px; margin-bottom:24px;">
                            <p style="margin:8px 0; font-size:16px; color:#111827;"><strong
                                    style="color:#374151;">Tenant Name:</strong> {{ $tenant->tenant }}</p>
                            <p style="margin:8px 0; font-size:16px; color:#111827;"><strong
                                    style="color:#374151;">Domain:</strong> {{ $tenant->domains->first()->domain }}</p>
                            <p style="margin:8px 0; font-size:16px; color:#111827;"><strong
                                    style="color:#374151;">Email:</strong> {{ $tenant->email }}</p>
                            <p style="margin:8px 0; font-size:16px; color:#111827;"><strong
                                    style="color:#374151;">Password:</strong> {{ $password }}</p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-bottom:24px;">
                            <a href="https://{{ $tenant->domains->first()->domain }}"
                                style="background-color:#3b82f6; color:#ffffff; text-decoration:none; font-weight:bold; padding:14px 28px; border-radius:12px; display:inline-block; font-size:16px;">
                                Login to Your Account
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <p style="margin:0 0 8px; color:#6b7280; font-size:16px;">Thank you!</p>
                            <p style="margin:0; color:#6b7280; font-size:16px;">Best regards,<br>The SheraziPOS Team</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>

</html>
