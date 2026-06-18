<!DOCTYPE html>
<html lang="de" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VersGo - Passwort zurücksetzen</title>

    <style>
        :root {
            --primary-orange: #FF6600; /* اللون البرتقالي من الشعار */
            --dark-black: #1A1A1A;     /* اللون الأسود الداكن من الشعار */
            --light-bg: #F9FAFB;       /* خلفية رمادية فاتحة مريحة للعين */
        }

        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--light-bg);
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 420px;
            margin: 20px;
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            border-top: 6px solid var(--primary-orange);
        }

        .logo-container {
            text-align: center;
            margin-bottom: 24px;
            display: flex;
            justify-content: center;
            align-items: center; /* Ensures content is centered vertically */
        }

        /* تنسيق لجعل الشعار المربع يبدو دائرياً واحترافياً */
        .logo-container img {
            width: 100px;
            height: 100px;
            object-fit: contain; /* تم تغييرها لتظهر الصورة كاملة */
            border-radius: 70%; 
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 2px solid white;
            background-color: white; /* يضيف خلفية بيضاء لملء أي مساحة فارغة في الإطار الدائري */
        }

        h2 {
            text-align: center;
            margin-bottom: 8px;
            color: var(--dark-black);
            font-size: 22px;
            font-weight: 700;
        }

        p {
            text-align: center;
            color: #6B7280;
            font-size: 14px;
            margin-bottom: 28px;
            line-height: 1.5;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-black);
            font-weight: 600;
            font-size: 14px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 14px;
            margin-bottom: 20px;
            border: 1.5px solid #E5E7EB;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
            background-color: #FAFAFA;
        }

        input:focus {
            outline: none;
            border-color: var(--primary-orange);
            background-color: #FFF;
            box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.15);
        }

        input[readonly] {
            background-color: #F3F4F6;
            color: #9CA3AF;
            cursor: not-allowed;
            border-color: #E5E7EB;
        }

        button {
            width: 100%;
            padding: 15px;
            background: var(--dark-black);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        button:hover {
            background: #333333;
        }

        button:active {
            transform: scale(0.98);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .alert {
            padding: 14px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.4;
            display: none;
        }

        .alert-success {
            background: #DEF7EC;
            color: #03543F;
            border: 1px solid #BCF0DA;
        }

        .alert-error {
            background: #FDE8E8;
            color: #9B1C1C;
            border: 1px solid #FBD5D5;
        }

        .footer-text {
            text-align: center;
            font-size: 12px;
            color: #9CA3AF;
            margin-top: 24px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="logo-container">
<img src="{{ asset('storage/logo.jpg') }}" alt="VersGo Logo">        </div>

        <h2>Passwort zurücksetzen</h2>
        <p>Geben Sie ein neues Passwort für Ihr Konto ein.</p>

        <div id="successBox" class="alert alert-success"></div>
        <div id="errorBox" class="alert alert-error"></div>

        <form id="resetForm">
            <input type="hidden" id="token" value="{{ $token }}">

            <label for="email">E-Mail-Adresse</label>
            <input
                type="email"
                id="email"
                value="{{ $email }}"
                readonly
                required
            >

            <label for="password">Neues Passwort</label>
            <input
                type="password"
                id="password"
                minlength="8"
                required
                placeholder="Mindestens 8 Zeichen"
            >

            <label for="password_confirmation">Passwort bestätigen</label>
            <input
                type="password"
                id="password_confirmation"
                minlength="8"
                required
                placeholder="Passwort erneut eingeben"
            >

            <button type="submit" id="submitBtn">Passwort ändern</button>
        </form>

        <div class="footer-text">
            VersGo Lieferdienst &copy; Alle Rechte vorbehalten.
        </div>
    </div>

    <script>
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        const successBox = document.getElementById('successBox');
        const errorBox = document.getElementById('errorBox');

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            successBox.style.display = 'none';
            errorBox.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerText = 'Wird geändert...';

            const payload = {
                email: document.getElementById('email').value,
                token: document.getElementById('token').value,
                password: document.getElementById('password').value,
                password_confirmation: document.getElementById('password_confirmation').value,
            };

            try {
                const response = await fetch('/api/reset-password', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                const data = await response.json();

                if (response.ok) {
                    successBox.innerText = data.message || 'Ihr Passwort wurde erfolgreich geändert.';
                    successBox.style.display = 'block';
                    form.reset();
                } else {
                    let message = data.message || 'Fehler beim Zurücksetzen des Passworts.';

                    if (data.errors) {
                        const firstKey = Object.keys(data.errors)[0];
                        if (firstKey) {
                            message = data.errors[firstKey][0];
                        }
                    }

                    errorBox.innerText = message;
                    errorBox.style.display = 'block';
                }
            } catch (error) {
                errorBox.innerText = 'Verbindung zum Server fehlgeschlagen.';
                errorBox.style.display = 'block';
            }

            submitBtn.disabled = false;
            submitBtn.innerText = 'Passwort ändern';
        });
    </script>
</body>
</html>