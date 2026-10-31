# PrimeBench

**PrimeBench** is a hard reasoning and intelligence benchmark for large language models.

It is not designed to be a friendly, broad, "everything is fine" evaluation. It is deliberately adversarial. Most of the items are built so that a model cannot simply pattern-match a familiar phrasing and produce the expected answer. It has to reason, count, decode, translate, disambiguate, or hold several constraints in mind at once. Strong models routinely finish below zero on PrimeBench, and that is by design — a negative score means the model's guessing behaviour and confident wrong answers cost it more than its genuine knowledge earns it.

PrimeBench is maintained by **JibayAI** — [jibay.ir](https://jibay.ir).

---

## Table of Contents

1. [What PrimeBench Measures](#what-primebench-measures)
2. [Benchmark Structure](#benchmark-structure)
3. [Scoring System](#scoring-system)
4. [Leaderboard](#leaderboard)
5. [How to Run PrimeBench](#how-to-run-primebench)
6. [The Official Prompt](#the-official-prompt)
7. [Output Format](#output-format)
8. [Scoring a Model's Submission](#scoring-a-models-submission)
9. [Thinking Mode and Tool Use](#thinking-mode-and-tool-use)
10. [Design Philosophy](#design-philosophy)
11. [Attribution and License](#attribution-and-license)

---

## What PrimeBench Measures

PrimeBench evaluates models at a **hard difficulty level** across the following capabilities:

1. **Intelligence** — raw problem-solving ability on unfamiliar, deliberately constructed items.
2. **Reasoning and thinking** — multi-step deduction, logical puzzles, constraint satisfaction, liar/truth-teller problems, and consistency checks.
3. **Mathematics** — number patterns, combinatorics, modular arithmetic, probability, algebra, and classic probability puzzles (e.g. conditional probability, the egg-drop problem, the 100-prisoners problem).
4. **Literature** — Persian literary comprehension, poetic imagery, and interpretation of classical verses.
5. **English** — language comprehension, ambiguity, anagrams, spelling, and word-level reasoning.
6. **Persian** — Persian-only puzzles, letter counting, dot counting, homographs, and script-level manipulation.
7. **Mixed English and Persian** — items where the problem statement, options, and required reasoning cross between the two languages, or are written in transliterated (Finglish) form.
8. **Meaning comprehension** — does the model actually understand what a sentence means, or is it pattern-matching keywords?
9. **Relationship comprehension** — analogy and relation items: A is to B as C is to D, family-tree relations, and category relations.
10. **General knowledge** — facts that a well-read reasoner should know, but embedded inside trick framing.
11. **Riddles** — classic and non-classic riddles where the obvious answer is wrong.
12. **Hidden tokens** — characters, words, and digits hidden inside strings, zero-width or near-invisible constructs, and patterns that are not visually obvious to a human reader but are decodable by a sufficiently careful AI model. This is one of the most distinctive sections of PrimeBench.
13. **Time and date exchange** — calendar conversion (Gregorian / Jalali / Hijri), leap years, day-of-year arithmetic, seconds-since-midnight, and cross-calendar date mapping.
14. **Dot counting** — counting dots (نقطه) in Persian words, letters, and full sentences, including the difference between dotted and undotted letter forms.
15. **Letter and word counting** — counting occurrences of specific letters, counting total letters in a sentence, counting repeated characters in a string, and reversing / anagramming strings exactly.

PrimeBench intentionally mixes these categories instead of grouping them, so a model cannot warm up on an easy category before hitting a hard one. The order is randomised.

---

## Benchmark Structure

PrimeBench contains **104 questions**.

The benchmark ships as two files:

### `Q.json`

A JSON map of question objects. Each entry has a key of the form `Question_1`, `Question_2`, … and contains the exact question text and the four shuffled options:

```json
{
    "Question_1": {
        "question": "The exact question text",
        "option_1": "First option",
        "option_2": "Second option",
        "option_3": "Third option",
        "option_4": "Fourth option"
    },
    "Question_2": {
        "question": "The exact question text",
        "option_1": "First option",
        "option_2": "Second option",
        "option_3": "Third option",
        "option_4": "Fourth option"
    }
}
```

- Question text and option text are preserved **exactly**. Nothing is trimmed, normalised, or removed.
- The order of the questions is randomised.
- The order of the options within each question is randomised independently.
- The correct answer moves with the option it belongs to, so a model cannot exploit option position.

### `A.txt`

The answer key. One line per question:

```
1:3
2:1
3:4
4:2
...
104:1
```

The format is `question_number:correct_option_number`, where the option number is 1–4.

---

## Scoring System

PrimeBench uses a penalty-based scoring model, not a plain accuracy percentage:

| Outcome | Points |
|---|---|
| Correct answer | **+1** |
| Wrong answer | **−3** |
| No answer (`none`) | **0** |

The final score is the sum:

```
final_score = (number_of_correct × 1) − (number_of_wrong × 3)
```

Questions left unanswered — or marked `none` — contribute nothing in either direction.

This means the benchmark does not reward blind guessing. A model that answers every question randomly will finish deeply negative. A model that knows when it does not know can protect its score by writing `none`. The scoring system therefore measures two things at once: **reasoning accuracy** and **calibrated self-knowledge**.

There are 104 questions, so:

- The theoretical maximum is **+104**.
- The worst possible score is **−312** (all 104 wrong).
- A model that answers 50 correctly, 50 wrong, and 4 `none` scores `50 − 150 = −100`.
- A model that answers 40 correctly, 10 wrong, and 54 `none` scores `40 − 30 = +10`.

The last example is the key insight of PrimeBench: **restraint is a skill**.

---

## Leaderboard

The following scores have been recorded on PrimeBench. Some models were run with thinking and/or tools enabled, and some were not. This distinction is recorded explicitly because it has a large effect on the final score.

| Model | Mode | Score |
|---|---|---|
| Jibay 5 High | think & tools | **+18** |
| Jibay 5 Low | default | **−71** |
| deepseek v4.1 flash | default | **−104** |
| deepseek v4.1 flash | think | **+17** |
| claude sonnet 5.5 medium | think & tools | **+38** |
| claude sonnet 5 medium | think & tools | **−108** |
| gpt-6 luna | default | **−45** |
| gpt-6 luna | think & tools | **+21** |
| Grok 4.6 | default | **−77** |
| Qwen3.8 max | default | **−74** |
| Qwen3.5 Plus | default | **−70** |
| Kimi K2.6 instant | default | **−76** |
| Gemini 3.5 flash-lite | default | **−45** |

### Reading the leaderboard

Two patterns stand out and are worth stating explicitly:

1. **Thinking mode matters enormously.** Every model that was tested both with and without reasoning had a large positive swing when thinking was enabled. `deepseek v4.1 flash` goes from −104 to +17. `gpt-6 luna` goes from −45 to +21. The same base model can be the worst and one of the best entries on the board depending purely on whether it is allowed to reason before answering.
2. **Version naming does not imply score ordering.** `claude sonnet 5.5 medium` and `claude sonnet 5 medium` land at +38 and −108 respectively. A model's positioning in a family does not predict its PrimeBench performance.

A positive score on PrimeBench means the model is answering meaningfully more questions correctly than it is answering wrongly, with the 3× wrong-answer penalty already applied. A negative score means the model's confident errors are outweighing its correct answers.

---

## How to Run PrimeBench

Running PrimeBench against a model is a three-step process.

### Step 1 — Provide `Q.json`

Give the model the full contents of `Q.json`. Do not summarise it, reformat it, or drop questions. The benchmark's difficulty depends on the model seeing the exact text, including any hidden characters, unusual spacing, mixed-script tokens, and transliterated phrases.

### Step 2 — Provide the official prompt

Send the model the exact prompt described in [The Official Prompt](#the-official-prompt). The prompt defines the answer format, the scoring rules, and the `none` escape hatch. Do not modify it if you intend your score to be comparable with the leaderboard.

### Step 3 — Collect the output and score it

Take the model's raw output, paste it into the scorer, and read the resulting score. The scorer parses the `Q:O` lines, compares them against `A.txt`, and computes positive points, negative points, correct count, wrong count, unanswered count, and the final score.

The scorer accepts answers separated by newlines, spaces, commas, or semicolons, so a model that returns all 104 answers on a single line will still be scored correctly. Invalid lines are shown separately rather than silently ignored.

---

## The Official Prompt

The following prompt is the canonical PrimeBench prompt. Use it verbatim.

```text
Based on the questions, choose the correct option.

Explanation:
Each correct option you choose earns one positive point.
Each incorrect option you choose incurs three negative points.
If you do not select an option for a question, you receive neither a negative nor a positive point.

Your output must only and exclusively be in this format Q:O
For example; the first question, option three:
1:3

If you do not want to or cannot answer a question, put none instead of the option number.
You have only one choice for each question.
Your score will be evaluated from 0 to 100 percent.
Provide the final answer.
```

Do not add hints, category labels, difficulty warnings, chain-of-thought instructions, or extra examples. The prompt is deliberately minimal so that the measured performance reflects the model rather than the prompt author.

---

## Output Format

The model must return one line per question in the form:

```
question_number:option_number
```

Examples:

```
1:3
2:none
3:1
4:4
```

Rules:

- `question_number` is the number of the question, from 1 to 104.
- `option_number` is one of `1`, `2`, `3`, or `4`.
- `none` means the model declines to answer and receives zero points for that question.
- Exactly one choice is allowed per question. If a question number appears twice, only the behaviour of the scorer applies; do not rely on duplicate entries.
- No explanations, no commentary, no markdown tables, no bullet points. Only `Q:O` lines.

A valid complete submission therefore looks like:

```
1:2
2:4
3:none
4:1
5:3
...
104:1
```

---

## Scoring a Model's Submission

The scorer compares the submission against `A.txt` and reports:

- **Total Questions** — number of questions in the answer key.
- **Correct** — questions answered with the correct option.
- **Wrong** — questions answered with an incorrect option, including malformed but parseable answers.
- **Unanswered** — questions marked `none` or omitted entirely.
- **Positive Points** — `correct × 1`.
- **Negative Points** — `wrong × 3`.
- **Final Score** — `positive − negative`.

The scorer is forgiving about layout but strict about correctness. It will happily parse a submission that uses spaces instead of newlines, mixed capitalisation of `none`, or extra whitespace around the colon. It will not forgive a wrong option.

If the answer key contains fewer than 104 entries, the scorer iterates only over the entries that exist and does not error. If a submitted question number does not exist in the answer key, it is ignored.

---

## Thinking Mode and Tool Use

PrimeBench explicitly records whether a model was run **with** or **without** extended thinking and tool use, because the effect on the final score is not marginal — it is often the difference between a strongly negative score and a positive one.

Observations from the leaderboard:

- `deepseek v4.1 flash`: **−104** without thinking, **+17** with thinking. A swing of 121 points.
- `gpt-6 luna`: **−45** without thinking, **+21** with thinking. A swing of 66 points.
- `Jibay 5 High` with thinking and tools reaches **+18**, while `Jibay 5 Low` without them sits at **−71**.

This is not a flaw in the benchmark. It is a finding. PrimeBench is constructed so that:

- Single-pass pattern matching is insufficient for many items.
- Several items require counting or decoding that is error-prone without deliberate step-by-step work.
- Several items contain near-miss distractors designed to catch the fast, confident, wrong answer.
- The 3× wrong-answer penalty punishes overconfidence much more than it punishes restraint.

A model that is allowed to think can enumerate options, count characters, decode hidden tokens, and verify its answer before committing. A model that is forced to answer instantly tends to fall back on heuristics, and PrimeBench is built to defeat heuristics.

When reporting a PrimeBench score, always state the mode. A score without a mode is not a comparable result.

---

## Design Philosophy

PrimeBench was built around four convictions.

### 1. Hard means hard

A benchmark that every frontier model scores 90% on stops being informative. PrimeBench is intentionally calibrated so that most models finish below zero and the best models finish only modestly positive. The distribution is supposed to be spread out across a wide negative-to-positive range, not compressed near the ceiling.

### 2. Guessing must be punished

With +1 for correct and −3 for wrong, a model that guesses uniformly at random on 4-option questions has expected value of `0.25×1 + 0.75×(−3) = −2` per question. Over 104 questions, that is an expected score of −208. A model that abstains on everything scores 0. Abstaining beats guessing by a wide margin. This forces models to demonstrate calibration, not just knowledge.

### 3. Categories must be interleaved

Questions are shuffled. Categories are not grouped. A model cannot adopt a "math mode" for a math block and then relax. It has to detect the nature of each item on the fly, which is much closer to real reasoning than a category-labelled exam.

### 4. Options must be shuffled

The correct answer is not always in position 2. It is not always the longest option. It is not always the most hedged option. Positions are randomised per question, so positional bias produces no advantage.

### 5. The two-language design is deliberate

PrimeBench is bilingual by construction. Persian items, English items, mixed items, and transliterated items all appear. This tests whether a model genuinely handles both languages at a reasoning level, rather than being strong in English and merely fluent-looking in Persian. It also catches models that silently translate a Persian puzzle into English, losing the very orthographic detail the puzzle depends on.

### 6. Hidden tokens are a first-class category

Some items in PrimeBench are designed so that the human eye does not immediately register the relevant character, spacing, or repetition, but a careful model can detect it. This category is included because visual-obviousness is not a requirement for reasoning, and models should be able to operate on the actual string they were given rather than on a fuzzy impression of it.

---

## Attribution and License

**PrimeBench** is created and maintained by **JibayAI** — [https://jibay.ir](https://jibay.ir).

- The benchmark, its question set, its answer key, its scoring rules, its prompt, and its leaderboard are the work of JibayAI.
- **No one is permitted to update, modify, edit, or publish PrimeBench under a different name or a different version.** This includes renaming, rebranding, re-releasing, forking under another identity, or distributing an altered question set as if it were PrimeBench.
- The 104-question set, `Q.json`, and `A.txt` must not be altered and re-published.
- Scores reported against a modified question set, a modified prompt, or a modified scoring rule are **not** PrimeBench scores and must not be presented as such.
- When reporting a PrimeBench result, credit JibayAI and link to [jibay.ir](https://jibay.ir).

**License: CC BY-ND 4.0** — Creative Commons Attribution-NoDerivatives 4.0 International.

Under this license:

- **Attribution** — you must give appropriate credit to JibayAI, provide a link to the license, and indicate if changes were made. You may do so in any reasonable manner, but not in any way that suggests JibayAI endorses you or your use.
- **NoDerivatives** — if you remix, transform, or build upon the material, you may not distribute the modified material.

Full license text: [https://creativecommons.org/licenses/by-nd/4.0/](https://creativecommons.org/licenses/by-nd/4.0/)

---

*PrimeBench — a hard reasoning benchmark by JibayAI.*
