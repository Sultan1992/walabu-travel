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
    const bookingWidget = form.closest(".booking-widget");
    const originLabelInput = form.querySelector('[data-place-input="origin"]');
    const originCodeInput = form.querySelector('[data-place-code="origin"]');
    const originSuggestions = form.querySelector('[data-suggestions="origin"]');
    const originNearbyButton = form.querySelector('[data-nearby-button="origin"]');
    const originClearButton = form.querySelector('[data-clear-place="origin"]');
    const destinationLabelInput = form.querySelector('[data-place-input="destination"]');
    const destinationCodeInput = form.querySelector('[data-place-code="destination"]');
    const destinationSuggestions = form.querySelector('[data-suggestions="destination"]');
    const destinationNearbyButton = form.querySelector('[data-nearby-button="destination"]');
    const destinationClearButton = form.querySelector('[data-clear-place="destination"]');
    const serviceTypeInput = form.querySelector("[data-service-type-input]");
    const serviceButtons = bookingWidget
      ? Array.from(bookingWidget.querySelectorAll(".booking-widget__service-tab[data-service-type]"))
      : [];
    const bundleHotelToggle = form.querySelector("[data-bundle-hotel-toggle]");
    const swapButton = form.querySelector(".booking-widget__swap");
    const tripTypeInput = form.querySelector("[data-trip-type-input]");
    const tripButtons = Array.from(form.querySelectorAll("[data-trip-type]"));
    const departureDateInput = form.querySelector('input[name="departure_date"]');
    const returnDateInput = form.querySelector("[data-return-date-input]");
    const returnLabel = form.querySelector("[data-return-label]");
    const returnWrap = form.querySelector("[data-return-wrap]");
    const returnDivider = form.querySelector("[data-return-divider]");
    const datePicker = form.querySelector("[data-date-picker]");
    const dateMonthContainer = form.querySelector("[data-date-months]");
    const datePrevButton = form.querySelector("[data-date-prev]");
    const dateNextButton = form.querySelector("[data-date-next]");
    const dateClearButton = form.querySelector("[data-date-clear]");
    const dateDoneButton = form.querySelector("[data-date-done]");
    const dateTriggers = {
      departure: form.querySelector('[data-date-trigger="departure"]'),
      return: form.querySelector('[data-date-trigger="return"]'),
    };
    const dateDisplays = {
      departure: form.querySelector('[data-date-display="departure"]'),
      return: form.querySelector('[data-date-display="return"]'),
    };
    const passengerTrigger = form.querySelector("[data-passenger-trigger]");
    const passengerSummary = form.querySelector("[data-passenger-summary]");
    const passengerPanel = form.querySelector("[data-passenger-panel]");
    const passengerTotalInput = form.querySelector("[data-passenger-total-input]");
    const passengerStepButtons = Array.from(form.querySelectorAll("[data-passenger-step]"));
    const passengerCountInputs = Array.from(form.querySelectorAll("[data-passenger-count-input]")).reduce(
      (accumulator, input) => {
        accumulator[input.dataset.passengerCountInput] = input;
        return accumulator;
      },
      {}
    );
    const passengerAgeInputs = Array.from(form.querySelectorAll("[data-passenger-ages-input]")).reduce(
      (accumulator, input) => {
        accumulator[input.dataset.passengerAgesInput] = input;
        return accumulator;
      },
      {}
    );
    const passengerCountDisplays = Array.from(
      form.querySelectorAll("[data-passenger-count-display]")
    ).reduce((accumulator, display) => {
      accumulator[display.dataset.passengerCountDisplay] = display;
      return accumulator;
    }, {});
    const passengerAgeLists = Array.from(form.querySelectorAll("[data-passenger-age-list]")).reduce(
      (accumulator, container) => {
        accumulator[container.dataset.passengerAgeList] = container;
        return accumulator;
      },
      {}
    );
    const autocompleteState = new WeakMap();
    const requestState = new WeakMap();
    const selectedPlaceState = {
      origin: null,
      destination: null,
    };
    const passengerCategoryMeta = {
      adult: {
        min: 1,
        labelSingular: "Adult",
        labelPlural: "Adults",
        ageLabel: "Adult",
      },
      child: {
        min: 0,
        labelSingular: "Child",
        labelPlural: "Children",
        ageMin: 2,
        ageMax: 11,
        defaultAge: 6,
        ageLabel: "Child",
      },
      infant_lap: {
        min: 0,
        labelSingular: "Infant on lap",
        labelPlural: "Infants on lap",
        ageMin: 0,
        ageMax: 1,
        defaultAge: 0,
        ageLabel: "Infant on lap",
      },
      infant_seat: {
        min: 0,
        labelSingular: "Infant on seat",
        labelPlural: "Infants on seat",
        ageMin: 0,
        ageMax: 1,
        defaultAge: 0,
        ageLabel: "Infant on seat",
      },
    };
    const passengerState = {
      adult: 1,
      child: 0,
      infant_lap: 0,
      infant_seat: 0,
      child_ages: [],
      infant_lap_ages: [],
      infant_seat_ages: [],
    };

    const parseCount = (value, fallback = 0) => {
      const nextValue = Number.parseInt(value, 10);
      return Number.isFinite(nextValue) ? nextValue : fallback;
    };

    const getPassengerAgeStateKey = (category) => `${category}_ages`;

    const normalizePassengerAges = (category, count) => {
      const meta = passengerCategoryMeta[category];
      const stateKey = getPassengerAgeStateKey(category);

      if (!meta || !stateKey) {
        return;
      }

      const nextAges = Array.isArray(passengerState[stateKey]) ? passengerState[stateKey].slice(0, count) : [];

      while (nextAges.length < count) {
        nextAges.push(meta.defaultAge);
      }

      passengerState[stateKey] = nextAges.map((age) => {
        const parsedAge = parseCount(age, meta.defaultAge);
        return Math.min(meta.ageMax, Math.max(meta.ageMin, parsedAge));
      });
    };

    const getPassengerTotal = () =>
      passengerState.adult +
      passengerState.child +
      passengerState.infant_lap +
      passengerState.infant_seat;

    const syncPassengerSummary = () => {
      if (!passengerSummary) {
        return;
      }

      const parts = Object.keys(passengerCategoryMeta)
        .map((category) => {
          const count = passengerState[category];
          const meta = passengerCategoryMeta[category];

          if (!count) {
            return "";
          }

          const label = count === 1 ? meta.labelSingular : meta.labelPlural;
          return `${count} ${label}`;
        })
        .filter(Boolean);

      passengerSummary.textContent = parts.length > 0 ? parts.join(", ") : "1 Adult";
    };

    const syncPassengerStepButtons = () => {
      const total = getPassengerTotal();

      passengerStepButtons.forEach((button) => {
        const category = button.getAttribute("data-passenger-step") || "";
        const delta = Number.parseInt(button.getAttribute("data-step") || "0", 10);
        const count = passengerState[category] || 0;
        const meta = passengerCategoryMeta[category];
        let disabled = false;

        if (!meta || delta === 0) {
          return;
        }

        if (delta < 0) {
          const nextCount = count - 1;
          disabled = nextCount < meta.min;

          if (category === "adult" && nextCount < passengerState.infant_lap) {
            disabled = true;
          }
        } else {
          disabled = total >= 9;

          if (category === "infant_lap" && passengerState.infant_lap >= passengerState.adult) {
            disabled = true;
          }
        }

        button.disabled = disabled;
        button.setAttribute("aria-disabled", disabled ? "true" : "false");
      });
    };

    const renderPassengerAgeFields = (category) => {
      const container = passengerAgeLists[category];
      const meta = passengerCategoryMeta[category];
      const stateKey = getPassengerAgeStateKey(category);
      const ages = stateKey ? passengerState[stateKey] || [] : [];

      if (!container || !meta || !stateKey) {
        return;
      }

      container.hidden = ages.length === 0;
      container.innerHTML = "";

      ages.forEach((age, index) => {
        const row = document.createElement("label");
        const label = document.createElement("span");
        const select = document.createElement("select");

        row.className = "booking-passenger-panel__age";
        label.className = "booking-passenger-panel__age-label";
        label.textContent = `${meta.ageLabel} ${index + 1} age`;

        select.className = "booking-passenger-panel__age-select";
        select.setAttribute("aria-label", `${meta.ageLabel} ${index + 1} age`);

        for (let ageValue = meta.ageMin; ageValue <= meta.ageMax; ageValue += 1) {
          const option = document.createElement("option");
          option.value = String(ageValue);
          option.textContent = ageValue === 0 ? "Under 1" : `${ageValue} year${ageValue === 1 ? "" : "s"}`;

          if (ageValue === age) {
            option.selected = true;
          }

          select.append(option);
        }

        select.addEventListener("change", () => {
          passengerState[stateKey][index] = parseCount(select.value, meta.defaultAge);
          syncPassengerState();
        });

        row.append(label, select);
        container.append(row);
      });
    };

    const normalizePassengerState = () => {
      passengerState.adult = Math.max(1, parseCount(passengerState.adult, 1));
      passengerState.child = Math.max(0, parseCount(passengerState.child, 0));
      passengerState.infant_lap = Math.max(0, parseCount(passengerState.infant_lap, 0));
      passengerState.infant_seat = Math.max(0, parseCount(passengerState.infant_seat, 0));

      if (passengerState.infant_lap > passengerState.adult) {
        passengerState.infant_lap = passengerState.adult;
      }

      let overflow = getPassengerTotal() - 9;

      if (overflow > 0) {
        ["infant_seat", "infant_lap", "child", "adult"].forEach((category) => {
          if (overflow <= 0) {
            return;
          }

          const removable = Math.max(0, passengerState[category] - passengerCategoryMeta[category].min);

          if (removable <= 0) {
            return;
          }

          const reduction = Math.min(removable, overflow);
          passengerState[category] -= reduction;
          overflow -= reduction;
        });
      }

      if (passengerState.infant_lap > passengerState.adult) {
        passengerState.infant_lap = passengerState.adult;
      }

      normalizePassengerAges("child", passengerState.child);
      normalizePassengerAges("infant_lap", passengerState.infant_lap);
      normalizePassengerAges("infant_seat", passengerState.infant_seat);
    };

    const syncPassengerState = () => {
      normalizePassengerState();

      if (passengerTotalInput) {
        passengerTotalInput.value = String(getPassengerTotal());
      }

      Object.keys(passengerCountInputs).forEach((category) => {
        passengerCountInputs[category].value = String(passengerState[category] || 0);
      });

      Object.keys(passengerAgeInputs).forEach((category) => {
        const stateKey = getPassengerAgeStateKey(category);
        passengerAgeInputs[category].value = stateKey
          ? (passengerState[stateKey] || []).join(",")
          : "";
      });

      Object.keys(passengerCountDisplays).forEach((category) => {
        passengerCountDisplays[category].textContent = String(passengerState[category] || 0);
      });

      syncPassengerSummary();
      syncPassengerStepButtons();
      renderPassengerAgeFields("child");
      renderPassengerAgeFields("infant_lap");
      renderPassengerAgeFields("infant_seat");
    };

    const closePassengerPanel = () => {
      if (!passengerPanel || !passengerTrigger) {
        return;
      }

      passengerPanel.hidden = true;
      passengerTrigger.setAttribute("aria-expanded", "false");
    };

    const openPassengerPanel = () => {
      if (!passengerPanel || !passengerTrigger) {
        return;
      }

      passengerPanel.hidden = false;
      passengerTrigger.setAttribute("aria-expanded", "true");
    };

    const togglePassengerPanel = () => {
      if (!passengerPanel || !passengerTrigger) {
        return;
      }

      if (passengerPanel.hidden) {
        openPassengerPanel();
        return;
      }

      closePassengerPanel();
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

      if (dateTriggers.return) {
        dateTriggers.return.hidden = !needsReturnDate;
      }

      if (returnDivider) {
        returnDivider.classList.toggle("is-hidden", !needsReturnDate);
      }

      if (!needsReturnDate) {
        returnDateInput.value = "";
        if (dateState.activeField === "return" && datePicker) {
          dateState.activeField = "departure";
          closeDatePicker();
        }
      }

      if (returnLabel) {
        returnLabel.textContent = tripType === "multi_city" ? "Second leg date" : "Returning";
      }

      syncDateTriggerLabels();
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

    const syncServiceButtons = () => {
      if (!serviceTypeInput || serviceButtons.length === 0) {
        return;
      }

      serviceButtons.forEach((button) => {
        const isActive = button.getAttribute("data-service-type") === serviceTypeInput.value;
        button.classList.toggle("is-active", isActive);
      });

      if (bundleHotelToggle) {
        bundleHotelToggle.checked = serviceTypeInput.value === "flight_hotel";
      }
    };

    const dateInputs = {
      departure: departureDateInput,
      return: returnDateInput,
    };
    const dateState = {
      activeField: "departure",
      viewMonth: null,
      open: false,
    };
    const weekdayLabels = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

    const createUtcDate = (year, monthIndex, day) =>
      new Date(Date.UTC(year, monthIndex, day, 12, 0, 0, 0));

    const parseIsoDate = (value) => {
      const match = String(value || "").trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);

      if (!match) {
        return null;
      }

      const year = Number.parseInt(match[1], 10);
      const month = Number.parseInt(match[2], 10);
      const day = Number.parseInt(match[3], 10);

      if (!Number.isFinite(year) || !Number.isFinite(month) || !Number.isFinite(day)) {
        return null;
      }

      return createUtcDate(year, month - 1, day);
    };

    const formatIsoDate = (date) => {
      if (!(date instanceof Date) || Number.isNaN(date.getTime())) {
        return "";
      }

      return `${String(date.getUTCFullYear()).padStart(4, "0")}-${String(date.getUTCMonth() + 1).padStart(2, "0")}-${String(date.getUTCDate()).padStart(2, "0")}`;
    };

    const formatDisplayDate = (value) => {
      const date = parseIsoDate(value);

      if (!date) {
        return "";
      }

      return new Intl.DateTimeFormat("en-US", {
        weekday: "short",
        month: "short",
        day: "2-digit",
      }).format(date);
    };

    const startOfMonth = (date) =>
      createUtcDate(date.getUTCFullYear(), date.getUTCMonth(), 1);

    const addMonths = (date, delta) =>
      createUtcDate(date.getUTCFullYear(), date.getUTCMonth() + delta, 1);

    const isSameDay = (left, right) =>
      Boolean(left && right) &&
      left.getUTCFullYear() === right.getUTCFullYear() &&
      left.getUTCMonth() === right.getUTCMonth() &&
      left.getUTCDate() === right.getUTCDate();

    const isBeforeDay = (left, right) =>
      Boolean(left && right) && left.getTime() < right.getTime();

    const getTodayUtc = () => {
      const now = new Date();
      return createUtcDate(now.getFullYear(), now.getMonth(), now.getDate());
    };

    const getCurrentMonthStart = () => startOfMonth(getTodayUtc());

    const syncDateTriggerLabels = () => {
      if (dateDisplays.departure) {
        dateDisplays.departure.textContent = dateInputs.departure?.value
          ? formatDisplayDate(dateInputs.departure.value)
          : "Select a date";
      }

      if (dateDisplays.return) {
        dateDisplays.return.textContent = dateInputs.return?.value
          ? formatDisplayDate(dateInputs.return.value)
          : "Select a date";
      }
    };

    const syncDateTriggerState = () => {
      const tripType = tripTypeInput ? tripTypeInput.value : "one_way";
      const needsReturnDate = tripType !== "one_way";

      if (dateTriggers.departure) {
        dateTriggers.departure.setAttribute("aria-expanded", dateState.open && dateState.activeField === "departure" ? "true" : "false");
      }

      if (dateTriggers.return) {
        dateTriggers.return.hidden = !needsReturnDate;
        dateTriggers.return.setAttribute("aria-expanded", dateState.open && dateState.activeField === "return" ? "true" : "false");
      }

      if (returnDivider) {
        returnDivider.classList.toggle("is-hidden", !needsReturnDate);
      }

      if (returnWrap) {
        returnWrap.classList.toggle("is-hidden", !needsReturnDate);
      }

      if (dateInputs.return) {
        dateInputs.return.disabled = !needsReturnDate;
        dateInputs.return.required = needsReturnDate;
      }

      if (!needsReturnDate && dateInputs.return) {
        dateInputs.return.value = "";
      }

      if (returnLabel) {
        returnLabel.textContent = tripType === "multi_city" ? "Second leg date" : "Returning";
      }
    };

    const renderDatePickerMonth = (monthStart) => {
      const monthTitle = new Intl.DateTimeFormat("en-US", {
        month: "long",
        year: "numeric",
      }).format(monthStart);
      const firstDayOffset = monthStart.getUTCDay();
      const today = getTodayUtc();
      const currentTripType = tripTypeInput ? tripTypeInput.value : "one_way";
      const departureDate = parseIsoDate(dateInputs.departure?.value || "");
      const returnDate = parseIsoDate(dateInputs.return?.value || "");
      const activeMonth = monthStart.getUTCMonth();
      const activeYear = monthStart.getUTCFullYear();
      const gridStart = createUtcDate(activeYear, activeMonth, 1 - firstDayOffset);
      const cells = [];

      for (let index = 0; index < 42; index += 1) {
        const cellDate = createUtcDate(
          gridStart.getUTCFullYear(),
          gridStart.getUTCMonth(),
          gridStart.getUTCDate() + index
        );
        const inMonth = cellDate.getUTCMonth() === activeMonth;
        const isoValue = formatIsoDate(cellDate);
        const isDisabled = isBeforeDay(cellDate, today);
        const isDeparture = departureDate && isSameDay(cellDate, departureDate);
        const isReturn = returnDate && isSameDay(cellDate, returnDate);
        const isRange =
          departureDate &&
          returnDate &&
          isBeforeDay(departureDate, cellDate) &&
          isBeforeDay(cellDate, returnDate);

        cells.push(`
          <button
            type="button"
            class="booking-date-picker__day ${inMonth ? "" : "is-outside"} ${isDeparture ? "is-selected is-range-start" : ""} ${isReturn ? "is-selected is-range-end" : ""} ${isRange ? "is-range" : ""} ${isDisabled ? "is-disabled" : ""}"
            data-date-value="${isoValue}"
            ${isDisabled ? "disabled" : ""}
            aria-pressed="${isDeparture || isReturn ? "true" : "false"}"
          >
            <span>${cellDate.getUTCDate()}</span>
          </button>
        `);
      }

      return `
        <section class="booking-date-picker__month" data-month="${activeYear}-${String(activeMonth + 1).padStart(2, "0")}">
          <h3 class="booking-date-picker__title">${escapeHtml(monthTitle)}</h3>
          <div class="booking-date-picker__weekdays">
            ${weekdayLabels.map((label) => `<span>${label}</span>`).join("")}
          </div>
          <div class="booking-date-picker__grid ${currentTripType !== "one_way" && departureDate && returnDate ? "has-range" : ""}">
            ${cells.join("")}
          </div>
        </section>
      `;
    };

    const renderDatePicker = () => {
      if (!datePicker || !dateMonthContainer) {
        return;
      }

      const today = getTodayUtc();
      const departureDate = parseIsoDate(dateInputs.departure?.value || "");
      const selectedDate = parseIsoDate(
        dateState.activeField === "return" && dateInputs.return?.value
          ? dateInputs.return.value
          : dateInputs.departure?.value || dateInputs.return?.value || ""
      );

      if (!dateState.viewMonth) {
        dateState.viewMonth = startOfMonth(selectedDate || departureDate || today);
      }

      if (dateState.viewMonth.getTime() < getCurrentMonthStart().getTime()) {
        dateState.viewMonth = getCurrentMonthStart();
      }

      const nextMonth = addMonths(dateState.viewMonth, 1);
      const canGoBack = dateState.viewMonth.getTime() > getCurrentMonthStart().getTime();

      datePicker.hidden = false;
      dateMonthContainer.innerHTML =
        renderDatePickerMonth(dateState.viewMonth) + renderDatePickerMonth(nextMonth);

      if (datePrevButton) {
        datePrevButton.disabled = !canGoBack;
      }
    };

    const openDatePicker = (field) => {
      if (!datePicker) {
        return;
      }

      dateState.open = true;
      dateState.activeField = field === "return" ? "return" : "departure";
      dateState.viewMonth = startOfMonth(
        parseIsoDate(
          dateState.activeField === "return" && dateInputs.return?.value
            ? dateInputs.return.value
            : dateInputs.departure?.value || dateInputs.return?.value || ""
        ) || getTodayUtc()
      );
      syncDateTriggerState();
      renderDatePicker();
    };

    const closeDatePicker = () => {
      if (!datePicker) {
        return;
      }

      dateState.open = false;
      datePicker.hidden = true;
      if (dateTriggers.departure) {
        dateTriggers.departure.setAttribute("aria-expanded", "false");
      }
      if (dateTriggers.return) {
        dateTriggers.return.setAttribute("aria-expanded", "false");
      }
    };

    const setDateValue = (field, value) => {
      const nextDate = parseIsoDate(value);

      if (!nextDate) {
        return;
      }

      const isoValue = formatIsoDate(nextDate);
      const tripType = tripTypeInput ? tripTypeInput.value : "one_way";

      if (field === "departure" && dateInputs.departure) {
        dateInputs.departure.value = isoValue;

        if (dateInputs.return && dateInputs.return.value) {
          const returnDate = parseIsoDate(dateInputs.return.value);

          if (returnDate && isBeforeDay(returnDate, nextDate)) {
            dateInputs.return.value = "";
          }
        }

        if (tripType === "one_way") {
          closeDatePicker();
          syncDateTriggerLabels();
          syncDateTriggerState();
          return;
        }

        dateState.activeField = "return";
        dateState.viewMonth = startOfMonth(nextDate);
        syncDateTriggerLabels();
        syncDateTriggerState();
        renderDatePicker();
        return;
      }

      if (field === "return" && dateInputs.return) {
        const departureDate = parseIsoDate(dateInputs.departure?.value || "");

        if (departureDate && isBeforeDay(nextDate, departureDate)) {
          dateInputs.departure.value = isoValue;
          dateInputs.return.value = "";
          dateState.activeField = "return";
          dateState.viewMonth = startOfMonth(nextDate);
          syncDateTriggerLabels();
          syncDateTriggerState();
          renderDatePicker();
          return;
        }

        dateInputs.return.value = isoValue;
        closeDatePicker();
      }

      syncDateTriggerLabels();
      syncDateTriggerState();
    };

    if (datePicker) {
      datePicker.addEventListener("click", (event) => {
        if (event.target.closest("[data-date-clear]") || event.target.closest("[data-date-done]")) {
          return;
        }
      });
    }

    if (datePrevButton) {
      datePrevButton.addEventListener("click", () => {
        dateState.viewMonth = addMonths(dateState.viewMonth || getCurrentMonthStart(), -1);

        if (dateState.viewMonth.getTime() < getCurrentMonthStart().getTime()) {
          dateState.viewMonth = getCurrentMonthStart();
        }

        renderDatePicker();
      });
    }

    if (dateNextButton) {
      dateNextButton.addEventListener("click", () => {
        dateState.viewMonth = addMonths(dateState.viewMonth || getCurrentMonthStart(), 1);
        renderDatePicker();
      });
    }

    if (dateClearButton) {
      dateClearButton.addEventListener("click", () => {
        if (dateInputs.departure) {
          dateInputs.departure.value = "";
        }

        if (dateInputs.return) {
          dateInputs.return.value = "";
        }

        dateState.activeField = "departure";
        dateState.viewMonth = getCurrentMonthStart();
        syncDateTriggerLabels();
        syncDateTriggerState();
        renderDatePicker();
      });
    }

    if (dateDoneButton) {
      dateDoneButton.addEventListener("click", () => {
        closeDatePicker();
      });
    }

    Object.keys(dateTriggers).forEach((field) => {
      const trigger = dateTriggers[field];

      if (!trigger) {
        return;
      }

      trigger.addEventListener("click", () => {
        if (datePicker && dateState.open && dateState.activeField === field) {
          closeDatePicker();
          return;
        }

        openDatePicker(field);
      });
    });

    if (dateMonthContainer) {
      dateMonthContainer.addEventListener("click", (event) => {
        const dayButton = event.target.closest("[data-date-value]");

        if (!dayButton) {
          return;
        }

        if (dayButton.disabled) {
          return;
        }

        const value = dayButton.getAttribute("data-date-value");

        if (!value) {
          return;
        }

        setDateValue(dateState.activeField, value);
      });
    }

    document.addEventListener("click", (event) => {
      if (!datePicker || datePicker.hidden) {
        return;
      }

      if (datePicker.contains(event.target)) {
        return;
      }

      if (
        dateTriggers.departure?.contains(event.target) ||
        dateTriggers.return?.contains(event.target)
      ) {
        return;
      }

      closeDatePicker();
    });

    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && datePicker && !datePicker.hidden) {
        closeDatePicker();
      }
    });

    syncDateTriggerLabels();
    syncDateTriggerState();

    if (passengerTrigger && passengerPanel) {
      passengerState.adult = parseCount(passengerCountInputs.adult ? passengerCountInputs.adult.value : 1, 1);
      passengerState.child = parseCount(passengerCountInputs.child ? passengerCountInputs.child.value : 0, 0);
      passengerState.infant_lap = parseCount(
        passengerCountInputs.infant_lap ? passengerCountInputs.infant_lap.value : 0,
        0
      );
      passengerState.infant_seat = parseCount(
        passengerCountInputs.infant_seat ? passengerCountInputs.infant_seat.value : 0,
        0
      );
      passengerState.child_ages = String(
        passengerAgeInputs.child ? passengerAgeInputs.child.value : ""
      )
        .split(",")
        .map((value) => value.trim())
        .filter(Boolean)
        .map((value) => parseCount(value, passengerCategoryMeta.child.defaultAge));
      passengerState.infant_lap_ages = String(
        passengerAgeInputs.infant_lap ? passengerAgeInputs.infant_lap.value : ""
      )
        .split(",")
        .map((value) => value.trim())
        .filter(Boolean)
        .map((value) => parseCount(value, passengerCategoryMeta.infant_lap.defaultAge));
      passengerState.infant_seat_ages = String(
        passengerAgeInputs.infant_seat ? passengerAgeInputs.infant_seat.value : ""
      )
        .split(",")
        .map((value) => value.trim())
        .filter(Boolean)
        .map((value) => parseCount(value, passengerCategoryMeta.infant_seat.defaultAge));

      passengerTrigger.addEventListener("click", () => {
        togglePassengerPanel();
      });

      passengerStepButtons.forEach((button) => {
        button.addEventListener("click", () => {
          const category = button.getAttribute("data-passenger-step") || "";
          const delta = Number.parseInt(button.getAttribute("data-step") || "0", 10);
          const meta = passengerCategoryMeta[category];

          if (!meta || !delta || button.disabled) {
            return;
          }

          passengerState[category] = (passengerState[category] || 0) + delta;
          syncPassengerState();
        });
      });

      document.addEventListener("click", (event) => {
        if (!passengerPanel.hidden && !form.contains(event.target)) {
          closePassengerPanel();
        }
      });

      form.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && !passengerPanel.hidden) {
          closePassengerPanel();
          passengerTrigger.focus();
        }
      });

      syncPassengerState();
    }

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
              <span class="booking-field__suggestion-code">${escapeHtml(suggestion.value || "")}</span>
              <span class="booking-field__suggestion-body">
                <span class="booking-field__suggestion-primary">${escapeHtml(
                  suggestion.place_name || suggestion.label || ""
                )}</span>
                ${
                  suggestion.secondary
                    ? `<span class="booking-field__suggestion-secondary">${escapeHtml(suggestion.secondary)}</span>`
                    : ""
                }
              </span>
            </button>
          `
        )
        .join("");
      container.hidden = false;

      container.querySelectorAll("[data-suggestion-index]").forEach((button) => {
        let selectionCommitted = false;

        const commitSuggestion = (event) => {
          if (selectionCommitted) {
            return;
          }

          selectionCommitted = true;

          if (event) {
            event.preventDefault();
          }

          const index = Number(button.getAttribute("data-suggestion-index"));
          const suggestion = suggestions[index];

          if (suggestion) {
            applySelection(labelInput, codeInput, container, suggestion);
          }

          window.setTimeout(() => {
            selectionCommitted = false;
          }, 0);
        };

        button.addEventListener("pointerdown", commitSuggestion);
        button.addEventListener("click", commitSuggestion);
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

      const clearButton =
        container === originSuggestions ? originClearButton : destinationClearButton;

      const syncClearButton = () => {
        if (!clearButton) {
          return;
        }

        clearButton.hidden = labelInput.value.trim().length === 0;
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

        syncClearButton();
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

      if (clearButton) {
        syncClearButton();
        clearButton.addEventListener("click", () => {
          labelInput.value = "";
          clearSelectedPlace();
          syncClearButton();
          closeSuggestions(container);
          labelInput.focus();
        });
      }

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
        if (originClearButton) {
          originClearButton.hidden = originLabelInput.value.trim().length === 0;
        }
        if (destinationClearButton) {
          destinationClearButton.hidden = destinationLabelInput.value.trim().length === 0;
        }

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

    if (serviceTypeInput && serviceButtons.length > 0) {
      serviceButtons.forEach((button) => {
        button.addEventListener("click", () => {
          const serviceUrl = button.getAttribute("data-service-url");

          if (serviceUrl) {
            window.location.assign(serviceUrl);
            return;
          }

          serviceTypeInput.value = button.getAttribute("data-service-type") || "flight";
          syncServiceButtons();
        });
      });
    }

    if (serviceTypeInput && bundleHotelToggle) {
      bundleHotelToggle.addEventListener("change", () => {
        serviceTypeInput.value = bundleHotelToggle.checked ? "flight_hotel" : "flight";
        syncServiceButtons();
      });
    }

    if (departureDateInput && returnDateInput) {
      departureDateInput.addEventListener("change", syncReturnDateState);
    }

    syncTripButtons();
    syncServiceButtons();

    form.addEventListener("submit", (event) => {
      if (passengerTrigger && passengerPanel) {
        syncPassengerState();
      }

      const originReady = originCodeInput && originCodeInput.value.trim().length === 3;
      const destinationReady = destinationCodeInput && destinationCodeInput.value.trim().length === 3;
      const tripType = tripTypeInput ? tripTypeInput.value : "one_way";
      const needsReturnDate = tripType !== "one_way";
      const returnReady =
        !needsReturnDate ||
        (returnDateInput &&
          returnDateInput.value.trim().length > 0 &&
          (!departureDateInput || returnDateInput.value >= departureDateInput.value));
      const passengersReady =
        !passengerTrigger ||
        (getPassengerTotal() > 0 && getPassengerTotal() <= 9 && passengerState.infant_lap <= passengerState.adult);

      if (!originReady || !destinationReady || !returnReady || !passengersReady) {
        event.preventDefault();

        if (!originReady && originLabelInput) {
          originLabelInput.focus();
        } else if (!destinationReady && destinationLabelInput) {
          destinationLabelInput.focus();
        } else if (!returnReady && returnDateInput) {
          returnDateInput.focus();
        } else if (!passengersReady && passengerTrigger) {
          openPassengerPanel();
          passengerTrigger.focus();
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

  document.querySelectorAll("[data-expiry-at]").forEach((timer) => {
    const expiryAt = timer.getAttribute("data-expiry-at");

    if (!expiryAt) {
      return;
    }

    const renderCountdown = () => {
      const diff = new Date(expiryAt).getTime() - Date.now();

      if (!Number.isFinite(diff) || diff <= 0) {
        timer.textContent = "00:00";
        return false;
      }

      const totalSeconds = Math.floor(diff / 1000);
      const minutes = Math.floor(totalSeconds / 60);
      const seconds = totalSeconds % 60;
      timer.textContent = `${String(minutes).padStart(2, "0")}:${String(seconds).padStart(2, "0")}`;
      return true;
    };

    if (!renderCountdown()) {
      return;
    }

    const intervalId = window.setInterval(() => {
      if (!renderCountdown()) {
        window.clearInterval(intervalId);
      }
    }, 1000);
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

});
