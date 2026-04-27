<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Connect</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:300&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center; /* vertical center */
            align-items: center;     /* horizontal center */
            background: linear-gradient(141deg, #ccc 25%, #eee 40%, #ddd 55%);
            font-family: 'Roboto', sans-serif;
            font-weight: 300;
            color: #555;
            overflow: hidden;
            font-size: clamp(24px, 5vw, 32px); /* Responsive font size */
        }

        .text {
        
            display: inline-block;
            overflow: hidden;
            white-space: nowrap;
            text-align: center;
        }

        /* Top text animation */
        .text.top {
            animation: fadeinout 10s forwards;
            color: #5e8ef5;;
        }

        /* Bottom text animation */
        .text.bottom {
            width: auto;
        }

        .text.bottom span {
            display: inline-block;
            transform: translateX(-150%); /* Start off-screen left */
            animation: slideLeftToCenter 3s forwards 1s; /* 3s animation with 1s delay */
        }

        @keyframes fadeinout {
            0%, 100% { opacity: 0; }
            20%, 80% { opacity: 1; }
        }

        @keyframes slideLeftToCenter {
            0% { transform: translateX(-150%); opacity: 0; }
            10% { opacity: 1; }
            100% { transform: translateX(0); opacity: 1; }
        }

        p {
            font-size: clamp(10px, 2vw, 12px);
            color: #999;
            margin-top: 1.5rem;
            text-align: center;
        }

        @media (max-width: 600px) {
            body {
                font-size: clamp(18px, 6vw, 28px);
            }

            .text.bottom span {
                transform: translateX(-200%);
            }
        }
    </style>
</head>

<body>
    <div class="text top"><h2>𝓦𝓔𝓛𝓒𝓞𝓜𝓔</h2></div>
    <div class="text bottom"><span>𝓨𝓸𝓾𝓻 𝓞𝓷𝓵𝓲𝓷𝓮 𝓗𝓮𝓪𝓵𝓽𝓱 𝓒𝓪𝓻𝓮 𝓐𝓼𝓼𝓲𝓼𝓽𝓪𝓷𝓬𝓮</span></div>
    <p>Loading...</p>

    <script>
        // Redirect after animation finishes
        setTimeout(() => {
            window.location.href = 'chatbot.php';
        }, 6000); // Adjust to match animation
    </script>
</body>

</html>