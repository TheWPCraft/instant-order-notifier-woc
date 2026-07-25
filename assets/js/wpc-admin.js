jQuery(document).ready(function ($) {
  const audio = new Audio(wpcData.audio);
  audio.volume = 0.7;

  const tableBody = $("#wpc-orders-table tbody");
  const reloadBtn = $("#wpc-reload-btn");
  const pauseBtn = $("#wpc-pause-btn");
  const soundBtn = $("#wpc-sound-btn");
  const popupText = $("#wpc-popup-text");
  const popupOk = $("#wpc-popup-ok");
  const unmuteReminder = $("#wpc-unmute-reminder");
  const fullOverlay = $("#wpc-full-unmute-overlay");

  const savedSound = localStorage.getItem("wpc_sound_enabled");

  let currentStatus = "";
  let lastSeenId = parseInt(wpcData.last_seen_order_id) || 0;
  let currentNewOrderId = 0;
  let repeatInterval = null;
  let currentPage = 1;
  let perPage = 10;
  let isPaused = false;
  let isSoundEnabled = false;
  let isAudioUnlocked = false;

  // Load saved sound preference
  if (savedSound === "1") {
    isSoundEnabled = true;
  }

  let savedPerPage = localStorage.getItem("wpc_per_page");
  if (savedPerPage) {
    perPage = parseInt(savedPerPage);
    $("#wpc-per-page").val(perPage);
  }

  $("#wpc-per-page").on("change", function () {
    perPage = parseInt($(this).val());
    localStorage.setItem("wpc_per_page", perPage);
    currentPage = 1;
    refreshTable(1, true);
  });

  const settings = wpcData.settings || {
    notification_enabled: "1",
    ringtone: "1",
    check_speed: "normal",
    desktop_notifications_enabled: "0",
  };

  const tip = $("#desktop-notification-tip");
  const checkbox = $("#desktop_notifications_enabled");

  if (checkbox.is(":checked")) {
    tip.show();
  }

  checkbox.on("change", function () {
    if ($(this).is(":checked")) {
      tip.slideDown();
    } else {
      tip.slideUp();
    }
  });

  if (
    settings.notification_enabled === "1" &&
    settings.desktop_notifications_enabled === "1" &&
    Notification.permission !== "granted"
  ) {
    Notification.requestPermission();
  }

  const statusColors = {
    processing: { bg: "#d1e7dd", text: "#0f5132" },
    completed: { bg: "#d4edda", text: "#155724" },
    "on-hold": { bg: "#fff3cd", text: "#856404" },
    pending: { bg: "#fff3cd", text: "#856404" },
    cancelled: { bg: "#f8d7da", text: "#721c24" },
    refunded: { bg: "#d1ecf1", text: "#0c5460" },
    failed: { bg: "#f8d7da", text: "#721c24" },
  };

  const isNotificationPage = $("#wpc-orders-table").length > 0;
  const isSettingsPage = $(".play-sound").length > 0;

  $("#wpc-status-filter").on("change", function () {
    currentStatus = $(this).val();
    currentPage = 1;
    refreshTable(1, true);
  });

  // ────────────────────────────────────────────────
  // Overlay & Reminder Functions
  // ────────────────────────────────────────────────

  function showFullUnmuteOverlay() {
    if (!isSoundEnabled && settings.notification_enabled === "1") {
      fullOverlay.addClass("visible");
    }
  }

  function hideFullUnmuteOverlay() {
    fullOverlay.removeClass("visible");
  }

  function showUnmuteReminder() {
    if (settings.notification_enabled !== "1") return;
    unmuteReminder.css({
      opacity: "1",
      transform: "translateY(0) scale(1)",
      display: "inline-block",
    });
    unmuteReminder.addClass("animate__animated animate__pulse animate__infinite");
  }

  function hideUnmuteReminder() {
    unmuteReminder.css({
      opacity: "0",
      transform: "translateY(10px) scale(0.9)",
    });
    unmuteReminder.removeClass("animate__animated animate__pulse animate__infinite");
    setTimeout(() => {
      unmuteReminder.css("display", "none");
    }, 400);
  }

  function updateUnmuteReminder() {
    if (!isSoundEnabled && settings.notification_enabled === "1") {
      showUnmuteReminder();
    } else {
      hideUnmuteReminder();
    }
  }

  // ────────────────────────────────────────────────
  // Audio Unlock
  // ────────────────────────────────────────────────

  function unlockAudio() {
    if (!isAudioUnlocked && isNotificationPage) {
      audio.play().catch(() => {}).then(() => {
        audio.pause();
        audio.currentTime = 0;
        isAudioUnlocked = true;
      });
    }
  }

  // Initial unlock if sound was enabled
  if (isSoundEnabled) {
    setTimeout(() => {
      unlockAudio();
    }, 500);
  }

  // ────────────────────────────────────────────────
  // Popup Management (Single Popup - Important Fix)
  // ────────────────────────────────────────────────

  let currentModalInstance = null;

  function showNewOrderPopup(orderData) {
    // Close any existing popup first
    if (currentModalInstance) {
      currentModalInstance.hide();
      currentModalInstance = null;
    }

    popupText.html(`
      Order #${orderData.id}<br>
      <strong>${orderData.name}</strong>
    `);

    const modalEl = document.getElementById("wpc-popup-modal");
    currentModalInstance = new bootstrap.Modal(modalEl, {
      backdrop: "static",
      keyboard: false,
    });

    currentModalInstance.show();

    // Play sound only if enabled and unlocked
    // if (isSoundEnabled && isAudioUnlocked) {
    //   audio.src = wpcData.audio_urls[settings.ringtone] || wpcData.audio;
    //   audio.currentTime = 0;
    //   audio.play().catch(() => {});

    //   if (repeatInterval) clearInterval(repeatInterval);
    //   repeatInterval = setInterval(() => {
    //     audio.currentTime = 0;
    //     audio.play().catch(() => {});
    //   }, 4000);
    // }
    if (isSoundEnabled && isAudioUnlocked) {
      const soundUrl = wpcData.audio_urls[settings.ringtone] || wpcData.audio;
      audio.src = soundUrl;
      audio.currentTime = 0;
      audio.play().catch(() => {});

      if (repeatInterval) clearInterval(repeatInterval);
      repeatInterval = setInterval(() => {
        audio.currentTime = 0;
        audio.play().catch(() => {});
      }, 4000);
    }

    updateUnmuteReminder();
  }

  // Popup OK Button
  if (popupOk.length) {
    popupOk.on("click", function () {
      if (currentModalInstance) {
        currentModalInstance.hide();
        currentModalInstance = null;
      }

      if (repeatInterval) {
        clearInterval(repeatInterval);
        repeatInterval = null;
      }

      lastSeenId = currentNewOrderId;
      currentNewOrderId = 0;

      $.post(wpcData.ajax_url, {
        action: "wpc_mark_order_seen",
        nonce: wpcData.nonce,
        order_id: lastSeenId,
      });
    });
  }

  // Close modal when hidden (cleanup)
  $('#wpc-popup-modal').on('hidden.bs.modal', function () {
    if (repeatInterval) {
      clearInterval(repeatInterval);
      repeatInterval = null;
    }
    currentModalInstance = null;
  });

  // ────────────────────────────────────────────────
  // Auto Refresh & New Order Detection
  // ────────────────────────────────────────────────

  if (isNotificationPage) {
    if (settings.notification_enabled !== "1") {
      isSoundEnabled = false;
      isPaused = true;
      soundBtn.html('<i class="bi bi-volume-mute-fill fs-5"></i>').attr("title", "Notifications Disabled");
      pauseBtn.html('<i class="bi bi-play-fill fs-5"></i>').attr("title", "Paused");
    }

    let checkInterval = 2000;
    if (settings.check_speed === "fast") checkInterval = 1000;
    else if (settings.check_speed === "slow") checkInterval = 6000;

    window.wpcAutoRefreshInterval = setInterval(function () {
      if (!isPaused && settings.notification_enabled === "1") {
        refreshTable(currentPage, false);
        loadDashboardStats();

        $.post(wpcData.ajax_url, {
          action: "wpc_check_new_order",
          nonce: wpcData.nonce,
        })
          .done(function (response) {
            if (
              response.success &&
              response.data &&
              response.data.id > lastSeenId
            ) {
              if (currentNewOrderId !== response.data.id) {
                currentNewOrderId = response.data.id;

                // Show only one popup at a time
                showNewOrderPopup(response.data);

                // Desktop Notification
                if (
                  settings.notification_enabled === "1" &&
                  settings.desktop_notifications_enabled === "1" &&
                  Notification.permission === "granted"
                ) {
                  new Notification(`New Order #${response.data.id}`, {
                    body: `Customer: ${response.data.name}\nTotal: ${response.data.total || "₹0"}`,
                  });
                }

                refreshTable(1, false);
              }
            }
          })
          .fail(function () {
            console.log("New order check failed");
          });
      }
    }, checkInterval);
  }

  // ────────────────────────────────────────────────
  // Sound Button Logic
  // ────────────────────────────────────────────────

  if (soundBtn.length) {
    soundBtn.on("click", function () {
      if (settings.notification_enabled !== "1") {
        alert("Please enable notifications in Settings first!");
        return;
      }

      unlockAudio();

      isSoundEnabled = !isSoundEnabled;
      localStorage.setItem("wpc_sound_enabled", isSoundEnabled ? "1" : "0");

      if (isSoundEnabled) {
        soundBtn.html('<i class="bi bi-volume-up-fill fs-5"></i>').attr("title", "Sound On");
        hideUnmuteReminder();
        hideFullUnmuteOverlay();

        audio.currentTime = 0;
        audio.play().catch(() => {});
      } else {
        soundBtn.html('<i class="bi bi-volume-mute-fill fs-5"></i>').attr("title", "Sound Off");
        showUnmuteReminder();
        showFullUnmuteOverlay();

        if (repeatInterval) {
          clearInterval(repeatInterval);
          repeatInterval = null;
        }
      }
    });
  }

  // Initial Sound Button & Overlay State
  if (settings.notification_enabled === "1") {
    if (isSoundEnabled) {
      soundBtn.html('<i class="bi bi-volume-up-fill fs-5"></i>').attr("title", "Sound On");
      hideUnmuteReminder();
      hideFullUnmuteOverlay();
    } else {
      soundBtn.html('<i class="bi bi-volume-mute-fill fs-5"></i>').attr("title", "Sound Off");
      showFullUnmuteOverlay();
      showUnmuteReminder();
    }
  }

  // ────────────────────────────────────────────────
  // Settings Page Sound Preview
  // ────────────────────────────────────────────────

  // if (isSettingsPage) {
  //   $(".play-sound").on("click", function () {
  //     const soundNum = $(this).data("sound");
  //     const soundUrl = wpcData.audio_urls[soundNum];
  //     if (soundUrl) {
  //       const previewAudio = new Audio(soundUrl);
  //       previewAudio.volume = 0.7;
  //       previewAudio.play().catch((e) => console.log("Audio preview failed:", e));
  //     }
  //   });
  // }

    // ────────────────────────────────────────────────
  // Settings Page Sound Preview
  // ────────────────────────────────────────────────

  if (isSettingsPage) {
    $(".play-sound").on("click", function () {
      const soundNum = $(this).data("sound");
      let soundUrl = wpcData.audio_urls[soundNum];

      if (soundUrl) {
        const previewAudio = new Audio(soundUrl);
        previewAudio.volume = 0.7;
        previewAudio.play().catch((e) => console.log("Preview failed:", e));
      }
    });

    // Handle Multiple File Selection Validation
    $("#custom_ringtones").on("change", function () {
        if (this.files.length > 2) {
            alert("You can only upload a maximum of 2 files at a time.");
            $(this).val('');
        }
    });

    // Remove Custom Sound Button - FIXED
    $("#remove_custom_sound, #remove_custom_sound_2").off("click").on("click", function () {
      const isSecond = $(this).attr('id') === 'remove_custom_sound_2';
      const slotName = isSecond ? "Sound 2" : "Sound 1";

      if (confirm(`Are you sure you want to remove your custom notification ${slotName}?`)) {
        const $form = $(this).closest('form');

        // Set the hidden field value
        if (isSecond) {
          $("#remove_custom_sound_2_input").val('1');
        } else {
          $("#remove_custom_sound_input").val('1');
        }

        // Ensure wpc_save_settings is present in POST data
        if ($form.find('input[name="wpc_save_settings"]').length === 0) {
            $('<input>').attr({
                type: 'hidden',
                name: 'wpc_save_settings',
                value: '1'
            }).appendTo($form);
        }

        $form.submit();
      }
    });
  }

  // ────────────────────────────────────────────────
  // Table & Other Functions (unchanged)
  // ────────────────────────────────────────────────

  function buildTableRows(orders) {
    if (orders.length === 0) {
      tableBody.html('<tr><td colspan="6" class="text-center py-2 text-muted">No orders yet</td></tr>');
      return;
    }

    let rows = "";
    orders.forEach(function (o) {
      const statusInfo = statusColors[o.status] || { bg: "#6c757d", text: "#ffffff" };
      const statusText = o.status.charAt(0).toUpperCase() + o.status.slice(1).replace("-", " ");

      rows += '<tr data-order-id="' + o.order_id + '">';
      rows += "<td><strong>#" + o.order_id + "</strong></td>";
      rows += "<td>" + o.customer_name + "</td>";
      rows += "<td><strong>" + o.total + "</strong></td>";
      rows += '<td><span class="status-badge status-' + o.status + '" style="background:' + statusInfo.bg + " !important; color:" + statusInfo.text + ' !important; padding:4px 8px; border-radius:4px; font-size:0.85em;">' + statusText + "</span></td>";
      rows += "<td>" + o.created_at + "</td>";
      rows += '<td><a href="' + o.edit_url + '" class="btn view" style="margin-right:5px;" target="_blank"><i class="bi bi-eye me-1"></i> View</a><button class="btn wpc-mark-read" data-id="' + o.order_id + '"><i class="bi bi-check2-all me-1"></i> Mark Read</button></td>';
      rows += "</tr>";
    });

    tableBody.html(rows);
    setupMarkReadButtons();

    reloadBtn.find("i").removeClass("bi-arrow-repeat").addClass("bi-arrow-clockwise");
  }

  function loadDashboardStats() {
    $.post(wpcData.ajax_url, {
      action: "wpc_get_dashboard_stats",
      nonce: wpcData.nonce,
    })
      .done(function (response) {
        if (response.success) {
          $("#wpc-today-orders").text(response.data.today);
          $("#wpc-processing-orders").text(response.data.processing);
          $("#wpc-completed-orders").text(response.data.completed);
          $("#wpc-cancelled-orders").text(response.data.cancelled);
        }
      })
      .fail(function () {
        console.log("Dashboard load failed");
      });
  }

  function setupMarkReadButtons() {
    $(".wpc-mark-read")
      .off("click")
      .on("click", function () {
        const btn = $(this);
        const orderId = btn.data("id");
        const row = btn.closest("tr");

        btn.prop("disabled", true).html('<i class="bi bi-hourglass-split me-1"></i> Removing...');

        $.post(wpcData.ajax_url, {
          action: "wpc_delete_order",
          order_id: orderId,
          nonce: wpcData.nonce,
        })
          .done(function (response) {
            if (response.success) {
              row.fadeOut(400, function () {
                row.remove();
              });
            } else {
              btn.prop("disabled", false).html('<i class="bi bi-check2-all me-1"></i> Mark Read');
            }
          })
          .fail(function () {
            btn.prop("disabled", false).html('<i class="bi bi-check2-all me-1"></i> Mark Read');
          });
      });
  }

  function refreshTable(page = 1, showLoading = false) {
    currentPage = page;

    if (showLoading && tableBody.length) {
      tableBody.html(`
        <tr>
          <td colspan="6" class="text-center py-3">
            <div class="spinner-border text-theme" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 mb-0 text-muted">Loading orders...</p>
          </td>
        </tr>
      `);
    }

    $.post(wpcData.ajax_url, {
      action: "wpc_get_orders_json",
      page: page,
      nonce: wpcData.nonce,
      per_page: perPage,
      status: currentStatus,
    })
      .done(function (response) {
        if (response.success && isNotificationPage) {
          buildTableRows(response.data.orders);
          updatePagination(response.data.total);
        } else if (showLoading) {
          tableBody.html('<tr><td colspan="6" class="text-center py-5 text-danger">Error loading orders</td></tr>');
        }
      })
      .fail(function () {
        if (showLoading) {
          tableBody.html('<tr><td colspan="6" class="text-center py-5 text-danger">Server error</td></tr>');
        }
      });
  }

  function updatePagination(total) {
    const totalPages = Math.ceil(total / perPage);

    if (totalPages <= 1) {
      $("#wpc-pagination").hide();
      return;
    }

    let pagination = '<nav><ul class="pagination justify-content-center">';

    pagination += `
      <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${currentPage - 1}"><i class="bi bi-arrow-left"></i></a>
      </li>
    `;

    for (let i = 1; i <= totalPages; i++) {
      pagination += `
        <li class="page-item ${i === currentPage ? "active" : ""}">
          <a class="page-link" href="#" data-page="${i}">${i}</a>
        </li>
      `;
    }

    pagination += `
      <li class="page-item ${currentPage === totalPages ? "disabled" : ""}">
        <a class="page-link" href="#" data-page="${currentPage + 1}"><i class="bi bi-arrow-right"></i></a>
      </li>
    `;

    pagination += "</ul></nav>";

    $("#wpc-pagination").html(pagination).show();

    $("#wpc-pagination .page-link").on("click", function (e) {
      e.preventDefault();
      const page = $(this).data("page");
      if (page >= 1 && page <= totalPages) {
        refreshTable(page, true);
      }
    });
  }

  if (isNotificationPage) {
    refreshTable(1, false);
    loadDashboardStats();
  }

  if (reloadBtn.length) {
    reloadBtn.on("click", function () {
      unlockAudio();
      const icon = reloadBtn.find("i");
      icon.removeClass("bi-arrow-clockwise").addClass("bi-arrow-repeat");
      refreshTable(currentPage, true);
      setTimeout(() => icon.removeClass("bi-arrow-repeat").addClass("bi-arrow-clockwise"), 1500);
    });
  }

  if (pauseBtn.length) {
    pauseBtn.on("click", function () {
      isPaused = !isPaused;
      if (isPaused) {
        pauseBtn.html('<i class="bi bi-play-fill fs-5"></i>').attr("title", "Resume");
      } else {
        pauseBtn.html('<i class="bi bi-pause-fill fs-5"></i>').attr("title", "Pause");
      }
    });
  }

  $(window).on("beforeunload", function () {
    if (window.wpcAutoRefreshInterval) {
      clearInterval(window.wpcAutoRefreshInterval);
    }
  });

  $("#toplevel_page_woc-order-notification .wp-menu-image.dashicons-bell")
    .parent()
    .addClass("wpc-bell-with-extra-class");
  $("#toplevel_page_woc-order-notification .wp-menu-image").addClass("wpc-custom-bell-icon");

  // Dashboard card click filter
  $(".wpc-card-filter").on("click", function () {
    const status = $(this).data("status");
    currentStatus = status || "";
    $("#wpc-status-filter").val(currentStatus);
    currentPage = 1;
    refreshTable(1, true);

    $("html, body").animate({
      scrollTop: $("#wpc-orders-table").offset().top - 100,
    }, 400);
  });
});
