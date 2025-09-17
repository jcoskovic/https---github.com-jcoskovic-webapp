<!DOCTYPE html>
<html lang="hr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abbrevio - Potvrda email adrese</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #28a745;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #28a745;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
        }

        .content {
            margin-bottom: 30px;
        }

        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 25px;
            line-height: 1.8;
        }

        .btn-container {
            text-align: center;
            margin: 30px 0;
        }

        .verify-btn {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }

        .verify-btn:hover {
            background-color: #218838;
        }

        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .footer {
            border-top: 1px solid #eee;
            padding-top: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .url-fallback {
            word-break: break-all;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
            margin-top: 15px;
            font-family: monospace;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <div class="logo">Abbrevio</div>
            <div class="subtitle">Sistem za upravljanje skraćenicama</div>
        </div>

        <div class="content">
            <div class="greeting">
                Dobrodošli {{ $userName }}!
            </div>

            <div class="message">
                Hvala vam što ste se registrirali na Abbrevio platformu. Da biste završili proces registracije, potrebno
                je da potvrdite svoju email adresu.
            </div>

            <div class="btn-container">
                <a href="{{ $verificationUrl }}" class="verify-btn">Potvrdite email adresu</a>
            </div>

            <div class="message">
                Ako ne možete kliknuti na dugme, kopirajte i zalijepite sljedeći link u vaš preglednik:
            </div>

            <div class="url-fallback">
                {{ $verificationUrl }}
            </div>

            <div class="info">
                <strong>Napomena:</strong> Ovaj link za potvrdu je valjan 24 sata od trenutka slanja. Ukoliko ne
                potvrdite email u tom periodu, možete zatražiti novi link za potvrdu.
            </div>
        </div>

        <div class="footer">
            <p>Srdačno,<br><strong>Abbrevio Tim</strong></p>
            <p style="font-size: 12px; color: #999;">
                Ovaj email je automatski generiran. Molimo ne odgovarajte na njega.
            </p>
        </div>
    </div>
</body>

</html>