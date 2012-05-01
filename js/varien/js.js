/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
function popWin(url,win,para) {
    var win = window.open(url,win,para);
    win.focus();
}

function setLocation(url){
    window.location.href = url;
}

function setPLocation(url, setFocus){
    if( setFocus ) {
        window.opener.focus();
    }
    window.opener.location.href = url;
}

function setLanguageCode(code, fromCode){
    //TODO: javascript cookies have different domain and path than php cookies
    var href = window.location.href;
    var after = '', dash;
    if (dash = href.match(/\#(.*)$/)) {
        href = href.replace(/\#(.*)$/, '');
        after = dash[0];
    }

    if (href.match(/[?]/)) {
        var re = /([?&]store=)[a-z0-9_]*/;
        if (href.match(re)) {
            href = href.replace(re, '$1'+code);
        } else {
            href += '&store='+code;
        }
    } else {
        href += '?store='+code;
    }
    if (typeof(fromCode) != 'undefined') {
        href += '&from_store='+fromCode;
    }
    href += after;

    setLocation(href);
}

/**
 * Set "odd", "even", "first" and "last" CSS classes for table rows and cells
 */
function decorateTable(table){
    table = $(table);
    if(table){
        var allRows = table.getElementsBySelector('tr')
        var bodyRows = table.getElementsBySelector('tbody tr');
        var headRows = table.getElementsBySelector('thead tr');
        var footRows = table.getElementsBySelector('tfoot tr');

        for(var i=0; i<bodyRows.length; i++){
            if((i+1)%2==0) {
                bodyRows[i].addClassName('even');
            }
            else {
                bodyRows[i].addClassName('odd');
            }
        }

        if(headRows.length) {
          headRows[0].addClassName('first');
          headRows[headRows.length-1].addClassName('last');
        }
        if(bodyRows.length) {
          bodyRows[0].addClassName('first');
          bodyRows[bodyRows.length-1].addClassName('last');
        }
        if(footRows.length) {
          footRows[0].addClassName('first');
          footRows[footRows.length-1].addClassName('last');
        }
        if(allRows.length) {
            for(var i=0;i<allRows.length;i++){
                var cols =allRows[i].getElementsByTagName('TD');
                if(cols.length) {
                    Element.addClassName(cols[cols.length-1], 'last');
                };
            }
        }
    }
}

/**
 * Set "odd", "even" and "last" CSS classes for list items
 */
function decorateList(list){
    if($(list)){
        var items = $(list).getElementsBySelector('li')
        if(items.length) items[items.length-1].addClassName('last');
        for(var i=0; i<items.length; i++){
            if((i+1)%2==0)
                items[i].addClassName('even');
            else
                items[i].addClassName('odd');
        }
    }
}

/**
 * Set "odd", "even" and "last" CSS classes for list items
 */
function decorateDataList(list){
  list = $(list);
    if(list){
        var items = list.getElementsBySelector('dt')
        if(items.length) items[items.length-1].addClassName('last');
        for(var i=0; i<items.length; i++){
            if((i+1)%2==0)
                items[i].addClassName('even');
            else
                items[i].addClassName('odd');
        }
        var items = list.getElementsBySelector('dd')
        if(items.length) items[items.length-1].addClassName('last');
        for(var i=0; i<items.length; i++){
            if((i+1)%2==0)
                items[i].addClassName('even');
            else
                items[i].addClassName('odd');
        }
    }
}


// Version 1.0
var isIE = navigator.appVersion.match(/MSIE/) == "MSIE";

if (!window.Varien)
    var Varien = new Object();

Varien.showLoading = function(){
    Element.show('loading-process');
}
Varien.hideLoading = function(){
    Element.hide('loading-process');
}
Varien.GlobalHandlers = {
    onCreate: function() {
        Varien.showLoading();
    },

    onComplete: function() {
        if(Ajax.activeRequestCount == 0) {
            Varien.hideLoading();
        }
    }
};

Ajax.Responders.register(Varien.GlobalHandlers);

/**
 * Quick Search form client model
 */
Varien.searchForm = Class.create();
Varien.searchForm.prototype = {
    initialize : function(form, field, emptyText){
        this.form   = $(form);
        this.field  = $(field);
        this.emptyText = emptyText;

        Event.observe(this.form,  'submit', this.submit.bind(this));
        Event.observe(this.field, 'focus', this.focus.bind(this));
        Event.observe(this.field, 'blur', this.blur.bind(this));
        this.blur();
    },

    submit : function(event){
        if (this.field.value == this.emptyText || this.field.value == ''){
            Event.stop(event);
            return false;
        }
        return true;
    },

    focus : function(event){
        if(this.field.value==this.emptyText){
            this.field.value='';
        }

    },

    blur : function(event){
        if(this.field.value==''){
            this.field.value=this.emptyText;
        }
    },

    initAutocomplete : function(url, destinationElement){
        new Ajax.Autocompleter(
            this.field,
            destinationElement,
            url,
            {
                paramName: this.field.name,
                minChars: 2,
                updateElement: this._selectAutocompleteItem.bind(this)
            }
        );
    },

    _selectAutocompleteItem : function(element){
        if(element.title){
            this.field.value = element.title;
        }
        this.submit();
    }
}

Varien.Tabs = Class.create();
Varien.Tabs.prototype = {
  initialize: function(selector) {
    var self=this;
    $$(selector+' a').each(function(el){
      el.href = 'javascript:void(0)';
      el.observe('click', self.showContent.bind(self, el));
      if (el.parentNode.hasClassName('active')) {
        self.showContent(el);
      }
    });
  },

  showContent: function(a) {
    var li = a.parentNode, ul = li.parentNode;
    ul.getElementsBySelector('li', 'ol').each(function(el){
      var contents = $(el.id+'_contents');
      if (el==li) {
        el.addClassName('active');
        contents.show();
      } else {
        el.removeClassName('active');
        contents.hide();
      }
    });
  }
}