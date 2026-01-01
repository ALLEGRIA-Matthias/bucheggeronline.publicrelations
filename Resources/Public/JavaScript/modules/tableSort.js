import Tablesort from '@typo3/backend/sortable-table.js';

export function initializeTableSort() {
    // 1. Finde alle Tabellen, die sortierbar sein sollen
    const sortableTables = document.querySelectorAll('table.table-sort');

    // 2. Iteriere durch jede gefundene Tabelle und initialisiere Tablesort einzeln
    if (sortableTables.length > 0) {
        sortableTables.forEach(tableElement => {
            if (tableElement) { // Zusätzliche Sicherheitsprüfung
                try {
                    new Tablesort(tableElement, {
                        // Hier deine Tablesort-Optionen, falls benötigt
                        // z.B. descending: true (falls Standard absteigend sein soll)
                    });
                    // console.log('Tablesort initialisiert für Tabelle:', tableElement);
                } catch (e) {
                    console.error('Fehler bei der Initialisierung von Tablesort für eine Tabelle:', tableElement, e);
                }
            }
        });
    }
}