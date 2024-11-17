jQuery(document).ready(function ($) {
  var modal = $("#log-details-modal");
  var span = $(".close");

  $(".view-details").on("click", function () {
    var logId = $(this).data("log-id");
    $.ajax({
      url: aiDesignBlockLogs.ajaxurl,
      type: "POST",
      data: {
        action: "ai_design_block_get_log_details",
        nonce: aiDesignBlockLogs.nonce,
        log_id: logId,
      },
      success: function (response) {
        if (response.success) {
          $("#log-details-content").html(response.data);
          modal.show();
        } else {
          alert("Error: " + response.data);
        }
      },
    });
  });

  $(".delete-log").on("click", function () {
    if (confirm("Are you sure you want to delete this log?")) {
      var logId = $(this).data("log-id");
      $.ajax({
        url: aiDesignBlockLogs.ajaxurl,
        type: "POST",
        data: {
          action: "ai_design_block_delete_log",
          nonce: aiDesignBlockLogs.nonce,
          log_id: logId,
        },
        success: function (response) {
          if (response.success) {
            location.reload();
          } else {
            alert("Error: " + response.data);
          }
        },
      });
    }
  });

  span.on("click", function () {
    modal.hide();
  });

  $(window).on("click", function (event) {
    if (event.target == modal[0]) {
      modal.hide();
    }
  });
});
