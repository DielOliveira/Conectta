<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Atendimento rejeitado</title>
    <style>
        * { box-sizing: border-box; }
        body {
            display: grid;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            place-items: center;
            background: linear-gradient(160deg, #fff7ed, #f3f4f6 60%);
            color: #172033;
            font: 16px/1.5 system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .card {
            width: min(100%, 460px);
            padding: 30px 24px;
            border: 1px solid #e5e7eb;
            border-radius: 22px;
            background: #fff;
            text-align: center;
            box-shadow: 0 20px 50px rgba(15, 23, 42, .10);
        }
        .icon-wrap {
            display: grid;
            width: 62px;
            height: 62px;
            margin: 0 auto 18px;
            place-items: center;
            border-radius: 19px;
            background: #fef3f2;
            color: #b42318;
        }
        svg {
            width: 30px;
            height: 30px;
            fill: none;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            stroke-width: 2;
        }
        h1 { margin: 0 0 8px; font-size: 1.45rem; }
        p { margin: 0; color: #667085; }
        .hint {
            margin-top: 20px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
            font-size: .9rem;
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="icon-wrap">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12" /></svg>
        </div>
        <h1>Atendimento rejeitado</h1>
        <p>A central recebeu o motivo informado e o horário foi liberado novamente.</p>
        <p class="hint">Você já pode fechar esta página.</p>
    </main>
</body>
</html>
