(()=>{var e;var t=algoliasearch(PBAlgolia.applicationId,PBAlgolia.apiKey),a=document.getElementById("book-cards"),n=document.getElementById("stats"),o=instantsearch({indexName:PBAlgolia.indexName,searchClient:t,searchFunction:function(e){e.state.query&&e.search(),window.algoliaHelper=e}});window.selectBookToClone=function(e){var t=document.getElementById("source-book-url"),o=document.getElementById("target-book-url");t.value=e;var l=e.split("/");o.value=l.length>2?l[3]:"",window.scrollTo(0,0),a.innerHTML="",n.innerHTML="",document.querySelector("#searchbox input").value=""},document.querySelector("#searchbox").addEventListener("input",(function(e){0===e.target.value.length&&(e.target.value="",a.innerHTML="",n.innerHTML="")})),o.addWidgets([instantsearch.widgets.searchBox({container:"#searchbox",placeholder:"Search openly licensed books",showSubmit:!1}),instantsearch.widgets.hits({escapeHTML:!0,container:"#book-cards",templates:{item:"".concat(PBAlgolia.hitsTemplate)}}),instantsearch.widgets.stats({container:"#stats",templates:{text:function(t,a){var n,o,l=a.html,r=t.nbHits<=20?t.nbHits:20;return l(e||(n=["",""],o||(o=n.slice(0)),e=Object.freeze(Object.defineProperties(n,{raw:{value:Object.freeze(o)}}))),PBAlgolia.resultsTemplate.replace("%resultsShown",r).replace("%totalResults",t.nbHits))}}})]),o.start()})();