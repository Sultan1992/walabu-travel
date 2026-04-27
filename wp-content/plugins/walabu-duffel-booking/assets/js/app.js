document.addEventListener("DOMContentLoaded", () => {
  const config = window.walabuDuffelBooking || {};
  const ajaxUrl = config.ajaxUrl || "";
  const action = config.suggestionsAction || "";
  const nonce = config.suggestionsNonce || "";
  const nearbyAction = config.nearbyAction || "";
  const nearbyNonce = config.nearbyNonce || "";
  const minChars = Number(config.minChars || 2);
  const nearbyRadius = Number(config.nearbyRadius || 100000);
  const strings = config.strings || {};
  const forms = document.querySelectorAll(".booking-widget__form");
  const filterForms = document.querySelectorAll(".booking-results-filters__form");

  const debounce = (fn, delay) => {
    let timer = null;

    return (...args) => {
      window.clearTimeout(timer);
      timer = window.setTimeout(() => fn(...args), delay);
    };
  };

  const escapeHtml = (value) =>
    String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");

  const normalizePlaceText = (value) =>
    String(value || "")
      .toLowerCase()
      .replace(/\s+/g, " ")
      .trim();

  const updateUrlParameter = (name, value) => {
    const url = new URL(window.location.href);

    if (value === null || value === undefined || value === "") {
      url.searchParams.delete(name);
    } else {
      url.searchParams.set(name, value);
    }

    return url;
  };

  forms.forEach((form) => {
    const originLabelInput = form.querySelector('[data-place-input="origin"]');
    const originCodeInput = form.querySelector('[data-place-code="origin"]');
    const originSuggestions = form.querySelector('[data-suggestions="origin"]');
    const originNearbyButton = form.querySelector('[data-nearby-button="origin"]');
    const destinationLabelInput = form.querySelector('[data-place-input="destination"]');
    const destinationCodeInput = form.querySelector('[data-place-code="destination"]');
    const destinationSuggestions = form.querySelector('[data-suggestions="destination"]');
    const destinationNearbyButton = form.querySelector('[data-nearby-button="destination"]');
    const swapButton = form.querySelector(".booking-widget__swap");
    const tripTypeInput = form.querySelector("[data-trip-type-input]");
    const tripButtons = Array.from(form.querySelectorAll("[data-trip-type]"));
    const departureDateInput = form.querySelector('input[name="departure_date"]');
    const returnDateInput = form.querySelector("[data-return-date-input]");
    const returnLabel = form.querySelector("[data-return-label]");
    const returnWrap = form.querySelector("[data-return-wrap]");
    const returnDivider = form.querySelector("[data-return-divider]");
    const autocompleteState = new WeakMap();
    const requestState = new WeakMap();
    const selectedPlaceState = {
      origin: null,
      destination: null,
    };

    const getRequestMeta = (container) =>
      requestState.get(container) || {
        requestId: 0,
        controller: null,
      };

    const invalidateRequest = (container, shouldAbort = true) => {
      if (!container) {
        return;
      }

      const meta = getRequestMeta(container);

      if (shouldAbort && meta.controller) {
        meta.controller.abort();
      }

      requestState.set(container, {
        requestId: meta.requestId + 1,
        controller: null,
      });
    };

    const closeSuggestions = (container) => {
      if (!container) {
        return;
      }

      invalidateRequest(container);
      container.innerHTML = "";
      container.hidden = true;
      autocompleteState.set(container, {
        suggestions: [],
        activeIndex: -1,
      });
    };

    const syncReturnDateState = () => {
      if (!tripTypeInput || !returnDateInput) {
        return;
      }

      const tripType = tripTypeInput.value || "one_way";
      const needsReturnDate = tripType !== "one_way";

      if (departureDateInput && departureDateInput.value) {
        returnDateInput.min = departureDateInput.value;

        if (returnDateInput.value && returnDateInput.value < departureDateInput.value) {
          returnDateInput.value = departureDateInput.value;
        }
      }

      returnDateInput.disabled = !needsReturnDate;
      returnDateInput.required = needsReturnDate;

      if (returnWrap) {
        returnWrap.classList.toggle("is-hidden", !needsReturnDate);
      }

      if (returnDivider) {
        returnDivider.classList.toggle("is-hidden", !needsReturnDate);
      }

      if (!needsReturnDate) {
        returnDateInput.value = "";
      }

      if (returnLabel) {
        returnLabel.textContent = tripType === "multi_city" ? "Second leg date" : "Returning";
      }
    };

    const syncTripButtons = () => {
      if (!tripTypeInput || tripButtons.length === 0) {
        return;
      }

      tripButtons.forEach((button) => {
        const isActive = button.getAttribute("data-trip-type") === tripTypeInput.value;
        button.classList.toggle("is-active", isActive);
      });

      syncReturnDateState();
    };

    const renderMessage = (container, message) => {
      if (!container) {
        return;
      }

      container.innerHTML = `<div class="booking-field__suggestion booking-field__suggestion--status">${escapeHtml(message)}</div>`;
      container.hidden = false;
      autocompleteState.set(container, {
        suggestions: [],
        activeIndex: -1,
      });
    };

    const syncNearbyButton = (button, suggestion) => {
      if (!button) {
        return;
      }

      const ready =
        suggestion &&
        typeof suggestion.latitude === "number" &&
        typeof suggestion.longitude === "number";

      button.disabled = !ready;
    };

    const applySelection = (labelInput, codeInput, container, suggestion) => {
      if (!labelInput || !codeInput) {
        return;
      }

      const selectedLabel = suggestion.label || suggestion.value || "";
      const selectedValue = suggestion.value || "";

      labelInput.value = selectedLabel;
      codeInput.value = selectedValue;
      labelInput.dataset.selectedPlace = selectedValue;
      labelInput.dataset.selectedPlaceLabel = selectedLabel;

      if (container === originSuggestions) {
        selectedPlaceState.origin = suggestion;
        syncNearbyButton(originNearbyButton, suggestion);
      }

      if (container === destinationSuggestions) {
        selectedPlaceState.destination = suggestion;
        syncNearbyButton(destinationNearbyButton, suggestion);
      }

      closeSuggestions(container);
    };

    const autoSelectExactMatch = (query, labelInput, codeInput, container, suggestions) => {
      const normalizedQuery = normalizePlaceText(query);

      if (!normalizedQuery || !Array.isArray(suggestions) || suggestions.length === 0) {
        return false;
      }

      const exactMatches = suggestions.filter((suggestion) => {
        const candidates = [
          suggestion.label,
          suggestion.value,
          suggestion.place_name,
          suggestion.city_name,
        ]
          .map(normalizePlaceText)
          .filter(Boolean);

        return candidates.includes(normalizedQuery);
      });

      if (exactMatches.length === 0) {
        return false;
      }

      const preferredMatch =
        exactMatches.find((suggestion) => suggestion.type === "city") || exactMatches[0];

      applySelection(labelInput, codeInput, container, preferredMatch);
      return true;
    };

    const autoSelectCityPrefixMatch = (query, labelInput, codeInput, container, suggestions) => {
      const normalizedQuery = normalizePlaceText(query);

      if (!normalizedQuery || !Array.isArray(suggestions) || suggestions.length === 0) {
        return false;
      }

      const cityMatches = suggestions.filter((suggestion) => {
        if (suggestion.type !== "city") {
          return false;
        }

        const candidates = [suggestion.place_name, suggestion.city_name, suggestion.label]
          .map(normalizePlaceText)
          .filter(Boolean);

        return candidates.some((candidate) => candidate.startsWith(normalizedQuery));
      });

      if (cityMatches.length === 0) {
        return false;
      }

      const exactCityMatch =
        cityMatches.find((suggestion) => normalizePlaceText(suggestion.city_name) === normalizedQuery) ||
        cityMatches.find((suggestion) => normalizePlaceText(suggestion.place_name) === normalizedQuery) ||
        cityMatches.find((suggestion) => normalizePlaceText(suggestion.label) === normalizedQuery);

      applySelection(labelInput, codeInput, container, exactCityMatch || cityMatches[0]);
      return true;
    };

    const getSuggestionButtons = (container) =>
      Array.from(container.querySelectorAll("[data-suggestion-index]"));

    const setActiveSuggestion = (container, index) => {
      if (!container) {
        return;
      }

      const buttons = getSuggestionButtons(container);
      const nextIndex =
        buttons.length > 0 && index >= 0 && index < buttons.length ? index : -1;

      buttons.forEach((button, buttonIndex) => {
        const isActive = buttonIndex === nextIndex;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-selected", isActive ? "true" : "false");

        if (isActive) {
          button.scrollIntoView({ block: "nearest" });
        }
      });

      const state = autocompleteState.get(container) || {
        suggestions: [],
        activeIndex: -1,
      };
      autocompleteState.set(container, {
        ...state,
        activeIndex: nextIndex,
      });
    };

    const moveActiveSuggestion = (container, direction) => {
      const buttons = getSuggestionButtons(container);

      if (buttons.length === 0) {
        return;
      }

      const state = autocompleteState.get(container) || {
        suggestions: [],
        activeIndex: -1,
      };

      let nextIndex = state.activeIndex + direction;

      if (nextIndex < 0) {
        nextIndex = buttons.length - 1;
      }

      if (nextIndex >= buttons.length) {
        nextIndex = 0;
      }

      setActiveSuggestion(container, nextIndex);
    };

    const selectActiveSuggestion = (labelInput, codeInput, container) => {
      const state = autocompleteState.get(container);

      if (!state || state.activeIndex < 0 || !Array.isArray(state.suggestions)) {
        return false;
      }

      const suggestion = state.suggestions[state.activeIndex];

      if (!suggestion) {
        return false;
      }

      applySelection(labelInput, codeInput, container, suggestion);
      return true;
    };

    const renderSuggestions = (container, labelInput, codeInput, suggestions) => {
      if (!container) {
        return;
      }

      if (!Array.isArray(suggestions) || suggestions.length === 0) {
        renderMessage(container, strings.noResults || "No matching airports or cities found.");
        return;
      }

      container.innerHTML = suggestions
        .map(
          (suggestion, index) => `
            <button
              type="button"
              class="booking-field__suggestion"
              data-suggestion-index="${index}"
              role="option"
              aria-selected="false"
            >
              <span class="booking-field__suggestion-primary">${escapeHtml(suggestion.label || "")}</span>
              ${
                suggestion.secondary
                  ? `<span class="booking-field__suggestion-secondary">${escapeHtml(suggestion.secondary)}</span>`
                  : ""
              }
            </button>
          `
        )
        .join("");
      container.hidden = false;

      container.querySelectorAll("[data-suggestion-index]").forEach((button) => {
        button.addEventListener("click", () => {
          const index = Number(button.getAttribute("data-suggestion-index"));
          const suggestion = suggestions[index];

          if (suggestion) {
            applySelection(labelInput, codeInput, container, suggestion);
          }
        });
      });

      autocompleteState.set(container, {
        suggestions,
        activeIndex: -1,
      });
    };

    const fetchSuggestions = debounce(async (query, labelInput, codeInput, container) => {
      if (!ajaxUrl || !action || !nonce || !container) {
        return;
      }

      const trimmedQuery = query.trim();

      if (trimmedQuery.length < minChars) {
        if (trimmedQuery.length === 0) {
          closeSuggestions(container);
        } else {
          invalidateRequest(container);
          closeSuggestions(container);
        }
        return;
      }

      const previousMeta = getRequestMeta(container);

      if (previousMeta.controller) {
        previousMeta.controller.abort();
      }

      const controller = typeof AbortController === "function" ? new AbortController() : null;
      const requestId = previousMeta.requestId + 1;

      requestState.set(container, {
        requestId,
        controller,
      });

      renderMessage(container, strings.searching || "Searching places...");

      const url = new URL(ajaxUrl, window.location.origin);
      url.searchParams.set("action", action);
      url.searchParams.set("nonce", nonce);
      url.searchParams.set("query", trimmedQuery);

      try {
        const response = await fetch(url.toString(), {
          method: "GET",
          credentials: "same-origin",
          signal: controller ? controller.signal : undefined,
        });
        const payload = await response.json();
        const activeMeta = getRequestMeta(container);
        const currentValue = labelInput ? labelInput.value.trim() : "";

        if (activeMeta.requestId !== requestId || currentValue !== trimmedQuery) {
          return;
        }

        if (!payload || typeof payload !== "object" || !payload.success) {
          renderMessage(container, strings.noResults || "No matching airports or cities found.");
          return;
        }

        const suggestions = payload.data && Array.isArray(payload.data.suggestions)
          ? payload.data.suggestions
          : [];

        if (autoSelectCityPrefixMatch(trimmedQuery, labelInput, codeInput, container, suggestions)) {
          return;
        }

        if (autoSelectExactMatch(trimmedQuery, labelInput, codeInput, container, suggestions)) {
          return;
        }

        renderSuggestions(
          container,
          labelInput,
          codeInput,
          suggestions
        );
      } catch (error) {
        if (error && error.name === "AbortError") {
          return;
        }

        const activeMeta = getRequestMeta(container);

        if (activeMeta.requestId !== requestId) {
          return;
        }

        renderMessage(container, strings.noResults || "No matching airports or cities found.");
      }
    }, 220);

    const fetchNearbyAirports = async (suggestion, labelInput, codeInput, container) => {
      if (
        !ajaxUrl ||
        !nearbyAction ||
        !nearbyNonce ||
        !container ||
        !suggestion ||
        typeof suggestion.latitude !== "number" ||
        typeof suggestion.longitude !== "number"
      ) {
        renderMessage(container, strings.nearbyHint || "Select a place first.");
        return;
      }

      const previousMeta = getRequestMeta(container);

      if (previousMeta.controller) {
        previousMeta.controller.abort();
      }

      const controller = typeof AbortController === "function" ? new AbortController() : null;
      const requestId = previousMeta.requestId + 1;

      requestState.set(container, {
        requestId,
        controller,
      });

      renderMessage(container, strings.searchingNearby || "Searching nearby airports...");

      const url = new URL(ajaxUrl, window.location.origin);
      url.searchParams.set("action", nearbyAction);
      url.searchParams.set("nonce", nearbyNonce);
      url.searchParams.set("lat", String(suggestion.latitude));
      url.searchParams.set("lng", String(suggestion.longitude));
      url.searchParams.set("rad", String(nearbyRadius));

      try {
        const response = await fetch(url.toString(), {
          method: "GET",
          credentials: "same-origin",
          signal: controller ? controller.signal : undefined,
        });
        const payload = await response.json();
        const activeMeta = getRequestMeta(container);

        if (activeMeta.requestId !== requestId) {
          return;
        }

        if (!payload || typeof payload !== "object" || !payload.success) {
          renderMessage(container, strings.noResults || "No matching airports or cities found.");
          return;
        }

        renderSuggestions(
          container,
          labelInput,
          codeInput,
          payload.data && Array.isArray(payload.data.suggestions)
            ? payload.data.suggestions
            : []
        );
      } catch (error) {
        if (error && error.name === "AbortError") {
          return;
        }

        const activeMeta = getRequestMeta(container);

        if (activeMeta.requestId !== requestId) {
          return;
        }

        renderMessage(container, strings.noResults || "No matching airports or cities found.");
      }
    };

    [
      [originLabelInput, originCodeInput, originSuggestions],
      [destinationLabelInput, destinationCodeInput, destinationSuggestions],
    ].forEach(([labelInput, codeInput, container]) => {
      if (!labelInput || !codeInput || !container) {
        return;
      }

      const clearSelectedPlace = () => {
        codeInput.value = "";
        delete labelInput.dataset.selectedPlace;
        delete labelInput.dataset.selectedPlaceLabel;

        if (container === originSuggestions) {
          selectedPlaceState.origin = null;
          syncNearbyButton(originNearbyButton, null);
        }

        if (container === destinationSuggestions) {
          selectedPlaceState.destination = null;
          syncNearbyButton(destinationNearbyButton, null);
        }
      };

      const selectExistingValue = () => {
        if (codeInput.value.trim().length === 3 && labelInput.value.trim().length > 0) {
          window.requestAnimationFrame(() => {
            try {
              labelInput.setSelectionRange(0, labelInput.value.length);
            } catch (error) {
              // Some browsers may reject selection updates on non-focused inputs.
            }
          });
        }
      };

      labelInput.addEventListener("pointerdown", selectExistingValue);

      labelInput.addEventListener("input", () => {
        if (codeInput.value.trim().length === 3) {
          clearSelectedPlace();
        }

        closeSuggestions(container);
        fetchSuggestions(labelInput.value, labelInput, codeInput, container);
      });

      labelInput.addEventListener("focus", () => {
        selectExistingValue();

        if (labelInput.value.trim().length >= minChars) {
          fetchSuggestions(labelInput.value, labelInput, codeInput, container);
        }
      });

      labelInput.addEventListener("blur", () => {
        window.setTimeout(() => {
          closeSuggestions(container);
        }, 140);
      });

      labelInput.addEventListener("keydown", (event) => {
        const isOpen = !container.hidden;

        if (event.key === "ArrowDown") {
          event.preventDefault();

          if (!isOpen && labelInput.value.trim().length >= minChars) {
            fetchSuggestions(labelInput.value, labelInput, codeInput, container);
            return;
          }

          moveActiveSuggestion(container, 1);
          return;
        }

        if (event.key === "ArrowUp") {
          event.preventDefault();

          if (!isOpen && labelInput.value.trim().length >= minChars) {
            fetchSuggestions(labelInput.value, labelInput, codeInput, container);
            return;
          }

          moveActiveSuggestion(container, -1);
          return;
        }

        if (event.key === "Enter" && isOpen) {
          if (selectActiveSuggestion(labelInput, codeInput, container)) {
            event.preventDefault();
          }
          return;
        }

        if (event.key === "Escape" && isOpen) {
          event.preventDefault();
          closeSuggestions(container);
        }
      });
    });

    if (swapButton && originLabelInput && originCodeInput && destinationLabelInput && destinationCodeInput) {
      swapButton.addEventListener("click", () => {
        const originLabel = originLabelInput.value;
        const originCode = originCodeInput.value;
        const originPlace = selectedPlaceState.origin;

        originLabelInput.value = destinationLabelInput.value;
        originCodeInput.value = destinationCodeInput.value;
        destinationLabelInput.value = originLabel;
        destinationCodeInput.value = originCode;
        selectedPlaceState.origin = selectedPlaceState.destination;
        selectedPlaceState.destination = originPlace;
        syncNearbyButton(originNearbyButton, selectedPlaceState.origin);
        syncNearbyButton(destinationNearbyButton, selectedPlaceState.destination);

        closeSuggestions(originSuggestions);
        closeSuggestions(destinationSuggestions);
      });
    }

    if (originNearbyButton && originLabelInput && originCodeInput && originSuggestions) {
      originNearbyButton.disabled = true;
      originNearbyButton.addEventListener("click", () => {
        fetchNearbyAirports(
          selectedPlaceState.origin,
          originLabelInput,
          originCodeInput,
          originSuggestions
        );
      });
    }

    if (destinationNearbyButton && destinationLabelInput && destinationCodeInput && destinationSuggestions) {
      destinationNearbyButton.disabled = true;
      destinationNearbyButton.addEventListener("click", () => {
        fetchNearbyAirports(
          selectedPlaceState.destination,
          destinationLabelInput,
          destinationCodeInput,
          destinationSuggestions
        );
      });
    }

    if (tripTypeInput && tripButtons.length > 0) {
      tripButtons.forEach((button) => {
        button.addEventListener("click", () => {
          tripTypeInput.value = button.getAttribute("data-trip-type") || "one_way";
          syncTripButtons();
        });
      });
    }

    if (departureDateInput && returnDateInput) {
      departureDateInput.addEventListener("change", syncReturnDateState);
    }

    syncTripButtons();

    form.addEventListener("submit", (event) => {
      const originReady = originCodeInput && originCodeInput.value.trim().length === 3;
      const destinationReady = destinationCodeInput && destinationCodeInput.value.trim().length === 3;
      const tripType = tripTypeInput ? tripTypeInput.value : "one_way";
      const needsReturnDate = tripType !== "one_way";
      const returnReady =
        !needsReturnDate ||
        (returnDateInput &&
          returnDateInput.value.trim().length > 0 &&
          (!departureDateInput || returnDateInput.value >= departureDateInput.value));

      if (!originReady || !destinationReady || !returnReady) {
        event.preventDefault();

        if (!originReady && originLabelInput) {
          originLabelInput.focus();
        } else if (!destinationReady && destinationLabelInput) {
          destinationLabelInput.focus();
        } else if (!returnReady && returnDateInput) {
          returnDateInput.focus();
        }
      }
    });
  });

  filterForms.forEach((form) => {
    form.querySelectorAll('input[type="checkbox"]').forEach((input) => {
      input.addEventListener("change", () => {
        if (typeof form.requestSubmit === "function") {
          form.requestSubmit();
          return;
        }

        form.submit();
      });
    });
  });

  document.addEventListener("click", (event) => {
    const selectButton = event.target.closest(".booking-result-card__select");

    if (!selectButton) {
      return;
    }

    if (selectButton.disabled) {
      event.preventDefault();
      return;
    }

    const selectUrl = selectButton.getAttribute("data-select-url");

    if (!selectUrl) {
      return;
    }

    event.preventDefault();
    window.location.assign(selectUrl);
  });

  document.addEventListener("click", (event) => {
    const loadMoreButton = event.target.closest("[data-load-more]");

    if (!loadMoreButton) {
      return;
    }

    const hiddenCards = document.querySelectorAll(".booking-result-card.is-hidden-by-default");

    hiddenCards.forEach((card) => {
      card.hidden = false;
      card.classList.remove("is-hidden-by-default");
    });

    loadMoreButton.remove();
  });
});
