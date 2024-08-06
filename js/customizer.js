(function ($) {
  $(document).ready(function () {
    var container = $("#custom_post_types_container");
    var addBtn = $("#add_new_post_type");
    var inputField = $("#custom_post_types_input");

    function updateValue() {
      var data = [];
      container.find(".custom_post_type_item").each(function () {
        var postType = $(this).find(".custom_post_type_select").val();
        var permalink = $(this).find(".custom_post_type_permalink").val();
        data.push({
          post_type: postType,
          permalink: permalink,
        });
      });
      inputField.val(JSON.stringify(data)).trigger("change");
    }

    function addItem(postType = "", permalink = "") {
      var index = container.find(".custom_post_type_item").length;
      var newItem = `
                <div class="custom_post_type_item" data-index="${index}">
                    <select class="custom_post_type_select">
                        ${headlessThemeData.post_types
                          .map(function (pt) {
                            return `<option value="${
                              pt.name
                            }" ${pt.name === postType ? "selected" : ""}>${pt.label}</option>`;
                          })
                          .join("")}
                    </select>
                    <input type="url" class="custom_post_type_permalink" value="${permalink}">
                    <button type="button" class="remove_post_type">Remove</button>
                </div>`;
      container.append(newItem);
    }

    addBtn.on("click", function () {
      addItem(); // Add an empty item
    });

    container.on("click", ".remove_post_type", function () {
      $(this).closest(".custom_post_type_item").remove();
      updateValue();
    });

    container.on("change", "select", function () {
      updateValue();
    });

    container.on("input", "input", function () {
        updateValue();
      });

    // Initialize with existing data
    var existingData = JSON.parse(inputField.val() || "[]");
    existingData.forEach(function (item) {
      addItem(item.post_type, item.permalink);
    });
  });
})(jQuery);
