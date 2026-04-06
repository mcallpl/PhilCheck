// PhilCheck — Health Journal App
document.addEventListener('DOMContentLoaded', () => {
    // Tab navigation
    const tabs = document.querySelectorAll('.nav-tab');
    const panels = document.querySelectorAll('.panel');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById(tab.dataset.panel).classList.add('active');
            if (tab.dataset.panel === 'journal') loadEntries();
            if (tab.dataset.panel === 'chat') chatInput.focus();
        });
    });

    // --- DROP ZONE & FILE UPLOAD ---
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const textArea = document.getElementById('pasteText');
    const uploadStatus = document.getElementById('uploadStatus');

    dropZone.addEventListener('click', () => fileInput.click());
    dropZone.addEventListener('dragover', e => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', e => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        if (e.dataTransfer.files.length) handleFiles(e.dataTransfer.files);
    });
    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) handleFiles(fileInput.files);
        fileInput.value = '';
    });

    async function handleFiles(files) {
        const formData = new FormData();
        for (let f of files) formData.append('files[]', f);

        uploadStatus.innerHTML = '<div class="upload-item"><div class="spinner"></div> Uploading and processing files...</div>';
        uploadStatus.classList.add('show');

        try {
            const res = await fetch('api/upload.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            let html = '';
            data.results.forEach(r => {
                if (r.success) {
                    html += `<div class="upload-item success">&#10003; ${r.file} — saved successfully</div>`;
                } else {
                    html += `<div class="upload-item error">&#10007; ${r.file} — ${r.error}</div>`;
                }
            });
            uploadStatus.innerHTML = html;
            showToast('Files processed!');
            loadStats();
        } catch (err) {
            uploadStatus.innerHTML = `<div class="upload-item error">&#10007; ${err.message}</div>`;
        }
    }

    // Save pasted text
    document.getElementById('saveTextBtn').addEventListener('click', async () => {
        const text = textArea.value.trim();
        if (!text) { showToast('Please enter some text first'); return; }

        const btn = document.getElementById('saveTextBtn');
        btn.disabled = true;
        btn.textContent = 'Saving...';

        try {
            const formData = new FormData();
            formData.append('text_content', text);
            const res = await fetch('api/upload.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            textArea.value = '';
            showToast('Text saved to your health journal!');
            loadStats();
        } catch (err) {
            showToast('Error: ' + err.message);
        } finally {
            btn.disabled = false;
            btn.textContent = 'Save to Journal';
        }
    });

    // --- CHAT ---
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    const typingIndicator = document.getElementById('typingIndicator');

    function sendMessage() {
        const msg = chatInput.value.trim();
        if (!msg) return;

        // Hide welcome
        const welcome = document.getElementById('chatWelcome');
        if (welcome) welcome.style.display = 'none';

        // Add user message
        appendMessage('user', msg);
        chatInput.value = '';
        chatInput.focus();

        // Show typing
        typingIndicator.classList.add('show');
        chatMessages.scrollTop = chatMessages.scrollHeight;
        sendBtn.disabled = true;

        fetch('api/chat.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: msg })
        })
        .then(r => r.json())
        .then(data => {
            typingIndicator.classList.remove('show');
            sendBtn.disabled = false;
            if (!data.success) throw new Error(data.error);
            appendMessage('assistant', data.reply);
        })
        .catch(err => {
            typingIndicator.classList.remove('show');
            sendBtn.disabled = false;
            appendMessage('assistant', 'Sorry, something went wrong: ' + err.message);
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keydown', e => {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });

    // Suggestion chips
    document.querySelectorAll('.suggestion').forEach(s => {
        s.addEventListener('click', () => {
            chatInput.value = s.textContent;
            sendMessage();
        });
    });

    function appendMessage(role, text) {
        const div = document.createElement('div');
        div.className = `chat-message ${role}`;
        div.innerHTML = role === 'assistant' ? formatMarkdown(text) : escapeHtml(text);
        const time = document.createElement('div');
        time.className = 'time';
        time.textContent = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        div.appendChild(time);
        chatMessages.insertBefore(div, typingIndicator);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function formatMarkdown(text) {
        return text
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.*?)\*/g, '<em>$1</em>')
            .replace(/^### (.*$)/gm, '<strong style="font-size:1.1em">$1</strong>')
            .replace(/^## (.*$)/gm, '<strong style="font-size:1.15em">$1</strong>')
            .replace(/^# (.*$)/gm, '<strong style="font-size:1.2em">$1</strong>')
            .replace(/^- (.*$)/gm, '&bull; $1')
            .replace(/^\d+\. (.*$)/gm, '$&')
            .replace(/\n\n/g, '</p><p>')
            .replace(/\n/g, '<br>')
            .replace(/^/, '<p>').replace(/$/, '</p>');
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    // --- JOURNAL ENTRIES ---
    let currentPage = 1;

    async function loadEntries(page = 1) {
        currentPage = page;
        const container = document.getElementById('entriesList');
        try {
            const res = await fetch(`api/entries.php?page=${page}`);
            const data = await res.json();
            if (!data.success) throw new Error(data.error);

            if (data.entries.length === 0) {
                container.innerHTML = '<p style="text-align:center;color:#999;padding:40px;">No entries yet. Add some health notes to get started!</p>';
                return;
            }

            container.innerHTML = data.entries.map(e => `
                <div class="entry-item" data-id="${e.id}">
                    <div class="entry-meta">
                        <span class="source">${e.source_type === 'pdf' ? 'PDF' : e.source_type === 'paste' ? 'Pasted' : 'File'}</span>
                        <span>${e.source_name || 'Direct Input'} &mdash; ${formatDate(e.created_at)}</span>
                        <button class="btn btn-danger" onclick="deleteEntry(${e.id})">Delete</button>
                    </div>
                    <div class="entry-preview">${escapeHtml(e.preview)}${e.content && e.content.length > 300 ? '...' : ''}</div>
                </div>
            `).join('');

            // Pagination
            const totalPages = Math.ceil(data.total / 20);
            if (totalPages > 1) {
                let pag = '<div style="text-align:center;margin-top:16px;">';
                if (page > 1) pag += `<button class="btn" onclick="loadEntries(${page - 1})" style="margin-right:8px">Previous</button>`;
                pag += `Page ${page} of ${totalPages}`;
                if (page < totalPages) pag += `<button class="btn" onclick="loadEntries(${page + 1})" style="margin-left:8px">Next</button>`;
                pag += '</div>';
                container.innerHTML += pag;
            }
        } catch (err) {
            container.innerHTML = `<p style="color:red;">Error loading entries: ${err.message}</p>`;
        }
    }

    window.loadEntries = loadEntries;

    window.deleteEntry = async function(id) {
        if (!confirm('Remove this entry from your journal?')) return;
        try {
            await fetch('api/entries.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });
            showToast('Entry removed');
            loadEntries(currentPage);
            loadStats();
        } catch (err) {
            showToast('Error: ' + err.message);
        }
    };

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    // --- STATS ---
    async function loadStats() {
        try {
            const res = await fetch('api/stats.php');
            const data = await res.json();
            if (!data.success) return;

            document.getElementById('statEntries').textContent = data.total_entries;
            document.getElementById('statChats').textContent = data.total_chats;

            if (data.first_entry) {
                const first = new Date(data.first_entry);
                const now = new Date();
                const days = Math.ceil((now - first) / (1000 * 60 * 60 * 24));
                document.getElementById('statDays').textContent = days;
            }
        } catch (err) { /* silent */ }
    }

    // --- TOAST ---
    function showToast(msg) {
        const toast = document.getElementById('toast');
        toast.textContent = msg;
        toast.classList.add('show');
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // --- SPEECH-TO-TEXT (Microphone) ---
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function setupMic(btnId, targetEl, isInput) {
        const btn = document.getElementById(btnId);
        if (!SpeechRecognition) {
            btn.title = 'Speech not supported in this browser';
            btn.style.opacity = '0.4';
            return;
        }

        let recognition = null;
        let isRecording = false;

        btn.addEventListener('click', () => {
            if (isRecording) {
                recognition.stop();
                return;
            }

            recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-US';

            let finalTranscript = '';
            const target = document.getElementById(targetEl);
            const startValue = isInput ? target.value : target.value;

            recognition.onstart = () => {
                isRecording = true;
                btn.classList.add('recording');
                btn.innerHTML = '&#9899; Stop';
            };

            recognition.onresult = (event) => {
                let interim = '';
                for (let i = event.resultIndex; i < event.results.length; i++) {
                    if (event.results[i].isFinal) {
                        finalTranscript += event.results[i][0].transcript;
                    } else {
                        interim += event.results[i][0].transcript;
                    }
                }
                if (isInput) {
                    target.value = startValue + finalTranscript + interim;
                } else {
                    target.value = startValue + (startValue ? '\n' : '') + finalTranscript + interim;
                }
            };

            recognition.onend = () => {
                isRecording = false;
                btn.classList.remove('recording');
                btn.innerHTML = '&#127908;' + (isInput ? '' : ' Speak');
                if (isInput) {
                    target.value = (startValue + finalTranscript).trim();
                } else {
                    target.value = (startValue + (startValue ? '\n' : '') + finalTranscript).trim();
                }
            };

            recognition.onerror = (e) => {
                console.error('Speech error:', e.error);
                isRecording = false;
                btn.classList.remove('recording');
                btn.innerHTML = '&#127908;' + (isInput ? '' : ' Speak');
                if (e.error === 'not-allowed') {
                    showToast('Please allow microphone access');
                }
            };

            recognition.start();
        });
    }

    setupMic('micBtn', 'pasteText', false);
    setupMic('chatMicBtn', 'chatInput', true);

    // --- AUTO-SAVE CHAT TO JOURNAL ---
    // Chat Q&A is already stored in chat_history table.
    // Also save each full exchange as a journal entry for AI context.
    function saveChatToJournal(question, answer) {
        const text = `Chat Q&A (${new Date().toLocaleString()}):\nQ: ${question}\nA: ${answer}`;
        const formData = new FormData();
        formData.append('text_content', text);
        fetch('api/upload.php', { method: 'POST', body: formData }).catch(() => {});
    }

    // Patch sendMessage to capture Q&A pairs
    const origFetch = window.fetch;
    const pendingQuestions = {};
    window.fetch = function(url, opts) {
        if (typeof url === 'string' && url.includes('api/chat.php') && opts && opts.body) {
            try {
                const body = JSON.parse(opts.body);
                if (body.message) {
                    return origFetch(url, opts).then(response => {
                        const clone = response.clone();
                        clone.json().then(data => {
                            if (data.success && data.reply) {
                                saveChatToJournal(body.message, data.reply);
                                loadStats();
                            }
                        }).catch(() => {});
                        return response;
                    });
                }
            } catch(e) {}
        }
        return origFetch(url, opts);
    };

    // Init
    loadStats();
});
