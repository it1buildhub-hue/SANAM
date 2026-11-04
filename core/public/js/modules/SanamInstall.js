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

/* global getAjaxCsrfToken */

import { ProgressIndicator } from '/js/modules/ProgressIndicator.js';

export function init_database()
{
    const messages_container = document.getElementById('sanam_install_messages_container');
    const success_container = document.getElementById('sanam_install_success');
    const back_button_container = document.getElementById('sanam_install_back');

    const request = new Request(
        `${CFG_SANAM.root_doc}/Install/InitDatabase`,
        {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Sanam-Csrf-Token': getAjaxCsrfToken(),
            },
        },
    );

    const progress_indicator = new ProgressIndicator({
        container: messages_container,
        request: request,
        success_callback: () => {
            success_container.querySelector('button[type="submit"]').removeAttribute('disabled');
            success_container.setAttribute('class', 'd-inline');
        },
        error_callback: () => {
            back_button_container.querySelector('button[type="submit"]').removeAttribute('disabled');
            back_button_container.setAttribute('class', 'd-inline');
        },
    });

    progress_indicator.start();
}

export async function update_database()
{
    const messages_container = document.getElementById('sanam_update_messages_container');
    const success_container = document.getElementById('sanam_update_success');

    const request = new Request(
        `${CFG_SANAM.root_doc}/Install/UpdateDatabase`,
        {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded;',
                'X-Requested-With': 'XMLHttpRequest',
                'X-Sanam-Csrf-Token': getAjaxCsrfToken(),
            },
        },
    );

    const progress_indicator = new ProgressIndicator({
        container: messages_container,
        request: request,
        success_callback: () => {
            success_container.querySelector('button[type="submit"]').removeAttribute('disabled');
            success_container.setAttribute('class', 'd-inline');
        },
    });

    progress_indicator.start();
}
