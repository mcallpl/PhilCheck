<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhilCheck — Your Health Journal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="header">
    <h1><span class="heart">&#9829;</span> PhilCheck</h1>
    <div class="header-stats">
        <span>&#128221; <span id="statEntries">0</span> entries</span>
        <span>&#128172; <span id="statChats">0</span> chats</span>
        <span>&#128197; <span id="statDays">0</span> days tracking</span>
    </div>
</div>

<div class="nav-tabs">
    <button class="nav-tab active" data-panel="add">Add Health Notes</button>
    <button class="nav-tab" data-panel="chat">Ask PhilCheck</button>
    <button class="nav-tab" data-panel="journal">My Journal</button>
</div>

<div class="main">

    <!-- ADD PANEL -->
    <div id="add" class="panel active">
        <div class="card">
            <h2>Drop Files Here</h2>
            <div class="drop-zone" id="dropZone">
                <div class="icon">&#128203;</div>
                <p>Drag & drop PDF or text files here</p>
                <p class="hint">or click to browse your files</p>
            </div>
            <input type="file" id="fileInput" multiple accept=".pdf,.txt,.text,.md,.csv,.log,.rtf" style="display:none">
            <div class="upload-status" id="uploadStatus"></div>
        </div>

        <div class="card">
            <h2>Or Paste Your Notes</h2>
            <div class="text-input-area">
                <textarea id="pasteText" placeholder="Paste or type your health notes here...&#10;&#10;For example:&#10;- What you ate today&#10;- How you're feeling&#10;- Any symptoms or energy levels&#10;- Notes from a doctor visit&#10;- Your conversation with AI about health"></textarea>
                <div class="btn-group">
                    <button class="btn btn-primary" id="saveTextBtn">Save to Journal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- CHAT PANEL -->
    <div id="chat" class="panel">
        <div class="card chat-container">
            <div class="chat-messages" id="chatMessages">
                <div id="chatWelcome" class="chat-welcome">
                    <div class="icon">&#129657;</div>
                    <h3>Hi Phil! Ask me anything about your health.</h3>
                    <p>I'll look through all your journal entries to give you personalized answers.</p>
                    <p style="font-size:15px;color:#999;">The more notes you add, the smarter I get!</p>
                    <div class="suggestions">
                        <div class="suggestion">How am I feeling lately?</div>
                        <div class="suggestion">What foods seem to bother me?</div>
                        <div class="suggestion">When do I have the most energy?</div>
                        <div class="suggestion">Show me my health trends</div>
                        <div class="suggestion">What should I eat right now?</div>
                    </div>
                </div>
                <div class="typing-indicator" id="typingIndicator">
                    <span></span><span></span><span></span>
                </div>
            </div>
            <div class="chat-input-area">
                <input type="text" id="chatInput" placeholder="Ask me about your health..." autocomplete="off">
                <button class="btn btn-primary" id="sendBtn">Send</button>
            </div>
        </div>
    </div>

    <!-- JOURNAL PANEL -->
    <div id="journal" class="panel">
        <div class="card">
            <h2>Your Health Journal</h2>
            <div id="entriesList">
                <p style="text-align:center;color:#999;padding:40px;">Loading entries...</p>
            </div>
        </div>
    </div>

</div>

<div class="toast" id="toast"></div>

<script src="js/app.js"></script>
</body>
</html>
