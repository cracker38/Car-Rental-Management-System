document.addEventListener('submit', function (e) {
  var form = e.target;
  if (form.matches('[data-confirm]')) {
    if (!window.confirm(form.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  }
});

document.querySelectorAll('[data-sidebar-toggle]').forEach(function (button) {
  button.addEventListener('click', function () {
    var shell = button.closest('.app-shell');
    if (!shell) return;
    var collapsed = shell.classList.toggle('sidebar-collapsed');
    button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
    button.setAttribute('aria-label', collapsed ? 'Expand navigation' : 'Collapse navigation');
  });
});

var homeNav = document.querySelector('.home-nav');
if (homeNav) {
  var updateHomeNav = function () {
    homeNav.classList.toggle('is-scrolled', window.scrollY > 24);
  };
  window.addEventListener('scroll', updateHomeNav, { passive: true });
  window.addEventListener('pageshow', updateHomeNav);
  updateHomeNav();
}

document.querySelectorAll('input[type="datetime-local"][data-min-now]').forEach(function (el) {
  if (!el.value) {
    var n = new Date();
    n.setMinutes(n.getMinutes() - n.getTimezoneOffset());
    el.min = n.toISOString().slice(0, 16);
  }
});

document.querySelectorAll('.booking-panel, .hire-search-form, .reservation-form').forEach(function (form) {
  var pickup = form.querySelector('[name="pickup_datetime"]');
  var returnAt = form.querySelector('[name="return_datetime"]');
  if (!pickup || !returnAt) return;
  var minimumHours = Math.max(1, parseInt(form.dataset.minRentalHours || '8', 10) || 8);
  var minimumDurationMs = minimumHours * 60 * 60 * 1000;

  function dateTimeValueAfter(value, milliseconds) {
    return new Date(new Date(value + 'Z').getTime() + milliseconds).toISOString().slice(0, 16);
  }

  function syncReturnDate() {
    if (pickup.value) {
      returnAt.min = dateTimeValueAfter(pickup.value, minimumDurationMs);
    } else {
      returnAt.min = pickup.min || '';
    }

    var duration = pickup.value && returnAt.value
      ? new Date(returnAt.value + 'Z').getTime() - new Date(pickup.value + 'Z').getTime()
      : null;
    returnAt.setCustomValidity(duration !== null && duration < minimumDurationMs
      ? 'The minimum booking duration is ' + minimumHours + ' hours.'
      : '');
  }

  pickup.addEventListener('input', syncReturnDate);
  pickup.addEventListener('change', syncReturnDate);
  returnAt.addEventListener('input', syncReturnDate);
  returnAt.addEventListener('change', syncReturnDate);
  form.addEventListener('submit', function (event) {
    syncReturnDate();
    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
    }
  });
  syncReturnDate();
});

document.querySelectorAll('[data-dismiss-chat]').forEach(function (button) {
  button.addEventListener('click', function () {
    var prompt = button.closest('.chat-prompt');
    if (prompt) prompt.classList.add('is-dismissed');
  });
});

document.querySelectorAll('.home-nav .navbar-collapse .nav-link[href^="#"]').forEach(function (link) {
  link.addEventListener('click', function () {
    var menu = link.closest('.navbar-collapse');
    if (menu && menu.classList.contains('show') && window.bootstrap) {
      window.bootstrap.Collapse.getOrCreateInstance(menu).hide();
    }
  });
});

document.querySelectorAll('[data-payment-poll]').forEach(function (el) {
  var pollUrl = el.getAttribute('data-payment-poll');
  if (!pollUrl) return;
  var tries = 0;
  var timer = setInterval(function () {
    tries += 1;
    fetch(pollUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (data && data.ledger_status && data.ledger_status !== 'pending') {
          clearInterval(timer);
          window.location.reload();
        }
      })
      .catch(function () {});
    if (tries >= 40) clearInterval(timer);
  }, 3000);
});

document.querySelectorAll('input[type="range"][data-range-label]').forEach(function (el) {
  var output = document.getElementById(el.id + '-output');
  if (!output) return;
  function updateRangeOutput() {
    var value = Number(el.value);
    var max = Number(el.max);
    output.textContent = 'RWF ' + value.toLocaleString() + (value >= max ? '+' : '');
  }
  el.addEventListener('input', updateRangeOutput);
  updateRangeOutput();
});

document.querySelectorAll('[data-submit-on-change]').forEach(function (el) {
  el.addEventListener('change', function () {
    if (el.form && typeof el.form.requestSubmit === 'function') el.form.requestSubmit();
  });
});
