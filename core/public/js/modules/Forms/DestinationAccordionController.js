/**
 * ---------------------------------------------------------------------
 *
 * SANAM
 *
 *
 * @copyright 2015-2026 Teclib' and contributors.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of SANAM.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */

import { SanamFormDestinationAutoConfigController } from "/js/modules/Forms/DestinationAutoConfigController.js";
import { SanamFormDestinationConditionController } from "/js/modules/Forms/DestinationConditionController.js";

export class SanamFormDestinationAccordionController
{
    constructor() {
        this.#watchForAccordionToggle();
    }

    triggerWatchers() {
        new SanamFormDestinationAutoConfigController();
        new SanamFormDestinationConditionController();
    }

    #watchForAccordionToggle() {
        const accordionWrapper = document.querySelector('#sanam-destinations-accordion');

        accordionWrapper.addEventListener('show.bs.collapse', async (e) => {
            const accordionItem = e.target;
            const accordionItemContent = accordionItem.querySelector('.accordion-body');
            if (accordionItemContent.innerHTML.trim() !== '') {
                return;
            }

            accordionItemContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary mb-3" role="status"></div></div>';

            const content = await $.ajax({
                url: `${CFG_SANAM.root_doc}/Form/${accordionItem.dataset.form}/Destinations/${accordionItem.dataset.formDestination}`,
                method: 'GET',
            });

            // Note: must use `$().html` to make sure we trigger scripts
            $(accordionItemContent).html(content);

            // We trigger the watcher
            this.triggerWatchers();
        });

        accordionWrapper.classList.remove('pe-none');
    }
}
