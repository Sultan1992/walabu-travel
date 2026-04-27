document.addEventListener('DOMContentLoaded', () => {
  const preferencesPanel = document.querySelector('[data-preferences-panel]');
  const preferencesTrigger = document.querySelector('[data-currency-trigger]');
  const authPanel = document.querySelector('[data-auth-panel]');
  const authTrigger = document.querySelector('[data-auth-trigger]');
  const authLabel = document.querySelector('[data-auth-label]');
  const authStorageKey = 'walabuTravelAccount';
  const authRedirectStorageKey = 'walabuTravelPostAuthRedirect';
  const authOpenTriggers = document.querySelectorAll('[data-auth-open]');
  const signedOutSections = document.querySelectorAll('[data-auth-required-signedout]');
  const signedInSections = document.querySelectorAll('[data-auth-required-signedin]');
  const accountNameTargets = document.querySelectorAll('[data-auth-account-name]');
  const accountEmailTargets = document.querySelectorAll('[data-auth-account-email]');

  const syncAuthDependentUi = () => {
    const account = readAccount();
    const isSignedIn = !!(account && account.name);

    signedOutSections.forEach((section) => {
      section.classList.toggle('is-hidden', isSignedIn);
    });

    signedInSections.forEach((section) => {
      section.classList.toggle('is-hidden', !isSignedIn);
    });

    accountNameTargets.forEach((node) => {
      node.textContent = isSignedIn ? account.name : 'Traveler';
    });

    accountEmailTargets.forEach((node) => {
      node.textContent = isSignedIn ? (account.email || '') : '';
    });
  };

  const readAccount = () => {
    try {
      return JSON.parse(window.localStorage.getItem(authStorageKey) || 'null');
    } catch (error) {
      return null;
    }
  };

  const writeAccount = (account) => {
    window.localStorage.setItem(authStorageKey, JSON.stringify(account));
  };

  const clearAccount = () => {
    window.localStorage.removeItem(authStorageKey);
  };

  const readPendingRedirect = () => {
    try {
      return window.sessionStorage.getItem(authRedirectStorageKey) || '';
    } catch (error) {
      return '';
    }
  };

  const writePendingRedirect = (url) => {
    try {
      window.sessionStorage.setItem(authRedirectStorageKey, url);
    } catch (error) {
      return;
    }
  };

  const clearPendingRedirect = () => {
    try {
      window.sessionStorage.removeItem(authRedirectStorageKey);
    } catch (error) {
      return;
    }
  };

  const syncAccountLabel = () => {
    if (!authLabel) {
      return;
    }

    const account = readAccount();
    authLabel.textContent = account && account.name ? account.name : 'Sign in';
  };

  syncAccountLabel();
  syncAuthDependentUi();

  if (preferencesPanel && preferencesTrigger) {
    const closeButtons = preferencesPanel.querySelectorAll('[data-preferences-close]');
    const currencyValue = preferencesTrigger.querySelector('.site-currency__value');
    const regionFlag = preferencesPanel.querySelector('[data-region-flag]');
    const selectRoots = preferencesPanel.querySelectorAll('[data-select-root]');

    const closeAllSelects = () => {
      selectRoots.forEach((root) => {
        const trigger = root.querySelector('[data-select-trigger]');
        const menu = root.querySelector('[data-select-menu]');

        if (trigger && menu) {
          trigger.setAttribute('aria-expanded', 'false');
          menu.classList.add('is-hidden');
        }
      });
    };

    const openPreferences = () => {
      preferencesPanel.classList.remove('is-hidden');
      preferencesTrigger.setAttribute('aria-expanded', 'true');
      document.body.classList.add('has-overlay-open');
      closeAllSelects();
    };

    const closePreferences = () => {
      preferencesPanel.classList.add('is-hidden');
      preferencesTrigger.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('has-overlay-open');
      closeAllSelects();
    };

    preferencesTrigger.addEventListener('click', () => {
      if (preferencesPanel.classList.contains('is-hidden')) {
        openPreferences();
      } else {
        closePreferences();
      }
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', closePreferences);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !preferencesPanel.classList.contains('is-hidden')) {
        closePreferences();
      }
    });

    selectRoots.forEach((root) => {
      const trigger = root.querySelector('[data-select-trigger]');
      const menu = root.querySelector('[data-select-menu]');
      const text = root.querySelector('[data-select-text]');

      if (!trigger || !menu || !text) {
        return;
      }

      trigger.addEventListener('click', (event) => {
        event.stopPropagation();
        const isOpen = !menu.classList.contains('is-hidden');
        closeAllSelects();

        if (!isOpen) {
          menu.classList.remove('is-hidden');
          trigger.setAttribute('aria-expanded', 'true');
        }
      });

      menu.querySelectorAll('[data-value]').forEach((item) => {
        item.addEventListener('click', () => {
          text.textContent = item.getAttribute('data-label') || '';

          menu.querySelectorAll('[data-value]').forEach((option) => {
            option.classList.remove('is-selected');
          });
          item.classList.add('is-selected');

          if (item.dataset.currency && currencyValue) {
            currencyValue.textContent = item.dataset.currency;
          }

          if (item.dataset.flag && regionFlag) {
            regionFlag.textContent = item.dataset.flag;
          }

          closeAllSelects();
        });
      });
    });

    document.addEventListener('click', (event) => {
      if (!preferencesPanel.classList.contains('is-hidden') && !preferencesPanel.contains(event.target) && !preferencesTrigger.contains(event.target)) {
        closePreferences();
        return;
      }

      if (!preferencesPanel.contains(event.target)) {
        closeAllSelects();
      }
    });
  }

  if (authPanel && authTrigger) {
    const closeButtons = authPanel.querySelectorAll('[data-auth-close]');
    const providerButtons = authPanel.querySelectorAll('[data-auth-provider]');
    const screens = authPanel.querySelectorAll('[data-auth-screen]');
    const providerLabel = authPanel.querySelector('[data-auth-provider-label]');
    const authForm = authPanel.querySelector('[data-auth-form]');
    const authName = authPanel.querySelector('[data-auth-name]');
    const authEmail = authPanel.querySelector('[data-auth-email]');
    const authBack = authPanel.querySelector('[data-auth-back]');
    const authSignout = authPanel.querySelector('[data-auth-signout]');
    let currentProvider = 'Email';

    const setScreen = (screenName) => {
      screens.forEach((screen) => {
        screen.classList.toggle('is-hidden', screen.getAttribute('data-auth-screen') !== screenName);
      });
    };

    const populateProfile = () => {
      const account = readAccount();

      if (!account) {
        return;
      }

      if (authName) {
        authName.textContent = account.name || 'Traveler';
      }

      if (authEmail) {
        authEmail.textContent = account.email || '';
      }
    };

    const openAuth = () => {
      const account = readAccount();
      authPanel.classList.remove('is-hidden');
      authTrigger.setAttribute('aria-expanded', 'true');
      document.body.classList.add('has-overlay-open');

      if (account) {
        populateProfile();
        setScreen('profile');
      } else {
        setScreen('providers');
      }
    };

    const closeAuth = () => {
      authPanel.classList.add('is-hidden');
      authTrigger.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('has-overlay-open');
      clearPendingRedirect();
      setScreen('providers');

      if (authForm) {
        authForm.reset();
      }
    };

    authTrigger.addEventListener('click', () => {
      clearPendingRedirect();
      openAuth();
    });
    authOpenTriggers.forEach((button) => {
      button.addEventListener('click', () => {
        clearPendingRedirect();
        openAuth();
      });
    });

    document.querySelectorAll('[data-auth-gated-link="my-trips"]').forEach((link) => {
      link.addEventListener('click', (event) => {
        const account = readAccount();

        if (account && account.name) {
          return;
        }

        event.preventDefault();
        const redirectUrl = link.getAttribute('data-auth-redirect') || link.getAttribute('href') || '';

        clearPendingRedirect();

        if (redirectUrl) {
          writePendingRedirect(redirectUrl);
        }

        openAuth();
      });
    });

    closeButtons.forEach((button) => {
      button.addEventListener('click', closeAuth);
    });

    providerButtons.forEach((button) => {
      button.addEventListener('click', () => {
        currentProvider = button.getAttribute('data-auth-provider') || 'Email';

        if (providerLabel) {
          providerLabel.textContent = currentProvider;
        }

        if (authForm) {
          const nameInput = authForm.querySelector('input[name="display_name"]');
          const emailInput = authForm.querySelector('input[name="email"]');

          if (nameInput) {
            nameInput.value = '';
          }

          if (emailInput) {
            emailInput.value = '';
            if (currentProvider === 'Apple') {
              emailInput.placeholder = 'your-apple-email@example.com';
            } else if (currentProvider === 'Google') {
              emailInput.placeholder = 'your-google-email@example.com';
            } else {
              emailInput.placeholder = 'you@example.com';
            }
          }
        }

        setScreen('form');
      });
    });

    if (authBack) {
      authBack.addEventListener('click', () => {
        setScreen('providers');
      });
    }

    if (authForm) {
      authForm.addEventListener('submit', (event) => {
        event.preventDefault();
        const formData = new FormData(authForm);
        const displayName = (formData.get('display_name') || '').toString().trim();
        const email = (formData.get('email') || '').toString().trim();

        if (!displayName || !email) {
          return;
        }

        writeAccount({
          provider: currentProvider,
          name: displayName,
          email,
        });

        syncAccountLabel();
        syncAuthDependentUi();
        populateProfile();

        const pendingRedirect = readPendingRedirect();
        if (pendingRedirect) {
          closeAuth();
          window.location.assign(pendingRedirect);
          return;
        }

        setScreen('profile');
      });
    }

    if (authSignout) {
      authSignout.addEventListener('click', () => {
        clearAccount();
        syncAccountLabel();
        syncAuthDependentUi();
        setScreen('providers');
      });
    }

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !authPanel.classList.contains('is-hidden')) {
        closeAuth();
      }
    });

    document.addEventListener('click', (event) => {
      if (!authPanel.classList.contains('is-hidden') && !authPanel.contains(event.target) && !authTrigger.contains(event.target)) {
        closeAuth();
      }
    });
  }

  document.querySelectorAll('[data-auth-signout-inline]').forEach((button) => {
    button.addEventListener('click', () => {
      clearAccount();
      clearPendingRedirect();
      syncAccountLabel();
      syncAuthDependentUi();
    });
  });

  document.querySelectorAll('[data-trip-tab]').forEach((button) => {
    button.addEventListener('click', () => {
      const tabName = button.getAttribute('data-trip-tab');

      document.querySelectorAll('[data-trip-tab]').forEach((tab) => {
        const active = tab === button;
        tab.classList.toggle('is-active', active);
        tab.setAttribute('aria-selected', active ? 'true' : 'false');
      });

      document.querySelectorAll('[data-trip-panel]').forEach((panel) => {
        panel.classList.toggle('is-hidden', panel.getAttribute('data-trip-panel') !== tabName);
      });
    });
  });

  document.querySelectorAll('.js-booking-form').forEach((form) => {
    const tripTypeInput = form.querySelector('input[name="trip_type"]');
    const returnField = form.querySelector('[data-return-field]');
    const departureInput = form.querySelector('input[name="departure_date"]');
    const returnInput = form.querySelector('input[name="return_date"]');
    const originInput = form.querySelector('input[name="origin"]');
    const destinationInput = form.querySelector('input[name="destination"]');
    const swapButton = form.querySelector('[data-swap-route]');

    const syncTripType = (value) => {
      if (tripTypeInput) {
        tripTypeInput.value = value;
      }

      form.querySelectorAll('[data-trip-type]').forEach((button) => {
        const active = button.getAttribute('data-trip-type') === value;
        button.classList.toggle('is-active', active);
        button.setAttribute('aria-pressed', active ? 'true' : 'false');
      });

      if (returnField) {
        returnField.classList.toggle('is-hidden', value === 'one_way');
      }

      if (returnInput && value === 'one_way') {
        returnInput.value = '';
      }
    };

    form.querySelectorAll('[data-trip-type]').forEach((button) => {
      button.addEventListener('click', () => {
        syncTripType(button.getAttribute('data-trip-type'));
      });
    });

    if (swapButton && originInput && destinationInput) {
      swapButton.addEventListener('click', () => {
        const currentOrigin = originInput.value;
        originInput.value = destinationInput.value;
        destinationInput.value = currentOrigin;
      });
    }

    if (departureInput && returnInput) {
      departureInput.addEventListener('change', () => {
        returnInput.min = departureInput.value;

        if (returnInput.value && returnInput.value < departureInput.value) {
          returnInput.value = departureInput.value;
        }
      });
    }

    syncTripType(tripTypeInput ? tripTypeInput.value : 'round_trip');
  });
});
