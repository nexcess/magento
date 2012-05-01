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
var varienTabs = new Class.create();

varienTabs.prototype = {
    initialize : function(containerId, destElementId,  activeTabId){
        this.containerId    = containerId;
        this.destElementId  = destElementId;
        this.activeTab = null;

        this.tabOnClick     = this.tabMouseClick.bindAsEventListener(this);

        this.tabs = $$('#'+this.containerId+' li a.tab-item-link');

        this.hideAllTabsContent();
        for(var tab in this.tabs){
            Event.observe(this.tabs[tab],'click',this.tabOnClick);
            // move tab contents to destination element
            if($(this.destElementId)){
                var tabContentElement = $(this.getTabContentElementId(this.tabs[tab]));
                if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
                    $(this.destElementId).appendChild(tabContentElement);
                    tabContentElement.container = this;
                    tabContentElement.statusBar = this.tabs[tab];
                    tabContentElement.tabObject  = this.tabs[tab];
                    this.tabs[tab].contentMoved = true;
                    this.tabs[tab].container = this;
                    this.tabs[tab].show = function(){
                        this.container.showTabContent(this);
                    }
                    if(varienGlobalEvents){
                        varienGlobalEvents.fireEvent('moveTab', {tab:this.tabs[tab]});
                    }
                }
            }
        }

        this.displayFirst = activeTabId;
        Event.observe(window,'load',this.moveTabContentInDest.bind(this));
    },

    moveTabContentInDest : function(){
        for(var tab=0; tab<this.tabs.length; tab++){
            if($(this.destElementId) &&  !this.tabs[tab].contentMoved){
                var tabContentElement = $(this.getTabContentElementId(this.tabs[tab]));
                if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
                    $(this.destElementId).appendChild(tabContentElement);
                    tabContentElement.container = this;
                    tabContentElement.statusBar = this.tabs[tab];
                    tabContentElement.tabObject  = this.tabs[tab];
                    this.tabs[tab].container = this;
                    this.tabs[tab].show = function(){
                        this.container.showTabContent(this);
                    }
                    if(varienGlobalEvents){
                        varienGlobalEvents.fireEvent('moveTab', {tab:this.tabs[tab]});
                    }
                }
            }
        }
        if (this.displayFirst) {
            this.showTabContent($(this.displayFirst));
            this.displayFirst = null;
        }
    },

    getTabContentElementId : function(tab){
        if(tab){
            return tab.id+'_content';
        }
        return false;
    },

    tabMouseClick : function(event){
        var tab = Event.findElement(event, 'a');
        if(tab.href.indexOf('#') != tab.href.length-1){
            if(Element.hasClassName(tab, 'ajax')){

            }
            else{
                location.href = tab.href;
            }
        }
        else {
            this.showTabContent(tab);
        }

        Event.stop(event);
    },

    hideAllTabsContent : function(){
        for(var tab in this.tabs){
            this.hideTabContent(this.tabs[tab]);
        }
    },

    showTabContent : function(tab){
        this.hideAllTabsContent();
        var tabContentElement = $(this.getTabContentElementId(tab));
        if(tabContentElement){
            Element.show(tabContentElement);
            //new Effect.Appear(tabContentElement, {duration :0.3});
            Element.addClassName(tab, 'active');
            this.activeTab = tab;
        }
        if(varienGlobalEvents){
            varienGlobalEvents.fireEvent('showTab', {tab:tab});
        }
    },

    hideTabContent : function(tab){
        var tabContentElement = $(this.getTabContentElementId(tab));
        if($(this.destElementId) && tabContentElement){
           Element.hide(tabContentElement);
           Element.removeClassName(tab, 'active');
        }
        if(varienGlobalEvents){
            varienGlobalEvents.fireEvent('hideTab', {tab:tab});
        }
    }
}