(function () {
    'use strict';

    const dataEl = document.getElementById('prmsQuickNavData');
    const searchUrlsEl = document.getElementById('prmsQuickNavSearchUrls');
    const openBtn = document.getElementById('prmsQuickNavOpen');
    const modalEl = document.getElementById('prmsQuickNavModal');
    const inputEl = document.getElementById('prmsQuickNavInput');
    const resultsEl = document.getElementById('prmsQuickNavResults');
    const emptyEl = document.getElementById('prmsQuickNavEmpty');
    const hintEl = document.getElementById('prmsQuickNavHint');
    const countEl = document.getElementById('prmsQuickNavCount');

    if (!dataEl || !modalEl || !inputEl || !resultsEl) {
        return;
    }

    // Bootstrap modals must not live inside the navbar (overflow/stacking breaks input focus).
    if (modalEl.parentElement && modalEl.parentElement !== document.body) {
        document.body.appendChild(modalEl);
    }

    /** @type {Array<{label:string,url:string,icon:string,group:string,keywords?:string,subtitle?:string}>} */
    const navItems = JSON.parse(dataEl.textContent || '[]');
    let activeIndex = 0;
    let visibleItems = navItems.slice();
    let modal = null;

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function normalize(value) {
        return String(value || '').toLowerCase().trim();
    }

    function queryTokens(query) {
        return normalize(query).split(/\s+/).filter(Boolean);
    }

    /** @type {Record<string, string>} */
    const searchUrlTemplates = searchUrlsEl ? JSON.parse(searchUrlsEl.textContent || '{}') : {};

    function searchUrl(key, query) {
        const template = searchUrlTemplates[key] || '';
        return template.replace('__QUERY__', encodeURIComponent(query));
    }

    function buildSearchTargets(query) {
        const q = query.trim();
        if (q.length < 2) {
            return [];
        }

        const role = document.body.dataset.prmsRole || '';
        const targets = [];

        if (['coordinator', 'supervisor', 'hod', 'project_student', 'research_student', 'normal_student'].includes(role)) {
            targets.push({
                label: 'Search approved library for “' + q + '”',
                url: searchUrl('archive', q),
                icon: 'fas fa-archive',
                group: 'Search',
                subtitle: 'Approved documents archive',
                keywords: 'search archive library documents ' + q,
            });
        }

        targets.push({
            label: 'Search public repository for “' + q + '”',
            url: searchUrl('public', q),
            icon: 'fas fa-globe',
            group: 'Search',
            subtitle: 'Published research & projects',
            keywords: 'search public repository publications ' + q,
        });

        if (role === 'admin') {
            targets.push({
                label: 'Search users for “' + q + '”',
                url: searchUrl('users', q),
                icon: 'fas fa-users',
                group: 'Search',
                subtitle: 'User management',
                keywords: 'search users accounts ' + q,
            });
        }

        return targets;
    }

    function matchScore(item, tokens) {
        const label = normalize(item.label);
        const haystack = normalize(
            item.label + ' ' + (item.group || '') + ' ' + (item.subtitle || '') + ' ' + (item.keywords || '')
        );

        if (tokens.length === 0) {
            return 1;
        }

        if (!tokens.every(function (token) { return haystack.indexOf(token) !== -1; })) {
            return -1;
        }

        let score = 10;
        tokens.forEach(function (token) {
            if (label === token) {
                score += 100;
            } else if (label.indexOf(token) === 0) {
                score += 50;
            } else if (label.indexOf(token) !== -1) {
                score += 20;
            } else {
                score += 5;
            }
        });

        return score;
    }

    function filterItems(query) {
        const tokens = queryTokens(query);
        const pages = navItems
            .map(function (item) {
                return { item: item, score: matchScore(item, tokens) };
            })
            .filter(function (row) { return row.score >= 0; })
            .sort(function (a, b) { return b.score - a.score; })
            .map(function (row) { return row.item; });

        return pages.concat(buildSearchTargets(query));
    }

    function updateFooter(query, count) {
        if (countEl) {
            const suffix = count === 1 ? '' : 's';
            countEl.textContent = query.trim() === ''
                ? count + ' destination' + suffix + ' available'
                : count + ' result' + suffix;
        }
    }

    function updateHint(query, count) {
        if (!hintEl) {
            return;
        }

        const trimmed = query.trim();
        if (trimmed === '') {
            hintEl.textContent = 'Type to filter pages, chapters, and reports. Try “deadlines”, “chapter”, or “consent”.';
            hintEl.classList.remove('d-none');
            return;
        }

        if (trimmed.length < 2) {
            hintEl.textContent = 'Keep typing to search the library and public repository.';
            hintEl.classList.remove('d-none');
            return;
        }

        if (count === 0) {
            hintEl.textContent = 'No matches for “' + trimmed + '”. Try a shorter or different keyword.';
            hintEl.classList.remove('d-none');
            return;
        }

        hintEl.classList.add('d-none');
    }

    function renderList(items, query) {
        visibleItems = items;
        activeIndex = 0;
        resultsEl.innerHTML = '';

        updateFooter(query, items.length);
        updateHint(query, items.length);

        if (!items.length) {
            emptyEl.classList.remove('d-none');
            return;
        }

        emptyEl.classList.add('d-none');

        let currentGroup = '';

        items.forEach(function (item, index) {
            if (item.group && item.group !== currentGroup) {
                currentGroup = item.group;
                const heading = document.createElement('div');
                heading.className = 'prms-quick-nav-group px-4 py-2 small text-uppercase fw-semibold sticky-top';
                heading.textContent = currentGroup;
                resultsEl.appendChild(heading);
            }

            const link = document.createElement('a');
            link.href = item.url;
            link.id = 'prms-quick-nav-item-' + index;
            link.className = 'list-group-item list-group-item-action prms-quick-nav-item d-flex align-items-center gap-3 py-3 px-4 border-0';
            link.dataset.index = String(index);
            link.setAttribute('role', 'option');
            link.innerHTML =
                '<span class="prms-quick-nav-icon d-inline-flex align-items-center justify-content-center rounded-3 flex-shrink-0">' +
                    '<i class="' + escapeHtml(item.icon || 'fas fa-link') + '" aria-hidden="true"></i>' +
                '</span>' +
                '<span class="flex-grow-1 min-w-0">' +
                    '<span class="d-block fw-semibold text-truncate prms-quick-nav-label">' + escapeHtml(item.label) + '</span>' +
                    '<span class="d-block small text-muted text-truncate prms-quick-nav-subtitle">' + escapeHtml(item.subtitle || item.group || '') + '</span>' +
                '</span>' +
                '<span class="prms-quick-nav-enter small text-muted flex-shrink-0" aria-hidden="true">↵</span>';

            link.addEventListener('mouseenter', function () {
                setActiveIndex(index);
            });

            link.addEventListener('click', function (event) {
                event.preventDefault();
                window.location.href = item.url;
            });

            resultsEl.appendChild(link);
        });

        highlightActive();
    }

    function highlightActive() {
        const links = resultsEl.querySelectorAll('a.prms-quick-nav-item');
        links.forEach(function (link) {
            const itemIndex = Number(link.dataset.index);
            if (itemIndex === activeIndex) {
                link.classList.add('active');
                link.setAttribute('aria-selected', 'true');
                link.scrollIntoView({ block: 'nearest' });
            } else {
                link.classList.remove('active');
                link.setAttribute('aria-selected', 'false');
            }
        });

        inputEl.setAttribute('aria-activedescendant', visibleItems.length ? 'prms-quick-nav-item-' + activeIndex : '');
    }

    function setActiveIndex(index) {
        if (!visibleItems.length) {
            return;
        }
        activeIndex = Math.max(0, Math.min(index, visibleItems.length - 1));
        highlightActive();
    }

    function openModal() {
        if (!window.bootstrap) {
            return;
        }
        if (!modal) {
            modal = window.bootstrap.Modal.getOrCreateInstance(modalEl, {
                backdrop: true,
                keyboard: true,
                focus: false,
            });
        }
        modal.show();
    }

    function focusSearchInput() {
        inputEl.removeAttribute('readonly');
        inputEl.disabled = false;
        window.setTimeout(function () {
            inputEl.focus({ preventScroll: true });
        }, 50);
    }

    function navigateToActive() {
        const item = visibleItems[activeIndex];
        if (item && item.url) {
            window.location.href = item.url;
        }
    }

    if (openBtn) {
        openBtn.addEventListener('click', openModal);
    }

    inputEl.addEventListener('input', function () {
        renderList(filterItems(inputEl.value), inputEl.value);
    });

    inputEl.setAttribute('role', 'combobox');
    inputEl.setAttribute('aria-autocomplete', 'list');
    inputEl.setAttribute('aria-controls', 'prmsQuickNavResults');
    resultsEl.setAttribute('role', 'listbox');

    inputEl.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex(activeIndex + 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex(activeIndex - 1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            navigateToActive();
        }
    });

    modalEl.addEventListener('shown.bs.modal', function () {
        inputEl.value = '';
        renderList(navItems, '');
        focusSearchInput();
    });

    document.addEventListener('keydown', function (event) {
        if (modalEl.classList.contains('show')) {
            return;
        }
        const isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
        const modifier = isMac ? event.metaKey : event.ctrlKey;
        if (modifier && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            openModal();
        }
    });
})();
