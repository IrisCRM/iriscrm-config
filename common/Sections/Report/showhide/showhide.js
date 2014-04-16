/*
A Prototype (version >=1.6) port of some of the functionality found in the original domCollapse:

   http://onlinetools.org/tools/domcollapse/

Released under the MIT license:

Copyright (c) 2008 Jonathan Callahan

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
*/

function toggleTarget(event) {
  // NOTE:  Within an event handler, 'this' always refers to the element they are registered on.
  this.next().toggle();
  if ( this.next().visible() ) {
    this.addClassName('expanded');
//    this.down('img').replace('<img src="./style/images/minus.gif" alt="hide section" />');
    this.down('div').replace('<div class="minus"></div>');
  } else {
    this.removeClassName('expanded');
//    this.down('img').replace('<img src="./style/images/plus.gif" alt="show section" />');
    this.down('div').replace('<div class="plus"></div>');
  }
}

document.observe('dom:loaded', function() {
  var triggers = $$('.trigger');
  for (var i = 0, len = triggers.length; i < len; ++i) {
//    triggers[i].insert({ top: '<img src="./style/images/plus.gif" alt="show section" />' });
    triggers[i].insert({ top: '<div class="plus"></div>' });
    triggers[i].next().hide_();
    triggers[i].observe('click',toggleTarget);
  }
  var expandeds = $$('.expanded');
  for (var i = 0, len = expandeds.length; i < len; ++i) {
//    expandeds[i].insert({ top: '<img src="./style/images/minus.gif" alt="hide section" />' });
    expandeds[i].insert({ top: '<div class="minus"></div>' });
//    expandeds[i].insert({ top: '<span class="minus">&nbsp;</span>' });
    expandeds[i].addClassName('trigger');
    expandeds[i].observe('click',toggleTarget);
  }
});

