/* ZeeBroast — shared front-end behaviour (AJAX cart) */
(function () {
  const BASE = window.ZB_BASE_URL || '';

  function updateCartBadge(count) {
    document.querySelectorAll('.icon-badge').forEach(el => el.remove());
    if (count > 0) {
      document.querySelectorAll('a[title="Cart"]').forEach(a => {
        const span = document.createElement('span');
        span.className = 'icon-badge';
        span.textContent = count;
        a.appendChild(span);
      });
    }
  }

  function toast(msg) {
    let t = document.getElementById('zb-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'zb-toast';
      t.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#e31c25;color:#fff;padding:12px 22px;border-radius:8px;font-size:13.5px;font-weight:600;z-index:999;box-shadow:0 8px 24px rgba(0,0,0,.4);opacity:0;transition:opacity .25s ease;';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.opacity = '1';
    clearTimeout(t._timer);
    t._timer = setTimeout(() => { t.style.opacity = '0'; }, 1800);
  }

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-add-cart');
    if (!btn) return;
    e.preventDefault();
    const type = btn.dataset.type;
    const id = btn.dataset.id;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Adding…';

    fetch(BASE + '/api/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add&type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id) + '&qty=1'
    })
      .then(r => r.json())
      .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.ok) {
          updateCartBadge(data.count);
          toast((data.name || 'Item') + ' added to cart');
        } else {
          toast(data.message || 'Could not add item');
        }
      })
      .catch(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        toast('Network error, please try again');
      });
  });

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-qty');
    if (!btn) return;
    e.preventDefault();
    const key = btn.dataset.key;
    const delta = parseInt(btn.dataset.delta, 10);
    const qtyEl = document.querySelector('.js-qty-value[data-key="' + key + '"]');
    let qty = parseInt(qtyEl.textContent, 10) + delta;
    if (qty < 0) qty = 0;

    fetch(BASE + '/api/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=update&key=' + encodeURIComponent(key) + '&qty=' + qty
    })
      .then(r => r.json())
      .then(() => window.location.reload());
  });

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.js-remove');
    if (!btn) return;
    e.preventDefault();
    fetch(BASE + '/api/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=remove&key=' + encodeURIComponent(btn.dataset.key)
    })
      .then(r => r.json())
      .then(() => window.location.reload());
  });
})();
