<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SIMDEX Confirmation Code</title>
</head>
<body style="margin:0;background:#f8faff;color:#0f172a;font-family:Arial,sans-serif">
    <div style="max-width:560px;margin:0 auto;padding:36px 18px">
        <div style="border:1px solid #dce5f5;border-radius:18px;background:#fff;padding:30px;box-shadow:0 16px 42px rgba(37,99,235,.12)">
            <div style="font-size:12px;font-weight:800;letter-spacing:.12em;color:#2563eb">SIMDEX SECURITY</div>
            <h1 style="margin:10px 0 8px;font-size:25px">Confirm your email access</h1>
            <p style="margin:0;color:#64748b;line-height:1.6">
                Use this code to continue the {{ $purpose }} process.
            </p>
            <div style="margin:24px 0;border-radius:14px;background:#eff6ff;padding:18px;text-align:center;font-size:34px;font-weight:800;letter-spacing:.22em;color:#2563eb">
                {{ $code }}
            </div>
            <p style="margin:0;color:#64748b;font-size:13px;line-height:1.6">
                This code expires in {{ $expiresInMinutes }} minutes. If you did not request it, you can safely ignore this email.
            </p>
        </div>
    </div>
</body>
</html>
