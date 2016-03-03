(function (window, Model) {
    window.request = Model.initialize();
    window.opts = {};
}(window, window.Model));

$(function () {
    $('#side-menu').metisMenu();
});

$(function () {
    $('select[value]').each(function () {
        $(this).val(this.getAttribute("value"));
    });
});

//Loads the correct sidebar on window load,
//collapses the sidebar on window resize.
// Sets the min-height of #page-wrapper to window size
$(function () {
    $(window).bind("load resize", function () {
        topOffset = 50;
        width = (this.window.innerWidth > 0) ? this.window.innerWidth : this.screen.width;
        if (width < 768) {
            $('div.navbar-collapse').addClass('collapse');
            topOffset = 100; // 2-row-menu
        } else {
            $('div.navbar-collapse').removeClass('collapse');
        }

        height = ((this.window.innerHeight > 0) ? this.window.innerHeight : this.screen.height) - 1;
        height = height - topOffset;
        if (height < 1)
            height = 1;
        if (height > topOffset) {
            $("#page-wrapper").css("min-height", (height) + "px");
        }
    });

    var url = window.location;
    var element = $('ul.nav a').filter(function () {
        return this.href == url || url.href.indexOf(this.href) == 0;
    }).addClass('active').parent().parent().addClass('in').parent();
    if (element.is('li')) {
        element.addClass('active');
    }
});

