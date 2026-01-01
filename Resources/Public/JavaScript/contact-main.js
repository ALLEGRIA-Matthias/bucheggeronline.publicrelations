/**
 * Dieses Haupt-Skript durchsucht das DOM nach [data-module] Attributen
 * und lädt dynamisch die entsprechenden JavaScript-Module aus dem /library Ordner.
 */

// Der DOMContentLoaded-Listener wird entfernt.
// Wir können direkt starten.

const elements = document.querySelectorAll('[data-module]');

elements.forEach(el => {
  const moduleName = el.dataset.module; // z.B. "contact-list-view"

  // Dynamischer Import basierend auf dem Modulnamen
  import(`./library/${moduleName}.js`)
    .then(module => {
      // Wir erwarten, dass jedes Modul eine default-Funktion exportiert
      if (module.default && typeof module.default === 'function') {
        // Wir führen die Funktion aus und übergeben das HTML-Element
        // als Kontext, damit das Modul weiß, wo es arbeiten soll.
        module.default(el);
      }
    })
    .catch(err => console.error(`[ac_contacts] Modul ${moduleName} konnte nicht geladen werden:`, err));
});