function confSubmit(form) {
	if (confirm("Once you've pressed 'Replace & Save' there is no going back! Have you checked 'Preview Replacements' to make sure this will do what you want it to do?")) {
		var input = document.createElement("input");
		input.setAttribute("type", "hidden");
		input.setAttribute("name", "replace_and_save");
		document.getElementById("searcharguments").appendChild(input);
		form.submit();
	}
}