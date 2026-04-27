<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Connect</title>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        /* BACKGROUND */
        .bg-slider {
            position: fixed;
            inset: 0;
            z-index: -1;
        }

        .bg-slider img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* LOGO CONTAINER */
        .logo-wrapper {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* LOGO with Glow/Reveal Effect */
        .logo {
            position: absolute;
            text-align: left;
            font-size: 60px;
            font-weight: bolder;
            font-style: italic;
            margin-left: 2px;
            background: 50% 100% / 50% 50% no-repeat
                        radial-gradient(ellipse at bottom, #fff, transparent, transparent);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            font-family: "Source Sans Pro", sans-serif;
            animation: reveal 2000ms ease-in-out forwards,
                       glow 1500ms linear infinite 2000ms,
                       logoShrinkMove 2000ms 2000ms cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
            opacity: 0;
            letter-spacing: 4px;
        }

        @keyframes reveal {
            0% {
                opacity: 0;
                letter-spacing: 20px;
                background-size: 0% 0%;
            }
            60% {
                opacity: 1;
                letter-spacing: 8px;
                background-size: 100% 100%;
            }
            80% {
                letter-spacing: 4px;
            }
            100% {
                opacity: 1;
                letter-spacing: 4px;
                background-size: 300% 300%;
            }
        }

        @keyframes glow {
            0%, 100% {
                text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
            }
            50% {
                text-shadow: 0 0 15px rgba(255, 255, 255, 0.8), 
                             0 0 25px rgba(255, 255, 255, 0.4);
            }
        }

        @keyframes logoShrinkMove {
            0% {
                font-size: 60px;
                letter-spacing: 4px;
                background-size: 300% 300%;
            }
            70% {
                font-size: 24px;
                letter-spacing: 2px;
                position: fixed;
                top: 20px;
                left: 20px;
                transform: translate(0, 0);
                z-index: 1000;
                background-size: 200% 200%;
            }
            100% {
                position: fixed;
                top: 20px;
                left: 20px;
                font-size: 24px;
                letter-spacing: 2px;
                transform: translate(0, 0);
                z-index: 1000;
                background-size: 200% 200%;
                animation: miniGlow 1500ms linear infinite;
            }
        }

        @keyframes miniGlow {
            0%, 100% {
                text-shadow: 0 0 3px rgba(255, 255, 255, 0.5);
            }
            50% {
                text-shadow: 0 0 8px rgba(255, 255, 255, 0.7), 
                             0 0 12px rgba(255, 255, 255, 0.3);
            }
        }

        /* ICON */
        .icon1 {
            position: absolute;
            width: 125px;
            top: -51px;
            left: 95%;
            transform: translateX(-50%);
            animation: iconEntrance 2000ms ease-out forwards,
                       iconShrink 2000ms 2000ms cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards,
                       iconFloat 2000ms infinite 4000ms ease-in-out;
            opacity: 0;
            filter: drop-shadow(0 5px 10px rgba(255, 255, 255, 0.3));
        }

        @keyframes iconEntrance {
            0% {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
                filter: drop-shadow(0 0 0 transparent);
            }
            60% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
                filter: drop-shadow(0 5px 10px rgba(255, 255, 255, 0.3));
            }
            100% {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
                filter: drop-shadow(0 5px 15px rgba(255, 255, 255, 0.4));
            }
        }

        @keyframes iconShrink {
            0% {
                width: 125px;
                top: -51px;
                left: 95%;
            }
            70% {
                width: 50px;
                top: -15px;
                left: 103%;
                transform: translateX(0) scale(0.9);
            }
            100% {
                width: 50px;
                top: -15px;
                left: 103%;
                transform: translateX(0) scale(1);
            }
        }

        @keyframes iconFloat {
            0%, 100% {
                transform: translateX(0) translateY(0);
            }
            50% {
                transform: translateX(0) translateY(-5px);
            }
        }
    </style>
</head>

<body>

    <!-- BACKGROUND RESTORED -->
    <div class="bg-slider">
        <img src="assets/Bg 1.png" alt="Background">
    </div>

    <!-- Logo -->
    <div class="logo-wrapper">
        <header class="logo" id="logo">
            POWER
            <img src="assets/icon1.png" class="icon1" alt="icon">
            <br>
            CONNECT
        </header>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.getElementById('logo');
            
            // Animation timeline:
            // 0-2s: Text reveal with glow effect
            // 2-4s: Shrink to top-left corner
            // 4s: Redirect to index.php
            
            setTimeout(function() {
                // Redirect after total animation time
                setTimeout(function() {
                    window.location.href = 'chatbot.php';
                }, 4000); // 2s reveal + 2s shrink = 4s total
            }, 0);
        });
    </script>
</body>
</html>