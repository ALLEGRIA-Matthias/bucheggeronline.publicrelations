// Beispiel: utils.js oder formHelpers.js

export function closeWindow() {
  if (confirm("Fenster schließen?")) {
    window.close();
  }
}

// Für formNumberPlus/Minus: Besser Event-Listener nutzen (siehe unten),
// aber hier die direkte Übersetzung, falls onclick="formNumberPlus(this)" bleibt:
export function formNumberPlus(buttonElement) {
  const targetSelector = buttonElement.getAttribute("data-target");
  if (!targetSelector) return;
  const target = document.querySelector(targetSelector);
  if (!target) return;

  let targetValue = parseInt(target.value, 10);
  if (isNaN(targetValue)) targetValue = 0;

  const maximum = target.getAttribute("max");
  const step = 1; // Oder aus data-step Attribut lesen

  if (maximum !== null) {
    if (targetValue + step <= parseInt(maximum, 10)) {
      target.value = targetValue + step;
    }
  } else {
    target.value = targetValue + step;
  }
  target.dispatchEvent(new Event('change', { bubbles: true })); // Falls andere Skripte darauf reagieren
}

export function formNumberMinus(buttonElement) {
  const targetSelector = buttonElement.getAttribute("data-target");
  if (!targetSelector) return;
  const target = document.querySelector(targetSelector);
  if (!target) return;

  let targetValue = parseInt(target.value, 10);
  if (isNaN(targetValue)) targetValue = 0;

  const minimum = target.getAttribute("min");
  const step = 1; // Oder aus data-step Attribut lesen

  if (minimum !== null) {
    if (targetValue - step >= parseInt(minimum, 10)) {
      target.value = targetValue - step;
    }
  } else {
    target.value = targetValue - step;
  }
  target.dispatchEvent(new Event('change', { bubbles: true }));
}

// Moderne Clipboard API
export async function copyTextToClipboard(text) {
  if (!navigator.clipboard) {
    // Fallback für sehr alte Browser (deine alte Methode, leicht angepasst)
    console.warn('Clipboard API not available, using fallback.');
    var dummy = document.createElement("textarea");
    document.body.appendChild(dummy);
    dummy.value = text;
    dummy.select();
    try {
      document.execCommand("copy");
    } catch (err) {
      console.error("Fallback copy failed", err);
    }
    document.body.removeChild(dummy);
    return;
  }
  try {
    await navigator.clipboard.writeText(text);
    // Optional: Nutzerfeedback geben, z.B. mit einem kleinen Toast/Alert
    // alert("Text kopiert!");
  } catch (err) {
    console.error('Fehler beim Kopieren des Textes: ', err);
  }
}