<?php
require_once __DIR__ . '/includes/auth.php';
requireAdmin();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhilCheck — Admin Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .dash-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 24px; }
        .stat-card { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); text-align: center; }
        .stat-card .stat-value { font-size: 36px; font-weight: 800; color: var(--primary); }
        .stat-card .stat-label { font-size: 14px; color: var(--text-light); margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .section { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); margin-bottom: 24px; }
        .section h2 { font-size: 20px; color: var(--primary-dark); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .section h2 .icon { font-size: 24px; }

        /* User table */
        .user-table { width: 100%; border-collapse: collapse; font-size: 16px; }
        .user-table th { text-align: left; padding: 10px 14px; border-bottom: 2px solid var(--border); color: var(--text-light); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; }
        .user-table td { padding: 12px 14px; border-bottom: 1px solid #EEE; }
        .user-table tr:hover td { background: #FAFFF8; }
        .role-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase; }
        .role-badge.admin { background: #E3F2FD; color: #1565C0; }
        .role-badge.user { background: #F3E5F5; color: #7B1FA2; }

        /* Profile cards */
        .profile-section { margin-bottom: 20px; }
        .profile-section h3 { font-size: 17px; color: var(--primary-dark); margin-bottom: 10px; display: flex; align-items: center; gap: 8px; }
        .profile-section h3 .picon { font-size: 20px; }
        .tag-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .tag { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 14px; }
        .tag.ailment { background: #FFEBEE; color: #C62828; }
        .tag.fear { background: #FFF3E0; color: #E65100; }
        .tag.interest { background: #E8F5E9; color: #2E7D32; }
        .tag.positive { background: #E3F2FD; color: #1565C0; }
        .tag.concern { background: #FCE4EC; color: #AD1457; }
        .tag.food-good { background: #E8F5E9; color: #2E7D32; }
        .tag.food-bad { background: #FFEBEE; color: #C62828; }
        .profile-text { font-size: 16px; line-height: 1.7; color: var(--text); }
        .profile-summary { font-size: 18px; line-height: 1.7; padding: 20px; background: linear-gradient(135deg, #E8F5E9, #F1F8E9); border-radius: 12px; border-left: 4px solid var(--primary); }
        .mood-indicator { font-size: 18px; font-weight: 600; padding: 8px 16px; border-radius: 8px; display: inline-block; }
        .mood-indicator.improving { background: #E8F5E9; color: #2E7D32; }
        .mood-indicator.stable { background: #E3F2FD; color: #1565C0; }
        .mood-indicator.declining { background: #FFEBEE; color: #C62828; }
        .mood-indicator.not_enough_data { background: #F5F5F5; color: #999; }
        .recommendation-list { list-style: none; padding: 0; }
        .recommendation-list li { padding: 10px 14px; margin-bottom: 8px; background: #FFFDE7; border-left: 3px solid #FFD54F; border-radius: 0 8px 8px 0; font-size: 15px; line-height: 1.5; }

        /* Recent items */
        .recent-item { padding: 12px 0; border-bottom: 1px solid #EEE; }
        .recent-item:last-child { border-bottom: none; }
        .recent-item .meta { font-size: 13px; color: var(--text-light); margin-bottom: 4px; }
        .recent-item .content { font-size: 15px; color: var(--text); }

        /* Loading */
        .loading-profile { text-align: center; padding: 60px; color: var(--text-light); }
        .loading-profile .spinner-lg { width: 40px; height: 40px; border: 4px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 16px; }
        .back-link { color: white; text-decoration: none; font-size: 15px; opacity: 0.9; }
        .back-link:hover { opacity: 1; text-decoration: underline; }
    </style>
</head>
<body>

<div class="header">
    <h1><span class="heart">&#9829;</span> PhilCheck <span style="font-size:16px;opacity:0.8;font-weight:400;">Admin Dashboard</span></h1>
    <div class="header-user">
        <a href="index.php" class="back-link">Back to App</a>
        <a href="logout.php" class="btn-logout">Sign Out</a>
    </div>
</div>

<div class="main">

    <!-- Stats Row -->
    <div class="dash-grid" id="statsGrid">
        <div class="stat-card"><div class="stat-value" id="dTotalEntries">-</div><div class="stat-label">Journal Entries</div></div>
        <div class="stat-card"><div class="stat-value" id="dTotalChats">-</div><div class="stat-label">Questions Asked</div></div>
        <div class="stat-card"><div class="stat-value" id="dTotalInsights">-</div><div class="stat-label">Insights Generated</div></div>
        <div class="stat-card"><div class="stat-value" id="dDaysTracking">-</div><div class="stat-label">Days Tracking</div></div>
    </div>

    <!-- Users -->
    <div class="section">
        <h2><span class="icon">&#128101;</span> Users</h2>
        <table class="user-table">
            <thead><tr><th>User</th><th>Role</th><th>Logins</th><th>Last Login</th><th>Joined</th></tr></thead>
            <tbody id="userTableBody"><tr><td colspan="5" style="text-align:center;color:#999;">Loading...</td></tr></tbody>
        </table>
    </div>

    <!-- Phil's Profile (AI-analyzed) -->
    <div class="section">
        <h2><span class="icon">&#129657;</span> Phil's Health Profile</h2>
        <p style="font-size:14px;color:var(--text-light);margin-bottom:16px;">AI-analyzed from all journal entries. Updated each time you visit this page.</p>
        <div id="profileContainer">
            <div class="loading-profile">
                <div class="spinner-lg"></div>
                <p>Analyzing Phil's journal entries...</p>
                <p style="font-size:14px;">This may take a moment</p>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="section">
            <h2><span class="icon">&#128221;</span> Recent Entries</h2>
            <div id="recentEntries"><p style="color:#999;">Loading...</p></div>
        </div>
        <div class="section">
            <h2><span class="icon">&#128172;</span> Recent Questions</h2>
            <div id="recentChats"><p style="color:#999;">Loading...</p></div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Load admin stats
    fetch('api/admin_stats.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            document.getElementById('dTotalEntries').textContent = data.total_entries;
            document.getElementById('dTotalChats').textContent = data.total_chats;
            document.getElementById('dTotalInsights').textContent = data.total_insights;

            if (data.first_entry) {
                const first = new Date(data.first_entry);
                const now = new Date();
                const days = Math.max(1, Math.ceil((now - first) / (1000 * 60 * 60 * 24)));
                document.getElementById('dDaysTracking').textContent = days;
            } else {
                document.getElementById('dDaysTracking').textContent = '0';
            }

            // Users table
            const tbody = document.getElementById('userTableBody');
            tbody.innerHTML = data.users.map(u => `
                <tr>
                    <td><strong>${esc(u.username)}</strong></td>
                    <td><span class="role-badge ${u.role}">${u.role}</span></td>
                    <td>${u.login_count}</td>
                    <td>${u.last_login ? formatDate(u.last_login) : 'Never'}</td>
                    <td>${formatDate(u.created_at)}</td>
                </tr>
            `).join('');

            // Recent entries
            const entriesDiv = document.getElementById('recentEntries');
            if (data.recent_entries.length === 0) {
                entriesDiv.innerHTML = '<p style="color:#999;text-align:center;">No entries yet</p>';
            } else {
                entriesDiv.innerHTML = data.recent_entries.map(e => `
                    <div class="recent-item">
                        <div class="meta">${formatDate(e.created_at)} &mdash; ${e.source_type}</div>
                        <div class="content">${esc(e.preview)}${e.preview.length >= 200 ? '...' : ''}</div>
                    </div>
                `).join('');
            }

            // Recent chats
            const chatsDiv = document.getElementById('recentChats');
            if (data.recent_chats.length === 0) {
                chatsDiv.innerHTML = '<p style="color:#999;text-align:center;">No questions yet</p>';
            } else {
                chatsDiv.innerHTML = data.recent_chats.map(c => `
                    <div class="recent-item">
                        <div class="meta">${formatDate(c.created_at)}</div>
                        <div class="content">"${esc(c.message)}"</div>
                    </div>
                `).join('');
            }
        })
        .catch(err => console.error('Stats error:', err));

    // Load AI profile
    fetch('api/admin_profile.php')
        .then(r => r.json())
        .then(data => {
            const container = document.getElementById('profileContainer');
            if (!data.success || !data.profile) {
                container.innerHTML = '<p style="text-align:center;color:#999;padding:40px;">Not enough journal entries yet to build a profile. Phil needs to add more health notes first.</p>';
                return;
            }

            const p = data.profile;
            let html = '';

            // Summary
            if (p.summary) {
                html += `<div class="profile-summary">${esc(p.summary)}</div>`;
            }

            // Mood
            html += '<div class="profile-section" style="margin-top:20px;">';
            html += '<h3><span class="picon">&#128578;</span> Overall Mood</h3>';
            if (p.overall_mood) html += `<p class="profile-text">${esc(p.overall_mood)}</p>`;
            if (p.mood_trend) html += `<div style="margin-top:8px;"><span class="mood-indicator ${p.mood_trend}">Trend: ${p.mood_trend.replace('_', ' ')}</span></div>`;
            html += '</div>';

            // Energy
            if (p.energy_level) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#9889;</span> Energy Patterns</h3>';
                html += `<p class="profile-text">${esc(p.energy_level)}</p>`;
                html += '</div>';
            }

            // Ailments
            if (p.ailments && p.ailments.length > 0) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#129658;</span> Health Complaints & Ailments</h3>';
                html += '<div class="tag-list">' + p.ailments.map(a => `<span class="tag ailment">${esc(a)}</span>`).join('') + '</div>';
                if (p.ailment_details) html += `<p class="profile-text" style="margin-top:12px;">${esc(p.ailment_details)}</p>`;
                html += '</div>';
            }

            // Fears & Concerns
            if (p.fears_concerns && p.fears_concerns.length > 0) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#128543;</span> Fears & Concerns</h3>';
                html += '<div class="tag-list">' + p.fears_concerns.map(f => `<span class="tag fear">${esc(f)}</span>`).join('') + '</div>';
                html += '</div>';
            }

            // Interests
            if (p.interests && p.interests.length > 0) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#11088;</span> Interests & Joys</h3>';
                html += '<div class="tag-list">' + p.interests.map(i => `<span class="tag interest">${esc(i)}</span>`).join('') + '</div>';
                html += '</div>';
            }

            // Food
            if (p.food_patterns) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#127858;</span> Food Patterns</h3>';
                if (p.food_patterns.eating_habits) html += `<p class="profile-text">${esc(p.food_patterns.eating_habits)}</p>`;
                if (p.food_patterns.foods_that_help && p.food_patterns.foods_that_help.length > 0) {
                    html += '<p style="margin-top:10px;font-weight:600;color:#2E7D32;">Foods that help:</p>';
                    html += '<div class="tag-list">' + p.food_patterns.foods_that_help.map(f => `<span class="tag food-good">${esc(f)}</span>`).join('') + '</div>';
                }
                if (p.food_patterns.foods_that_hurt && p.food_patterns.foods_that_hurt.length > 0) {
                    html += '<p style="margin-top:10px;font-weight:600;color:#C62828;">Foods that seem to cause issues:</p>';
                    html += '<div class="tag-list">' + p.food_patterns.foods_that_hurt.map(f => `<span class="tag food-bad">${esc(f)}</span>`).join('') + '</div>';
                }
                html += '</div>';
            }

            // Sleep
            if (p.sleep_patterns) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#128164;</span> Sleep Patterns</h3>';
                html += `<p class="profile-text">${esc(p.sleep_patterns)}</p>`;
                html += '</div>';
            }

            // Social
            if (p.social_connections) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#128106;</span> Social Connections</h3>';
                html += `<p class="profile-text">${esc(p.social_connections)}</p>`;
                html += '</div>';
            }

            // Positive signs
            if (p.positive_signs && p.positive_signs.length > 0) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#127775;</span> Positive Signs</h3>';
                html += '<div class="tag-list">' + p.positive_signs.map(s => `<span class="tag positive">${esc(s)}</span>`).join('') + '</div>';
                html += '</div>';
            }

            // Concerning signs
            if (p.concerning_signs && p.concerning_signs.length > 0) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#9888;&#65039;</span> Things to Watch</h3>';
                html += '<div class="tag-list">' + p.concerning_signs.map(s => `<span class="tag concern">${esc(s)}</span>`).join('') + '</div>';
                html += '</div>';
            }

            // Recommendations
            if (p.recommendations && p.recommendations.length > 0) {
                html += '<div class="profile-section">';
                html += '<h3><span class="picon">&#128161;</span> How You Can Help</h3>';
                html += '<ul class="recommendation-list">' + p.recommendations.map(r => `<li>${esc(r)}</li>`).join('') + '</ul>';
                html += '</div>';
            }

            container.innerHTML = html;
        })
        .catch(err => {
            document.getElementById('profileContainer').innerHTML = '<p style="color:red;text-align:center;">Error loading profile: ' + err.message + '</p>';
        });

    function formatDate(d) {
        return new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
    }

    function esc(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
});
</script>
</body>
</html>
