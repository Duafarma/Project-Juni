<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sedang Dalam Pengembangan</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --bg-light: #f2f4f8;
      --bg-dark: #121212;
      --text-light: #1f1f1f;
      --text-dark: #ffffff;
      --accent: #4e8cff;
      --shadow: rgba(0,0,0,0.1);
    }

    body {
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: 'Poppins', sans-serif;
      background: var(--bg-light);
      color: var(--text-light);
      transition: background 0.3s, color 0.3s;
    }

    @media (prefers-color-scheme: dark) {
      body {
        background: var(--bg-dark);
        color: var(--text-dark);
      }
    }

    .container {
      text-align: center;
      padding: 40px;
      border-radius: 20px;
      box-shadow: 0 10px 30px var(--shadow);
      background: rgba(255,255,255,0.7);
      backdrop-filter: blur(12px);
      animation: fadeIn 1s ease-in-out;
    }

    @media (prefers-color-scheme: dark) {
      .container {
        background: rgba(30,30,30,0.7);
      }
    }

    h1 {
      font-size: 2.2rem;
      margin-bottom: 10px;
      animation: slideDown 0.8s ease;
    }

    p {
      font-size: 1.1rem;
      margin-bottom: 25px;
    }

    .gear {
      position: relative;
      display: inline-block;
      width: 80px;
      height: 80px;
      margin-bottom: 20px;
    }

    .gear::before,
    .gear::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      border: 6px solid var(--accent);
      border-radius: 50%;
      box-sizing: border-box;
      animation: spin 6s linear infinite;
    }

    .gear::after {
      width: 60%;
      height: 60%;
      top: 20%;
      left: 20%;
      border: 4px solid var(--accent);
      animation-duration: 3s;
      animation-direction: reverse;
    }

    .button {
      display: inline-block;
      padding: 10px 25px;
      border-radius: 8px;
      background: var(--accent);
      color: white;
      font-weight: 600;
      text-decoration: none;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .button:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(78, 140, 255, 0.3);
    }

    footer {
      margin-top: 30px;
      font-size: 0.85rem;
      opacity: 0.7;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="gear"></div>
    <h1>Halaman Sedang Dalam Pengembangan</h1>
    <p>Kami sedang bekerja keras untuk menghadirkan sesuatu yang luar biasa. Silakan kembali lagi nanti!</p>
    <a href="https://imsduafarma.link/192.268.908.09/" class="button">Kembali ke Beranda</a>
    <footer>© 2025 — Dibuat dengan ❤️ oleh Tim IT Dua Farma</footer>
  </div>
</body>
</html>
