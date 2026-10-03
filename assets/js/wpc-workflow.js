jQuery(document).ready(function($) {
    let currentFilter = '';
    let currentDateFilter = 'today';
    let currentSearch = '';
    let currentSort = 'newest';
    let isPro = wpcData.is_pro || false;
    let nextOrderId = 0;
    let selectedOrders = [];
    let currentModalOrderId = 0;

    const autoOpenKey = 'wpc_workflow_auto_open';
    let autoOpen = localStorage.getItem(autoOpenKey) === '1';

    function init() {
        $('#wpc-auto-open-toggle').prop('checked', autoOpen);

        loadQueue();
        loadStats();

        // Auto refresh stats/queue every 30 seconds
        setInterval(function() {
            loadQueue(false);
            loadStats();
        }, 30000);
    }

    function loadQueue(showLoading = true) {
        if (showLoading) {
            $('#wpc-workflow-queue').html(`
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2">Loading your workflow...</p>
                </div>
            `);
        }

        $.post(wpcData.ajax_url, {
            action: 'wpc_get_workflow_queue',
            nonce: wpcData.nonce,
            status: currentFilter,
            date_filter: currentDateFilter,
            search: currentSearch,
            sort: currentSort
        }, function(response) {
            if (response.success) {
                renderQueue(response.data);
                handleFreeLimit(response.data);
                updateBulkToolbar();
            }
        });
    }

    function loadStats() {
        $.post(wpcData.ajax_url, {
            action: 'wpc_get_workflow_stats',
            nonce: wpcData.nonce,
            date_filter: currentDateFilter
        }, function(response) {
            if (response.success) {
                $('#wpc-stat-new').text(response.data.new);
                $('#wpc-stat-attention').text(response.data.need_attention);
                $('#wpc-stat-processing').text(response.data.processing);
                $('#wpc-stat-ready').text(response.data.ready);
            }
        });
    }

    function renderQueue(data) {
        const $queue = $('#wpc-workflow-queue');
        if (data.orders.length === 0) {
            $queue.html(`
                <div class="wpc-empty-state">
                    <i class="bi bi-check-circle"></i>
                    <h4>✅ All Orders Handled</h4>
                    <p class="text-muted">You have no orders waiting for action.</p>
                </div>
            `);
            return;
        }

        let html = '';
        data.orders.forEach(order => {
            const priorityClass = 'priority-' + (order.priority || 'normal');

            // Fallback for corrupted status
            let currentStatus = order.workflow_status;
            if (!currentStatus || currentStatus === '0') {
                currentStatus = 'new';
            }

            const isSelected = selectedOrders.includes(order.id);
            const statusLabel = currentStatus.charAt(0).toUpperCase() + currentStatus.slice(1);

            html += `
                <div class="card wpc-order-card ${priorityClass} ${isSelected ? 'selected' : ''} shadow-sm" data-id="${order.id}">
                    <div class="card-body d-flex">
                        <div class="wpc-card-select-wrap">
                            <input type="checkbox" class="form-check-input wpc-order-select" ${isSelected ? 'checked' : ''}>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <div class="wpc-order-card-header">
                                        #${order.id} • ${order.wc_status_label}
                                    </div>
                                    <div class="wpc-order-card-title">${order.customer_name}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold fs-5">${order.total}</div>
                                    <span class="badge wpc-workflow-badge ${getBadgeClass(currentStatus)}">${statusLabel}</span>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="wpc-order-age">
                                    <i class="bi bi-clock me-1"></i> ${order.human_time}
                                </div>
                                <div class="wpc-actions d-flex gap-2">
                                    <button class="btn btn-sm btn-outline-primary wpc-open-modal-btn">Open Order</button>
                                    ${getActionButtons({...order, workflow_status: currentStatus})}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $queue.html(html);
        setupActionHandlers();
        setupSelectionHandlers();
    }

    function getBadgeClass(status) {
        switch(status) {
            case 'new': return 'bg-danger';
            case 'acknowledged': return 'bg-warning text-dark';
            case 'processing': return 'bg-info text-dark';
            case 'ready': return 'bg-success';
            default: return 'bg-secondary';
        }
    }

    function getActionButtons(order, isModal = false) {
        const btnClass = isModal ? 'wpc-modal-status-btn' : 'wpc-status-btn';
        switch(order.workflow_status) {
            case 'new':
                return `<button class="btn btn-sm btn-primary ${btnClass}" data-next="acknowledged">Acknowledge</button>`;
            case 'acknowledged':
                return `<button class="btn btn-sm btn-info ${btnClass}" data-next="processing">Start Processing</button>`;
            case 'processing':
                return `<button class="btn btn-sm btn-success ${btnClass}" data-next="ready">Mark Ready</button>`;
            case 'ready':
                return `<button class="btn btn-sm btn-dark ${btnClass}" data-next="completed">Complete</button>`;
            default: return '';
        }
    }

    function setupActionHandlers() {
        $('.wpc-status-btn').off('click').on('click', function() {
            const $btn = $(this);
            const orderId = $btn.closest('.wpc-order-card').data('id');
            const nextStatus = $btn.data('next');
            updateOrderStatus(orderId, nextStatus, $btn, false);
        });

        $('.wpc-open-modal-btn').off('click').on('click', function() {
            const orderId = $(this).closest('.wpc-order-card').data('id');
            openQuickProcessModal(orderId);
        });
    }

    function updateOrderStatus(orderId, nextStatus, $btn, isModal = false) {
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');

        $.post(wpcData.ajax_url, {
            action: 'wpc_update_workflow_status',
            nonce: wpcData.nonce,
            order_id: orderId,
            workflow_status: nextStatus
        }, function(response) {
            if (response.success) {
                if (isModal) {
                    $('#wpc-modal-actions').html('');
                    checkNextOrderForModal(orderId);
                } else {
                    if (autoOpen) {
                        openNextOrder(orderId);
                    } else {
                        checkNextOrder(orderId);
                    }
                }
                loadQueue(false);
                loadStats();
            }
        });
    }

    // Modal Specific Logic
    function openQuickProcessModal(orderId) {
        currentModalOrderId = orderId;
        const modalEl = document.getElementById('wpc-quick-process-modal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        $('#wpc-modal-order-id').text('Order #' + orderId);
        $('#wpc-modal-loader').show();
        $('#wpc-modal-content').hide();
        $('#wpc-modal-next-step').hide();

        modal.show();
        loadOrderDetailsIntoModal(orderId);
    }

    function loadOrderDetailsIntoModal(orderId) {
        $.post(wpcData.ajax_url, {
            action: 'wpc_get_order_details',
            nonce: wpcData.nonce,
            order_id: orderId
        }, function(response) {
            if (response.success) {
                const data = response.data;
                $('#wpc-modal-customer-name').text(data.customer_name);
                $('#wpc-modal-customer-email').text(data.customer_email);
                $('#wpc-modal-customer-phone').text(data.customer_phone || 'No phone');
                $('#wpc-modal-total').html(data.total);
                $('#wpc-modal-wc-status').text(data.wc_status_label);

                const wfs = data.workflow_status || 'new';
                $('#wpc-modal-workflow-status').text(wfs.charAt(0).toUpperCase() + wfs.slice(1)).attr('class', 'badge ' + getBadgeClass(wfs));

                $('#wpc-modal-view-wc').attr('href', data.edit_url);

                let itemsHtml = '';
                data.items.forEach(item => {
                    itemsHtml += `<div class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-bold">${item.name}</div>
                            <small class="text-muted">Quantity: ${item.qty}</small>
                        </div>
                        <div class="fw-bold">${item.subtotal}</div>
                    </div>`;
                });
                $('#wpc-modal-items-list').html(itemsHtml);

                $('#wpc-modal-actions').html(getActionButtons({workflow_status: wfs}, true));

                // Bind modal action buttons
                $('.wpc-modal-status-btn').on('click', function() {
                    const nextStatus = $(this).data('next');
                    updateOrderStatus(orderId, nextStatus, $(this), true);
                });

                $('#wpc-modal-loader').hide();
                $('#wpc-modal-content').fadeIn();
            }
        });
    }

    function checkNextOrderForModal(currentOrderId) {
        $.post(wpcData.ajax_url, {
            action: 'wpc_get_next_order',
            nonce: wpcData.nonce,
            status: currentFilter,
            search: currentSearch,
            sort: currentSort,
            date_filter: currentDateFilter,
            exclude_id: currentOrderId
        }, function(response) {
            if (response.success && response.data.order_id) {
                nextOrderId = response.data.order_id;
                $('#wpc-modal-next-id').text(nextOrderId);
                $('#wpc-modal-next-step').removeClass('bg-info').addClass('bg-success');
                $('#wpc-modal-next-message').text('✓ Order processed successfully!');
                $('#wpc-modal-btn-next').show();
                $('#wpc-modal-next-step').slideDown();
            } else {
                nextOrderId = 0;
                $('#wpc-modal-next-step').removeClass('bg-success').addClass('bg-info');
                $('#wpc-modal-next-message').text('🎉 All orders processed! No more orders in the queue.');
                $('#wpc-modal-btn-next').hide();
                $('#wpc-modal-next-step').slideDown();
            }
        });
    }

    $('#wpc-modal-btn-next').on('click', function() {
        if (nextOrderId) {
            $('#wpc-modal-content').fadeOut(200, function() {
                $('#wpc-modal-loader').show();
                $('#wpc-modal-next-step').hide();
                $('#wpc-modal-order-id').text('Order #' + nextOrderId);
                loadOrderDetailsIntoModal(nextOrderId);
            });
        }
    });

    function setupSelectionHandlers() {
        $('.wpc-order-select').on('change', function() {
            const $card = $(this).closest('.wpc-order-card');
            const id = $card.data('id');

            if ($(this).is(':checked')) {
                if (!selectedOrders.includes(id)) selectedOrders.push(id);
                $card.addClass('selected');
            } else {
                selectedOrders = selectedOrders.filter(o => o !== id);
                $card.removeClass('selected');
                $('#wpc-select-all').prop('checked', false);
            }
            updateBulkToolbar();
        });

        $('#wpc-select-all').off('change').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.wpc-order-select').prop('checked', isChecked).trigger('change');
        });
    }

    function updateBulkToolbar() {
        const count = selectedOrders.length;
        if (count > 0) {
            $('#wpc-bulk-toolbar').attr('style', 'display:flex !important;');
            $('#wpc-selected-count').text(count);
        } else {
            $('#wpc-bulk-toolbar').attr('style', 'display:none !important;');
        }
    }

    $('#wpc-apply-bulk').on('click', function() {
        const action = $('#wpc-bulk-action').val();
        if (!action) {
            alert('Please select a bulk action.');
            return;
        }

        if (!isPro) {
            alert('Bulk actions are a Pro feature. Please upgrade to unlock.');
            window.open(wpcData.upgrade_url, '_blank');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        $.post(wpcData.ajax_url, {
            action: 'wpc_bulk_update_workflow_status',
            nonce: wpcData.nonce,
            order_ids: selectedOrders,
            workflow_status: action
        }, function(response) {
            $btn.prop('disabled', false).text('Apply');
            if (response.success) {
                selectedOrders = [];
                $('#wpc-select-all').prop('checked', false);
                loadQueue(false);
                loadStats();
            } else {
                alert(response.data.message || 'Error processing bulk action.');
            }
        });
    });

    function checkNextOrder(currentOrderId) {
        $.post(wpcData.ajax_url, {
            action: 'wpc_get_next_order',
            nonce: wpcData.nonce,
            status: currentFilter,
            search: currentSearch,
            sort: currentSort,
            date_filter: currentDateFilter,
            exclude_id: currentOrderId
        }, function(response) {
            if (response.success && response.data.order_id) {
                nextOrderId = response.data.order_id;
                showNextOrderToast(nextOrderId);
            }
        });
    }

    function openNextOrder(currentOrderId) {
        $.post(wpcData.ajax_url, {
            action: 'wpc_get_next_order',
            nonce: wpcData.nonce,
            status: currentFilter,
            search: currentSearch,
            sort: currentSort,
            date_filter: currentDateFilter,
            exclude_id: currentOrderId
        }, function(response) {
            if (response.success && response.data.order_id) {
                const nextId = response.data.order_id;
                openQuickProcessModal(nextId);
            }
        });
    }

    function showNextOrderToast(id) {
        $('#wpc-next-order-text').text('Next Order: #' + id);
        const toastEl = document.querySelector('#wpc-next-order-toast .toast');
        const toast = new bootstrap.Toast(toastEl, { delay: 10000 });
        toast.show();
    }

    $('#wpc-open-next-order').on('click', function() {
        if (nextOrderId) {
            const toastEl = document.querySelector('#wpc-next-order-toast .toast');
            const toast = bootstrap.Toast.getInstance(toastEl);
            if (toast) toast.hide();
            openQuickProcessModal(nextOrderId);
        }
    });

    function handleFreeLimit(data) {
        if (!data.is_pro && data.total_active > data.limit) {
            $('#wpc-free-total').text(data.total_active);
            $('#wpc-free-limit-card').show();
        } else {
            $('#wpc-free-limit-card').hide();
        }
    }

    // Filters & Sorting
    $('#wpc-workflow-date-filter').on('change', function() {
        currentDateFilter = $(this).val();
        loadQueue();
        loadStats();
    });

    $('#wpc-workflow-status-filter').on('change', function() {
        currentFilter = $(this).val();
        loadQueue();
    });

    $('#wpc-workflow-sort').on('change', function() {
        currentSort = $(this).val();
        loadQueue();
    });

    $('#wpc-auto-open-toggle').on('change', function() {
        autoOpen = $(this).is(':checked');
        localStorage.setItem(autoOpenKey, autoOpen ? '1' : '0');
    });

    $('#wpc-workflow-refresh').on('click', function() {
        loadQueue();
        loadStats();
    });

    let searchTimeout;
    $('#wpc-workflow-search').on('input', function() {
        clearTimeout(searchTimeout);
        currentSearch = $(this).val();
        searchTimeout = setTimeout(loadQueue, 500);
    });

    $('.wpc-workflow-stat-card').on('click', function() {
        currentFilter = $(this).data('status');
        if (currentFilter === 'high_priority') {
            $('#wpc-workflow-status-filter').val('high_priority');
        } else {
            $('#wpc-workflow-status-filter').val(currentFilter);
        }
        loadQueue();
    });

    init();
});
