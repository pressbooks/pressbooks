!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(n.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var o in e)n.d(r,o,function(t){return e[t]}.bind(null,o));return r},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="/",n(n.s=4)}({4:function(e,t,n){e.exports=n("dvui")},EuuU:function(e,t,n){"use strict";t.a=function(e,t,n){var r,o=document.createElement("div"),i=document.createElement("p"),a=document.getElementsByTagName("h1")[0];if(i.setAttribute("aria-live","assertive"),i.insertAdjacentHTML("beforeend",t),o.classList.add("notice","notice-".concat(e)),o.appendChild(i),n){r=document.createElement("button");var s=document.createElement("span");r.classList.add("notice-dismiss"),s.classList.add("screen-reader-text"),s.appendChild(document.createTextNode("Dismiss this notice.")),r.appendChild(s),o.classList.add("is-dismissible"),o.appendChild(r)}a.parentNode.insertBefore(o,a.nextSibling),r&&(r.onclick=function(){o.parentNode.removeChild(o)})}},ZDLt:function(e,t,n){"use strict";var r=function(e){return e>9?e:"0".concat(e)};t.a=function(){var e=document.getElementById("pb-sse-seconds"),t=document.getElementById("pb-sse-minutes"),n=0;return t.textContent="00:",e.textContent="00",setInterval(function(){e.textContent=r(++n%60),t.textContent=r(parseInt(n/60,10))+":"},1e3)}},dvui:function(e,t,n){"use strict";n.r(t);var r=n("EuuU"),o=n("xvUD"),i=n("ZDLt"),a=n("kaPc");jQuery(function(e){e(document).ready(function(){var t;e(".front-background-image-upload-button").click(function(n){n.preventDefault(),t||(t=wp.media.frames.file_frame=wp.media({multiple:!1})).on("select",function(){var n=t.state().get("selection").first().toJSON();e("#front_background_image").val(n.url),e(".front-background-image").attr("src",n.url),e(".front-background-image-preview-wrap").removeClass("hidden"),e(".front-background-image-upload-button, .front-background-image-description").addClass("hidden")}),t.open()})}),e(".delete-front-background-image").on("click",function(){e("#front_background_image").val(""),e(".front-background-image-preview-wrap").addClass("hidden"),e(".front-background-image-upload-button, .front-background-image-description").removeClass("hidden")});var t=e("#ppi"),n=e("#custom_ppi");""!==t.val()&&n.parent().parent().hide(),t.on("change",function(){""===e(this).val()?n.parent().parent().show():(n.parent().parent().hide(),n.val(e(this).val()))}),e(".colorpicker").wpColorPicker();var s=e(".settings-form"),c=e("#generate-pdf"),u=e("#generate-jpg"),l=e("#pb-sse-progressbar"),d=e("#pb-sse-info"),p=e(".notice"),f=null,h=function(t){var n=e("form."+t),i=PB_CoverGeneratorToken.ajaxUrl+(PB_CoverGeneratorToken.ajaxUrl.includes("?")?"&":"?")+e.param(n.find(":input")),s=new(a.NativeEventSource||a.EventSourcePolyfill)(i);s.onopen=function(){e(window).on("beforeunload",function(){return PB_CoverGeneratorToken.unloadWarning})},s.onmessage=function(t){var n=JSON.parse(t.data);switch(n.action){case"updateStatusBar":l.val(parseInt(n.percentage,10)),d.html(n.info);break;case"complete":s.close(),e(window).unbind("beforeunload"),n.error?(l.val(0).hide(),c.attr("disabled",!1).show(),u.attr("disabled",!1).show(),Object(r.a)("error",n.error,!0),f&&Object(o.a)(f)):window.location=PB_CoverGeneratorToken.redirectUrl}},s.onerror=function(){s.close(),l.removeAttr("value"),d.html("EventStream Connection Error "+PB_CoverGeneratorToken.reloadSnippet),e(window).unbind("beforeunload"),f&&Object(o.a)(f)}};s.on("saveAndGenerate",function(t,n){return c.hide(),u.hide(),l.val(0).show(),p.remove(),f=Object(i.a)(),d.html(PB_CoverGeneratorToken.ajaxSubmitMsg),e(this).ajaxSubmit({done:h(n),timeout:5e3}),!1}),c.click(function(){var t=tinymce.get("pb_about_unlimited");if(t){var n=t.getContent();e("#pb_about_unlimited").val(n)}s.trigger("saveAndGenerate",["pdf"])}),u.click(function(){s.trigger("saveAndGenerate",["jpg"])})})},kaPc:function(e,t,n){var r,o,i;!function(n){"use strict";var a=n.setTimeout,s=n.clearTimeout,c=n.XMLHttpRequest,u=n.XDomainRequest,l=n.EventSource,d=n.document,p=n.Promise,f=n.fetch,h=n.Response,v=n.TextDecoder,y=n.TextEncoder,g=n.AbortController;if(null==Object.create&&(Object.create=function(e){function t(){}return t.prototype=e,new t}),null!=p&&null==p.prototype.finally&&(p.prototype.finally=function(e){return this.then(function(t){return p.resolve(e()).then(function(){return t})},function(t){return p.resolve(e()).then(function(){throw t})})}),null!=f){var m=f;f=function(e,t){return p.resolve(m(e,t))}}function b(){this.bitsNeeded=0,this.codePoint=0}null==g&&(g=function(){this.signal=null,this.abort=function(){}}),b.prototype.decode=function(e){function t(e,t,n){if(1===n)return e>=128>>t&&e<<t<=2047;if(2===n)return e>=2048>>t&&e<<t<=55295||e>=57344>>t&&e<<t<=65535;if(3===n)return e>=65536>>t&&e<<t<=1114111;throw new Error}function n(e,t){if(6===e)return t>>6>15?3:t>31?2:1;if(12===e)return t>15?3:2;if(18===e)return 3;throw new Error}for(var r="",o=this.bitsNeeded,i=this.codePoint,a=0;a<e.length;a+=1){var s=e[a];0!==o&&(s<128||s>191||!t(i<<6|63&s,o-6,n(o,i)))&&(o=0,i=65533,r+=String.fromCharCode(i)),0===o?(s>=0&&s<=127?(o=0,i=s):s>=192&&s<=223?(o=6,i=31&s):s>=224&&s<=239?(o=12,i=15&s):s>=240&&s<=247?(o=18,i=7&s):(o=0,i=65533),0===o||t(i,o,n(o,i))||(o=0,i=65533)):(o-=6,i=i<<6|63&s),0===o&&(i<=65535?r+=String.fromCharCode(i):(r+=String.fromCharCode(55296+(i-65535-1>>10)),r+=String.fromCharCode(56320+(i-65535-1&1023))))}return this.bitsNeeded=o,this.codePoint=i,r};null!=v&&null!=y&&function(){try{return"test"===(new v).decode((new y).encode("test"),{stream:!0})}catch(e){console.log(e)}return!1}()||(v=b);var C=function(){};function w(e){this.withCredentials=!1,this.responseType="",this.readyState=0,this.status=0,this.statusText="",this.responseText="",this.onprogress=C,this.onreadystatechange=C,this._contentType="",this._xhr=e,this._sendTimeout=0,this._abort=C}function T(e){return e.replace(/[A-Z]/g,function(e){return String.fromCharCode(e.charCodeAt(0)+32)})}function S(e){for(var t=Object.create(null),n=e.split("\r\n"),r=0;r<n.length;r+=1){var o=n[r].split(": "),i=o.shift(),a=o.join(": ");t[T(i)]=a}this._map=t}function _(){}function x(e){this._headers=e}function E(){}function O(){this._listeners=Object.create(null)}function j(e){a(function(){throw e},0)}function A(e){this.type=e,this.target=void 0}function k(e,t){A.call(this,e),this.data=t.data,this.lastEventId=t.lastEventId}function P(e,t){A.call(this,e),this.status=t.status,this.statusText=t.statusText,this.headers=t.headers}w.prototype.open=function(e,t){this._abort(!0);var n=this,r=this._xhr,o=1,i=0;this._abort=function(e){0!==n._sendTimeout&&(s(n._sendTimeout),n._sendTimeout=0),1!==o&&2!==o&&3!==o||(o=4,r.onload=C,r.onerror=C,r.onabort=C,r.onprogress=C,r.onreadystatechange=C,r.abort(),0!==i&&(s(i),i=0),e||(n.readyState=4,n.onreadystatechange())),o=0};var u=function(){if(1===o){var e=0,t="",i=void 0;if("contentType"in r)e=200,t="OK",i=r.contentType;else try{e=r.status,t=r.statusText,i=r.getResponseHeader("Content-Type")}catch(n){e=0,t="",i=void 0}0!==e&&(o=2,n.readyState=2,n.status=e,n.statusText=t,n._contentType=i,n.onreadystatechange())}},l=function(){if(u(),2===o||3===o){o=3;var e="";try{e=r.responseText}catch(e){}n.readyState=3,n.responseText=e,n.onprogress()}},d=function(){l(),1!==o&&2!==o&&3!==o||(o=4,0!==i&&(s(i),i=0),n.readyState=4,n.onreadystatechange())},p=function(){i=a(function(){p()},500),3===r.readyState&&l()};r.onload=d,r.onerror=d,r.onabort=d,"sendAsBinary"in c.prototype||"mozAnon"in c.prototype||(r.onprogress=l),r.onreadystatechange=function(){null!=r&&(4===r.readyState?d():3===r.readyState?l():2===r.readyState&&u())},"contentType"in r&&(t+=(-1===t.indexOf("?")?"?":"&")+"padding=true"),r.open(e,t,!0),"readyState"in r&&(i=a(function(){p()},0))},w.prototype.abort=function(){this._abort(!1)},w.prototype.getResponseHeader=function(e){return this._contentType},w.prototype.setRequestHeader=function(e,t){var n=this._xhr;"setRequestHeader"in n&&n.setRequestHeader(e,t)},w.prototype.getAllResponseHeaders=function(){return null!=this._xhr.getAllResponseHeaders?this._xhr.getAllResponseHeaders():""},w.prototype.send=function(){if("ontimeout"in c.prototype||null==d||null==d.readyState||"complete"===d.readyState){var e=this._xhr;e.withCredentials=this.withCredentials,e.responseType=this.responseType;try{e.send(void 0)}catch(e){throw e}}else{var t=this;t._sendTimeout=a(function(){t._sendTimeout=0,t.send()},4)}},S.prototype.get=function(e){return this._map[T(e)]},_.prototype.open=function(e,t,n,r,o,i,a){e.open("GET",o);var s=0;for(var c in e.onprogress=function(){var t=e.responseText.slice(s);s+=t.length,n(t)},e.onreadystatechange=function(){if(2===e.readyState){var n=e.status,o=e.statusText,i=e.getResponseHeader("Content-Type"),a=e.getAllResponseHeaders();t(n,o,i,new S(a),function(){e.abort()})}else 4===e.readyState&&r()},e.withCredentials=i,e.responseType="text",a)Object.prototype.hasOwnProperty.call(a,c)&&e.setRequestHeader(c,a[c]);e.send()},x.prototype.get=function(e){return this._headers.get(e)},E.prototype.open=function(e,t,n,r,o,i,a){var s=new g,c=s.signal,u=new v;f(o,{headers:a,credentials:i?"include":"same-origin",signal:c,cache:"no-store"}).then(function(e){var r=e.body.getReader();return t(e.status,e.statusText,e.headers.get("Content-Type"),new x(e.headers),function(){s.abort(),r.cancel()}),new p(function(e,t){var o=function(){r.read().then(function(t){if(t.done)e(void 0);else{var r=u.decode(t.value,{stream:!0});n(r),o()}}).catch(function(e){t(e)})};o()})}).finally(function(){r()})},O.prototype.dispatchEvent=function(e){e.target=this;var t=this._listeners[e.type];if(null!=t)for(var n=t.length,r=0;r<n;r+=1){var o=t[r];try{"function"==typeof o.handleEvent?o.handleEvent(e):o.call(this,e)}catch(e){j(e)}}},O.prototype.addEventListener=function(e,t){e=String(e);var n=this._listeners,r=n[e];null==r&&(r=[],n[e]=r);for(var o=!1,i=0;i<r.length;i+=1)r[i]===t&&(o=!0);o||r.push(t)},O.prototype.removeEventListener=function(e,t){e=String(e);var n=this._listeners,r=n[e];if(null!=r){for(var o=[],i=0;i<r.length;i+=1)r[i]!==t&&o.push(r[i]);0===o.length?delete n[e]:n[e]=o}},k.prototype=Object.create(A.prototype),P.prototype=Object.create(A.prototype);var N=-1,R=0,I=1,B=2,H=-1,G=0,L=1,D=2,M=3,U=/^text\/event\-stream;?(\s*charset\=utf\-8)?$/i,q=function(e,t){var n=parseInt(e,10);return n!=n&&(n=t),J(n)},J=function(e){return Math.min(Math.max(e,1e3),18e6)},Z=function(e,t,n){try{"function"==typeof t&&t.call(e,n)}catch(e){j(e)}};function X(e,t){O.call(this),this.onopen=void 0,this.onmessage=void 0,this.onerror=void 0,this.url=void 0,this.readyState=void 0,this.withCredentials=void 0,this._close=void 0,function(e,t,n){t=String(t);var r=null!=n&&Boolean(n.withCredentials),o=J(1e3),i=null!=n&&null!=n.heartbeatTimeout?q(n.heartbeatTimeout,45e3):J(45e3),l="",d=o,p=!1,f=null!=n&&null!=n.headers?JSON.parse(JSON.stringify(n.headers)):void 0,h=null!=n&&null!=n.Transport?n.Transport:null!=c&&"withCredentials"in c.prototype||null==u?c:u,v=!z||null!=n&&null!=n.Transport?new w(new h):void 0,y=null==v?new E:new _,g=void 0,m=0,b=N,C="",T="",S="",x="",O=G,X=0,K=0,Q=function(t,n,r,i,a){if(b===R)if(g=a,200===t&&null!=r&&U.test(r)){b=I,p=!0,d=o,e.readyState=I;var s=new P("open",{status:t,statusText:n,headers:i});e.dispatchEvent(s),Z(e,e.onopen,s)}else{var c="";200!==t?(n&&(n=n.replace(/\s+/g," ")),c="EventSource's response has a status "+t+" "+n+" that is not 200. Aborting the connection."):c="EventSource's response has a Content-Type specifying an unsupported type: "+(null==r?"-":r.replace(/\s+/g," "))+". Aborting the connection.",j(new Error(c)),F();var s=new P("error",{status:t,statusText:n,headers:i});e.dispatchEvent(s),Z(e,e.onerror,s)}},W=function(t){if(b===I){for(var n=-1,r=0;r<t.length;r+=1){var c=t.charCodeAt(r);c!=="\n".charCodeAt(0)&&c!=="\r".charCodeAt(0)||(n=r)}var u=(-1!==n?x:"")+t.slice(0,n+1);x=(-1===n?x:"")+t.slice(n+1),""!==u&&(p=!0);for(var f=0;f<u.length;f+=1){var c=u.charCodeAt(f);if(O===H&&c==="\n".charCodeAt(0))O=G;else if(O===H&&(O=G),c==="\r".charCodeAt(0)||c==="\n".charCodeAt(0)){if(O!==G){O===L&&(K=f+1);var h=u.slice(X,K-1),v=u.slice(K+(K<f&&u.charCodeAt(K)===" ".charCodeAt(0)?1:0),f);"data"===h?(C+="\n",C+=v):"id"===h?T=v:"event"===h?S=v:"retry"===h?(o=q(v,o),d=o):"heartbeatTimeout"===h&&(i=q(v,i),0!==m&&(s(m),m=a(function(){V()},i)))}if(O===G){if(""!==C){l=T,""===S&&(S="message");var y=new k(S,{data:C.slice(1),lastEventId:T});if(e.dispatchEvent(y),"message"===S&&Z(e,e.onmessage,y),b===B)return}C="",S=""}O=c==="\r".charCodeAt(0)?H:G}else O===G&&(X=f,O=L),O===L?c===":".charCodeAt(0)&&(K=f+1,O=D):O===D&&(O=M)}}},$=function(){if(b===I||b===R){b=N,0!==m&&(s(m),m=0),m=a(function(){V()},d),d=J(Math.min(16*o,2*d)),e.readyState=R;var t=new A("error");e.dispatchEvent(t),Z(e,e.onerror,t)}},F=function(){b=B,null!=g&&(g(),g=void 0),0!==m&&(s(m),m=0),e.readyState=B},V=function(){if(m=0,b===N){p=!1,m=a(function(){V()},i),b=R,C="",S="",T=l,x="",X=0,K=0,O=G;var e=t;"data:"!==t.slice(0,5)&&"blob:"!==t.slice(0,5)&&""!==l&&(e+=(-1===t.indexOf("?")?"?":"&")+"lastEventId="+encodeURIComponent(l));var n={Accept:"text/event-stream"};if(null!=f)for(var o in f)Object.prototype.hasOwnProperty.call(f,o)&&(n[o]=f[o]);try{y.open(v,Q,W,$,e,r,n)}catch(e){throw F(),e}}else p||null==g?(p=!1,m=a(function(){V()},i)):(j(new Error("No activity within "+i+" milliseconds. Reconnecting.")),g(),g=void 0)};e.url=t,e.readyState=R,e.withCredentials=r,e._close=F,V()}(this,e,t)}var z=null!=f&&null!=h&&"body"in h.prototype;X.prototype=Object.create(O.prototype),X.prototype.CONNECTING=R,X.prototype.OPEN=I,X.prototype.CLOSED=B,X.prototype.close=function(){this._close()},X.CONNECTING=R,X.OPEN=I,X.CLOSED=B,X.prototype.withCredentials=void 0,function(n){if("object"==typeof e.exports){var a=n(t);void 0!==a&&(e.exports=a)}else o=[t],void 0===(i="function"==typeof(r=n)?r.apply(t,o):r)||(e.exports=i)}(function(e){e.EventSourcePolyfill=X,e.NativeEventSource=l,null==c||null!=l&&"withCredentials"in l.prototype||(e.EventSource=X)})}("undefined"!=typeof window?window:this)},xvUD:function(e,t,n){"use strict";t.a=function(e){var t=document.getElementById("pb-sse-seconds");document.getElementById("pb-sse-minutes").textContent="",t.textContent="",clearInterval(e)}}});