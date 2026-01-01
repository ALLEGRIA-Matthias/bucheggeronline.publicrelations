// tableFilter.js oder Teil deiner main.js

/**
 * Ermittelt die (0-basierten) Spaltenindizes, die für die Filterung herangezogen werden sollen.
 * @param {HTMLTableElement} tableElement - Das Tabellenelement.
 * @param {HTMLInputElement} inputElement - Das Filter-Input-Element.
 * @returns {number[]} Ein Array von 0-basierten Spaltenindizes.
 */
function getColumnIndicesToSearch(tableElement, inputElement) {
    const headerCells = Array.from(tableElement.querySelectorAll('thead th'));
    let candidateIndices = [];

    // 1. Kandidaten basierend auf `data-filterable` in <th> bestimmen
    const thWithDataFilterable = headerCells.filter(th => th.hasAttribute('data-filterable'));
    if (thWithDataFilterable.length > 0) {
        // Wenn mindestens ein <th> 'data-filterable' hat, sind nur diese Spalten Kandidaten
        candidateIndices = headerCells.map((th, index) => th.hasAttribute('data-filterable') ? index : -1).filter(index => index !== -1);
    } else if (headerCells.length > 0) {
        // Wenn kein <th> 'data-filterable' hat, sind alle Spalten Kandidaten
        candidateIndices = Array.from({ length: headerCells.length }, (_, i) => i);
    } else {
        // Keine Header-Zellen -> keine spezifischen Spalten, der Filter durchsucht dann alle <td>s einer Zeile
        return [];
    }

    // 2. `data-filter-only` auf dem Input-Feld prüfen (hat Vorrang)
    const filterOnlyAttr = inputElement.dataset.filterOnly;
    if (filterOnlyAttr) {
        // Konvertiere 1-basierte, komma-separierte Spaltennummern in 0-basierte Indizes
        return filterOnlyAttr.split(',')
            .map(s => parseInt(s.trim(), 10) - 1)
            .filter(n => !isNaN(n) && n >= 0 && n < headerCells.length); // Gültige Indizes
    }

    // 3. `data-filter-not` auf dem Input-Feld prüfen (wenn `filterOnly` nicht gesetzt ist)
    const filterNotAttr = inputElement.dataset.filterNot;
    if (filterNotAttr) {
        // Konvertiere 1-basierte, komma-separierte Spaltennummern in 0-basierte Indizes
        const notIndices = filterNotAttr.split(',')
            .map(s => parseInt(s.trim(), 10) - 1)
            .filter(n => !isNaN(n) && n >= 0);
        return candidateIndices.filter(index => !notIndices.includes(index));
    }

    // 4. Wenn weder `filterOnly` noch `filterNot` gesetzt sind, die Kandidaten verwenden
    return candidateIndices;
}

/**
 * Aktualisiert den Textinhalt des Counter-Elements.
 * @param {HTMLElement} counterElement - Das HTML-Element des Counters.
 * @param {number} visibleCount - Anzahl der sichtbaren Zeilen.
 * @param {number} originalCount - Ursprüngliche Gesamtanzahl der Zeilen.
 * @param {string} label - Das Label für die gezählten Elemente (z.B. "Mailings").
 */
function updateCounter(counterElement, visibleCount, originalCount, label) {
    if (counterElement) {
        counterElement.textContent = `${visibleCount} von ${originalCount} ${label}`;
    }
}

/**
 * Initialisiert alle Tabellenfilter auf der Seite.
 */
export function initializeTableFilters() {
    const filterInputs = document.querySelectorAll('input.filter-table');

    filterInputs.forEach(inputElement => {
        const tableId = inputElement.dataset.filterTable;
        if (!tableId) {
            console.error('FEHLER: Filter-Input fehlt das data-filter-table Attribut.', inputElement);
            return;
        }

        const tableElement = document.getElementById(tableId);
        if (!tableElement) {
            console.error(`FEHLER: Tabelle mit ID "${tableId}" für Filter-Input nicht gefunden. Bitte prüfe das data-filter-table Attribut. Erwartet wurde z.B. "mailingslist", nicht "mailings".`, inputElement);
            return;
        }

        const tbodyElement = tableElement.querySelector('tbody');
        if (!tbodyElement) {
            console.error(`FEHLER: Tabelle mit ID "${tableId}" hat kein tbody Element.`, tableElement);
            return;
        }

        const allRows = Array.from(tbodyElement.querySelectorAll('tr')); // Alle Zeilen einmalig sammeln

        // Counter Setup
        let counterElement = null;
        let counterLabel = "";
        const originalRowCount = allRows.length; // Die tatsächliche Anzahl der Zeilen im tbody

        const counterId = inputElement.dataset.filterCounter;
        if (counterId) {
            counterElement = document.getElementById(counterId);
            if (counterElement) {
                // Setze das 'data-counter-original' Attribut basierend auf den tatsächlichen Zeilen
                counterElement.dataset.counterOriginal = originalRowCount;
                // Label vom Input nehmen, oder vom Counter-Element selbst als Fallback
                counterLabel = inputElement.dataset.counterLabel || counterElement.dataset.counterLabel || "";
                updateCounter(counterElement, originalRowCount, originalRowCount, counterLabel); // Initialer Zustand
            } else {
                console.warn(`WARNUNG: Counter-Element mit ID "${counterId}" nicht gefunden.`, inputElement);
            }
        }

        // Spalten bestimmen, die durchsucht werden sollen
        const columnIndicesToSearch = getColumnIndicesToSearch(tableElement, inputElement);

        inputElement.addEventListener('input', () => { // 'input' Event ist besser als 'keyup'
            const searchTerm = inputElement.value.toLowerCase().trim();
            let visibleRowCount = 0;

            allRows.forEach(row => {
                let rowSearchableText = '';
                const cells = row.querySelectorAll('td');

                if (columnIndicesToSearch.length > 0) {
                    columnIndicesToSearch.forEach(colIndex => {
                        if (cells[colIndex]) {
                            rowSearchableText += (cells[colIndex].textContent || "").toLowerCase() + " ";
                        }
                    });
                } else { // Fallback: Durchsuche alle Zellen, wenn keine spezifischen Spalten definiert sind
                    cells.forEach(cell => {
                        rowSearchableText += (cell.textContent || "").toLowerCase() + " ";
                    });
                }
                rowSearchableText = rowSearchableText.trim();

                if (rowSearchableText.includes(searchTerm)) {
                    row.style.display = ''; // Zeile anzeigen
                    visibleRowCount++;
                } else {
                    row.style.display = 'none'; // Zeile ausblenden
                }
            });

            if (counterElement) {
                updateCounter(counterElement, visibleRowCount, originalRowCount, counterLabel);
            }
        });

        // Initialen Filter auslösen, falls das Input-Feld bereits einen Wert hat (z.B. nach Neuladen mit ausgefüllter Suche)
        if (inputElement.value) {
            inputElement.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
}