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

var giftMessagesController = {
    toogleRequired: function(source, objects)
    {
        if(!$(source).value.blank()) {
            objects.each(function(item) {
               $(item).addClassName('required-entry');
            });
        } else {
            objects.each(function(item) {
                if($(source).formObj && $(source).formObj.validator) {
                    $(source).formObj.validator.reset(item);
                }
                $(item).removeClassName('required-entry');
            });
        }
    },
    toogleGiftMessage: function(container) {
        if(!$(container).toogleGiftMessage) {
            $(container).toogleGiftMessage = true;
            $(this.getFieldId(container, 'edit')).show();
            $(container).getElementsByClassName('action-link')[0].addClassName('open');
            $(container).getElementsByClassName('default-text')[0].hide();
            $(container).getElementsByClassName('close-text')[0].show();
            this.toogleRequired(this.getFieldId(container, 'message'), [
                this.getFieldId(container, 'sender'),
                this.getFieldId(container, 'recipient')
            ]);
        } else {
            $(container).toogleGiftMessage = false;
            $(this.getFieldId(container, 'message')).formObj = $(this.getFieldId(container, 'form'));

            if(!$(this.getFieldId(container, 'form')).validator) {
                $(this.getFieldId(container, 'form')).validator = new Validation(this.getFieldId(container, 'form'));
            }

            if(!$(this.getFieldId(container, 'form')).validator.validate()) {
                return false;
            }

            new Ajax.Updater(container, $(this.getFieldId(container, 'form')).action, {
                parameters: Form.serialize($(this.getFieldId(container, 'form')), true),
                loaderArea: container
            });
        }

        return false;
    },
    saveGiftMessage: function(container) {
        this.toogleRequired(this.getFieldId(container, 'message'), [
            this.getFieldId(container, 'sender'),
            this.getFieldId(container, 'recipient')
        ]);

        $(this.getFieldId(container, 'message')).formObj = $(this.getFieldId(container, 'form'));

        if(!$(this.getFieldId(container, 'form')).validator) {
            $(this.getFieldId(container, 'form')).validator = new Validation(this.getFieldId(container, 'form'));
        }

        if(!$(this.getFieldId(container, 'form')).validator.validate()) {
            return;
        }

        new Ajax.Request($(this.getFieldId(container, 'form')).action, {
            parameters: Form.serialize($(this.getFieldId(container, 'form')), true),
            loaderArea: container
        });
    },
    getFieldId: function(container, name) {
        return container + '_' + name;
    }
};

