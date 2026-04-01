(function () {
  'use strict';

  var config = window.EasyAuthLoginData || {};

  function scorePassword(value) {
    var score = 0;
    if (!value) return score;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value)) score++;
    if (/[0-9]/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return score;
  }

  function strengthColor(score) {
    if (score <= 1) return '#dc2626';
    if (score <= 2) return '#f59e0b';
    return '#16a34a';
  }

  function setMessage(root, text, isError) {
    var msg = root.querySelector('[data-easy-auth-message="1"]');
    if (!msg) return;
    msg.textContent = text || '';
    msg.classList.toggle('is-error', !!isError);
    msg.classList.toggle('is-success', !isError && !!text);
  }

  function togglePassword(event) {
    var button = event.target.closest('[data-password-toggle="1"]');
    if (!button) return;

    var wrap = button.closest('.easy-auth-password-wrap');
    if (!wrap) return;

    var input = wrap.querySelector('input[type="password"], input[type="text"]');
    if (!input) return;

    if (input.type === 'password') {
      input.type = 'text';
      button.textContent = 'Hide';
    } else {
      input.type = 'password';
      button.textContent = 'Show';
    }
  }

  function switchTab(event) {
    var tab = event.target.closest('.easy-auth-tab');
    if (!tab) return;

    var root = tab.closest('[data-easy-auth-root="1"]');
    var target = tab.getAttribute('data-tab');
    if (!root || !target) return;

    root.querySelectorAll('.easy-auth-tab').forEach(function (item) {
      item.classList.toggle('is-active', item === tab);
    });

    root.querySelectorAll('.easy-auth-panel').forEach(function (panel) {
      panel.classList.toggle('is-active', panel.getAttribute('data-panel') === target);
    });

    setMessage(root, '', false);
  }

  function updateStrength(event) {
    var input = event.target.closest('[data-password-strength="1"]');
    if (!input) return;

    var panel = input.closest('.easy-auth-panel');
    if (!panel) return;

    var bar = panel.querySelector('[data-strength-bar="1"] span');
    if (!bar) return;

    var score = scorePassword(input.value);
    var percentage = Math.max(10, (score / 4) * 100);

    bar.style.width = percentage + '%';
    bar.style.backgroundColor = strengthColor(score);
  }

  function formToObject(form) {
    var out = {};
    new FormData(form).forEach(function (value, key) {
      out[key] = value;
    });
    return out;
  }

  async function postJson(endpoint, payload) {
    var response = await fetch((config.restUrl || '') + endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': config.nonce || ''
      },
      body: JSON.stringify(payload || {})
    });

    var data = await response.json().catch(function () {
      return {};
    });

    if (!response.ok) {
      var message = (data && data.message) || config.messages?.networkError || 'Request failed.';
      throw new Error(message);
    }

    return data;
  }

  async function submitForm(event) {
    var form = event.target.closest('.easy-auth-form');
    if (!form) return;

    event.preventDefault();

    var root = form.closest('[data-easy-auth-root="1"]');
    if (!root) return;

    var endpoint = form.getAttribute('data-endpoint') || '';
    var submit = form.querySelector('button[type="submit"]');
    if (submit) submit.disabled = true;

    try {
      var data = await postJson(endpoint, formToObject(form));
      setMessage(root, data.message || 'Success', false);
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    } catch (error) {
      setMessage(root, error.message || (config.messages && config.messages.networkError) || 'Request failed', true);
    } finally {
      if (submit) submit.disabled = false;
    }
  }

  async function logout(event) {
    var button = event.target.closest('[data-easy-auth-logout="1"]');
    if (!button) return;

    button.disabled = true;
    try {
      var data = await postJson('/logout', {});
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        window.location.reload();
      }
    } catch (error) {
      button.disabled = false;
    }
  }

  function applyThemeVars() {
    document.querySelectorAll('.easy-auth-shell, .easy-auth-authenticated').forEach(function (root) {
      if (config.buttonColor) {
        root.style.setProperty('--easy-auth-button', config.buttonColor);
      }
      if (config.buttonTextColor) {
        root.style.setProperty('--easy-auth-button-text', config.buttonTextColor);
      }
    });
  }

  document.addEventListener('click', function (event) {
    switchTab(event);
    togglePassword(event);
    logout(event);
  });

  document.addEventListener('input', updateStrength);
  document.addEventListener('submit', submitForm);
  document.addEventListener('DOMContentLoaded', applyThemeVars);
})();
