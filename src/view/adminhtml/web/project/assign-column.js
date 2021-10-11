define([
    'mage/adminhtml/grid'
], function () {
    'use strict';

    return function (config) {
        var selectedEntities = config.selectedEntities,
            projectEntities = new Set(selectedEntities),
            gridJsObject = window[config.gridJsObjectName],
            gridParam = config.gridParam,
            name = config.name
        $(name).value = JSON.stringify([...projectEntities]);

        /**
         * Register Project Entity
         *
         * @param {Object} grid
         * @param {Object} element
         * @param {Boolean} checked
         */
        function registerProjectEntity(grid, element, checked) {
            if (checked) {
                projectEntities.add(element.value);
            } else {
                projectEntities.delete(element.value);
            }
            $(name).value = JSON.stringify([...projectEntities]);
            grid.reloadParams[gridParam] = projectEntities.keys();
        }

        /**
         * Click on entity row
         *
         * @param {Object} grid
         * @param {String} event
         */
        function projectEntityRowClick(grid, event) {
            var trElement = Event.findElement(event, 'tr'),
                eventElement = Event.element(event),
                isInputCheckbox = eventElement.tagName === 'INPUT' && eventElement.type === 'checkbox',
                isInputPosition = grid.targetElement &&
                    grid.targetElement.tagName === 'INPUT' &&
                    grid.targetElement.name === 'position',
                checked = false,
                checkbox = null;

            if (eventElement.tagName === 'LABEL' &&
                trElement.querySelector('#' + eventElement.htmlFor) &&
                trElement.querySelector('#' + eventElement.htmlFor).type === 'checkbox'
            ) {
                event.stopPropagation();
                trElement.querySelector('#' + eventElement.htmlFor).trigger('click');

                return;
            }

            if (trElement && !isInputPosition) {
                checkbox = Element.getElementsBySelector(trElement, 'input');

                if (checkbox[0]) {
                    checked = isInputCheckbox ? checkbox[0].checked : !checkbox[0].checked;
                    gridJsObject.setCheckboxChecked(checkbox[0], checked);
                }
            }
        }

        gridJsObject.rowClickCallback = projectEntityRowClick;
        gridJsObject.checkboxCheckCallback = registerProjectEntity;
    };
});
