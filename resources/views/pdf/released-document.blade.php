<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>{{ $recordRequest->reference_no }}</title>
        <style>
            body { font-family: sans-serif; font-size: 12px; color: #111; }
            h1 { font-size: 16px; margin-bottom: 4px; }
            .meta { margin-bottom: 24px; color: #555; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
            td { padding: 6px 0; vertical-align: top; }
            td.label { width: 180px; color: #555; }
            .qr { text-align: center; margin-top: 32px; }
            .qr img { width: 140px; height: 140px; }
            .qr p { font-size: 10px; color: #555; word-break: break-all; }
        </style>
    </head>
    <body>
        <h1>Office of the Registrar</h1>
        <p class="meta">Reference {{ $recordRequest->reference_no }}</p>

        <table>
            <tr>
                <td class="label">Document</td>
                <td>{{ $recordRequest->document_type->label() }}</td>
            </tr>
            <tr>
                <td class="label">Requester</td>
                <td>{{ $recordRequest->fullName() }}</td>
            </tr>
            <tr>
                <td class="label">Purpose</td>
                <td>{{ $recordRequest->purpose }}</td>
            </tr>
            <tr>
                <td class="label">Copies</td>
                <td>{{ $recordRequest->copies }}</td>
            </tr>
            <tr>
                <td class="label">Released</td>
                <td>{{ $release->released_at->format('M j, Y g:i A') }}</td>
            </tr>
        </table>

        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="Verification QR code">
            <p>Scan to verify authenticity, or visit:<br>{{ $verificationUrl }}</p>
        </div>
    </body>
</html>
