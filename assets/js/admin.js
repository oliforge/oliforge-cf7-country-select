(() => {
  const initGridPickers = () => {
    document.querySelectorAll('.oliforge-country-grid-picker').forEach((root) => {
      const items = [...root.querySelectorAll('.oliforge-country-settings__item')];
      const boxes = items.map((item) => item.querySelector('input[type="checkbox"]'));
      const search = root.querySelector('[data-role="search"]');
      const count = root.querySelector('[data-role="count"]');
      const updateCount = () => { count.textContent = `${boxes.filter((box) => box.checked).length} / ${boxes.length}`; };
      root.querySelector('[data-role="select-all"]').addEventListener('click', () => { boxes.forEach((box) => { box.checked = true; }); updateCount(); });
      root.querySelector('[data-role="clear-all"]').addEventListener('click', () => { boxes.forEach((box) => { box.checked = false; }); updateCount(); });
      search.addEventListener('input', () => { const query = search.value.toLocaleLowerCase().trim(); items.forEach((item) => { item.hidden = query && !item.dataset.countrySearch.includes(query); }); });
      boxes.forEach((box) => box.addEventListener('change', updateCount));
      updateCount();
    });
  };

  const initTransferPickers = () => {
    document.querySelectorAll('.oliforge-country-picker').forEach((root) => {
      const availableList = root.querySelector('[data-role="available-list"]');
      const selectedList = root.querySelector('[data-role="selected-list"]');
      const availableSearch = root.querySelector('[data-role="available-search"]');
      const selectedSearch = root.querySelector('[data-role="selected-search"]');
      const availableCount = root.querySelector('[data-role="available-count"]');
      const selectedCount = root.querySelector('[data-role="selected-count"]');
      const addAllBtn = root.querySelector('[data-role="add-all"]');
      const removeAllBtn = root.querySelector('[data-role="remove-all"]');

      const insertSorted = (list, item) => {
        const order = Number(item.dataset.order);
        const next = [...list.children].find((sibling) => Number(sibling.dataset.order) > order);
        list.insertBefore(item, next || null);
      };

      const updateCounts = () => {
        availableCount.textContent = String(availableList.children.length);
        selectedCount.textContent = String(selectedList.children.length);
      };

      const applyFilter = (input, list) => {
        const query = input.value.toLocaleLowerCase().trim();
        [...list.children].forEach((item) => {
          item.hidden = Boolean(query) && !item.dataset.search.includes(query);
        });
      };

      root.addEventListener('change', (event) => {
        const checkbox = event.target;
        if (checkbox.type !== 'checkbox') return;
        const item = checkbox.closest('li');
        if (!item) return;
        insertSorted(checkbox.checked ? selectedList : availableList, item);
        updateCounts();
      });

      addAllBtn.addEventListener('click', () => {
        [...availableList.querySelectorAll('li:not([hidden])')].forEach((item) => {
          item.querySelector('input[type="checkbox"]').checked = true;
          insertSorted(selectedList, item);
        });
        updateCounts();
      });

      removeAllBtn.addEventListener('click', () => {
        [...selectedList.querySelectorAll('li:not([hidden])')].forEach((item) => {
          item.querySelector('input[type="checkbox"]').checked = false;
          insertSorted(availableList, item);
        });
        updateCounts();
      });

      availableSearch.addEventListener('input', () => applyFilter(availableSearch, availableList));
      selectedSearch.addEventListener('input', () => applyFilter(selectedSearch, selectedList));

      updateCounts();
    });
  };

  const initConfirmButtons = () => {
    document.querySelectorAll('[data-oliforge-confirm]').forEach((button) => {
      button.addEventListener('click', (event) => {
        if (!window.confirm(button.dataset.oliforgeConfirm)) {
          event.preventDefault();
        }
      });
    });
  };

  const initToastNotice = () => {
    const toast = document.querySelector('.oliforge-toast-notice');

    // Drop the notice query arg from the address bar so reloading the page
    // (or bookmarking it) doesn't keep re-showing the same "saved" banner.
    const url = new URL(window.location.href);
    if (url.searchParams.has('oliforge_list_notice')) {
      url.searchParams.delete('oliforge_list_notice');
      window.history.replaceState({}, '', url.toString());
    }

    if (!toast) return;

    window.setTimeout(() => {
      toast.style.transition = 'opacity .2s ease, transform .2s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
      window.setTimeout(() => toast.remove(), 200);
    }, 4000);
  };

  const initSlugValidation = () => {
    document.querySelectorAll('[data-oliforge-existing-slugs]').forEach((input) => {
      const taken = input.dataset.oliforgeExistingSlugs
        .split(',')
        .map((slug) => slug.trim().toLowerCase())
        .filter(Boolean);

      const validate = () => {
        const slug = input.value.trim().toLowerCase();
        input.setCustomValidity(
          taken.includes(slug) ? input.dataset.oliforgeDuplicateMessage : ''
        );
      };

      input.addEventListener('input', validate);
      validate();
    });
  };

  initGridPickers();
  initTransferPickers();
  initConfirmButtons();
  initToastNotice();
  initSlugValidation();
})();
