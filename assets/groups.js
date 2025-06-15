document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('pco-groups-search');
    const typeSelect = document.getElementById('pco-groups-type');
    const daySelect = document.getElementById('pco-groups-day');
    const locationSelect = document.getElementById('pco-groups-location');
    const cards = Array.from(document.querySelectorAll('.pco-group-card'));
    const noResults = document.getElementById('pco-groups-no-results');

    // Store all possible options for each dropdown
    const allTypes = Array.from(typeSelect.options).map(opt => opt.value).filter(v => v);
    const allDays = Array.from(daySelect.options).map(opt => opt.value).filter(v => v);
    const allLocations = Array.from(locationSelect.options).map(opt => opt.value).filter(v => v);

    // Helper to update dropdown options dynamically
    function updateDropdown(select, values, allLabel, map) {
        const current = select.value;
        select.innerHTML = `<option value="">${allLabel}</option>`;
        values.forEach(val => {
            const option = document.createElement('option');
            option.value = val;
            option.textContent = (map && map[val]) ? map[val] : val; // Use mapping if available
            select.appendChild(option);
        });
        if (values.includes(current)) {
            select.value = current; // Restore selection if still valid
        } else {
            select.value = ''; // Reset if the current value is no longer valid
        }
    }

    // Main filtering logic
    function filterCards(triggeredBy) {
        const term = searchInput.value.trim().toLowerCase();
        const selectedType = typeSelect.value;
        const selectedDay = daySelect.value;
        const selectedLocation = locationSelect.value;

        // Filter cards and collect visible options
        const visibleTypes = new Set();
        const visibleDays = new Set();
        const visibleLocations = new Set();

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            const type = card.getAttribute('data-type');
            const day = card.getAttribute('data-day');
            const location = card.getAttribute('data-location');

            const matchesSearch = !term || name.includes(term);
            const matchesType = !selectedType || type === selectedType;
            const matchesDay = !selectedDay || day === selectedDay;
            const matchesLocation = !selectedLocation || location === selectedLocation;

            const isVisible = matchesSearch && matchesType && matchesDay && matchesLocation;
            card.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                if (type) visibleTypes.add(type);
                if (day) visibleDays.add(day);
                if (location) visibleLocations.add(location);
            }
        });

        // Update dropdowns dynamically based on visible cards
        if (triggeredBy !== typeSelect) {
            updateDropdown(typeSelect, allTypes.filter(t => visibleTypes.has(t)), 'All Types', typeof PCO_GROUP_TYPE_MAP !== 'undefined' ? PCO_GROUP_TYPE_MAP : null);
        }
        if (triggeredBy !== daySelect) {
            updateDropdown(daySelect, allDays.filter(d => visibleDays.has(d)), 'All Days');
        }
        if (triggeredBy !== locationSelect) {
            updateDropdown(locationSelect, allLocations.filter(l => visibleLocations.has(l)), 'All Locations', typeof PCO_GROUP_LOCATION_MAP !== 'undefined' ? PCO_GROUP_LOCATION_MAP : null);
        }

        // Show/hide "no results" message
        const anyVisible = cards.some(card => card.style.display !== 'none');
        if (noResults) {
            noResults.style.display = anyVisible ? 'none' : '';
        }
    }

    // Attach event listeners to filters
    searchInput.addEventListener('input', function() { filterCards(searchInput); });
    typeSelect.addEventListener('change', function() { filterCards(typeSelect); });
    daySelect.addEventListener('change', function() { filterCards(daySelect); });
    locationSelect.addEventListener('change', function() { filterCards(locationSelect); });

    // Initial filtering to populate dropdowns
    filterCards();
});