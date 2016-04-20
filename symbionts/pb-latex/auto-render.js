(function() {
		
var findEndOfMath = function(delimiter, text, startIndex) {
    // Adapted from
    // https://github.com/Khan/perseus/blob/master/src/perseus-markdown.jsx
    var index = startIndex;
    var braceLevel = 0;

    var delimLength = delimiter.length;

    while (index < text.length) {
        var character = text[index];

        if (braceLevel <= 0 &&
            text.slice(index, index + delimLength) === delimiter) {
            return index;
        } else if (character === "\\") {
            index++;
        } else if (character === "{") {
            braceLevel++;
        } else if (character === "}") {
            braceLevel--;
        }

        index++;
    }

    return -1;
};

var splitAtDelimiters = function(startData, leftDelim, rightDelim, display, format) {
    var finalData = [];

    for (var i = 0; i < startData.length; i++) {
        if (startData[i].type === "text") {
            var text = startData[i].data;

            var lookingForLeft = true;
            var currIndex = 0;
            var nextIndex;

            nextIndex = text.indexOf(leftDelim);
            if (nextIndex !== -1) {
                currIndex = nextIndex;
                finalData.push({
                    type: "text",
                    data: text.slice(0, currIndex)
                });
                lookingForLeft = false;
            }

            while (true) {
                if (lookingForLeft) {
                    nextIndex = text.indexOf(leftDelim, currIndex);
                    if (nextIndex === -1) {
                        break;
                    }

                    finalData.push({
                        type: "text",
                        data: text.slice(currIndex, nextIndex)
                    });

                    currIndex = nextIndex;
                } else {
                    nextIndex = findEndOfMath(
                        rightDelim,
                        text,
                        currIndex + leftDelim.length);
                    if (nextIndex === -1) {
                        break;
                    }

                    finalData.push({
                        type: "math",
                        data: text.slice(
                            currIndex + leftDelim.length,
                            nextIndex),
                        rawData: text.slice(
                            currIndex,
                            nextIndex + rightDelim.length),
                        display: display,
                        format: format
                    });

                    currIndex = nextIndex + rightDelim.length;
                }

                lookingForLeft = !lookingForLeft;
            }

            finalData.push({
                type: "text",
                data: text.slice(currIndex)
            });
        } else {
            finalData.push(startData[i]);
        }
    }

    return finalData;
};

var splitWithDelimiters = function(text, delimiters) {
    var data = [{type: "text", data: text}];
    for (var i = 0; i < delimiters.length; i++) {
        var delimiter = delimiters[i];
        data = splitAtDelimiters(
            data, delimiter.left, delimiter.right,
            delimiter.display || false, delimiter.format);
    }
    return data;
};

var renderMathInText = function(text, delimiters) {
    var data = splitWithDelimiters(text, delimiters);

    var fragment = document.createDocumentFragment();

    for (var i = 0; i < data.length; i++) {
        if (data[i].type === "text") {
            fragment.appendChild(document.createTextNode(data[i].data));
        } else {
            var span = document.createElement("span");
            var math = data[i].data;
            if (data[i].format == "asciimath") {
            	    math = "\\displaystyle "+AMTparseAMtoTeX(math);
            } else if (math.indexOf("\\displaystyle")==-1) {
            	    math = "\\displaystyle "+math;
            }
            try {
                katex.render(math, span, {
                    displayMode: data[i].display
                });
            } catch (e) {
                if (!(e instanceof katex.ParseError)) {
                    throw e;
                }
                span.className = "mj";
                if (data[i].format == "asciimath") {
                	span.innerHTML = "`"+data[i].data+"`";
                } else {
                	span.innerHTML = "\\("+data[i].data+"\\)";
                }
                MathJax.Hub.Queue(["Typeset",MathJax.Hub,span]);
            }
            fragment.appendChild(span);
        }
    }

    return fragment;
};

var renderElem = function(elem, delimiters, ignoredTags) {
    for (var i = 0; i < elem.childNodes.length; i++) {
        var childNode = elem.childNodes[i];
        if (childNode.nodeType === 3) {
            // Text node
            var frag = renderMathInText(childNode.textContent, delimiters);
            i += frag.childNodes.length - 1;
            elem.replaceChild(frag, childNode);
        } else if (childNode.nodeType === 1) {
            // Element node
            var shouldRender = ignoredTags.indexOf(
                childNode.nodeName.toLowerCase()) === -1;

            if (shouldRender) {
                renderElem(childNode, delimiters, ignoredTags);
            }
        }
        // Otherwise, it's something else, and ignore it.
    }
};

var defaultOptions = {
    delimiters: [
       // {left: "`", right: "`", display: false, format: "asciimath"},
        {left: "[latex]", right: "[/latex]", display: false, format: "tex"}
    ],

    ignoredTags: [
        "script", "noscript", "style", "textarea", "pre", "code"
    ]
};

var extend = function(obj) {
    // Adapted from underscore.js' `_.extend`. See LICENSE.txt for license.
    var source, prop;
    for (var i = 1, length = arguments.length; i < length; i++) {
        source = arguments[i];
        for (prop in source) {
            if (Object.prototype.hasOwnProperty.call(source, prop)) {
                obj[prop] = source[prop];
            }
        }
    }
    return obj;
};

var renderMathInElement = function(elem, options) {
    if (!elem) {
    	//let's not throw errors if there's nothing to render.
        return;
        //throw new Error("No element provided to render");
    }

    options = extend({}, defaultOptions, options);

    renderElem(elem, options.delimiters, options.ignoredTags);
};

window.renderMathInElement = renderMathInElement;

})();

jQuery(function() {
	renderMathInElement(jQuery(".entry-content").get(0));	
});
