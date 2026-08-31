(() => {
  'use strict';

  const SELECTOR = 'select[data-oliforge-country-select="1"]';
  const normalize = (value) => String(value || '').toLocaleLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();

  // Instances register their own re-measure closure here so a single theme
  // change (dark-mode class toggle, Customizer live preview, prefers-color-
  // scheme flip) can refresh every enhanced field on the page, not just the
  // one being initialized right now.
  const themeSyncCallbacks = [];
  let themeSyncQueued = false;
  const scheduleThemeSync = () => {
    if (themeSyncQueued) return;
    themeSyncQueued = true;
    window.requestAnimationFrame(() => { themeSyncQueued = false; themeSyncCallbacks.forEach((sync) => sync()); });
  };

  const init = (select) => {
    if (!(select instanceof HTMLSelectElement) || select.dataset.oliforgeCountrySelectReady) return;
    select.dataset.oliforgeCountrySelectReady = '1';

    const wrapper = document.createElement('div');
    wrapper.className = 'oliforge-cf7-country-select';

    const controlWrap = document.createElement('div');
    controlWrap.className = 'oliforge-cf7-country-select__control-wrap';

    // A readonly text input intentionally inherits generic theme rules such as
    // `.contact-form input`, which a button-based combobox cannot inherit.
    const control = document.createElement('input');
    control.type = 'text';
    control.readOnly = true;
    control.className = 'oliforge-cf7-country-select__control';
    control.setAttribute('role', 'combobox');
    control.setAttribute('aria-haspopup', 'listbox');
    control.setAttribute('aria-expanded', 'false');
    control.setAttribute('aria-autocomplete', 'list');
    const requestedSize = Number.parseInt(select.dataset.controlSize || '40', 10);
    control.size = Number.isFinite(requestedSize) && requestedSize > 0 ? Math.min(requestedSize, 100) : 40;

    const visibleClasses = (select.dataset.visibleClass || '').split(/\s+/).filter(Boolean);
    visibleClasses.forEach((className) => control.classList.add(className));

    // Probe a throwaway input carrying the same theme/CF7 classes to read the
    // border/radius/background a plain text field would actually get from the
    // active theme, then apply those to the wrapper below instead of a fixed
    // design-system value — that's what makes the row match sibling fields.
    // Re-run via themeSyncCallbacks so a dark-mode toggle or Customizer live
    // preview (which don't reload the page) keep this in sync too.
    const syncThemeStyle = () => {
      const probe = document.createElement('input');
      probe.type = 'text';
      probe.tabIndex = -1;
      probe.style.position = 'absolute';
      probe.style.visibility = 'hidden';
      probe.style.pointerEvents = 'none';
      visibleClasses.forEach((className) => probe.classList.add(className));
      select.parentNode.insertBefore(probe, select.nextSibling);
      const probeStyle = getComputedStyle(probe);
      wrapper.style.setProperty('--oliforge-country-border', probeStyle.borderTopColor);
      wrapper.style.setProperty('--oliforge-country-radius', probeStyle.borderTopLeftRadius);
      const background = probeStyle.backgroundColor;
      if (background && background !== 'rgba(0, 0, 0, 0)') wrapper.style.setProperty('--oliforge-country-bg', background);
      controlWrap.style.borderWidth = probeStyle.borderTopWidth;
      controlWrap.style.borderStyle = probeStyle.borderTopStyle;
      probe.remove();
    };
    syncThemeStyle();
    themeSyncCallbacks.push(syncThemeStyle);

    // Copy safe author-defined aria/data attributes to the visible control.
    [...select.attributes].forEach((attribute) => {
      const name = attribute.name.toLowerCase();
      if ((name.startsWith('aria-') || name.startsWith('data-')) && !name.startsWith('data-oliforge-')) {
        if (!['aria-controls', 'aria-describedby', 'aria-expanded', 'aria-invalid', 'aria-required'].includes(name)) {
          control.setAttribute(name, attribute.value);
        }
      }
    });

    if (select.id) {
      control.id = `${select.id}-control`;
      control.setAttribute('aria-controls', `${select.id}-country-list`);
    }
    if (select.hasAttribute('tabindex')) {
      control.setAttribute('tabindex', select.getAttribute('tabindex'));
      select.removeAttribute('tabindex');
    }
    if (select.hasAttribute('autocomplete')) control.setAttribute('autocomplete', select.getAttribute('autocomplete'));

    const selectedFlag = document.createElement('img');
    selectedFlag.className = 'oliforge-cf7-country-select__selected-flag';
    selectedFlag.alt = '';
    selectedFlag.loading = 'lazy';
    selectedFlag.hidden = true;

    const chevron = document.createElement('span');
    chevron.className = 'oliforge-cf7-country-select__chevron';
    chevron.setAttribute('aria-hidden', 'true');

    const dropdown = document.createElement('div');
    dropdown.className = 'oliforge-cf7-country-select__dropdown';
    dropdown.hidden = true;

    const searchEnabled = select.dataset.searchEnabled !== '0';
    const showFlags = select.dataset.showFlags !== '0';
    const showChevron = select.dataset.showChevron !== '0';
    const showValidationBorder = select.dataset.validationBorder === '1';
    wrapper.classList.toggle('has-validation-border', showValidationBorder);
    wrapper.classList.toggle('has-flag', showFlags);
    wrapper.classList.toggle('has-chevron', showChevron);

    const searchWrap = document.createElement('div');
    searchWrap.className = 'oliforge-cf7-country-select__search-wrap';
    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'oliforge-cf7-country-select__search';
    search.placeholder = select.dataset.searchPlaceholder || 'Search country';
    search.autocomplete = 'off';
    search.setAttribute('aria-label', search.placeholder);

    const list = document.createElement('div');
    list.className = 'oliforge-cf7-country-select__list';
    list.setAttribute('role', 'listbox');
    if (select.id) list.id = `${select.id}-country-list`;

    const empty = document.createElement('div');
    empty.className = 'oliforge-cf7-country-select__empty';
    empty.textContent = select.dataset.noResults || 'No countries found';
    empty.hidden = true;
    const items = [];

    const renderControl = () => {
      const option = select.options[select.selectedIndex];
      const isPlaceholder = !option || !option.value;
      control.value = option ? option.textContent : (select.dataset.placeholder || 'Select a country');
      control.classList.toggle('is-placeholder', isPlaceholder);
      if (showFlags && option && option.value && option.dataset.flag) {
        selectedFlag.src = option.dataset.flag;
        selectedFlag.hidden = false;
      } else {
        selectedFlag.removeAttribute('src');
        selectedFlag.hidden = true;
      }
    };

    const updateSelectedState = () => items.forEach(({ element, option }) => element.setAttribute('aria-selected', String(option.value === select.value)));

    const syncValidationState = () => {
      const invalid = select.classList.contains('wpcf7-not-valid') || select.getAttribute('aria-invalid') === 'true';
      control.classList.toggle('wpcf7-not-valid', invalid && showValidationBorder);
      wrapper.classList.toggle('is-invalid', invalid);
      control.setAttribute('aria-invalid', invalid ? 'true' : 'false');
      control.setAttribute('aria-required', select.getAttribute('aria-required') === 'true' ? 'true' : 'false');
      const tip = select.closest('.wpcf7-form-control-wrap')?.querySelector('.wpcf7-not-valid-tip');
      if (tip) {
        if (!tip.id) tip.id = `${select.name || 'country'}-error`;
        control.setAttribute('aria-describedby', tip.id);
      } else control.removeAttribute('aria-describedby');
    };

    const clearValidationAfterSelection = () => {
      if (!select.value) return;
      select.classList.remove('wpcf7-not-valid');
      select.setAttribute('aria-invalid', 'false');
      select.closest('.wpcf7-form-control-wrap')?.querySelectorAll('.wpcf7-not-valid-tip').forEach((tip) => tip.remove());
      syncValidationState();
    };

    const loadDeferredFlag = (img) => {
      if (!img?.dataset.src) return;
      img.src = img.dataset.src;
      img.removeAttribute('data-src');
    };

    // Observe flags against the scrollable list, not the viewport. A small
    // vertical root margin preloads the next rows just before they scroll into
    // view while avoiding requests for the rest of the country catalogue.
    const flagObserver = showFlags && 'IntersectionObserver' in window
      ? new IntersectionObserver((entries, observer) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            loadDeferredFlag(entry.target);
            observer.unobserve(entry.target);
          });
        }, { root: list, rootMargin: '96px 0px', threshold: 0.01 })
      : null;

    const observeDeferredFlags = () => {
      const deferredFlags = list.querySelectorAll('img[data-src]');
      if (!flagObserver) {
        deferredFlags.forEach(loadDeferredFlag);
        return;
      }
      deferredFlags.forEach((img) => flagObserver.observe(img));
    };

    const close = (restoreFocus = false) => {
      dropdown.hidden = true;
      control.setAttribute('aria-expanded', 'false');
      search.value = '';
      items.forEach(({ element }) => { element.hidden = false; });
      empty.hidden = true;
      if (restoreFocus) control.focus();
    };
    const open = () => {
      dropdown.hidden = false;
      control.setAttribute('aria-expanded', 'true');
      // Start observing only after the dropdown becomes visible; hidden rows
      // otherwise have no intersection geometry. Only visible/nearby flags get
      // a src. Older browsers fall back to loading all deferred flags here.
      observeDeferredFlags();
      window.requestAnimationFrame(() => (searchEnabled ? search : items[0]?.element)?.focus());
    };

    const options = [...select.options];
    const ordered = [options[0], ...options.slice(1).filter((o) => o.dataset.preferred === '1'), ...options.slice(1).filter((o) => o.dataset.preferred !== '1')].filter(Boolean);
    let separatorAdded = false;
    ordered.forEach((option, index) => {
      if (!separatorAdded && index > 1 && ordered[index - 1]?.dataset.preferred === '1' && option.dataset.preferred !== '1') {
        const separator = document.createElement('div');
        separator.className = 'oliforge-cf7-country-select__separator';
        separator.setAttribute('role', 'separator');
        list.appendChild(separator);
        separatorAdded = true;
      }
      const item = document.createElement('button');
      item.type = 'button';
      item.className = 'oliforge-cf7-country-select__option';
      if (!option.value) item.classList.add('is-placeholder');
      item.setAttribute('role', 'option');
      item.dataset.value = option.value;
      item.setAttribute('aria-selected', String(option.selected));
      if (showFlags && option.value && option.dataset.flag) {
        const img = document.createElement('img');
        img.dataset.src = option.dataset.flag; // assigned lazily in open(), not here
        img.alt = '';
        img.loading = 'lazy';
        item.appendChild(img);
      }
      const label = document.createElement('span');
      label.textContent = option.textContent;
      item.appendChild(label);
      const searchable = normalize(`${option.textContent} ${option.value}`);
      items.push({ element: item, option, searchable });
      item.addEventListener('click', () => {
        select.value = option.value;
        [...select.options].forEach((entry) => { entry.selected = entry.value === option.value; });
        select.dispatchEvent(new Event('input', { bubbles: true }));
        select.dispatchEvent(new Event('change', { bubbles: true }));
        renderControl();
        updateSelectedState();
        clearValidationAfterSelection();
        close(true);
      });
      list.appendChild(item);
    });

    search.addEventListener('input', () => {
      const query = normalize(search.value);
      let visible = 0;
      items.forEach(({ element, searchable, option }) => {
        const match = !query || (!option.value ? !query : searchable.includes(query));
        element.hidden = !match;
        if (match) visible += 1;
      });
      empty.hidden = visible !== 0;
    });
    search.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') { event.preventDefault(); close(true); return; }
      if (event.key === 'ArrowDown') {
        const firstVisible = items.find(({ element }) => !element.hidden);
        if (firstVisible) { event.preventDefault(); firstVisible.element.focus(); }
      }
      if (event.key === 'Enter') {
        const visibleItems = items.filter(({ element }) => !element.hidden);
        if (visibleItems.length === 1) { event.preventDefault(); visibleItems[0].element.click(); }
      }
    });
    list.addEventListener('keydown', (event) => {
      const visible = items.map(({ element }) => element).filter((element) => !element.hidden);
      const index = visible.indexOf(document.activeElement);
      if (event.key === 'Escape') { event.preventDefault(); close(true); }
      else if (event.key === 'ArrowDown' && index > -1) { event.preventDefault(); (visible[index + 1] || visible[0])?.focus(); }
      else if (event.key === 'ArrowUp' && index > -1) { event.preventDefault(); index === 0 ? search.focus() : visible[index - 1]?.focus(); }
    });

    // Bound to the whole row (not just the input) since the flag/chevron are
    // now normal flex siblings of the input rather than overlays sitting on
    // top of it — a click on either icon needs to open the list too.
    controlWrap.addEventListener('click', () => {
      control.focus();
      dropdown.hidden ? open() : close(false);
    });
    control.addEventListener('keydown', (event) => {
      if (['Enter', ' ', 'ArrowDown', 'ArrowUp'].includes(event.key)) { event.preventDefault(); if (dropdown.hidden) open(); }
      if (event.key === 'Escape') { event.preventDefault(); close(false); }
    });
    document.addEventListener('click', (event) => { if (!wrapper.contains(event.target)) close(false); });
    select.addEventListener('change', () => { renderControl(); updateSelectedState(); syncValidationState(); });

    if (searchEnabled) { searchWrap.appendChild(search); dropdown.appendChild(searchWrap); }
    dropdown.append(list, empty);
    controlWrap.appendChild(control);
    if (showFlags) controlWrap.appendChild(selectedFlag);
    if (showChevron) controlWrap.appendChild(chevron);
    wrapper.append(controlWrap, dropdown);

    select.parentNode.insertBefore(wrapper, select.nextSibling);
    select.classList.add('oliforge-cf7-country-select__native--enhanced');
    select.removeAttribute('required');
    renderControl();
    syncValidationState();

    let validationSyncQueued = false;
    const scheduleValidationSync = () => {
      if (validationSyncQueued) return;
      validationSyncQueued = true;
      window.requestAnimationFrame(() => { validationSyncQueued = false; syncValidationState(); });
    };

    const cf7Wrap = select.closest('.wpcf7-form-control-wrap');
    new MutationObserver(scheduleValidationSync).observe(select, { attributes: true, attributeFilter: ['class', 'aria-invalid', 'aria-describedby'] });
    if (cf7Wrap) new MutationObserver(scheduleValidationSync).observe(cf7Wrap, { childList: true, subtree: true });
    const form = select.closest('form');
    if (form) form.addEventListener('reset', () => window.setTimeout(() => { renderControl(); updateSelectedState(); syncValidationState(); close(false); }, 0));
  };

  const selectsIn = (root = document) => {
    const found = [];
    if (root instanceof Element && root.matches(SELECTOR)) found.push(root);
    if (root.querySelectorAll) found.push(...root.querySelectorAll(SELECTOR));
    return found;
  };
  const boot = (root = document) => selectsIn(root).forEach(init);
  const syncAll = (root = document) => selectsIn(root).forEach((select) => select.dispatchEvent(new Event('change', { bubbles: false })));
  const closeAll = (root = document) => root.querySelectorAll?.('.oliforge-cf7-country-select__dropdown:not([hidden])').forEach((dropdown) => {
    dropdown.hidden = true;
    dropdown.closest('.oliforge-cf7-country-select')?.querySelector('.oliforge-cf7-country-select__control')?.setAttribute('aria-expanded', 'false');
  });

  document.addEventListener('DOMContentLoaded', () => boot());
  ['wpcf7invalid', 'wpcf7submit', 'wpcf7mailfailed', 'wpcf7mailsent', 'wpcf7reset'].forEach((eventName) => document.addEventListener(eventName, (event) => window.setTimeout(() => { boot(event.target); syncAll(event.target); closeAll(event.target); }, 0)));
  const observer = new MutationObserver((mutations) => mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => { if (node.nodeType === 1) boot(node); })));
  const startObserver = () => { if (document.body) observer.observe(document.body, { childList: true, subtree: true }); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startObserver, { once: true }); else startObserver();

  // A theme's own dark-mode toggle or Customizer live preview usually works
  // by flipping a class/attribute on <html> or <body> without reloading the
  // page — catch that and re-measure every enhanced field's border/radius.
  new MutationObserver(scheduleThemeSync).observe(document.documentElement, { attributes: true });
  const startBodyThemeObserver = () => { if (document.body) new MutationObserver(scheduleThemeSync).observe(document.body, { attributes: true }); };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', startBodyThemeObserver, { once: true }); else startBodyThemeObserver();
  window.matchMedia?.('(prefers-color-scheme: dark)')?.addEventListener?.('change', scheduleThemeSync);
})();
