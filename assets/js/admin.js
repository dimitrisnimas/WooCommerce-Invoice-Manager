jQuery(document).ready(function ($) {
  "use strict";

  // Debug button click handler
  $("#debug-upload").on("click", function () {
    $.ajax({
      url: wcInvoiceManager.ajaxurl,
      type: "POST",
      data: {
        action: "debug_upload",
      },
      success: function (response) {
        console.log("Debug response:", response);
        alert("Debug data logged to console and server logs");
      },
      error: function (xhr, status, error) {
        console.log("Debug error:", error);
        alert("Debug error: " + error);
      },
    });
  });

  // Order selection change handler
  $("#order_id").on("change", function () {
    var selectedOption = $(this).find("option:selected");

    if (selectedOption.val()) {
      var customerName = selectedOption.data("customer");
      var customerEmail = selectedOption.data("email");
      var total = selectedOption.data("total");
      var status = selectedOption.data("status");
      var date = selectedOption.data("date");

      var details =
        "<strong>Πελάτης:</strong> " +
        customerName +
        "<br>" +
        "<strong>Email:</strong> " +
        customerEmail +
        "<br>" +
        "<strong>Σύνολο:</strong> " +
        total +
        "<br>" +
        "<strong>Κατάσταση:</strong> " +
        status +
        "<br>" +
        "<strong>Ημερομηνία:</strong> " +
        date;

      $("#order-details").html(details);
      $("#order-details-row").show().addClass("show");
    } else {
      $("#order-details-row").hide().removeClass("show");
    }
  });

  // File upload form submission
  $("#invoice-upload-form").on("submit", function (e) {
    e.preventDefault();

    console.log("Form submission started");

    // Validate form
    var orderId = $("#order_id").val();
    var fileInput = $("#invoice_file")[0];

    if (!orderId) {
      alert("Παρακαλώ επιλέξτε μια παραγγελία");
      return;
    }

    if (!fileInput.files.length) {
      alert("Παρακαλώ επιλέξτε ένα αρχείο");
      return;
    }

    var formData = new FormData(this);
    formData.append("action", "upload_invoice");

    console.log("Form data prepared:", formData);

    $.ajax({
      url: wcInvoiceManager.ajaxurl,
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      beforeSend: function () {
        console.log("AJAX request starting");
        showLoadingOverlay();
        $('input[type="submit"]').prop("disabled", true).val("Ανέβασμα...");
      },
      success: function (response) {
        console.log("AJAX success response:", response);
        hideLoadingOverlay();
        $('input[type="submit"]')
          .prop("disabled", false)
          .val("Ανέβασμα Παραστατικού");

        if (response.success) {
          showMessage(response.data, "success");
          $("#invoice-upload-form")[0].reset();
          $("#order-details-row").hide().removeClass("show");
          // Reload the invoices list
          setTimeout(function () {
            location.reload();
          }, 2000);
        } else {
          showMessage(response.data, "error");
        }
      },
      error: function (xhr, status, error) {
        console.log("AJAX error:", {
          xhr: xhr,
          status: status,
          error: error,
          responseText: xhr.responseText,
        });

        hideLoadingOverlay();
        $('input[type="submit"]')
          .prop("disabled", false)
          .val("Ανέβασμα Παραστατικού");

        var errorMessage = "Σφάλμα κατά το ανέβασμα";

        // Try to parse error response
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        } else if (xhr.responseText) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.data) {
              errorMessage = response.data;
            }
          } catch (e) {
            // Use default error message
            console.log("Could not parse error response:", e);
          }
        }

        showMessage(errorMessage, "error");
      },
    });
  });

  // Send invoice functionality
  $(document).on("click", ".send-invoice-btn", function (e) {
    e.preventDefault();

    var invoiceId = $(this).data("invoice-id");
    var button = $(this);

    if (
      !confirm("Είστε σίγουροι ότι θέλετε να στείλετε αυτό το παραστατικό;")
    ) {
      return;
    }

    $.ajax({
      url: wcInvoiceManager.ajaxurl,
      type: "POST",
      data: {
        action: "send_invoice",
        invoice_id: invoiceId,
        send_nonce: wcInvoiceManager.sendNonce,
      },
      beforeSend: function () {
        button.prop("disabled", true).text("Αποστολή...");
      },
      success: function (response) {
        button.prop("disabled", false);

        if (response.success) {
          showMessage(response.data, "success");
          // Update the button to show sent status
          var sentDate =
            new Date().toLocaleDateString("el-GR") +
            " " +
            new Date().toLocaleTimeString("el-GR");
          button
            .parent()
            .html('<span class="sent-date">' + sentDate + "</span>");
          button
            .closest("tr")
            .find(".status-pending")
            .removeClass("status-pending")
            .addClass("status-sent")
            .text("Απεστάλη");
        } else {
          showMessage(response.data, "error");
          button.text("Αποστολή");
        }
      },
      error: function (xhr, status, error) {
        button.prop("disabled", false).text("Αποστολή");

        var errorMessage = "Σφάλμα κατά την αποστολή";

        // Try to parse error response
        if (xhr.responseJSON && xhr.responseJSON.data) {
          errorMessage = xhr.responseJSON.data;
        } else if (xhr.responseText) {
          try {
            var response = JSON.parse(xhr.responseText);
            if (response.data) {
              errorMessage = response.data;
            }
          } catch (e) {
            // Use default error message
          }
        }

        showMessage(errorMessage, "error");
      },
    });
  });

  // File input change handler
  $("#invoice_file").on("change", function () {
    var file = this.files[0];
    var maxSize = 10 * 1024 * 1024; // 10MB

    if (file && file.size > maxSize) {
      alert("Το αρχείο είναι πολύ μεγάλο. Μέγιστο μέγεθος: 10MB");
      this.value = "";
      return;
    }

    if (file) {
      var fileName = file.name;
      var ext = fileName.split('.').pop().toLowerCase();

      if (ext !== "pdf") {
        alert("Μόνο αρχεία PDF επιτρέπονται");
        this.value = "";
        return;
      }
    }
  });

  // Helper functions
  function showLoadingOverlay() {
    if ($("#loading-overlay").length === 0) {
      $("body").append(
        '<div id="loading-overlay"><div class="loading-spinner"></div></div>'
      );
    }
    $("#loading-overlay").show();
  }

  function hideLoadingOverlay() {
    $("#loading-overlay").hide();
  }

  function showMessage(message, type) {
    var messageClass = "notice notice-" + type;
    var messageHtml =
      '<div class="' + messageClass + '"><p>' + message + "</p></div>";

    // Remove existing messages
    $(".wc-invoice-manager-admin .notice").remove();

    // Add new message
    $(".wc-invoice-manager-admin").prepend(messageHtml);

    // Auto-hide success messages after 5 seconds
    if (type === "success") {
      setTimeout(function () {
        $(".wc-invoice-manager-admin .notice-success").fadeOut();
      }, 5000);
    }

    // Scroll to top
    $("html, body").animate({ scrollTop: 0 }, 500);
  }

  // Initialize order details if an order is already selected
  if ($("#order_id").val()) {
    $("#order_id").trigger("change");
  }

  // Auto-refresh invoices list every 30 seconds if there are pending invoices
  setInterval(function () {
    var pendingInvoices = $(".status-pending").length;
    if (pendingInvoices > 0) {
      // Could implement auto-refresh here if needed
    }
  }, 30000);
});
