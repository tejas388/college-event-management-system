document.addEventListener('click', async (e) => {
  const btn = e.target.closest('[data-action]');
  if (!btn) return;
  const action = btn.dataset.action;
  const eventId = btn.dataset.eventId;
  if (!eventId) return;

  const form = new FormData();
  form.append('event_id', eventId);

  const res = await fetch(`api/event.php?action=${action}`, { method: 'POST', body: form });
  if (res.ok) {
    location.reload();
  } else {
    alert('Action failed');
  }
});
