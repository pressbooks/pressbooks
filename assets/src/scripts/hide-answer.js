var pressed = false;

jQuery(".show-answer").click(function() {
  pressed = !pressed;
  var target = "#" + jQuery(this).data("target");

  // Animation
  jQuery(target).slideToggle(200);

  // Accessibility
  jQuery(this).toggleClass('expanded collapsed');
  jQuery(this).attr('aria-pressed', pressed);
});
