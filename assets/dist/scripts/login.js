!function(t){var n={};function e(o){if(n[o])return n[o].exports;var i=n[o]={i:o,l:!1,exports:{}};return t[o].call(i.exports,i,i.exports,e),i.l=!0,i.exports}e.m=t,e.c=n,e.d=function(t,n,o){e.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:o})},e.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return e.d(n,"a",n),n},e.o=function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},e.p="",e(e.s=12)}({12:function(t,n,e){t.exports=e("hAZf")},hAZf:function(t,n){var e=document.querySelector("h1"),o=document.querySelector("div#login"),i=document.createElement("p");i.classList.add("subtitle"),document.body.classList.contains("login-action-login")?i.textContent=PB_Login.logInTitle:document.body.classList.contains("login-action-lostpassword")?i.textContent=PB_Login.lostPasswordTitle:document.body.classList.contains("login-action-rp")?i.textContent=PB_Login.resetPasswordTitle:document.body.classList.contains("login-action-resetpass")?i.textContent=PB_Login.passwordResetTitle:i.textContent=PB_Login.logInTitle,document.body.insertBefore(e,document.body.firstChild),o.insertBefore(i,o.firstChild)}});