<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEU Library</title>

    <style>
        /* --- RESET AND BASE STYLES --- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body, html {
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #fff;
            font-family: 'Arial', sans-serif;
        }

        /* --- MAIN LAYOUT --- */
        .header-container {
            position: relative;
            width: 100%;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        /* --- TRICOLOR BORDER ANIMATIONS --- */
        .header-container::before,
        .header-container::after {
            content: "";
            position: absolute;
            left: 0;
            width: 100%;
            height: 36px;
            z-index: 50;
            animation: slideInOnce 1.2s ease-out forwards;
        }

        .header-container::before {
            top: 0;
            background: linear-gradient(to bottom, #1e7d32 33.33%, #ffffff 33.33% 66.66%, #c62828 66.66%);
            transform: translateX(-100%);
        }

        .header-container::after {
            bottom: 0;
            background: linear-gradient(to bottom, #c62828 33.33%, #ffffff 33.33% 66.66%, #1e7d32 66.66%);
            transform: translateX(100%);
        }

        /* --- BACKGROUND AND OVERLAY --- */
        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background-image: url('https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LIBRARY.jpg?raw=true');
            background-size: cover;
            background-position: center;
            filter: blur(6px);
            transform: scale(1.1);
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            background: rgba(255, 255, 255, 0.1);
        }

        /* --- LOGO CONTAINER AND IMAGE --- */
        .logo-container {
            position: relative;
            z-index: 10;
            width: 400px;
            height: 400px;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: white;
            border-radius: 50%;
            border: 8px solid #fff;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: double-bump 0.8s ease-out 0.5s 2;
        }

        .logo-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* --- KEYFRAMES --- */
        @keyframes slideInOnce {
            to { transform: translateX(0); }
        }

        @keyframes double-bump {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.08); }
        }

        /* --- RESPONSIVE BREAKPOINTS --- */
        @media (max-width: 768px) {
            .logo-container { width: 280px; height: 280px; border-width: 6px; }
            .header-container::before, .header-container::after { height: 28px; }
        }

        @media (max-width: 480px) {
            .logo-container { width: 200px; height: 200px; border-width: 4px; }
            .header-container::before, .header-container::after { height: 20px; }
        }
    </style>
</head>

<body>
    <div class="header-container">
        <div class="background-image"></div>
        <div class="overlay"></div>
        <div class="logo-container">
            <img src="https://github.com/NathanielCoronado/NEU-LIBRARY-LOG/blob/main/NEU%20LOGO.jpg?raw=true" 
                 alt="NEU Logo" class="logo-image">
        </div>
    </div>

    <script>
        setTimeout(function () {
            const nextStep = localStorage.getItem('userFlow');
            window.location.href = 'login.php';
        }, 2500);
    </script>
</body>
</html>