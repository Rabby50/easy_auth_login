(function () {
  'use strict';

  var config = window.EasyAuthLoginData || {};
  var otpResendCooldownSeconds = Number(config.otpResendCooldownSeconds || 30);

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

    syncSplitSideContent(root, target);

    setMessage(root, '', false);
  }

  function syncSplitSideContent(root, target) {
    if (!root) return;

    root.querySelectorAll('[data-easy-auth-side-for]').forEach(function (copy) {
      copy.classList.toggle('is-active', copy.getAttribute('data-easy-auth-side-for') === target);
    });
  }

  function initSplitSideContent() {
    document.querySelectorAll('[data-easy-auth-root="1"]').forEach(function (root) {
      var activeTab = root.querySelector('.easy-auth-tab.is-active');
      var tabName = activeTab ? activeTab.getAttribute('data-tab') : 'login';
      syncSplitSideContent(root, tabName || 'login');
    });
  }

  function toggleNeoTheme(event) {
    var toggle = event.target.closest('[data-easy-auth-theme-toggle="1"]');
    if (!toggle) return;

    var root = toggle.closest('[data-easy-auth-theme-root="1"]');
    if (!root) return;

    var isLight = root.classList.toggle('is-light');
    root.classList.toggle('is-dark', !isLight);
    toggle.setAttribute('aria-pressed', isLight ? 'true' : 'false');
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
      handleForgotOtpFlow(form, data);
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

  function handleForgotOtpFlow(form, data) {
    if (!form || !form.matches('[data-easy-auth-forgot="1"]')) {
      return;
    }

    if (!data || !data.awaiting_otp || !data.otp_token) {
      return;
    }

    var otpRow = form.querySelector('[data-easy-auth-otp-row="1"]');
    var otpToken = form.querySelector('[data-easy-auth-otp-token="1"]');
    var submit = form.querySelector('[data-easy-auth-forgot-submit="1"], button[type="submit"]');
    var cooldownWrap = form.querySelector('[data-easy-auth-otp-cooldown-wrap="1"]');
    var expiryWrap = form.querySelector('[data-easy-auth-otp-expiry="1"]');

    if (otpRow) {
      otpRow.hidden = false;
      var otpInput = otpRow.querySelector('input[name="otp_code"]');
      if (otpInput) {
        otpInput.required = true;
      }
    }

    if (otpToken && data.otp_token) {
      otpToken.value = data.otp_token;
    }

    form.setAttribute('data-endpoint', '/forgot-password/verify-otp');

    if (submit) {
      submit.textContent = 'Verify OTP';
    }

    if (cooldownWrap) {
      cooldownWrap.hidden = false;
      startOtpCooldown(form, data.cooldown_seconds || otpResendCooldownSeconds);
    }

    if (expiryWrap) {
      expiryWrap.hidden = false;
      startOtpExpiryCountdown(form, data.expires_in || 45);
    }
  }

  function startOtpExpiryCountdown(form, seconds) {
    if (!form) return;

    var expiryWrap = form.querySelector('[data-easy-auth-otp-expiry="1"]');
    var otpInput = form.querySelector('input[name="otp_code"]');
    var submit = form.querySelector('[data-easy-auth-forgot-submit="1"], button[type="submit"]');
    var total = Math.max(1, Number(seconds || 45));

    if (!expiryWrap) {
      return;
    }

    if (form._easyAuthOtpExpiryTimer) {
      clearInterval(form._easyAuthOtpExpiryTimer);
      form._easyAuthOtpExpiryTimer = null;
    }

    expiryWrap.textContent = 'OTP expires in ' + total + 's';

    form._easyAuthOtpExpiryTimer = setInterval(function () {
      total -= 1;
      if (total <= 0) {
        clearInterval(form._easyAuthOtpExpiryTimer);
        form._easyAuthOtpExpiryTimer = null;
        expiryWrap.textContent = 'OTP expired. Request a new OTP.';
        if (otpInput) {
          otpInput.disabled = true;
        }
        if (submit) {
          submit.disabled = true;
        }
        return;
      }

      expiryWrap.textContent = 'OTP expires in ' + total + 's';
    }, 1000);
  }

  function startOtpCooldown(form, seconds) {
    if (!form) return;

    var resendButton = form.querySelector('[data-easy-auth-otp-resend="1"]');
    var countdown = form.querySelector('[data-easy-auth-otp-countdown="1"]');
    var total = Math.max(1, Number(seconds || 30));

    if (!resendButton || !countdown) {
      return;
    }

    if (form._easyAuthOtpTimer) {
      clearInterval(form._easyAuthOtpTimer);
      form._easyAuthOtpTimer = null;
    }

    resendButton.disabled = true;
    countdown.textContent = 'Resend OTP in ' + total + 's';

    form._easyAuthOtpTimer = setInterval(function () {
      total -= 1;
      if (total <= 0) {
        clearInterval(form._easyAuthOtpTimer);
        form._easyAuthOtpTimer = null;
        resendButton.disabled = false;
        countdown.textContent = 'You can resend OTP now.';
        return;
      }

      countdown.textContent = 'Resend OTP in ' + total + 's';
    }, 1000);
  }

  async function resendForgotOtp(event) {
    var button = event.target.closest('[data-easy-auth-otp-resend="1"]');
    if (!button || button.disabled) {
      return;
    }

    var form = button.closest('[data-easy-auth-forgot="1"]');
    if (!form) {
      return;
    }

    var root = form.closest('[data-easy-auth-root="1"]');
    var userLoginInput = form.querySelector('input[name="user_login"]');
    var userLogin = userLoginInput ? (userLoginInput.value || '').trim() : '';

    if (!userLogin) {
      if (root) {
        setMessage(root, 'Enter your email or username first.', true);
      }
      return;
    }

    button.disabled = true;
    try {
      var data = await postJson('/forgot-password/request-otp', { user_login: userLogin });
      var otpInput = form.querySelector('input[name="otp_code"]');
      var submit = form.querySelector('[data-easy-auth-forgot-submit="1"], button[type="submit"]');
      if (otpInput) {
        otpInput.disabled = false;
        otpInput.value = '';
      }
      if (submit) {
        submit.disabled = false;
      }
      handleForgotOtpFlow(form, data);
      if (root) {
        setMessage(root, data.message || 'OTP resent.', false);
      }
    } catch (error) {
      button.disabled = false;
      if (root) {
        setMessage(root, error.message || 'Unable to resend OTP.', true);
      }
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

  async function handleOAuthClick(event) {
    var button = event.target.closest('[data-easy-auth-oauth]');
    if (!button) return;

    var provider = button.getAttribute('data-easy-auth-oauth');
    if (!provider) return;

    button.disabled = true;
    button.textContent = 'Loading...';

    try {
      var data = await postJson('/oauth/authorize', { provider: provider });
      if (data.auth_url) {
        window.location.href = data.auth_url;
      } else {
        throw new Error('No authorization URL returned');
      }
    } catch (error) {
      button.disabled = false;
      button.textContent = 'Continue with ' + (provider === 'google' ? 'Google' : 'GitHub');
      alert(error.message || 'Failed to initiate OAuth flow');
    }
  }

  document.addEventListener('click', function (event) {
    switchTab(event);
    togglePassword(event);
    toggleNeoTheme(event);
    resendForgotOtp(event);
    logout(event);
    handleOAuthClick(event);
  });

  document.addEventListener('input', updateStrength);
  document.addEventListener('submit', submitForm);
  document.addEventListener('DOMContentLoaded', function () {
    applyThemeVars();
    initSplitSideContent();
  });
})();
