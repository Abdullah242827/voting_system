document.getElementById('registerForm')?.addEventListener('submit', function(event) {
    event.preventDefault();
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const role = document.getElementById('role').value;

    fetch('register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, role })
    })
    .then(response => response.text())
    .then(raw => {
        console.log("Raw response from PHP:", raw); // debug log
        const data = JSON.parse(raw);
        if (data.success) {
            alert('Registration successful! You can now login.');
            window.location.href = 'login.html';
        } else {
            alert('Registration failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error during registration: ' + error.message);
    });
});


// Login user
document.getElementById('loginForm')?.addEventListener('submit', function(event) {
    event.preventDefault();
    const username = document.getElementById('loginUsername').value;
    const password = document.getElementById('loginPassword').value;
    const role = document.getElementById('loginRole').value;

    fetch('login.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password, role })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.role === 'admin') {
                window.location.href = 'admin.html'; // ✅ Redirect to admin.html
            } else {
                window.location.href = 'voter.html'; // Or any other page for voters
            }
        } else {
            alert('Login failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error during login: ' + error.message);
    });
});


// Create a new poll
document.getElementById('pollForm')?.addEventListener('submit', function(event) {
    event.preventDefault();

    const pollTitle = document.getElementById('pollTitle').value.trim();
    const pollOptionsRaw = document.getElementById('pollOptions').value.trim();

    if (!pollTitle) {
        alert('Please enter a poll title.');
        return;
    }

    if (!pollOptionsRaw) {
        alert('Please enter at least one poll option.');
        return;
    }

    // Split options by comma and filter out empty ones
    const pollOptions = pollOptionsRaw.split(',')
        .map(option => option.trim())
        .filter(option => option.length > 0);

    if (pollOptions.length === 0) {
        alert('Please enter valid poll options.');
        return;
    }

    // Set end_date to 7 days from now
    const now = new Date();
    const endDateObj = new Date(now);
    endDateObj.setDate(now.getDate() + 7);
    const end_date = endDateObj.toISOString().slice(0, 19).replace('T', ' ');

    const description = "Default poll description";  // You can make a new input field for this

    // Prepare candidates array as required by backend
    const candidates = pollOptions.map(option => ({
        name: option,
        description: ""   // Empty description for each candidate; you can extend UI for this
    }));

    const payload = {
        title: pollTitle,
        description: description,
        start_date: "2000-01-01 00:00:00", // dummy, ignored by backend
        end_date: end_date,
        candidates: candidates
    };

    fetch('createPoll.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        credentials: 'include',  // Important: send cookies/session with request
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Poll created successfully!');
            // Optionally reset form
            document.getElementById('pollForm').reset();
        } else {
            alert('Failed to create poll: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating poll: ' + error.message);
    });
});


// Voting function
function vote(election_id, candidate_id) {
    fetchWithLoader('vote.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ election_id, candidate_id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Vote recorded successfully!', 'success');
            if (typeof location !== 'undefined' && location.pathname.endsWith('castVote.html')) {
                setTimeout(() => location.reload(), 1000);
            }
        } else {
            showMessage('Voting failed: ' + data.message, 'error');
            if (data.message && data.message.toLowerCase().includes('not logged in')) {
                window.location.href = 'login.html';
            }
        }
    })
    .catch(error => {
        showMessage('Error during voting: ' + error.message, 'error');
    });
}

// Loader spinner
function showLoader(show = true) {
    let loader = document.getElementById('globalLoader');
    if (!loader) {
        loader = document.createElement('div');
        loader.id = 'globalLoader';
        loader.style.position = 'fixed';
        loader.style.top = '0';
        loader.style.left = '0';
        loader.style.width = '100vw';
        loader.style.height = '100vh';
        loader.style.background = 'rgba(255,255,255,0.6)';
        loader.style.display = 'flex';
        loader.style.alignItems = 'center';
        loader.style.justifyContent = 'center';
        loader.style.zIndex = 10000;
        loader.innerHTML = '<div style="border:6px solid #f3f3f3;border-top:6px solid #2980b9;border-radius:50%;width:40px;height:40px;animation:spin 1s linear infinite;"></div>';
        document.body.appendChild(loader);
        const style = document.createElement('style');
        style.innerHTML = "@keyframes spin{0%{transform:rotate(0deg);}100%{transform:rotate(360deg);}}";
        document.head.appendChild(style);
    }
    loader.style.display = show ? 'flex' : 'none';
}

// Wrap fetch with loader for all AJAX calls
function fetchWithLoader(...args) {
    showLoader(true);
    return fetch(...args).finally(() => showLoader(false));
}

// Admin: Check votes for a user (on checkVotes.html)
document.getElementById('checkVotesButton')?.addEventListener('click', function() {
    const username = document.getElementById('checkUsername').value.trim();
    if (!username) {
        alert('Please enter a username.');
        return;
    }
    fetch('getVotesForUser.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify({ username })
    })
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('voteResults');
        if (!data.success) {
            container.innerHTML = `<p style="color:red;">${data.message}</p>`;
            return;
        }
        if (!data.votes || data.votes.length === 0) {
            container.innerHTML = "<p>No votes found for this user.</p>";
            return;
        }
        let html = "<h3>User Votes</h3><ul>";
        data.votes.forEach(vote => {
            html += `<li>
                <strong>Poll:</strong> ${vote.election.title} <br>
                <strong>Voted for:</strong> ${vote.candidate.name} <br>
                <strong>Date:</strong> ${vote.vote_time}
            </li>`;
        });
        html += "</ul>";
        container.innerHTML = html;
    });
});
