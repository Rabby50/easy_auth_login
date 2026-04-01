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

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-easy-auth-page-id="1"]').forEach(function (hiddenInput) {
      var row = hiddenInput.closest('td');
      if (row) {
        initPicker(row);
      }
    });
  });
})();
