/**
 * Frontend JavaScript for Direct to Admin Form
 */
;(($) => {
  // Form validation and submission
  $(document).ready(() => {
    // Client-side form validation
    $(".dtaf-form").on("submit", function (e) {
      var $form = $(this)
      var isValid = true

      // Reset previous errors
      $form.find(".dtaf-error-text").remove()
      $form.find(".dtaf-field-error").removeClass("dtaf-field-error")

      // Validate required fields
      $form.find("[required]").each(function () {
        var $field = $(this)
        var value = $field.val()

        if (!value) {
          isValid = false
          $field.addClass("dtaf-field-error")
          $field.after('<span class="dtaf-error-text">' + dtaf_frontend.messages.required_field + "</span>")
        }
      })

      // Validate email
      var $email = $form.find('input[type="email"]')
      if ($email.length && $email.val() && !isValidEmail($email.val())) {
        isValid = false
        $email.addClass("dtaf-field-error")
        $email.after('<span class="dtaf-error-text">' + dtaf_frontend.messages.invalid_email + "</span>")
      }

      // Validate phone
      var $phone = $form.find('input[name="phone"]')
      if ($phone.length && $phone.val() && !isValidPhone($phone.val())) {
        isValid = false
        $phone.addClass("dtaf-field-error")
        $phone.after('<span class="dtaf-error-text">' + dtaf_frontend.messages.invalid_phone + "</span>")
      }

      // Validate ID card
      var $idcard = $form.find('input[name="idcard"]')
      if ($idcard.length && $idcard.val() && !isValidIdCard($idcard.val())) {
        isValid = false
        $idcard.addClass("dtaf-field-error")
        $idcard.after('<span class="dtaf-error-text">' + dtaf_frontend.messages.invalid_idcard + "</span>")
      }

      // Validate file uploads
      $form.find('input[type="file"]').each(function () {
        var $file = $(this)

        if ($file.val()) {
          var file = this.files[0]
          var fileSize = file.size
          var fileName = file.name
          var fileExt = fileName.split(".").pop().toLowerCase()

          // Get allowed file types and max size from data attributes
          var allowedTypes = $file.data("allowed-types")
            ? $file.data("allowed-types").split(",")
            : ["jpg", "jpeg", "png", "pdf", "doc", "docx"]
          var maxSize = $file.data("max-size") ? $file.data("max-size") * 1024 * 1024 : 5 * 1024 * 1024 // Default 5MB

          // Check file type
          if ($.inArray(fileExt, allowedTypes) === -1) {
            isValid = false
            $file.addClass("dtaf-field-error")
            $file.after('<span class="dtaf-error-text">' + dtaf_frontend.messages.invalid_file_type + "</span>")
          }

          // Check file size
          if (fileSize > maxSize) {
            isValid = false
            $file.addClass("dtaf-field-error")
            $file.after('<span class="dtaf-error-text">' + dtaf_frontend.messages.file_too_large + "</span>")
          }
        }
      })

      // If not valid, prevent form submission
      if (!isValid) {
        e.preventDefault()

        // Scroll to first error
        $("html, body").animate(
          {
            scrollTop: $form.find(".dtaf-field-error:first").offset().top - 100,
          },
          500,
        )

        return false
      }

      // If using AJAX submission
      if (typeof dtaf_frontend !== "undefined" && e.originalEvent) {
        e.preventDefault()

        // Show loading indicator
        $form.find(".dtaf-submit-button").prop("disabled", true)
        $form.find(".dtaf-loading").show()

        // Collect form data
        var formData = new FormData(this)
        formData.append("action", "dtaf_submit_form")

        // Send AJAX request
        $.ajax({
          url: dtaf_frontend.ajax_url,
          type: "POST",
          data: formData,
          processData: false,
          contentType: false,
          success: (response) => {
            if (response.success) {
              // Show success message
              $form.before('<div class="dtaf-message dtaf-success-message">' + response.data.message + "</div>")
              $form[0].reset()
            } else {
              // Show error message
              $form.before('<div class="dtaf-message dtaf-error-message">' + response.data.message + "</div>")

              // Show field-specific errors if available
              if (response.data.errors) {
                $.each(response.data.errors, (field, error) => {
                  var $field = $form.find('[name="' + field + '"]')
                  $field.addClass("dtaf-field-error")
                  $field.after('<span class="dtaf-error-text">' + error + "</span>")
                })
              }
            }

            // Scroll to message
            $("html, body").animate(
              {
                scrollTop: $form.prev(".dtaf-message").offset().top - 50,
              },
              500,
            )

            // Hide loading indicator
            $form.find(".dtaf-submit-button").prop("disabled", false)
            $form.find(".dtaf-loading").hide()
          },
          error: () => {
            // Show error message
            $form.before('<div class="dtaf-message dtaf-error-message">เกิดข้อผิดพลาดในการส่งข้อมูล โปรดลองอีกครั้ง</div>')

            // Scroll to message
            $("html, body").animate(
              {
                scrollTop: $form.prev(".dtaf-message").offset().top - 50,
              },
              500,
            )

            // Hide loading indicator
            $form.find(".dtaf-submit-button").prop("disabled", false)
            $form.find(".dtaf-loading").hide()
          },
        })
      }
    })

    // Helper functions for validation
    function isValidEmail(email) {
      var pattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/
      return pattern.test(email)
    }

    function isValidPhone(phone) {
      var pattern = /^[0-9\-+().\s]{7,20}$/
      return pattern.test(phone)
    }

    function isValidIdCard(idcard) {
      // Thai ID card validation (13 digits)
      var pattern = /^\d{13}$/
      return pattern.test(idcard)
    }
  })
})(jQuery)
