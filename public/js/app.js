document.addEventListener('submit', function (e) {
  var form = e.target;
  if (form.matches('[data-confirm]')) {
    if (!window.confirm(form.getAttribute('data-confirm'))) {
      e.preventDefault();
    }
  }
});

document.querySelectorAll('input[type="datetime-local"][data-min-now]').forEach(function (el) {
  if (!el.value) {
    var n = new Date();
    n.setMinutes(n.getMinutes() - n.getTimezoneOffset());
    el.min = n.toISOString().slice(0, 16);
  }
});

document.querySelectorAll('.booking-panel').forEach(function (form) {
  var pickup = form.querySelector('[name="pickup_datetime"]');
  var returnAt = form.querySelector('[name="return_datetime"]');
  if (!pickup || !returnAt) return;

  function syncReturnDate() {
    returnAt.min = pickup.value || pickup.min || '';
    if (pickup.value && returnAt.value && returnAt.value <= pickup.value) {
      returnAt.setCustomValidity('Choose a return time after pick-up.');
    } else {
      returnAt.setCustomValidity('');
    }
  }

  pickup.addEventListener('change', syncReturnDate);
  returnAt.addEventListener('change', syncReturnDate);
  form.addEventListener('submit', function (event) {
    syncReturnDate();
    if (!form.checkValidity()) {
      event.preventDefault();
      form.reportValidity();
    }
  });
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
