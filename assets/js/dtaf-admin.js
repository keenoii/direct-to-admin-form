/**
 * Admin JavaScript for Direct to Admin Form
 */
;(($) => {
  // Ensure ajaxurl is defined (WordPress should define it)
  if (typeof ajaxurl === "undefined") {
    console.error("ajaxurl is not defined. Ensure it is properly enqueued by WordPress.")
    return // Stop execution if ajaxurl is not defined
  }

  // Ensure dtaf_admin is defined (should be localized by WordPress)
  if (typeof dtaf_admin === "undefined") {
    console.error("dtaf_admin is not defined. Ensure it is properly localized by WordPress.")
    return // Stop execution if dtaf_admin is not defined
  }

  $(document).ready(() => {
    // Modal functionality
    var modal = $("#dtaf-submission-details-modal")
    var modalClose = $(".dtaf-modal-close")

    // Open modal when clicking "View Modal" link
    $(document).on("click", ".dtaf-view-details-modal", function (e) {
      e.preventDefault()
      var submissionId = $(this).data("id")

      // Show loading state
      modal.show()
      $("#dtaf-modal-body").html(
        '<div class="dtaf-loading"><span class="spinner is-active"></span> กำลังโหลดข้อมูล...</div>',
      )

      // Fetch submission details via AJAX
      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "dtaf_ajax_get_submission_details",
          submission_id: submissionId,
          nonce: dtaf_admin.nonce,
        },
        success: (response) => {
          if (response.success) {
            $("#dtaf-modal-body").html(response.data.html)
          } else {
            $("#dtaf-modal-body").html('<div class="notice notice-error"><p>' + response.data.message + "</p></div>")
          }
        },
        error: () => {
          $("#dtaf-modal-body").html('<div class="notice notice-error"><p>เกิดข้อผิดพลาดในการโหลดข้อมูล</p></div>')
        },
      })
    })

    // Close modal when clicking X or outside the modal
    modalClose.on("click", () => {
      modal.hide()
    })

    $(window).on("click", (e) => {
      if ($(e.target).is(modal)) {
        modal.hide()
      }
    })

    // AJAX status update from detail page
    $(document).on("submit", "#dtaf-update-status-form", function (e) {
      e.preventDefault()

      var form = $(this)
      var submitButton = form.find('button[type="submit"]')
      var originalButtonText = submitButton.text()

      // Show loading state
      submitButton.prop("disabled", true).text("กำลังบันทึก...")

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "dtaf_ajax_update_submission_status",
          submission_id: form.find('input[name="submission_id"]').val(),
          status: form.find("#dtaf_status").val(),
          admin_notes: form.find("#dtaf_admin_notes").val(),
          notify_submitter: form.find("#dtaf_notify_submitter").is(":checked") ? 1 : 0,
          nonce: form.find("#dtaf_status_nonce").val(),
        },
        success: (response) => {
          if (response.success) {
            // Show success message
            $("#dtaf-ajax-message")
              .removeClass("notice-error")
              .addClass("notice-success")
              .html("<p>" + response.data.message + "</p>")
              .show()
              .delay(3000)
              .fadeOut()

            // Update status history if provided
            if (response.data.history_html) {
              $('.postbox:contains("ประวัติการเปลี่ยนแปลงสถานะ") .inside').html(response.data.history_html)
            }
          } else {
            // Show error message
            $("#dtaf-ajax-message")
              .removeClass("notice-success")
              .addClass("notice-error")
              .html("<p>" + response.data.message + "</p>")
              .show()
          }

          // Reset button state
          submitButton.prop("disabled", false).text(originalButtonText)
        },
        error: () => {
          $("#dtaf-ajax-message")
            .removeClass("notice-success")
            .addClass("notice-error")
            .html("<p>เกิดข้อผิดพลาดในการส่งข้อมูล</p>")
            .show()

          submitButton.prop("disabled", false).text(originalButtonText)
        },
      })
    })

    // Delete submission confirmation
    $(document).on("click", ".dtaf-delete-submission", function (e) {
      e.preventDefault()

      var submissionId = $(this).data("id")
      var nonce = $(this).data("nonce")
      var row = $("#submission-" + submissionId)

      if (confirm(dtaf_admin.messages.confirm_delete)) {
        // Show loading state
        $(this).text(dtaf_admin.messages.loading)

        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "dtaf_ajax_delete_submission",
            submission_id: submissionId,
            nonce: nonce,
          },
          success: (response) => {
            if (response.success) {
              // If we're on the detail page, redirect to list
              if (window.location.href.indexOf("action=view") !== -1) {
                window.location.href = response.data.redirect_url
                return
              }

              // Remove row with animation
              row.css("background-color", "#f8d7da").fadeOut(400, function () {
                $(this).remove()

                // Show message if table is now empty
                if ($(".dtaf-submissions-table tbody tr").length === 0) {
                  $(".dtaf-submissions-table tbody").append(
                    '<tr class="no-items"><td class="colspanchange" colspan="8">ไม่พบข้อมูลการร้องเรียนที่ตรงกับเงื่อนไข</td></tr>',
                  )
                }
              })

              // Show success message
              $("#dtaf-ajax-message")
                .removeClass("notice-error")
                .addClass("notice-success")
                .html("<p>" + response.data.message + "</p>")
                .show()
                .delay(3000)
                .fadeOut()
            } else {
              // Show error message
              $("#dtaf-ajax-message")
                .removeClass("notice-success")
                .addClass("notice-error")
                .html("<p>" + response.data.message + "</p>")
                .show()
            }
          },
          error: () => {
            $("#dtaf-ajax-message")
              .removeClass("notice-success")
              .addClass("notice-error")
              .html("<p>เกิดข้อผิดพลาดในการลบข้อมูล</p>")
              .show()
          },
        })
      }
    })
    

    // Delete form confirmation
    $(document).on("click", ".dtaf-delete-form", function (e) {
      e.preventDefault()

      var formId = $(this).data("id")
      var nonce = $(this).data("nonce")
      var row = $("#form-row-" + formId)

      if (confirm(dtaf_admin.messages.confirm_delete)) {
        // Show loading state
        $(this).text(dtaf_admin.messages.loading)

        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "dtaf_ajax_delete_form",
            form_id: formId,
            nonce: nonce,
          },
          success: (response) => {
            if (response.success) {
              // Remove row with animation
              row.css("background-color", "#f8d7da").fadeOut(400, function () {
                $(this).remove()

                // Show message if table is now empty
                if ($(".dtaf-form-manager-table tbody tr").length === 0) {
                  $(".dtaf-form-manager-table tbody").append(
                    '<tr><td colspan="4">ยังไม่มีแบบฟอร์มที่สร้างเอง หรือมีเพียงฟอร์มค่าเริ่มต้น</td></tr>',
                  )
                }
              })

              // Show success message
              $("#dtaf-ajax-message")
                .removeClass("notice-error")
                .addClass("notice-success")
                .html("<p>" + response.data.message + "</p>")
                .show()
                .delay(3000)
                .fadeOut()
            } else {
              // Show error message
              $("#dtaf-ajax-message")
                .removeClass("notice-success")
                .addClass("notice-error")
                .html("<p>" + response.data.message + "</p>")
                .show()
            }
          },
          error: () => {
            $("#dtaf-ajax-message")
              .removeClass("notice-success")
              .addClass("notice-error")
              .html("<p>เกิดข้อผิดพลาดในการลบแบบฟอร์ม</p>")
              .show()
          },
        })
      }
    })

    // Copy shortcode to clipboard
    $(document).on("click", ".dtaf-copy-shortcode", function () {
      var shortcode = $(this).data("shortcode") || $(this).text()
      var tempInput = $("<input>")
      $("body").append(tempInput)
      tempInput.val(shortcode).select()
      document.execCommand("copy")
      tempInput.remove()

      // Visual feedback
      var $this = $(this)
      $this.addClass("copied")

      // Show tooltip
      var originalText = $this.text()
      $this.text("คัดลอกแล้ว!")

      setTimeout(() => {
        $this.text(originalText)
        $this.removeClass("copied")
      }, 1500)
    })

    // Quick edit status
    $(document).on("click", ".dtaf-quick-edit-status", function (e) {
      e.preventDefault()

      var $this = $(this)
      var submissionId = $this.data("id")
      var currentStatus = $this.closest("td").find(".dtaf-status-text").text()

      // Create and show the quick edit form
      var $form = $('<form class="dtaf-quick-edit-form"></form>')
      var $select = $('<select name="quick_status"></select>')

      // Add status options
      var statuses = ["ใหม่", "กำลังดำเนินการ", "รอข้อมูลเพิ่มเติม", "แก้ไขแล้ว", "ปิดเรื่อง"]

      $.each(statuses, (i, status) => {
        $select.append($("<option></option>").val(status).text(status))
      })

      $select.val(currentStatus)

      $form.append($select)
      $form.append('<button type="submit" class="button button-small">บันทึก</button>')
      $form.append('<button type="button" class="button button-small dtaf-quick-edit-cancel">ยกเลิก</button>')

      // Replace the status text with the form
      $this.closest("td").find(".dtaf-status-text").hide().after($form)
      $this.hide()

      // Handle form submission
      $form.on("submit", (e) => {
        e.preventDefault()

        var newStatus = $select.val()

        $.ajax({
          url: ajaxurl,
          type: "POST",
          data: {
            action: "dtaf_ajax_update_submission_status",
            submission_id: submissionId,
            status: newStatus,
            admin_notes: "", // No notes for quick edit
            nonce: dtaf_admin.nonce,
          },
          success: (response) => {
            if (response.success) {
              // Update the status text and class
              var $statusText = $this.closest("td").find(".dtaf-status-text")
              $statusText.text(newStatus).show()

              // Update status class
              var statusClass = "status-" + newStatus.toLowerCase().replace(/\s+/g, "-")
              var $row = $this.closest("tr")

              // Remove all status classes
              $row.removeClass((index, className) => (className.match(/(^|\s)status-\S+/g) || []).join(" "))

              // Add new status class
              $row.addClass(statusClass)

              // Remove the form and show the edit link
              $form.remove()
              $this.show()

              // Show success message
              $("#dtaf-ajax-message")
                .removeClass("notice-error")
                .addClass("notice-success")
                .html("<p>" + response.data.message + "</p>")
                .show()
                .delay(3000)
                .fadeOut()
            } else {
              alert(response.data.message)
              $form.remove()
              $this.closest("td").find(".dtaf-status-text").show()
              $this.show()
            }
          },
          error: () => {
            alert("เกิดข้อผิดพลาดในการอัปเดตสถานะ")
            $form.remove()
            $this.closest("td").find(".dtaf-status-text").show()
            $this.show()
          },
        })
      })

      // Handle cancel button
      $form.on("click", ".dtaf-quick-edit-cancel", () => {
        $form.remove()
        $this.closest("td").find(".dtaf-status-text").show()
        $this.show()
      })
    })

    // Bulk actions
    $("#doaction, #doaction2").on("click", function (e) {
      var action = $(this).siblings("select").val()
      var checkedBoxes = $('input[name="submission_ids[]"]:checked')

      if (action === "-1") {
        alert("กรุณาเลือกการกระทำ")
        e.preventDefault()
        return
      }

      if (checkedBoxes.length === 0) {
        alert("กรุณาเลือกรายการที่ต้องการดำเนินการ")
        e.preventDefault()
        return
      }

      if (action === "bulk_delete") {
        if (!confirm(dtaf_admin.messages.confirm_bulk_delete)) {
          e.preventDefault()
          return
        }
      }
    })

    // Select all checkboxes
    $("#cb-select-all-1, #cb-select-all-2").on("click", function () {
      $('input[name="submission_ids[]"]').prop("checked", this.checked)
    })

    // Print submission
    $(".dtaf-print-submission").on("click", (e) => {
      e.preventDefault()
      window.print()
    })

    // Initialize color pickers
    if ($.fn.wpColorPicker) {
      $(".dtaf-color-picker").wpColorPicker()
    }

    // Test Line Notify
    $("#dtaf-test-line-notify").on("click", function (e) {
      e.preventDefault()

      var $button = $(this)
      var originalText = $button.text()
      var token = $("#dtaf_line_notify_token").val()

      if (!token) {
        alert("กรุณากรอก Line Notify Token ก่อนทดสอบ")
        return
      }

      // Show loading state
      $button.prop("disabled", true).text("กำลังทดสอบ...")

      $.ajax({
        url: ajaxurl,
        type: "POST",
        data: {
          action: "dtaf_test_line_notify",
          token: token,
          nonce: dtaf_admin.nonce,
        },
        success: (response) => {
          if (response.success) {
            alert("ทดสอบสำเร็จ! ตรวจสอบการแจ้งเตือนใน Line ของคุณ")
          } else {
            alert("เกิดข้อผิดพลาด: " + response.data.message)
          }

          // Reset button state
          $button.prop("disabled", false).text(originalText)
        },
        error: () => {
          alert("เกิดข้อผิดพลาดในการทดสอบ")
          $button.prop("disabled", false).text(originalText)
        },
      })
    })
  })
})(jQuery)