$(document).ready(function () {

    //initialize beautiful datetime picker
    $("input[type=date]").datepicker();
    $("input[type=date]").datepicker("option", "dateFormat", "yy-mm-dd");
    var dateFormat = $("input[type=date]").datepicker( "option", "dateFormat" );
    $("input[type=date]").datepicker( "option", "dateFormat", "yy-mm-dd" );

    $('#serp_stats').submit(function (e) {
        $('#stats').html('<p class="text-center"><i class="fa fa-spinner fa-spin fa-5x"></i></p>');
        e.preventDefault();
        var data = $(this).serializeArray();
        request.read({
            action: $(this).attr("action"),
            data: data,
            callback: function (data) {
                $('#stats').html('');
                if ($('#socialType').length !== 0) {
                    $('#socialType').html(data.social.type + " of " + data.social.media);
                }
                if (data.data) {
                    Morris.Line({
                        element: 'stats',
                        data: toArray(data.data),
                        xkey: 'y',
                        ykeys: ['a'],
                        labels: [data.label || 'Rank']
                    });
                }
            }
        });
    });

    $("#crawlErrorForm").submit(function (e) {
        $('#stats').html('<p class="text-center"><i class="fa fa-spinner fa-spin fa-5x"></i></p>');
        e.preventDefault();
        var data = $(this).serializeArray();

        request.read({
            action: $(this).attr("action"),
            data: data,
            callback: function (data) {
                $('#stats').html('');
                if (data.response) {
                    Morris.Bar({
                        element: 'stats',
                        data: toArray(data.response),
                        xkey: 'x',
                        ykeys: ['y'],
                        labels: ['Total']
                    });
                }

                if (data.params) {
                    $("#updateCat").html('<p>Category: <strong>'+ data.params.category + '</strong></p><p>Platform: <strong>'+ data.params.platform +'</strong></p>');
                }
            }
        });
    })

    // find all the selectors 
    var types = $('#addOptions select');
    types.on("change", function () { // bind the change function
        var value = $(this).val();

        // if text box is selected then show it and hide the file upload or vice-versa
        if (value === "text") {
            $("#type").find("input[type='text']").toggleClass("hide").attr("required", "");
            $("#type").find("input[type='file']").toggleClass("hide");
        } else if (value === "image") {
            $("#type").find("input[type='file']").toggleClass("hide");
            $("#type").find("input[type='text']").toggleClass("hide").removeAttr("required");
        }
    });

    $(".selectAll").click(function(e) {
        $(this).focus();
        document.execCommand('SelectAll');
    });

    $("#searchModel").change(function() {
        var self = $(this);
        $('#searchField').html('');
        request.read({
            action: "admin/fields/" + this.value,
            callback: function(data) {
                var d = $.parseJSON(data);
                $.each(d, function (field, property) {
                    $('#searchField').append('<option value="'+ field +'">'+ field +'</option>');
                })
            }
        });
    });

    $(document).on('change', '#searchField', function(event) {
        var fields = ["created", "modified"],
            date = $.inArray(this.value, fields);
        if (date !== -1) {
            $("input[name=value]").val('');
            $("input[name=value]").datepicker();
            $("input[name=value]").datepicker("option", "dateFormat", "yy-mm-dd");
        };
    });


    $("#trigger").change(function() {
        var self = $(this);
        $('#helpTrigger').html('');
        request.read({
            action: "detector/read/trigger/" + this.value,
            callback: function(data) {
                var d = $.parseJSON(data);
                $('#helpTrigger').html(d.help);
            }
        });
    });

    $("#action").change(function() {
        var self = $(this);
        $('#helpAction').html('');
        request.read({
            action: "detector/read/action/" + this.value,
            callback: function(data) {
                var d = $.parseJSON(data);
                $('#helpAction').html(d.help);
            }
        });
    });

    $('.delete').click(function(e) {
        e.preventDefault();
        var self = $(this);
        bootbox.confirm("Are you sure, you want to proceed with the action?", function(result) {
            if (result) {
                window.location.href = self.attr('href');
            }
        });
    });

    $("#referer").on("change", function (e) {
        e.preventDefault();

        if ($(this).val() != "google") {
            $("#tldSelection").attr("disabled", true);
        } else {
            $("#tldSelection").attr("disabled", false);
        }
    });

    $(".googl").click(function(e) {
        e.preventDefault();
        var item = $(this),
            shortURL = item.data('url');
        item.html('<i class="fa fa-spinner fa-pulse"></i>');
        request.read({
            action: "analytics/referer",
            data: {shortURL: shortURL},
            callback: function(data) {
                item.html('Click : '+ data.googl.analytics.allTime.shortUrlClicks);
            }
        });

    });

    $(".website").click(function(e) {
        e.preventDefault();
        var item = $(this),
            website = item.data('website'),
            action = item.data('action');
        item.html('<i class="fa fa-spinner fa-pulse"></i>');
        request.read({
            action: "analytics/website",
            data: {website: website, action: action},
            callback: function(data) {
                if (data.success) {
                    item.html('Hit: '+ data.count);
                } else {
                    item.html('Hit: 0');
                }
            }
        });
    });

    $('.priority').on('change', function (e) {
        e.preventDefault();
        var data = {
            trigger: $(this).data('trigger'),
            priority: $(this).val(),
            action: 'changePriority'
        },
        self = $(this),
        total = $('#totalPriorities').html();

        if (window.lastPriority == data.priority) {
            return false;
        }
        window.lastPriority = data.priority;

        self.html('<i class="fa fa-spinner fa-spin"></i> Wait');
        request.create({
            action: 'detector/updatePriority',
            data: data,
            callback: function (d) {
                var html = '';
                if (!d.success) {
                    alert('Something went wrong!!');
                    return false;
                }
                $('#triggerPriority_' + data.trigger).html(data.priority);

                for (var i = 0; i < total; ++i) {
                    html += '<option value="' + i + '"';
                    if (i == data.priority) {
                        html += 'selected=""';
                    }
                    html += '>' + i + '</option>'
                }
                self.html(html);
            }
        })
    });

    $('.pingStats').on('click', function (e) {
        e.preventDefault();
        var item = $(this),
            status = $('#status_' + item.data('record'));
        item.html('<i class="fa fa-spinner fa-pulse"></i>');
        request.read({
            action: 'analytics/ping',
            data: {record: item.data('record')},
            callback: function (data) {
                if (data.success) {
                    item.html('Pinged: ' + data.count);
                } else {
                    item.html('Pinged: 0');
                }

                if (data.status == "up") {
                    status.html('<span class="label label-success"><i class="fa fa-arrow-up"></i> UP</span>');
                } else if (data.status == "down") {
                    status.html('<span class="label label-danger"><i class="fa fa-arrow-down"></i> DOWN</span>')
                }
            }
        });
    });
});

function stats() {
    request.read({
        action: "analytics/detector",
        callback: function(data) {
            var gdpData = data.stats.analytics;
            $('#world-map').vectorMap({
                map: 'world_mill_en',
                series: {
                    regions: [{
                        values: gdpData,
                        scale: ['#C8EEFF', '#0071A4'],
                        normalizeFunction: 'polynomial'
                    }]
                },
                onRegionTipShow: function(e, el, code) {
                    if (gdpData.hasOwnProperty(code)) {
                        el.html(el.html() + ' (Clicks - ' + gdpData[code] + ')');
                    } else{
                        el.html(el.html() + ' (Clicks - 0)');
                    };
                }
            });
        }
    });
}

function toArray(object) {
    var array = $.map(object, function (value, index) {
        return [value];
    });
    return array;
}