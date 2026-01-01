import * as mdb from '/typo3conf/ext/ac_base/Resources/Public/MDBootstrap/js/mdb.es.min.js';

document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('events-container');
    if (!container) return;

    const clientUid = container.dataset.clientUid;
    const datatableElement = document.getElementById('datatable-events');
    const filters = container.querySelectorAll('.event-filter');
    let datatableInstance = null;
    let currentFilter = 'upcoming'; // Default-Filter

    const buildUrl = (action, params = {}) => {
        const url = new URL(window.location.origin);
        url.searchParams.set('type', '2024');
        url.searchParams.set('tx_publicrelations_presscenterajax[controller]', 'Pressecenter\\Ajax');
        url.searchParams.set('tx_publicrelations_presscenterajax[action]', action);
        url.searchParams.set('tx_publicrelations_presscenterajax[client]', clientUid);
        for (const key in params) {
            url.searchParams.set(`tx_publicrelations_presscenterajax[${key}]`, params[key]);
        }
        return url.toString();
    };

    const loadData = async (filterMode) => {
        try {
            const url = buildUrl('listEvents', { filterMode });
            console.log(url);
            const response = await fetch(url);
            const data = await response.json();
            updateUI(data);
        } catch (error) {
            console.error("Could not fetch event data:", error);
        }
    };

    const updateUI = (data) => {
        // Zähler aktualisieren
        document.getElementById('count-upcoming').textContent = data.counts.upcoming;
        document.getElementById('count-upcoming-with-guests').textContent = data.counts.upcoming_with_guests;
        document.getElementById('count-archived').textContent = data.counts.archived;

        // Daten für die Tabelle formatieren
        const formattedEvents = data.events.map(event => {
            const eventDate = new Date(event.date * 1000).toLocaleString('de-DE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
            const eventTime = new Date(event.date * 1000).toLocaleString('de-DE', {
                hour: '2-digit',
                minute: '2-digit'
            });
            const eventDateHtml = `<div class="fw-bold">${eventDate}</div><small class="text-muted">um ${eventTime} Uhr</small>`;
            const eventHtml = `<div class="fw-bold">${event.title}</div><small class="text-muted">${event.location_name || 'Kein Ort angegeben'}</small>`;
            const guestsHtml = `${event.guest_count} Gäste`;
            const actionsHtml = `
                <div class="btn-group shadow-0" role="group" aria-label="Aktionen">
                    <a href="${event.link_event_view}" class="btn btn-sm btn-link" title="Gästeliste ansehen">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>`;

            return {
                date: eventDateHtml,
                event: eventHtml,
                guests: guestsHtml,
                actions: actionsHtml,
                link: event.link_event_view
            };
        });

        if (datatableInstance) {
            datatableInstance.update({ rows: formattedEvents }, { forceRerender: true });
        } else {
            datatableInstance = new mdb.Datatable(datatableElement, {
                columns: [
                    { label: 'Datum', field: 'date', sort: true, width: 200 },
                    { label: 'Event', field: 'event', sort: true },
                    { label: 'Gästeinfo', field: 'guests', sort: false, width: 150 },
                    { label: 'Aktionen', field: 'actions', sort: false, width: 300 }
                ],
                rows: formattedEvents,
            }, {
                sm: true,
                striped: true,
                entries: 25,
                clickableRows: true
            });
            datatableElement.addEventListener('rowClicked.mdb.datatable', (e) => {
                // Prüfen, ob der Klick auf den Aktions-Buttons war.
                // e.target ist das Element, das geklickt wurde (z.B. das <i> Icon oder die <td> Zelle)
                if (e.target.closest('.btn-group')) {
                    return; // Klick war auf den Buttons, der <a>-Tag regelt das.
                }

                // Datenobjekt (mit 'link') aus der Instanz holen via e.index
                // (Der Screenshot bestätigt, dass e.index existiert)
                const rowData = datatableInstance._rows[e.index];

                // Wenn irgendwo anders in die Zeile geklickt wurde
                if (rowData && rowData.link) {
                    window.location.href = rowData.link;
                }
            });
        }
    };

    // Event Listener für die Filter
    filters.forEach(filter => {
        filter.addEventListener('click', () => {
            container.querySelector('.active-filter').classList.remove('active-filter');
            filter.classList.add('active-filter');
            currentFilter = filter.dataset.filter;
            loadData(currentFilter);
        });
    });

    // Initiales Laden der Daten
    loadData(currentFilter);
});