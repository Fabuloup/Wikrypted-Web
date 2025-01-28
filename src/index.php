<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon.png" type="image/png">
    <title>Decode Wikipedia Article</title>
    <style>
        body {
            background-color: black;
            color: green;
            font-family: monospace;
            font-size: 1rem;
        }
        .terminal {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid green;
        }
        .cursor {
            background-color: gray;
            color: black;
        }
        .result {
            margin-top: 10px;
            min-height: 50px;
        }
        button {
            font-size: 1.5em;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1>Wikrypted</h1>
    <h2>Decode Wikipedia Article</h2>
    <div class="terminal" id="terminal"></div>
    <div class="result" id="result"></div>
    <button onclick="hint()">Hint</button>
    <button id="refresh" onclick="refresh()">Abandon</button>
    <script>
        let cursorX = 0, cursorY = 0;
        let lookupTable = {};
        let originalArticle = {};
        let encodedArticle = {};
        let originalLines = [];
        let lines = [];

        function fetchArticle() {
            fetch('server.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
            })
            .then(response => response.json())
            .then(data => {
                cursorX = 0;
                cursorY = 0;
                lookupTable = {};
                originalArticle = data.original;
                encodedArticle = data.encoded;
                originalLines = wrapLineToConsoleWidth(ArticleToString(originalArticle));
                lines = wrapLineToConsoleWidth(ArticleToString(encodedArticle));
                const resultDiv = document.getElementById('result');
                resultDiv.innerHTML = '';
                displayArticle();
            });
        }

        function ArticleToString(article) {
            const outline = new Array(article.title.length + 1).join("=");
            return `${article.title}\n${outline}\n${article.description}`;
        }

        function wrapLineToConsoleWidth(content) {
            const consoleWidth = 80;
            const lines = content.split('\n');
            const result = [];

            lines.forEach(line => {
            if (line.length <= consoleWidth) {
                result.push(line);
            } else {
                for (let start = 0; start < line.length; start += consoleWidth) {
                    result.push(line.substring(start, start + consoleWidth));
                }
            }
            });

            return result;
        }

        function displayArticle() {
            const terminal = document.getElementById('terminal');
            terminal.innerHTML = '';

            lines.forEach((line, y) => {
                const div = document.createElement('div');
                for (let x = 0; x < line.length; x++) {
                    const span = document.createElement('span');
                    let char = line[x];
                    if (lookupTable[originalLines[y][x]]) {
                        char = lookupTable[originalLines[y][x]];
                    }
                    if (x === cursorX && y === cursorY) {
                        span.classList.add('cursor');
                    }
                    span.textContent = char;
                    span.onclick = () => { cursorX = x; cursorY = y; displayArticle(); };
                    div.appendChild(span);
                }
                terminal.appendChild(div);
            });

            if(isDecoded()) {
                const resultDiv = document.getElementById('result');
                resultDiv.innerHTML = `Article decoded !<br><a href="${originalArticle.url}">${originalArticle.url}</a>`;

                const refreshBtn = document.getElementById('refresh');
                refreshBtn.innerText = 'New game';
            }
        }

        function isDecoded() {
            for (let i = 0; i < encodedArticle.description.length; i++) {
            const encodedChar = encodedArticle.description[i];
            const originalChar = originalArticle.description[i];
            if (lookupTable[originalChar] !== undefined) {
                if (lookupTable[originalChar] !== originalChar && lookupTable[originalChar].toLowerCase() !== originalChar.toLowerCase()) {
                return false;
                }
                } else if (encodedChar !== originalChar && encodedChar.toLowerCase() !== originalChar.toLowerCase()) {
                    return false;
                }
            }
            return true;
        }

        function hint() {
            for (let x = 0; x < encodedArticle.description.length; x++) {
                const char = originalArticle.description[x];
                if (encodedArticle.description[x] !== char && lookupTable[char] !== char) {
                    lookupTable[char] = char;
                    displayArticle();
                    return;
                }
            }
        }

        function refresh() {
            //location.reload();
            fetchArticle();
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowUp') cursorY = Math.max(0, cursorY - 1);
            else if (event.key === 'ArrowDown') cursorY = Math.min(lines.length - 1, cursorY + 1);
            else if (event.key === 'ArrowLeft') cursorX = Math.max(0, cursorX - 1);
            else if (event.key === 'ArrowRight') cursorX = Math.min(lines[cursorY].length - 1, cursorX + 1);
            else if (event.key.length === 1) {
                const originalChar = originalLines[cursorY][cursorX];
                lookupTable[originalChar] = event.key;
            }
            displayArticle();
        });

        fetchArticle();
    </script>
</body>
</html>