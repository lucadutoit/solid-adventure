<?php
require_once __DIR__ . '/../php/config/database.php';
if (!isLoggedIn()) {
    header('Location: /swift-swap/pages/login.php');
    exit;
}
$title = 'Messages';
?>
<!DOCTYPE html>
<html lang="en">
<?php require_once __DIR__ . '/../includes/head.php'; ?>
<body class="page-wrap">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

  <main>
    <div class="container">
      <h1 style="font-size:1.5rem;font-weight:800;margin-bottom:1.25rem">Messages</h1>

      <div class="messages-layout">
        <div class="convo-list" id="convo-list">
          <div class="loading">Loading…</div>
        </div>

        <div class="chat-area" id="chat-area">
          <div style="flex:1;display:flex;align-items:center;justify-content:center;color:var(--gray-400)">
            <div class="text-center">
              <i class="fa-solid fa-comments" style="font-size:2.5rem;margin-bottom:0.75rem;display:block"></i>
              Select a conversation
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script src="/swift-swap/js/main.js"></script>
  <script>
    let activeConvo  = null;
    let pollInterval = null;

    async function loadConversations() {
      const list = document.getElementById('convo-list');
      try {
        const data = await API.get('/api/messages.php');
        if (!data.conversations?.length) {
          list.innerHTML = '<div class="empty-state" style="padding:2rem;text-align:center;color:var(--gray-400)">No messages yet</div>';
          return;
        }
        list.innerHTML = data.conversations.map(c => `
          <div class="convo-item" data-listing="${c.listing_id}" data-user="${c.other_user_id}" data-name="${escapeHtml(c.other_username)}">
            <div class="convo-name">
              ${escapeHtml(c.other_username)}
              ${c.unread_count > 0 ? `<span class="badge">${c.unread_count}</span>` : ''}
            </div>
            <div class="convo-last">${escapeHtml(c.listing_title)}</div>
            <div class="text-muted" style="font-size:0.75rem">${timeAgo(c.last_message_at)}</div>
          </div>`).join('');

        document.querySelectorAll('.convo-item').forEach(item => {
          item.addEventListener('click', () => openConversation(
            parseInt(item.dataset.listing),
            parseInt(item.dataset.user),
            item.dataset.name
          ));
        });
      } catch (err) {
        list.innerHTML = `<div class="alert alert-error">${escapeHtml(err.message)}</div>`;
      }
    }

    async function openConversation(listingId, otherUserId, otherName) {
      activeConvo = { listingId, otherUserId };
      clearInterval(pollInterval);

      document.querySelectorAll('.convo-item').forEach(i => {
        i.classList.toggle('active', parseInt(i.dataset.listing) === listingId && parseInt(i.dataset.user) === otherUserId);
      });

      const area = document.getElementById('chat-area');
      area.innerHTML = `
        <div style="padding:0.875rem;border-bottom:1px solid var(--gray-200);font-weight:600">
          ${escapeHtml(otherName)}
        </div>
        <div class="chat-messages" id="chat-messages"></div>
        <div class="chat-input-area">
          <input type="text" id="chat-input" placeholder="Type a message…" autocomplete="off">
          <button class="btn btn-primary" id="chat-send">Send</button>
        </div>`;

      await fetchMessages(listingId);

      document.getElementById('chat-send').addEventListener('click', () => sendMessage(listingId, otherUserId));
      document.getElementById('chat-input').addEventListener('keydown', e => {
        if (e.key === 'Enter') sendMessage(listingId, otherUserId);
      });

      pollInterval = setInterval(() => fetchMessages(listingId), 5000);
    }

    async function fetchMessages(listingId) {
      try {
        const data = await API.get('/api/messages.php', { listing_id: listingId });
        const box  = document.getElementById('chat-messages');
        if (!box) return;
        const wasBottom = box.scrollHeight - box.scrollTop <= box.clientHeight + 40;
        box.innerHTML = data.messages.map(m => `
          <div class="msg-bubble ${m.sender_id === Auth.userId ? 'mine' : 'theirs'}">
            ${escapeHtml(m.body)}
            <div style="font-size:0.7rem;opacity:0.65;margin-top:0.2rem">${timeAgo(m.created_at)}</div>
          </div>`).join('');
        if (wasBottom) box.scrollTop = box.scrollHeight;
      } catch { /* silent */ }
    }

    async function sendMessage(listingId, receiverId) {
      const input = document.getElementById('chat-input');
      const body  = input.value.trim();
      if (!body) return;
      input.value = '';
      try {
        await API.post('/api/messages.php', { listing_id: listingId, receiver_id: receiverId, body });
        await fetchMessages(listingId);
      } catch (err) {
        input.value = body;
        alert(err.message);
      }
    }

    loadConversations();
  </script>
</body>
</html>
