const burgerBtn = document.getElementById('burgerBtn');
const mainNav = document.getElementById('mainNav');

if (burgerBtn && mainNav) {
  burgerBtn.addEventListener('click', () => {
    mainNav.classList.toggle('open');
  });
}

const likeButtons = document.querySelectorAll('.like-btn[data-post-id]');

likeButtons.forEach((btn) => {
  const postId = btn.dataset.postId;
  const isAuthorized = btn.dataset.auth === '1';
  const csrfToken = btn.dataset.csrf || '';
  const countEl = btn.querySelector('span');

  if (btn.dataset.liked === '1') {
    btn.classList.add('liked');
  }

  btn.addEventListener('click', async () => {
    if (!isAuthorized || !postId) {
      return;
    }

    btn.disabled = true;
    try {
      const formData = new FormData();
      formData.append('post_id', postId);
      formData.append('csrf_token', csrfToken);

      const response = await fetch('toggle_like.php', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      const data = await response.json();
      if (!response.ok || !data.ok) {
        throw new Error(data.message || 'Не удалось обновить лайк.');
      }

      if (countEl) {
        countEl.textContent = String(data.likes_count || 0);
      }
      btn.dataset.liked = data.liked ? '1' : '0';
      btn.classList.toggle('liked', !!data.liked);
    } catch (error) {
      const fallbackMessage = 'Ошибка обновления лайка. Попробуйте снова.';
      window.alert(error instanceof Error && error.message ? error.message : fallbackMessage);
    } finally {
      btn.disabled = !isAuthorized;
    }
  });
});
