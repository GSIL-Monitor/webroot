$(function () {
    function parse_json(str) {
        try {
            return JSON.stringify(JSON.parse(str), null, '        ');
        } catch (e) {
            return str;
        }
    }
    
    $("form").submit(function () {
        var form = $(this);
        var inputs = form.serializeArray();
        var params = {};
        for (var i = 0; i < inputs.length; i++) {
            params[inputs[i].name] = inputs[i].value;
        }

        var request = '';
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: params,
            xhrFields: { withCredentials: true },
        }).done(function (data, status, xhr) {
        	
            var headers = xhr.getAllResponseHeaders();
            var html = request +
                    'HTTP/1.1 ' + xhr.status + ' ' + xhr.statusText + "\r\n"
                    + headers + "\r\n\r\n" + parse_json(xhr.responseText);
            form.find(".result").css('background-color', 'white');
            form.find(".result").html(html).show();
            
        }).fail(function (xhr, status, error) {
            var headers = xhr.getAllResponseHeaders();
            var html = request +
                    'HTTP/1.1 ' + xhr.status + ' ' + xhr.statusText + "\r\n"
                    + headers + "\r\n\r\n" + parse_json(xhr.responseText);
            form.find(".result").html(html).show();
        });
        form.find(".result").html("加载中...");
        return false;
    });
    
    $(".openNewWindow").click(function(){
		var obj =$(this); 
		var id=obj.attr('form_id');
		id="#"+id.replace(/\//g,"\\\/");
		var form = $(id);
		var inputs = form.serializeArray();
		var url =obj.attr('url')+"?";
		for (var i = 0; i < inputs.length; i++) {
		    if (inputs[i].name.substr(0, 1) != '_') {
		        url+=inputs[i].name+'='+inputs[i].value+'&';
		    }
		}
		window.open(url,id);
    });
});
