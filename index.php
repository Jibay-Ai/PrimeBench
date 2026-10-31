<?php
header('Content-Type: text/html; charset=utf-8');

$aFile = __DIR__ . '/A.txt';

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = $_POST['answers'] ?? '';
    $userAnswers = [];
    $invalidLines = [];

    $normalized = preg_replace('/[\s,;]+(?=\d+\s*:)/u', "\n", trim($rawInput));
    $lines = preg_split('/\r\n|\r|\n/', $normalized);

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (!preg_match('/^(\d+)\s*:\s*(none|[1-4])$/iu', $line, $m)) {
            $invalidLines[] = $line;
            continue;
        }
        $qNum = (int)$m[1];
        $ans  = strtolower($m[2]);
        $userAnswers[$qNum] = ($ans === 'none') ? 'none' : (int)$ans;
    }

    if (!file_exists($aFile) || !is_readable($aFile)) {
        $error = 'Answer key file (A.txt) not found.';
    } else {
        $aLines = preg_split('/\r\n|\r|\n/', file_get_contents($aFile));
        $answerKey = [];
        foreach ($aLines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if (preg_match('/^(\d+)\s*:\s*([1-4])$/', $line, $m)) {
                $answerKey[(int)$m[1]] = (int)$m[2];
            }
        }

        if (empty($answerKey)) {
            $error = 'Answer key (A.txt) is empty or invalid.';
        } else {
            $total = count($answerKey);
            $correct = 0;
            $wrong = 0;
            $unanswered = 0;

            foreach ($answerKey as $qNum => $correctOpt) {
                $user = $userAnswers[$qNum] ?? 'none';
                if ($user === 'none') {
                    $unanswered++;
                } elseif ($user === $correctOpt) {
                    $correct++;
                } else {
                    $wrong++;
                }
            }

            $positive = $correct * 1;
            $negative = $wrong * 3;
            $final = $positive - $negative;

            $result = [
                'total' => $total,
                'correct' => $correct,
                'wrong' => $wrong,
                'unanswered' => $unanswered,
                'positive' => $positive,
                'negative' => $negative,
                'final' => $final,
                'invalidLines' => $invalidLines,
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quiz Scorer</title>
<style>
@font-face {
    font-family: 'Jibay';
    src: url('https://jibay.ir/Arial.ttf') format('truetype');
    font-weight: normal;
    font-style: normal;
    font-display: swap;
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Jibay', Tahoma, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.card {
    width: 100%;
    max-width: 640px;
    background: rgba(255, 255, 255, 0.96);
    backdrop-filter: blur(10px);
    border-radius: 24px;
    padding: 32px 28px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
    animation: fadeIn 0.6s ease;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to   { opacity: 1; transform: translateY(0); }
}
h1 {
    font-size: 1.7rem;
    color: #4a3f8f;
    text-align: center;
    margin-bottom: 6px;
}
.subtitle {
    text-align: center;
    color: #8b87b5;
    font-size: 0.95rem;
    margin-bottom: 24px;
}
.field { margin-bottom: 18px; }
label {
    display: block;
    font-size: 0.95rem;
    color: #4a3f8f;
    margin-bottom: 8px;
    font-weight: bold;
}
textarea {
    width: 100%;
    font-family: 'Jibay', Tahoma, monospace;
    font-size: 1rem;
    padding: 12px 14px;
    border: 2px solid #e0dcf5;
    border-radius: 14px;
    background: #faf9ff;
    color: #333;
    outline: none;
    transition: all 0.25s;
    resize: vertical;
    min-height: 160px;
    line-height: 1.6;
    direction: ltr;
    text-align: left;
}
textarea:focus {
    border-color: #7c6ee0;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(124, 110, 224, 0.15);
}
.btn {
    width: 100%;
    padding: 15px;
    font-family: 'Jibay', Tahoma, sans-serif;
    font-size: 1.15rem;
    color: #fff;
    background: linear-gradient(135deg, #7c6ee0, #a855f7);
    border: none;
    border-radius: 16px;
    cursor: pointer;
    margin-top: 12px;
    transition: all 0.25s;
    box-shadow: 0 8px 22px rgba(124, 110, 224, 0.4);
}
.btn:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(124, 110, 224, 0.5); }
.btn:active { transform: translateY(0); }
.results {
    margin-top: 24px;
    padding: 20px;
    border-radius: 16px;
    background: #f7f6ff;
    border: 2px solid #eee9fb;
}
.results h2 {
    font-size: 1.2rem;
    color: #4a3f8f;
    margin-bottom: 16px;
    text-align: center;
}
.grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.stat {
    background: #fff;
    padding: 14px;
    border-radius: 12px;
    border: 1px solid #eee9fb;
    text-align: center;
}
.stat .label {
    font-size: 0.85rem;
    color: #8b87b5;
    margin-bottom: 4px;
}
.stat .value {
    font-size: 1.3rem;
    font-weight: bold;
    color: #4a3f8f;
}
.stat.positive .value { color: #2e9e6b; }
.stat.negative .value { color: #d64545; }
.stat.final {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #7c6ee0, #a855f7);
    border: none;
}
.stat.final .label { color: rgba(255,255,255,0.8); }
.stat.final .value { color: #fff; font-size: 1.6rem; }
.error-box {
    margin-top: 20px;
    padding: 14px 18px;
    border-radius: 12px;
    background: #fdecec;
    border: 2px solid #f5c2c2;
    color: #b02a2a;
    font-size: 0.95rem;
}
.warn-box {
    margin-top: 16px;
    padding: 12px 16px;
    border-radius: 12px;
    background: #fff8e1;
    border: 2px solid #ffe08a;
    color: #8a6d00;
    font-size: 0.9rem;
    word-break: break-all;
}
@media (max-width: 480px) {
    .card { padding: 24px 18px; border-radius: 20px; }
    h1 { font-size: 1.4rem; }
    .grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="card">
    <h1>Quiz Scorer</h1>
    <p class="subtitle">Enter your answers below and check your score</p>

    <form method="post" action="">
        <div class="field">
            <label for="answers">Your Answers</label>
            <textarea id="answers" name="answers" placeholder="1:2&#10;2:4&#10;3:none" required><?= htmlspecialchars($_POST['answers'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="btn">Check Score</button>
    </form>

    <?php if ($error !== null): ?>
        <div class="error-box"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($result !== null): ?>
        <div class="results">
            <h2>Results</h2>
            <div class="grid">
                <div class="stat">
                    <div class="label">Total Questions</div>
                    <div class="value"><?= (int)$result['total'] ?></div>
                </div>
                <div class="stat">
                    <div class="label">Unanswered</div>
                    <div class="value"><?= (int)$result['unanswered'] ?></div>
                </div>
                <div class="stat">
                    <div class="label">Correct</div>
                    <div class="value"><?= (int)$result['correct'] ?></div>
                </div>
                <div class="stat">
                    <div class="label">Wrong</div>
                    <div class="value"><?= (int)$result['wrong'] ?></div>
                </div>
                <div class="stat positive">
                    <div class="label">Positive Points</div>
                    <div class="value">+<?= (int)$result['positive'] ?></div>
                </div>
                <div class="stat negative">
                    <div class="label">Negative Points</div>
                    <div class="value">-<?= (int)$result['negative'] ?></div>
                </div>
                <div class="stat final">
                    <div class="label">Final Score</div>
                    <div class="value"><?= (int)$result['final'] ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($result['invalidLines'])): ?>
            <div class="warn-box">
                <strong>Ignored invalid lines:</strong>
                <?= htmlspecialchars(implode(' | ', $result['invalidLines']), ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>