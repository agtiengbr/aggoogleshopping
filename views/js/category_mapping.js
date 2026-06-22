(function () {
  function setExpanded(toggleButton, expanded) {
    var item = toggleButton.closest('.aggs-tree-item');
    if (!item) {
      return;
    }

    var children = null;
    for (var i = 0; i < item.children.length; i++) {
      if (item.children[i].classList.contains('aggs-tree-children')) {
        children = item.children[i];
        break;
      }
    }
    if (!children) {
      return;
    }

    var icon = toggleButton.querySelector('i');
    toggleButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');

    if (expanded) {
      children.classList.remove('aggs-tree-collapsed');
      if (icon) {
        icon.classList.remove('icon-angle-right');
        icon.classList.add('icon-angle-down');
      }
    } else {
      children.classList.add('aggs-tree-collapsed');
      if (icon) {
        icon.classList.remove('icon-angle-down');
        icon.classList.add('icon-angle-right');
      }
    }
  }

  function bindTaxonomyAutocomplete() {
    document.querySelectorAll('[data-aggs-taxonomy-autocomplete]').forEach(function (widget) {
      var displayInput = widget.querySelector('.aggs-autocomplete-display');
      var valueInput = widget.querySelector('.aggs-autocomplete-value');
      var menu = widget.querySelector('.aggs-autocomplete-menu');
      var ajaxUrl = widget.getAttribute('data-ajax-url') || '';
      if (!displayInput || !valueInput || !menu || ajaxUrl === '') {
        return;
      }

      var debounceTimer = null;
      var requestId = 0;

      function closeMenu() {
        menu.classList.add('aggs-hidden');
      }

      function openMenu() {
        menu.classList.remove('aggs-hidden');
      }

      function renderEmpty(message) {
        menu.innerHTML = '';
        var emptyItem = document.createElement('li');
        emptyItem.className = 'aggs-autocomplete-empty';
        emptyItem.textContent = message;
        menu.appendChild(emptyItem);
        openMenu();
      }

      function renderResults(results) {
        menu.innerHTML = '';

        if (!results || results.length === 0) {
          renderEmpty('Nenhuma categoria encontrada');
          return;
        }

        results.forEach(function (result) {
          var item = document.createElement('li');
          item.className = 'aggs-autocomplete-item';
          item.setAttribute('data-value', result.id || '');
          item.setAttribute('data-label', result.label || '');
          item.textContent = result.label || '';
          menu.appendChild(item);
        });

        openMenu();
      }

      function selectItem(item) {
        valueInput.value = item.getAttribute('data-value') || '';
        displayInput.value = item.getAttribute('data-label') || '';
        closeMenu();
      }

      function fetchResults(query) {
        var currentRequest = ++requestId;

        fetch(ajaxUrl + '&q=' + encodeURIComponent(query), {
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
          .then(function (response) {
            return response.json();
          })
          .then(function (payload) {
            if (currentRequest !== requestId) {
              return;
            }

            renderResults((payload && payload.results) || []);
          })
          .catch(function () {
            if (currentRequest !== requestId) {
              return;
            }

            renderEmpty('Erro ao buscar categorias');
          });
      }

      function scheduleSearch(query) {
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(function () {
          fetchResults(query);
        }, 250);
      }

      displayInput.addEventListener('focus', function () {
        var query = displayInput.value.trim();
        if (query.length >= 2) {
          scheduleSearch(query);
        }
      });

      displayInput.addEventListener('input', function () {
        valueInput.value = '';
        var query = displayInput.value.trim();
        if (query.length < 2) {
          closeMenu();
          menu.innerHTML = '';
          return;
        }

        scheduleSearch(query);
      });

      displayInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          closeMenu();
        }
      });

      displayInput.addEventListener('blur', function () {
        window.setTimeout(function () {
          if (valueInput.value === '' && displayInput.value.trim() !== '') {
            displayInput.value = '';
          }
          closeMenu();
        }, 150);
      });

      menu.addEventListener('mousedown', function (event) {
        var item = event.target.closest('.aggs-autocomplete-item');
        if (!item) {
          return;
        }
        event.preventDefault();
        selectItem(item);
      });
    });
  }

  function bindAutocomplete() {
    document.querySelectorAll('[data-aggs-autocomplete]').forEach(function (widget) {
      var displayInput = widget.querySelector('.aggs-autocomplete-display');
      var valueInput = widget.querySelector('.aggs-autocomplete-value');
      var menu = widget.querySelector('.aggs-autocomplete-menu');
      if (!displayInput || !valueInput || !menu) {
        return;
      }

      var items = Array.prototype.slice.call(menu.querySelectorAll('.aggs-autocomplete-item'));

      function closeMenu() {
        menu.classList.add('aggs-hidden');
      }

      function openMenu() {
        filterMenu(displayInput.value.trim());
        menu.classList.remove('aggs-hidden');
      }

      function filterMenu(query) {
        var normalized = query.toLowerCase();
        var visibleCount = 0;

        items.forEach(function (item) {
          var label = (item.getAttribute('data-label') || item.textContent || '').toLowerCase();
          var value = (item.getAttribute('data-value') || '').toLowerCase();
          var matches = normalized === '' || label.indexOf(normalized) !== -1 || value.indexOf(normalized) !== -1;
          item.style.display = matches ? '' : 'none';
          if (matches) {
            visibleCount++;
          }
        });

        var emptyItem = menu.querySelector('.aggs-autocomplete-empty');
        if (visibleCount === 0) {
          if (!emptyItem) {
            emptyItem = document.createElement('li');
            emptyItem.className = 'aggs-autocomplete-empty';
            emptyItem.textContent = 'Nenhum resultado';
            menu.appendChild(emptyItem);
          }
          emptyItem.style.display = '';
        } else if (emptyItem) {
          emptyItem.style.display = 'none';
        }
      }

      function selectItem(item) {
        valueInput.value = item.getAttribute('data-value') || '';
        displayInput.value = item.getAttribute('data-label') || item.textContent || '';
        closeMenu();
      }

      function syncValueFromDisplay() {
        var query = displayInput.value.trim().toLowerCase();
        if (query === '') {
          valueInput.value = '';
          return;
        }

        var matched = null;
        items.forEach(function (item) {
          var label = (item.getAttribute('data-label') || item.textContent || '').toLowerCase();
          var value = (item.getAttribute('data-value') || '').toLowerCase();
          if (query === label || query === value) {
            matched = item;
          }
        });

        if (matched) {
          selectItem(matched);
        } else {
          valueInput.value = displayInput.value.trim();
        }
      }

      displayInput.addEventListener('focus', openMenu);
      displayInput.addEventListener('input', function () {
        valueInput.value = '';
        openMenu();
      });
      displayInput.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          closeMenu();
        }
      });
      displayInput.addEventListener('blur', function () {
        window.setTimeout(function () {
          syncValueFromDisplay();
          closeMenu();
        }, 150);
      });

      menu.addEventListener('mousedown', function (event) {
        var item = event.target.closest('.aggs-autocomplete-item');
        if (!item) {
          return;
        }
        event.preventDefault();
        selectItem(item);
      });
    });
  }

  function bindCategorySearch(root) {
    var dataNode = document.getElementById('aggs-category-search-data');
    var searchWrap = root.querySelector('.aggs-category-search');
    if (!dataNode || !searchWrap) {
      return;
    }

    var index = [];
    try {
      index = JSON.parse(dataNode.textContent || '[]');
    } catch (error) {
      index = [];
    }

    var input = searchWrap.querySelector('.aggs-category-search-input');
    var menu = searchWrap.querySelector('.aggs-category-search-menu');
    if (!input || !menu) {
      return;
    }

    function closeMenu() {
      menu.classList.add('aggs-hidden');
    }

    function openMenu() {
      menu.classList.remove('aggs-hidden');
    }

    function renderMenu(query) {
      var normalized = query.toLowerCase();
      menu.innerHTML = '';

      var matches = index.filter(function (entry) {
        if (normalized === '') {
          return false;
        }

        return entry.name.toLowerCase().indexOf(normalized) !== -1
          || entry.path.toLowerCase().indexOf(normalized) !== -1;
      }).slice(0, 20);

      if (matches.length === 0) {
        var empty = document.createElement('li');
        empty.className = 'aggs-autocomplete-empty';
        empty.textContent = 'Nenhuma categoria encontrada';
        menu.appendChild(empty);
        openMenu();
        return;
      }

      matches.forEach(function (entry) {
        var item = document.createElement('li');
        item.className = 'aggs-autocomplete-item';
        item.innerHTML = '<span class="aggs-autocomplete-item-label"></span><span class="aggs-autocomplete-item-path"></span>';
        item.querySelector('.aggs-autocomplete-item-label').textContent = entry.name;
        item.querySelector('.aggs-autocomplete-item-path').textContent = entry.path;
        item.addEventListener('mousedown', function (event) {
          event.preventDefault();
          window.location.href = entry.url;
        });
        menu.appendChild(item);
      });

      openMenu();
    }

    function resetTreeFilter() {
      root.querySelectorAll('.aggs-tree-item').forEach(function (item) {
        item.classList.remove('aggs-tree-item-hidden', 'aggs-tree-item-match');
      });
    }

    function filterTree(query) {
      var normalized = query.trim().toLowerCase();
      resetTreeFilter();

      if (normalized.length < 2) {
        return;
      }

      var items = Array.prototype.slice.call(root.querySelectorAll('.aggs-tree-item'));
      var visibleItems = {};

      items.forEach(function (item) {
        var name = (item.getAttribute('data-category-name') || '').toLowerCase();
        var path = (item.getAttribute('data-category-path') || '').toLowerCase();
        var matches = name.indexOf(normalized) !== -1 || path.indexOf(normalized) !== -1;
        if (matches) {
          visibleItems[item.getAttribute('data-category-id')] = true;
          item.classList.add('aggs-tree-item-match');
        }
      });

      items.forEach(function (item) {
        var id = item.getAttribute('data-category-id');
            if (visibleItems[id]) {
              var parent = item.parentElement;
              while (parent) {
                if (parent.classList && parent.classList.contains('aggs-tree-item')) {
                  visibleItems[parent.getAttribute('data-category-id')] = true;
                  var row = parent.firstElementChild;
                  if (row && row.classList.contains('aggs-tree-row')) {
                    var toggle = row.querySelector('.aggs-tree-toggle');
                    if (toggle) {
                      setExpanded(toggle, true);
                    }
                  }
                }
            if (parent.classList && parent.classList.contains('aggs-tree-children')) {
              parent.classList.remove('aggs-tree-collapsed');
            }
            parent = parent.parentElement;
          }
        }
      });

      items.forEach(function (item) {
        var id = item.getAttribute('data-category-id');
        if (!visibleItems[id]) {
          item.classList.add('aggs-tree-item-hidden');
        }
      });
    }

    input.addEventListener('input', function () {
      var query = input.value.trim();
      renderMenu(query);
      filterTree(query);
    });

    input.addEventListener('focus', function () {
      if (input.value.trim() !== '') {
        renderMenu(input.value.trim());
      }
    });

    input.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        input.value = '';
        closeMenu();
        resetTreeFilter();
      }

      if (event.key === 'Enter') {
        var firstItem = menu.querySelector('.aggs-autocomplete-item');
        if (firstItem) {
          event.preventDefault();
          firstItem.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
        }
      }
    });

    input.addEventListener('blur', function () {
      window.setTimeout(closeMenu, 150);
    });
  }

  function bindTree(root) {
    root.addEventListener('click', function (event) {
      var toggle = event.target.closest('.aggs-tree-toggle');
      if (!toggle || !root.contains(toggle)) {
        return;
      }

      event.preventDefault();
      var expanded = toggle.getAttribute('aria-expanded') === 'true';
      setExpanded(toggle, !expanded);
    });

    var expandAll = root.querySelector('[data-aggs-action="expand-all"]');
    if (expandAll) {
      expandAll.addEventListener('click', function (event) {
        event.preventDefault();
        root.querySelectorAll('.aggs-tree-toggle').forEach(function (toggle) {
          setExpanded(toggle, true);
        });
      });
    }

    var collapseAll = root.querySelector('[data-aggs-action="collapse-all"]');
    if (collapseAll) {
      collapseAll.addEventListener('click', function (event) {
        event.preventDefault();
        root.querySelectorAll('.aggs-tree-toggle').forEach(function (toggle) {
          setExpanded(toggle, false);
        });
      });
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    bindAutocomplete();
    bindTaxonomyAutocomplete();

    var root = document.getElementById('aggs-category-tree-root');
    if (root) {
      bindCategorySearch(root);
      bindTree(root);
    }
  });
})();
