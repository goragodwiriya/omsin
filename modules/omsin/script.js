function initIerecord() {
  var doStatusChanged = function() {
    if (this.value == "TRANSFER") {
      $G("transfer").removeClass("hidden");
      $E("write_category").parentNode.parentNode.style.display = "none";
      $E("write_wallet").parentNode.parentNode.style.display = "none";
      $E("write_wallet_name").parentNode.parentNode.style.display = "none";
      $E("write_comment").parentNode.parentNode.style.display = "none";
    } else if (this.value == "INIT") {
      $G("transfer").addClass("hidden");
      $E("write_category").parentNode.parentNode.style.display = "none";
      $E("write_wallet").parentNode.parentNode.style.display = "none";
      $E("write_wallet_name").parentNode.parentNode.style.display = "block";
      $E("write_comment").parentNode.parentNode.style.display = "none";
      $E("write_wallet_name").focus();
    } else {
      $G("transfer").addClass("hidden");
      $E("write_category").parentNode.parentNode.style.display = "block";
      $E("write_wallet").parentNode.parentNode.style.display = "block";
      $G("write_category").setValue("").reset();
      $E("write_wallet_name").parentNode.parentNode.style.display = "none";
      $E("write_comment").parentNode.parentNode.style.display = "block";
    }
  };
  $G("write_status").addEvent("change", doStatusChanged);
  doStatusChanged.call($E("write_status"));
}

var doDatabaseReset = function() {
  if (confirm(CONFIRM_RESET_DATABASE)) {
    if (confirm(CONFIRM_RESET_DATABASE_B)) {
      send(
        WEB_URL + "xhr.php/omsin/model/database/action",
        "action=reset",
        doFormSubmit
      );
    }
  }
};