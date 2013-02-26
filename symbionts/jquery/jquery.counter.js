/*
   jQuery (character and word) counter
   Copyright (C) 2009  Wilkins Fernandez

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
(function($) {
    $.fn.extend({
        counter: function(options) {
            // Set the default values, use comma to separate the settings
            var defaults = {
                type: 'char',   // {char || word}
                count: 'down',  // count {up || down} from or to the goal number
                goal: 140       // count {to || from} this number                
            };
            var options = $.extend({}, defaults, options);
            var flag = false;   // Set to true when goal is reached

            // Loop through each instance of the object that being is passed (the users selector).
            // Allows for multiple instances of the jQuery methods available to THIS (instance of the) object
            // (you can use this plug-in more than once on a page).
            return this.each(function() {
                var msg;
                var $obj = $(this);

                // Sets the appropriate message based on the options
                function get_msg_equation(objLength) {
                    // Make sure that the right values are set
                    if (typeof options.type !== 'string') {
                    } else {
                        switch (options.type) {
                            case 'char':
                                if (options.count === 'down') {
                                    msg = " character(s) left";
                                    return (options.goal - objLength);
                                }
                                else if (options.count === 'up') {
                                    msg = " characters (" + options.goal + " max)";
                                    return objLength;
                                }
                                break;
                            case 'word':
                                if (options.count === 'down') {
                                    msg = " word(s) left";
                                    return (options.goal - objLength);
                                }
                                else if (options.count === 'up') {
                                    msg = " words (" + options.goal + " max)";
                                    return objLength;
                                }
                                break;
                            default:
                        } //END switch
                    } // END if
                } // END function

                // * Initialize *: the bind event needs an object to bind to
                $('<div id=\"' + this.id + '_counter\"><span>' + get_msg_equation($($obj).val().length ) + '</span>' + msg + '</div>').insertAfter($obj);

                // Cache the counter selector
                var $currentCount = $("#" + this.id + "_counter" + " span");

                // Bind events to a function that returns the length
                // of the characters || words in the given text field.
                $obj.bind('keyup click blur focus change paste', function(new_length) {
                    // Update characters depending on the option selected
                    switch (options.type) {
                        case 'char':
                            new_length = $($obj).val().length;
                            break;
                        case 'word':
                            if ($obj.val() === '') {
                                new_length = 0;
                            }
                            else {
                                new_length = $.trim($obj.val())
                                    .replace(/\s+/g, " ")
                                    .split(' ').length;
                            }
                            break;
                        default:
                    } // END switch

                    // Set flag TRUE when counter reaches goal
                    switch (options.count) {
                        case 'up':
                            if (get_msg_equation(new_length) >= options.goal && options.type === 'char') {
                                $(this).val($(this).val().substring(0, options.goal));
                                flag = true;
                                break;
                            }
                            if (get_msg_equation(new_length) === options.goal && options.type === 'word') {
                                flag = true;
                                break;
                            } else if (get_msg_equation(new_length) > options.goal && options.type === 'word') {
                                $(this).val("");
								$currentCount.text("0");
                                flag = true;
                                break;
                            }
                            break;
                        case 'down':
                            if (get_msg_equation(new_length) <= 0 && options.type === 'char') {
                                $(this).val($(this).val().substring(0, options.goal));
                                flag = true;
                                break;
                            }
                            if (get_msg_equation(new_length) === 0 && options.type === 'word') {
                                flag = true;
                            } else if (get_msg_equation(new_length) < 0 && options.type === 'word') {
                                $(this).val("");
                                flag = true;
                                break;
                            }
                            break;
                        default:
                    } // END switch

                    // Listen on keydown to catch the last character or word typed
                    // and prevent the user from typing
                    $obj.keydown(function(event) {
                        if (flag) {
                            this.focus();
                            // Listen for delete & backspace
                            if ((event.keyCode !== 46 && event.keyCode !== 8)) {
                                if ($(this).val().length > options.goal && options.type === 'char') {
                                    $(this).val($(this).val().substring(0, options.goal));
                                    return false;   // Stop the default action (typing)
									// Listen for blank (spacebar) & return 
                                } else if (event.keyCode !== 32 && event.keyCode !== 8 && options.type === 'word') {   //Allow to continue typing last word
                                    return true;
                                } else {
                                    return false;   // Stop the default action (typing)
                                }
                            } else {
                                flag = false;
                                return true;
                            }
                        }
                    }); // END keydown
                    $currentCount.text(get_msg_equation(new_length));
                }); // END Bind
            }); //END return
        } // END counter function
    }); // END extend
}) // END function
(jQuery); // Return jQuery object