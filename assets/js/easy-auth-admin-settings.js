(function () {
  'use strict';

  var config = window.EasyAuthAdminData || {};

  function debounce(fn, wait) {
    var timer = null;
    return function () {
      var args = arguments;
      clearTimeout(timer);
      timer = setTimeout(function () {
        fn.apply(null, args);
      }, wait);
    };
  }

  function buildResultItem(page) {
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'button-link';
    button.style.display = 'block';
    button.style.padding = '4px 0';
    button.setAttribute('data-page-id', String(page.id));
    button.setAttribute('data-page-title', page.title || '');
    button.textContent = (page.title || ('#' + page.id)) + ' (' + page.id + ')';
    return button;
  }

  function renderResults(container, pages) {
    container.innerHTML = '';

    if (!pages.length) {
      var empty = document.createElement('p');
      empty.style.margin = '4px 0';
      empty.textContent = config.noResults || 'No pages found.';
      container.appendChild(empty);
      return;
    }

    pages.forEach(function (page) {
      container.appendChild(buildResultItem(page));
    });
  }

  async function searchPages(query) {
    var url = new URL(config.ajaxUrl || '', window.location.origin);
    url.searchParams.set('action', 'easy_auth_search_pages');
    url.searchParams.set('nonce', config.nonce || '');
    url.searchParams.set('q', query || '');

    var response = await fetch(url.toString(), {
      method: 'GET',
      credentials: 'same-origin'
    });

    var data = await response.json();
    if (!response.ok || !data || !data.success) {
      return [];
    }

    return Array.isArray(data.data) ? data.data : [];
  }

  async function sendTestEmail(recipientEmail) {
    var body = new URLSearchParams();
    body.set('action', 'easy_auth_send_test_email');
    body.set('nonce', config.testEmailNonce || '');
    body.set('recipient_email', recipientEmail || '');

    var response = await fetch(config.ajaxUrl || '', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: body.toString()
    });

    var data = await response.json().catch(function () {
      return {};
    });

    if (!response.ok || !data || !data.success) {
      throw new Error((data && data.data && data.data.message) || 'Unable to send test email.');
    }

    return data.data || {};
  }

  function initPicker(row) {
    var hidden = row.querySelector('[data-easy-auth-page-id="1"]');
    var input = row.querySelector('[data-easy-auth-page-search="1"]');
    var clear = row.querySelector('[data-easy-auth-page-clear="1"]');
    var results = row.querySelector('[data-easy-auth-page-results="1"]');

    if (!hidden || !input || !clear || !results) {
      return;
    }

    input.placeholder = config.searchPlaceholder || input.placeholder;
    clear.textContent = config.clearText || clear.textContent;

    var runSearch = debounce(async function () {
      var term = input.value.trim();
      if (term.length < 1) {
        results.innerHTML = '';
        return;
      }

      var pages = await searchPages(term);
      renderResults(results, pages);
    }, 250);

    input.addEventListener('input', runSearch);

    results.addEventListener('click', function (event) {
      var button = event.target.closest('[data-page-id]');
      if (!button) {
        return;
      }

      hidden.value = button.getAttribute('data-page-id') || '0';
      input.value = button.getAttribute('data-page-title') || '';
      results.innerHTML = '';
    });

    clear.addEventListener('click', function () {
      hidden.value = '0';
      input.value = '';
      results.innerHTML = '';
      input.focus();
    });
  }

  function syncMultiHidden(hidden, selectedWrap) {
    var ids = [];
    selectedWrap.querySelectorAll('[data-selected-page-id]').forEach(function (item) {
      var id = parseInt(item.getAttribute('data-selected-page-id') || '0', 10);
      if (id > 0) {
        ids.push(id);
      }
    });
    hidden.value = ids.join(',');
  }

  function createSelectedChip(page) {
    var chip = document.createElement('button');
    chip.type = 'button';
    chip.className = 'button';
    chip.style.margin = '0 6px 6px 0';
    chip.setAttribute('data-selected-page-id', String(page.id));
    chip.setAttribute('data-selected-page-title', page.title || '');
    chip.textContent = (page.title || ('#' + page.id)) + ' (' + page.id + ')';
    return chip;
  }

  function initMultiPicker(row) {
    var hidden = row.querySelector('[data-easy-auth-page-ids="1"]');
    var input = row.querySelector('[data-easy-auth-pages-search="1"]');
    var selectedWrap = row.querySelector('[data-easy-auth-pages-selected="1"]');
    var results = row.querySelector('[data-easy-auth-pages-results="1"]');

    if (!hidden || !input || !selectedWrap || !results) {
      return;
    }

    var runSearch = debounce(async function () {
      var term = input.value.trim();
      if (term.length < 1) {
        results.innerHTML = '';
        return;
      }

      var pages = await searchPages(term);
      results.innerHTML = '';

      if (!pages.length) {
        renderResults(results, []);
        return;
      }

      pages.forEach(function (page) {
        var alreadySelected = selectedWrap.querySelector('[data-selected-page-id="' + page.id + '"]');
        if (!alreadySelected) {
          results.appendChild(buildResultItem(page));
        }
      });
    }, 250);

    input.addEventListener('input', runSearch);

    results.addEventListener('click', function (event) {
      var button = event.target.closest('[data-page-id]');
      if (!button) {
        return;
      }

      var page = {
        id: parseInt(button.getAttribute('data-page-id') || '0', 10),
        title: button.getAttribute('data-page-title') || ''
      };

      if (page.id <= 0 || selectedWrap.querySelector('[data-selected-page-id="' + page.id + '"]')) {
        return;
      }

      selectedWrap.appendChild(createSelectedChip(page));
      syncMultiHidden(hidden, selectedWrap);
      input.value = '';
      results.innerHTML = '';
      input.focus();
    });

    selectedWrap.addEventListener('click', function (event) {
      var chip = event.target.closest('[data-selected-page-id]');
      if (!chip) {
        return;
      }

      chip.remove();
      syncMultiHidden(hidden, selectedWrap);
    });

    syncMultiHidden(hidden, selectedWrap);
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-easy-auth-page-id="1"]').forEach(function (hiddenInput) {
      var row = hiddenInput.closest('td');
      if (row) {
        initPicker(row);
      }
    });

    document.querySelectorAll('[data-easy-auth-page-ids="1"]').forEach(function (hiddenInput) {
      var row = hiddenInput.closest('td');
      if (row) {
        initMultiPicker(row);
      }
    });

    document.addEventListener('click', async function (event) {
      var button = event.target.closest('[data-easy-auth-send-test-email="1"]');
      if (!button) {
        return;
      }

      var row = button.closest('td');
      if (!row) {
        return;
      }

      var emailInput = row.querySelector('[data-easy-auth-test-email="1"]');
      var status = row.querySelector('[data-easy-auth-test-email-status="1"]');
      var recipient = emailInput ? (emailInput.value || '').trim() : '';

      if (!recipient || recipient.indexOf('@') === -1) {
        if (status) {
          status.textContent = 'Please enter a valid recipient email.';
          status.style.color = '#b91c1c';
        }
        return;
      }

      var originalText = button.textContent;
      button.disabled = true;
      button.textContent = config.sendingText || 'Sending...';

      try {
        var result = await sendTestEmail(recipient);
        if (status) {
          status.textContent = result.message || 'Test email sent successfully.';
          status.style.color = '#166534';
        }
      } catch (error) {
        if (status) {
          status.textContent = (error && error.message) ? error.message : 'Unable to send test email.';
          status.style.color = '#b91c1c';
        }
      } finally {
        button.disabled = false;
        button.textContent = config.sendTestText || originalText || 'Send Test Email';
      }
    });
  });
})();
