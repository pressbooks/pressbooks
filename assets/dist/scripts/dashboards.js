document.addEventListener("alpine:init",(function(){Alpine.store("checklist",{completed:!1,reset:!1,loading:!0,toggleComplete:function(){this.completed=!this.completed},toggleReset:function(){this.reset=!this.reset},updateCompleted:function(){var e=document.querySelectorAll('.network-checklist input[type="checkbox"]'),t=Array.from(e).every((function(e){return e.checked}));this.completed=t,this.loading=!1}})})),document.addEventListener("updateCompleted",(function(e){var t=e.detail,n=t.completed;t.reset&&Alpine.store("checklist").toggleReset(),n&&Alpine.store("checklist").toggleComplete()})),document.addEventListener("DOMContentLoaded",(function(){Alpine.store("checklist").updateCompleted()}));