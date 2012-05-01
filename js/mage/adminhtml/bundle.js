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
/**
 * JavaScript classes for bundle products
 *
 * @package     Adminhtml
 * @subpackage  Bundle
 */
if(typeof Bundle == 'undefined') {
    Bundle = {};
}

Bundle.Tabs = new Class.create();
Bundle.Tabs.prototype = {
    initialize : function(containerId, destElementId,  activeTabId, addTemplateId){
        this.containerId    = containerId;
        this.destElementId  = destElementId;
        this.addTemplate = new Template($(addTemplateId).innerHTML.replace(/_id_/g, "#{id}").replace(/_url_/g, "#{url}"));
        this.activeTab = null;
        
        this.tabOnClick     = this.tabMouseClick.bindAsEventListener(this);
        
        this.tabs = $$('#'+this.containerId+' li a.option-item-link');
                
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
                    this.tabs[tab].ajaxLoaded = false;
                    this.tabs[tab].show = function(){
                        this.container.showTabContent(this);
                    }
                    this.tabs[tab].remove = function(){
                    	if(this.container.getTabContentElementId(this)) {
                    		Element.remove($(this.container.getTabContentElementId(this)));
                    	}
                        Element.remove(this.parentNode);
                    }
                    this.tabs[tab].setLabel = function(value) {
                    	this.getElementsByClassName('option-label-text')[0].update(value.escapeHTML());
                    }
                }
            }
        }
        
        this.showTabContent($(activeTabId));
        Event.observe(window,'load',this.moveTabContentInDest.bind(this));
    },
    
    moveTabContentInDest : function(){
        for(var tab in this.tabs){
            if($(this.destElementId) &&  !this.tabs[tab].contentMoved){
                var tabContentElement = $(this.getTabContentElementId(this.tabs[tab]));
                if(tabContentElement && tabContentElement.parentNode.id != this.destElementId){
                    $(this.destElementId).appendChild(tabContentElement);
                    tabContentElement.container = this;
                    tabContentElement.statusBar = this.tabs[tab];
                    tabContentElement.tabObject  = this.tabs[tab];
                    this.tabs[tab].container = this;
                    this.tabs[tab].ajaxLoaded = false;
                    this.tabs[tab].show = function(){
                        this.container.showTabContent(this);
                    }
                    this.tabs[tab].remove = function(){
                    	if(this.container.getTabContentElementId(this)) {
                    		Element.remove($(this.container.getTabContentElementId(this)));
                    	}
                        Element.remove(this.parentNode);
                    }
                    this.tabs[tab].setLabel = function(value) {
                    	this.getElementsByClassName('option-label-text')[0].update(value.escapeHTML());
                    }
                }
            }
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
        
        if(tab.href.indexOf('#') != tab.href.length-1 && !tab.ajaxLoaded){
        	var options = {};
        	if(tab.parameters) {
        		options.parameters = tab.parameters;
        	}        	
        	options.evalScripts = true;
        	new Ajax.Updater(this.getTabContentElementId(tab), tab.href, options);
        	this.showTabContent(tab);
        	tab.ajaxLoaded = true;
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
            Element.addClassName(tab, 'active');
            this.activeTab = tab;
        }
        
    },
    
    hideTabContent : function(tab){
        var tabContentElement = $(this.getTabContentElementId(tab));
        if($(this.destElementId) && tabContentElement){
           Element.hide(tabContentElement);
           Element.removeClassName(tab, 'active');
        }
      
    },
    addTab		: function(options) {
    	options.id = this.getUniqueId();
    	if(!options.url) {
    		options.url = '#';
    	}
    	if(!options.content) {
    		options.content = '';
    	}
    	
    	var tabHtml = this.addTemplate.evaluate(options);
    	new Insertion.Bottom(this.containerId, tabHtml);
    	
    	this.tabs.push($(options.id));
    	
    	Event.observe(options.id,'click',this.tabOnClick);
    	this.moveTabContentInDest();
    	return this.tabs[this.tabs.length-1];
    },
    getUniqueId : function() {
    	if (!this.uniqueCount) {
    		this.uniqueCount = 1;
    	} else {
    		this.uniqueCount++;	
    	}    	
    	return this.containerId + '_tab_' + this.uniqueCount;
    }
}

