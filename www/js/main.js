function escapeHtml(text) {
  return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
}
function testInputString(fromobj, toobj, page){
	$.ajax({
		url: "/ajax.php?what=test-string&page="+page+"&data=" + encodeURIComponent($(fromobj).attr("value"))
	}).done(function(data){
		$(toobj)
		.empty()
		.append($("<b>Result: </b>"))
		.append($("<span></span>").html(escapeHtml(data).replace(/\n/g, "<br />")))
		.append($("<b> (" + data.length + "/140)</b>"));
	});
}
function testReplyInputString(from_rep_from_obj, from_rep_to_obj, tests, toobj, page){
	$.ajax({
		url: "/ajax.php?what=test-string&page="+page+"&repfrom=" + encodeURIComponent($(from_rep_from_obj).attr("value")) + "&repto=" + encodeURIComponent($(from_rep_to_obj).attr("value")) + "&test=" + encodeURIComponent($(tests).attr("value"))
	}).done(function(data){
		if(data.substring(0,3)=='bad')
			$(toobj)
			.html("<span style='color:red;text-weight:bold;'>Bad expression</span>: ")
			.append($("<span></span>").html(escapeHtml(data.substring(4)).replace(/\n/g, "<br />")));
		else if(data=='nomatch')
			$(toobj).html("<span style='color:bluetext-weight:bold;'>The text does not match!</span>");
		else
			$(toobj)
			.empty()
			.append($("<b>Result: </b>"))
			.append($("<span></span>").html(escapeHtml(data.substring(2)).replace(/\n/g, "<br />")))
			.append($("<b> (" + data.length + "/140)</b>"));
	});
}