!function(e){function t(t){for(var r,i,c=t[0],u=t[1],l=t[2],f=0,d=[];f<c.length;f++)i=c[f],Object.prototype.hasOwnProperty.call(n,i)&&n[i]&&d.push(n[i][0]),n[i]=0;for(r in u)Object.prototype.hasOwnProperty.call(u,r)&&(e[r]=u[r]);for(s&&s(t);d.length;)d.shift()();return a.push.apply(a,l||[]),o()}function o(){for(var e,t=0;t<a.length;t++){for(var o=a[t],r=!0,c=1;c<o.length;c++){var u=o[c];0!==n[u]&&(r=!1)}r&&(a.splice(t--,1),e=i(i.s=o[0]))}return e}var r={},n={0:0},a=[];function i(t){if(r[t])return r[t].exports;var o=r[t]={i:t,l:!1,exports:{}};return e[t].call(o.exports,o,o.exports,i),o.l=!0,o.exports}i.m=e,i.c=r,i.d=function(e,t,o){i.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},i.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},i.t=function(e,t){if(1&t&&(e=i(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(i.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)i.d(o,r,function(t){return e[t]}.bind(null,r));return o},i.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return i.d(t,"a",t),t},i.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},i.p="";var c=window.webpackJsonp=window.webpackJsonp||[],u=c.push.bind(c);c.push=t,c=c.slice();for(var l=0;l<c.length;l++)t(c[l]);var s=u;a.push([12,1]),o()}({12:function(e,t,o){"use strict";o.r(t),function(e){o(13),o(15);var t=o(11),r=o.n(t);o(38);e("oembed[url]").each((function(t,o){var r=e(o),n=r.attr("url");r.html('<div style="position: relative; padding-bottom: 100%; height: 0; padding-bottom: 56.2493%;">\n                <iframe style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" src="'.concat(n,'" frameborder="0" allowfullscreen>\n                </iframe>\n            </div>'))})),r.a.create(document.querySelector("#editor"),{language:"ru",mediaEmbed:{previewsInData:!0,removeProviders:["instagram","twitter","googleMaps","flickr","facebook"]},ckfinder:{uploadUrl:"/pages/image-upload",options:{resourceType:"Images",headers:{"X-CSRF-TOKEN":e('meta[name="csrf-token"]').attr("content")}}}}).then((function(t){document.querySelector(".toolbar-container").prepend(t.ui.view.toolbar.element),t.model.document.on("change:data",(function(){e("#data-editor").val(t.getData())}))})).catch((function(e){console.error(e.stack)}))}.call(this,o(2))},13:function(e,t,o){var r=o(3),n=o(14);"string"==typeof(n=n.__esModule?n.default:n)&&(n=[[e.i,n,""]]);var a={insert:"head",singleton:!1},i=(r(n,a),n.locals?n.locals:{});e.exports=i},14:function(e,t,o){},15:function(e,t,o){"use strict";var r=o(10),n=o.n(r);o(34),o(36);window.Popper=o(9).default,window.axios=n.a,window.axios.defaults.headers.common["X-Requested-With"]="XMLHttpRequest";var a=document.head.querySelector('meta[name="csrf-token"]');a?window.axios.defaults.headers.common["X-CSRF-TOKEN"]=a.content:console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token")}});