Bundle.Options = Class.create();
Bundle.Options.prototype = {
	initialize: function(containerId, tabObject, gridUrl, fieldId, addTemplateHtmlId)
	{
		this.options = [];
		this.containerId = containerId;
		this.fieldId = fieldId;
		this.container = $(this.containerId);
		this.field = $(this.fieldId);
		
		this.addTemplate = new Template($(addTemplateHtmlId).innerHTML.replace(/new__id__/g, "#{id}").replace(/ disabled="no-validation"/g, '').replace(/ disabled/g, '').replace(/="'([^']*)'"/g, '="$1"'));
		this.validItemsField = $(addTemplateHtmlId + '_count_of_items');
        this.gridUrl = gridUrl;
		this.tabObject = tabObject;
		this.updateInput();		
	},
	addItem: function(optionId, label, position, products) {
		if(label.blank()) {
			label = this.optionLabelNewText + (this.options.length+1);
		}
		var templateVars = {
			id: this.getUniqueId(),
			label: label.escapeHTML(),
			position: parseInt(position),
			index: this.options.length
		};
		
		var optionHtml = this.addTemplate.evaluate(templateVars);
		new Insertion.Bottom(this.containerId, optionHtml);
		var option = $(templateVars.id);
		option.products = $H(products);
		option.optionId = optionId;
		option.label = label;
		option.position = position;
        option.container = this;
		option.tab = this.tabObject.addTab({
			label:label,
			url:  this.gridUrl
		});
		this.options.push(option);
		this.updateGrid(option);
		this.updateInput();
	},
	update: function(index) {
		var option = this.options[index];
		option.label = this.getInputValueByCss('option-label', option);
		option.position = this.getInputValueByCss('option-position', option);
		option.tab.setLabel(option.label);
		this.updateGrid(option);
		this.updateInput();
	},
	updateGrid: function(option) {
		option.tab.parameters = {};
		if(option.optionId) {
			option.tab.parameters.option = option.optionId;			
		}
		
		option.tab.parameters['products[]'] = $H(option.products).keys();
		option.tab.parameters.gridId = 'link_grid_' + this.options.indexOf(option);
		option.tab.parameters.jsController = this.jsController;
		option.tab.parameters.index = this.options.indexOf(option);
		
		if(option.grid) {
			option.grid.reloadParams = option.tab.parameters;
		}
	},
	updateInput: function () {
		this.field.value = this.getOptionsJSON();
        if(this.field.value != '[]') {
            this.validItemsField.value = 'have-items';
        } else {
            this.validItemsField.value = 'no-items';
        }
	},
	getOptionsJSON: function() {
		var result = [];
		this.options.each(function(option) {
			if(option) {
				result.push({
					label: option.label,
					position: option.position,
					id: option.optionId,
					links: option.products
				});
			}
		});
		
		return result.toJSON();
	},
	getInputValueByCss: function(css, elem) 
	{
		var inputs = elem.getElementsByClassName(css);
		
		if(inputs.length == 0) {
			return '';
		}
		
		return inputs[0].value;
	},
	deleteItem: function(index) {
		this.options[index].tab.remove();
		this.options[index].remove();
		this.options[index] = false;
		this.updateInput();
	},
	getUniqueId : function() {
    	if (!this.uniqueCount) {
    		this.uniqueCount = 1;
    	} else {
    		this.uniqueCount++;	
    	}    	
    	return this.containerId + '_option_' + this.uniqueCount;
    },
    initGrid:  function(index, grid) {
		this.options[index].grid  = grid;
		this.options[index].grid.tabIndex = 1000;
		this.options[index].grid.rowClickCallback = this.rowClick.bind(this);
		this.options[index].grid.initRowCallback = this.rowInit.bind(this);
		this.options[index].grid.checkboxCheckCallback = this.registerProduct.bind(this);
		this.options[index].grid.option = this.options[index];
		this.options[index].grid.rows.each(this.eachRow.bind(grid));
        this.updateGrid(this.options[index]);
        this.updateInput();
	},
	eachRow: function(row) {
		this.option.container.rowInit(this, row);
	},
	registerProduct: function(grid, element, checked) {
		if(checked){
            if(element.inputElements) {
            	grid.option.products[element.value]={};
                for(var i = 0; i < element.inputElements.length; i++) {
               		element.inputElements[i].disabled = false;
               		grid.option.products[element.value][element.inputElements[i].name] = element.inputElements[i].value;
                }
            }
        }
        else{
            if(element.inputElements){
            	for(var i = 0; i < element.inputElements.length; i++) {
                	element.inputElements[i].disabled = true;
            	}
            }

            grid.option.products.remove(element.value);
        }
        this.updateGrid(grid.option);
        this.updateInput();        
	},
	rowClick: function(grid, event) {
		var trElement = Event.findElement(event, 'tr');
        var isInput   = Event.element(event).tagName == 'INPUT';
        if(trElement){
            var checkbox = Element.getElementsBySelector(trElement, 'input');
            if(checkbox[0]){
                var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                grid.setCheckboxChecked(checkbox[0], checked);
            }
        }
	},
	inputChange:	 function(event) {
		var element = Event.element(event);
        if(element && element.checkboxElement && element.checkboxElement.checked){
            element.checkboxElement.grid.option.products[element.checkboxElement.value][element.name] = element.value;
	        this.updateGrid(element.checkboxElement.grid.option);
	        this.updateInput();
        }
	},
	rowInit: 		 function(grid, row) {
		var checkbox = $(row).getElementsByClassName('checkbox')[0];
        var inputs = $(row).getElementsByClassName('input-text');
        if(checkbox && inputs.length > 0) {
            checkbox.inputElements = inputs;
            checkbox.grid = grid;
            for(var i = 0; i < inputs.length; i++) {
            	inputs[i].checkboxElement = checkbox;
            	if(grid.option.products[checkbox.value] && grid.option.products[checkbox.value][inputs[i].name]) {
            		inputs[i].value = grid.option.products[checkbox.value][inputs[i].name];
            	}
            	inputs[i].disabled = !checkbox.checked;
            	inputs[i].tabIndex = grid.tabIndex++;
                Event.observe(inputs[i],'keyup', this.inputChange.bind(this));
                Event.observe(inputs[i],'change', this.inputChange.bind(this));
            }
        }
	}
}
