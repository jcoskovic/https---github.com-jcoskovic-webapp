<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abbrevio - Resetovanje lozinke</title>
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
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #007bff;
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
        .reset-btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
        }
        .reset-btn:hover {
            background-color: #0056b3;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
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
                Pozdrav {{ $userName }},
            </div>
            
            <div class="message">
                Primili ste ovaj email jer je zatraženo resetovanje lozinke za vaš Abbrevio račun.
            </div>
            
            <div class="btn-container">
                <a href="{{ $resetUrl }}" class="reset-btn">Resetuj lozinku</a>
            </div>
            
            <div class="message">
                Ako ne možete kliknuti na dugme, kopirajte i zalepite sledeći link u vaš browser:
            </div>
            
            <div class="url-fallback">
                {{ $resetUrl }}
            </div>
            
            <div class="warning">
                <strong>Važno:</strong> Ovaj link je valjan samo 1 sat od trenutka slanja. Ako niste vi zatražili resetovanje lozinke, ignorišite ovaj email - vaš račun je siguran.
            </div>
        </div>
        
        <div class="footer">
            <p>Srdačno,<br><strong>Abbrevio Tim</strong></p>
            <p style="font-size: 12px; color: #999;">
                Ovaj email je automatski generisan. Molimo ne odgovarajte na njega.
            </p>
        </div>
    </div>
</body>
</html>
