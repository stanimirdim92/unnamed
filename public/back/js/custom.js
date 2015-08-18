;(function (win, doc, $, undefined) {
    'use strict';

    /**
     * Create SEO captions/URLs for menus and content.
     */
    function fixSEOCaption(caption) {
        return caption.toLowerCase().replace(/(^\s+|[^a-zA-Z0-9 ]+|\s+$)/g,"").replace(/\s+/g, "-");
    }

    function appendTranslationNode(elementAppend) {
        var $constKey =$("#newTranslationsConstName").val().toUpperCase();
        $(elementAppend).append($("<label><span>"+$constKey+"</span><input type='text' size='35' name='"+$constKey+"' placeholder='"+$constKey+"' required='required' value='"+$("#newTranslationsText").val()+"' /><button type='button' class='btn btn-sm delete translation_delete'><i class='fa fa-trash-o'></i></button></label><br>"));
    }

    $(doc).ready(function ($) {
        'use strict';

        /**
         * Custom dialog window for delete button
         */
        $(".dialog_delete").on("click", function (e) {
            e.preventDefault();
            $("#delete_" + $(this).attr("id")).fadeIn(650);
        });

        /*
         * Custom cancel button for delete dialog. Acts as a close button
         */
        $(".cancel").on("click", function (e) {
            e.preventDefault();
            $(".dialog_hide").fadeOut(650);
        });

        $(".add-translation").on("click", function (e) {
            e.preventDefault();
            appendTranslationNode("#translationsArray fieldset");
        });

        $(".add-new-translation").on("click", function (e) {
            e.preventDefault();
            $(".toggle-translation-box").fadeToggle("slow");
        });

        $(".translation_delete").on("click", function (e) {
            e.preventDefault();
            $(this).parent("label").remove();
        });

        /**
         * replace: this is a menu caption => this-is-a-menu-caption, trim all white space and other characters
         */
        if ($("#titleLink").val() !== undefined) {
            $("#titleLink").val(fixSEOCaption($("#seo-caption").val()));
        }
        if ($("#menulink").val() !== undefined) {
            $("#menulink").val(fixSEOCaption($("#seo-caption").val()));
        }

        $("#seo-caption").on("keyup select change", function () {
            if ($("#menulink").val() !== undefined) {
                $("#menulink").val(fixSEOCaption($("#seo-caption").val()));
            } else {
                $("#titleLink").val(fixSEOCaption($("#seo-caption").val()));
            }
        });

        /**
         * AJAX search form.
         */
        var $urlSplit = win.location.href.toString().split(win.location.host)[1].split("/");
        $(".ajax-search").on("keyup", function () {
            var $search = $(".ajax-search").val();
            if ($.trim($search).length > 2) {
                $.ajax({
                    type: "GET",
                    url: "/admin/" + $urlSplit[2] + "/search",
                    data: {"ajaxsearch": $search},
                    dataType: "json",
                    contentType: "application/json; charset=utf-8;",
                    cache: !1,
                }).done(function (result, request, headers) {
                    if (request === "success" && result.statusType === "success") {
                        $("#results").empty();
                        $.each(result.ajaxsearch, function (key, value) {
                            var $ul = $("<ul class='table-row'>");
                            var $val = $.parseJSON(value);
                            for (var property in $val) {
                                if ($val.hasOwnProperty(property) && $val[property]) {
                                    $ul.append("<li data-id ='"+$val["id"]+"' class='table-cell'>"+$val[property]+"</li>");
                                }
                            }
                            $("#results").append($ul);
                        });
                    } else {
                        $("#results").empty();
                        $("#results").append("<p>No matches</p>");
                    }
                }).fail(function (error) {
                    console.log("Error:", error.responseText); //TODO must create a dialog popup
                });
                $("#results").show();
                $("#linked").hide();
            } else {
                $("#results").hide();
                $("#linked").show();
            }
        });
    });
})(this, document, jQuery);