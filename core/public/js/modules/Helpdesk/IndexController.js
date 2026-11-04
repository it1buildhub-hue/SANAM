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

/* global _, sanam_toast_error */

export class SanamHelpdeskIndexController
{
    constructor()
    {
        const input = this.#getSearchInput();
        const results_div = this.#getSearchResultsDiv();

        // Toggle search view when the search input is active.
        // We can't just use focus / focusout here because it is a bit too strict.
        // It is better to watch for clicks directly as we can keep the search
        // view enabled when the user click on the results.
        input.addEventListener('focus', (e) => this.#enableSearchView(e));
        input.addEventListener('click', (e) => this.#enableSearchView(e));
        results_div.addEventListener('click', (e) => this.#enableSearchView(e));
        document.addEventListener('click', (e) => this.#disableSearchView(e));

        // Execute search when someting is typed
        const searchDebounced = _.debounce(
            this.#executeSearch.bind(this), // .bind keep the correct "this" context
            400,
            false
        );
        input.addEventListener('input', searchDebounced);
    }

    async #executeSearch()
    {
        const input = this.#getSearchInput();
        try {
            const url = `${CFG_SANAM.root_doc}/Helpdesk/Search`;
            const url_params = new URLSearchParams({
                filter: input.value,
            });
            const response = await fetch(`${url}?${url_params}`);

            if (!response.ok) {
                throw new Error("Failed to load results");
            }
            this.#getSearchResultsDiv().innerHTML = await response.text();
        } catch (e) {
            console.error(e);
            sanam_toast_error(
                __("Unexpected error")
            );
        }
    }

    #enableSearchView(event)
    {
        event.stopPropagation();
        this.#getSearchOverlayDiv().style.opacity = 0.4;
        this.#getSearchOverlayDiv().style.visibility = "visible";
        this.#getSearchInput().classList.add('remove-bottom-border-radius');
        this.#getSearchResultsDiv().classList.remove('d-none');
    }

    #disableSearchView(event)
    {
        event.stopPropagation();
        this.#getSearchOverlayDiv().style.opacity = 0;
        this.#getSearchOverlayDiv().style.visibility = "hidden";
        this.#getSearchInput().classList.remove('remove-bottom-border-radius');
        this.#getSearchResultsDiv().classList.add('d-none');
    }

    #getSearchOverlayDiv()
    {
        return document.querySelector("#search-overlay");
    }

    #getSearchInput()
    {
        return document.querySelector("#search-input");
    }

    #getSearchResultsDiv()
    {
        return document.querySelector("#search-results");
    }
}
