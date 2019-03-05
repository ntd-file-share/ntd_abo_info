function ntd_open() {
  var trigger_element = event.target;
  if (trigger_element.classList) {
    trigger_element.classList.toggle("ntd_open");
  } else {
    // For IE9
    var classes = trigger_element.className.split(" ");
    var i = classes.indexOf("ntd_open");

    if (i >= 0)
    classes.splice(i, 1);
    else
    classes.push("ntd_open");
    trigger_element.className = classes.join(" ");
  }
}
