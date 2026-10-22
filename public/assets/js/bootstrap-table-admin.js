(function () {
    var MIN_REFRESH_MS = 2000;
    var refreshPending = false;

    //==============================================================
    // Server request through bootstrap-table: data-ajax
    // - manual refresh is throttled to MIN_REFRESH_MS
    // - search / sort / pagination run instantly (no artificial wait,
    //   no loading flash; the built-in overlay is suppressed)
    // - search text, page and sort order are preserved: bootstrap-table
    //   always sends the current state, we never touch it
    //==============================================================
    window.adminTableAjax = function (opts) {
        var isManualRefresh = refreshPending;
        refreshPending = false;

        if (!isManualRefresh) {
            // Search / sort / pagination: no artificial wait, no loading flash
            hideBuiltInLoading();
        }

        var startedAt = Date.now();
        var originalSuccess = opts.success;
        var originalError = opts.error;

        function runAfterMinimum(callback) {
            if (isManualRefresh) {
                showBuiltInLoading();
                spinRefreshIcon(true);
            }
            var remaining = isManualRefresh
                ? Math.max(0, MIN_REFRESH_MS - (Date.now() - startedAt))
                : 0;

            function finish() {
                callback();
                if (isManualRefresh) {
                    spinRefreshIcon(false);
                }
            }

            if (remaining > 0) {
                setTimeout(finish, remaining);
            } else {
                finish();
            }
        }

        opts.success = function (data) {
            runAfterMinimum(function () {
                originalSuccess(data);
            });
        };

        opts.error = function (xhr) {
            runAfterMinimum(function () {
                originalError(xhr);
            });
        };

        return window.jQuery.ajax(opts);
    };

    function tableSelector() {
        return document.querySelector('.bt-admin-card table[data-toggle="table"]');
    }

    function hideBuiltInLoading() {
        if (window.jQuery && tableSelector()) {
            window.jQuery(tableSelector()).bootstrapTable('hideLoading');
        }
    }

    function showBuiltInLoading() {
        if (window.jQuery && tableSelector()) {
            window.jQuery(tableSelector()).bootstrapTable('showLoading');
        }
    }

    //========== loading overlay content ("Loading…") ==========
    window.adminLoadingTemplate = function () {
        return '<span class="loading-wrap admin-loading">' +
            '<span class="admin-loading-spinner"></span>' +
            '<span class="loading-text">Loading…</span>' +
            '</span>';
    };

    //========== request params (server-side paging/sorting/search) ==========
    window.adminQueryParams = function (p) {
        var params = {
            sort: p.sort || 'id',
            order: p.order || 'desc',
            offset: p.offset || 0,
            limit: p.limit,
            search: p.search
        };

        if (typeof window.adminExtraParams === 'function') {
            var extra = window.adminExtraParams();
            for (var key in extra) {
                if (extra[key] !== undefined && extra[key] !== null && extra[key] !== '') {
                    params[key] = extra[key];
                }
            }
        }

        return params;
    };

    //========== helpers ==========
    function escapeValue(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    //========== cell formatters ==========
    var AVATAR_PALETTE = 6;

    function avatarInitials(name) {
        var parts = String(name || '').trim().split(/\s+/).filter(Boolean);
        if (!parts.length) {
            return '—';
        }
        return parts.slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('');
    }

    window.customerNameFormatter = function (value, row) {
        var name = escapeValue(row.raw_name);
        var company = escapeValue(row.company_name);
        var subtitle = company || (row.customer_type === 'individual' ? 'Personal account' : '');
        var html = '<div class="cell-media">'
            + '<span class="avatar-chip av-' + (parseInt(row.id, 10) % AVATAR_PALETTE) + '">' + escapeValue(avatarInitials(row.raw_name)) + '</span>'
            + '<div class="cell-media-text">'
            + '<div class="fw-semibold">' + (name || '—') + '</div>';
        if (subtitle) {
            html += '<small class="text-muted">' + subtitle + '</small>';
        }
        return html + '</div></div>';
    };

    window.customerTypeFormatter = function (value) {
        if (value === 'business') {
            return '<span class="badge-pill-type badge-type-business">Business</span>';
        }
        return '<span class="badge-pill-type badge-type-individual">Individual</span>';
    };

    window.customerTextFormatter = function (value) {
        return value ? escapeValue(value) : '—';
    };

    window.customerLocationFormatter = function (value) {
        return value
            ? '<span class="loc-wrap"><i class="bi bi-geo-alt"></i>' + escapeValue(value) + '</span>'
            : '—';
    };

    window.customerActionsFormatter = function (value, row) {
        var name = escapeValue(row.raw_name);
        return '<div class="d-flex justify-content-center">'
            + '<a href="' + escapeValue(row.edit_url) + '" class="table-action-btn table-action-edit" title="Edit" aria-label="Edit ' + name + '"><i class="mdi mdi-pencil-outline"></i></a>'
            + '<button type="button" class="table-action-btn table-action-delete" title="Delete" aria-label="Delete ' + name + '" data-delete-url="' + escapeValue(row.delete_url) + '" data-delete-name="' + name + '"><i class="mdi mdi-delete-outline"></i></button>'
            + '</div>';
    };

    //========== invoice cell formatters ==========
    window.invoiceNumberFormatter = function (value, row) {
        var number = escapeValue(value);
        return '<a href="' + escapeValue(row.show_url) + '" class="fw-semibold text-decoration-none">'
            + number + '</a>';
    };

    window.invoiceCustomerFormatter = function (value, row) {
        if (!value) {
            return '—';
        }
        var name = escapeValue(row.customer_name);
        var hash = 0;
        for (var i = 0; i < String(row.customer_name || '').length; i++) {
            hash = (hash * 31 + row.customer_name.charCodeAt(i)) >>> 0;
        }
        return '<div class="cell-media">'
            + '<span class="avatar-chip av-' + (hash % AVATAR_PALETTE) + '">' + escapeValue(avatarInitials(row.customer_name)) + '</span>'
            + '<div class="cell-media-text"><div class="fw-semibold">' + name + '</div></div>'
            + '</div>';
    };

    window.invoiceStatusFormatter = function (value) {
        var labels = {
            paid: 'Paid',
            overdue: 'Overdue',
            sent: 'Sent'
        };
        if (!value || !labels[value]) {
            return '—';
        }
        return '<span class="badge-pill-type badge-status-' + escapeValue(value) + '">' + labels[value] + '</span>';
    };

    window.invoiceDueDateFormatter = function (value, row) {
        var dueDate = row.due_date_raw ? new Date(row.due_date_raw) : null;
        if (!dueDate) {
            return '—';
        }
        var label = escapeValue(value);
        if (row.status === 'overdue') {
            return '<span class="badge-pill-type badge-type-overdue" title="Overdue">' + label + '</span>';
        }
        return escapeValue(value);
    };

    window.invoiceAmountFormatter = function (value, row) {
        return '<div class="fw-semibold">' + escapeValue(row.grand_total_display) + '</div>';
    };

    window.invoiceActionsFormatter = function (value, row) {
        var number = escapeValue(row.invoice_number);
        var amount = escapeValue(row.grand_total_display);
        return '<div class="d-flex justify-content-center">'
            + '<a href="' + escapeValue(row.pdf_url) + '" target="_blank" class="table-action-btn table-action-pdf" title="Download PDF" aria-label="Download PDF ' + number + '"><i class="mdi mdi-download"></i></a>'
            + '<a href="' + escapeValue(row.edit_url) + '" class="table-action-btn table-action-edit" title="Edit" aria-label="Edit ' + number + '"><i class="mdi mdi-pencil-outline"></i></a>'
            + '<button type="button" class="table-action-btn table-action-delete" title="Delete" aria-label="Delete ' + number + '" data-delete-url="' + escapeValue(row.delete_url) + '" data-delete-name="' + number + '" data-delete-amount="' + amount + '"><i class="mdi mdi-delete-outline"></i></button>'
            + '</div>';
    };

    function spinRefreshIcon(on) {
        var icon = document.querySelector('.bt-admin-card [data-bt-refresh] i')
            || document.querySelector('.bt-admin-card .columns button[name="refresh"] .bi-arrow-repeat')
            || document.querySelector('.bt-admin-card .columns button[name="refresh"] i');
        if (icon) {
            icon.classList.toggle('bt-spin', !!on);
        }
    }

    // Detect manual refresh clicks before bootstrap-table handles them
    // (capture phase so it runs first; search/sort/paging never set the flag)
    document.addEventListener('click', function (event) {
        var button = event.target.closest('button[name="refresh"], [data-bt-refresh]');
        if (button && button.closest('.bt-admin-card')) {
            refreshPending = true;
        }
    }, true);

    //========== custom toolbar controls (search / filter / refresh) ==========
    var searchTimer = null;

    function refreshTable(pageOne) {
        if (!window.jQuery) {
            return;
        }
        var table = window.jQuery('.bt-admin-card table[data-toggle="table"]');
        if (table.length) {
            table.bootstrapTable('refresh', pageOne ? { pageNumber: 1 } : undefined);
        }
    }

    document.addEventListener('input', function (event) {
        if (!event.target.closest('[data-bt-search]')) {
            return;
        }
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () {
            refreshTable(true);
        }, 300);
    });

    document.addEventListener('change', function (event) {
        if (event.target.closest('[data-bt-filter]')) {
            refreshTable(true);
        }
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-bt-refresh]')) {
            refreshTable(false);
        }
    });

    //========== delete modal (event delegation, works on JS-rendered rows) ==========
    var modalEl = document.getElementById('deleteModal');
    var form = document.getElementById('deleteRecordForm');
    var nameEl = document.getElementById('deleteRecordName');

    function openModal() {
        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id = 'deleteModalBackdrop';
        backdrop.addEventListener('click', closeModal);
        document.body.appendChild(backdrop);
    }

    function closeModal() {
        if (window.bootstrap && window.bootstrap.Modal) {
            var instance = window.bootstrap.Modal.getInstance(modalEl);
            if (instance) {
                instance.hide();
                return;
            }
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        var backdrop = document.getElementById('deleteModalBackdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-delete-url]');
        if (button) {
            form.action = button.getAttribute('data-delete-url');
            nameEl.textContent = button.getAttribute('data-delete-name');
            openModal();
        }
    });

    modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (button) {
        button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modalEl.classList.contains('show')) {
            closeModal();
        }
    });

    //========== double-click row to open view ==========
    document.addEventListener('dblclick', function (event) {
        // If clicking on buttons, links, inputs, checkboxes, or modal elements, ignore
        if (event.target.closest('a, button, input, textarea, select, .table-action-btn, .dropdown-menu, label')) {
            return;
        }

        var tr = event.target.closest('.bt-admin-card table tbody tr');
        if (!tr || tr.classList.contains('no-records-found')) {
            return;
        }

        var table = tr.closest('table');
        var showUrl = tr.getAttribute('data-show-url');

        // Check bootstrap-table row data if available
        if (!showUrl && window.jQuery && table && window.jQuery(table).data('bootstrap.table')) {
            var index = tr.getAttribute('data-index');
            if (index !== null && index !== undefined) {
                var data = window.jQuery(table).bootstrapTable('getData');
                if (data && data[index] && data[index].show_url) {
                    showUrl = data[index].show_url;
                }
            }
        }

        // Fallback: check if row has an invoice or customer link
        if (!showUrl) {
            var link = tr.querySelector('a.fw-semibold[href], a.inv-number[href], a[href*="/invoices/"], a[href*="/customers/"]');
            if (link && link.href && !link.href.includes('/edit') && !link.href.includes('/pdf') && !link.href.includes('/destroy')) {
                showUrl = link.href;
            }
        }

        if (showUrl) {
            window.location.href = showUrl;
        }
    });

    if (window.jQuery) {
        window.jQuery(document).on('dbl-click-row.bs.table', function (e, row) {
            if (e && e.target && window.jQuery(e.target).closest('a, button, input, select, textarea, .table-action-btn, .dropdown-menu, label').length) {
                return;
            }
            if (row && row.show_url) {
                window.location.href = row.show_url;
            }
        });
    }
})();
