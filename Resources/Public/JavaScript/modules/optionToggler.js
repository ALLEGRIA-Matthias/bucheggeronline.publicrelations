export function initializeOptionToggler() {
    // Find all radio buttons that are part of a toggle group
    const togglers = document.querySelectorAll('input[type="radio"][data-toggle-group]');

    if (!togglers.length) {
        return;
    }

    // Keep track of which groups we've already attached listeners to
    const initializedGroups = new Set();

    togglers.forEach(toggler => {
        const groupName = toggler.getAttribute('data-toggle-group');
        if (initializedGroups.has(groupName)) {
            return; // Listener for this group is already set up
        }

        // Get all radios within the same group
        const groupRadios = document.querySelectorAll(`input[type="radio"][data-toggle-group="${groupName}"]`);

        const toggleLogic = () => {
            groupRadios.forEach(radio => {
                // Get the target selector from the specific radio
                const targetSelector = radio.getAttribute('data-toggle-target');
                if (!targetSelector) return;

                const targetElement = document.querySelector(targetSelector);
                if (!targetElement) return;

                // Check if this radio is the selected one
                if (radio.checked) {
                    targetElement.classList.remove('d-none');
                } else {
                    targetElement.classList.add('d-none');
                }
            });
        };

        // Attach the listener to each radio in the group
        groupRadios.forEach(radio => {
            radio.addEventListener('change', toggleLogic);
        });

        // Run it once on initialization to set the correct initial state
        toggleLogic();
        
        initializedGroups.add(groupName);
    });
}