// Challenge Page JavaScript

// Toggle Admin Dropdown
function toggleDropdown() {
    const dropdown = document.getElementById('adminDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function (event) {
    const dropdown = document.getElementById('adminDropdown');
    const button = document.querySelector('.nav-dropdown-btn');

    if (button && dropdown && !button.contains(event.target) && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Filter Challenges Function
function filterChallenges() {
    // 1. Get all filter values
    const searchTerm = document.getElementById('searchInput') ? document.getElementById('searchInput').value.toLowerCase() : '';
    const selectedCategory = document.getElementById('categoryFilter') ? document.getElementById('categoryFilter').value : '';
    const selectedCity = document.getElementById('cityFilter') ? document.getElementById('cityFilter').value : '';
    const selectedPointsRange = document.getElementById('pointsFilter') ? document.getElementById('pointsFilter').value : '';
    const selectedDifficulty = document.getElementById('difficultyFilter') ? document.getElementById('difficultyFilter').value : '';

    const cards = document.querySelectorAll('.challenge-card');

    cards.forEach(card => {
        // 2. Get card data attributes
        const title = card.getAttribute('data-title').toLowerCase();
        const category = card.getAttribute('data-category');
        const city = card.getAttribute('data-city');
        const points = parseInt(card.getAttribute('data-points'));
        const difficulty = card.getAttribute('data-difficulty');

        // 3. Check matches
        const matchesSearch = title.includes(searchTerm);
        const matchesCategory = !selectedCategory || category === selectedCategory;
        const matchesCity = !selectedCity || city === selectedCity;

        // Difficulty Match
        const matchesDifficulty = !selectedDifficulty || difficulty === selectedDifficulty;

        let matchesPoints = true;
        if (selectedPointsRange) {
            if (selectedPointsRange === '0-200') {
                matchesPoints = points >= 0 && points <= 200;
            } else if (selectedPointsRange === '201-500') {
                matchesPoints = points >= 201 && points <= 500;
            } else if (selectedPointsRange === '501-1000') {
                matchesPoints = points >= 501 && points <= 1000;
            } else if (selectedPointsRange === '1000+') {
                matchesPoints = points > 1000;
            }
        }

        // 4. Show or Hide the PARENT WRAPPER
        // Finding the parent <a> tag ensures the grid gap disappears
        const cardWrapper = card.closest('.card-link-wrapper');

        if (cardWrapper) {
            if (matchesSearch && matchesCategory && matchesCity && matchesPoints && matchesDifficulty) {
                // Use empty string '' to let CSS control the display (block/flex/grid)
                // distinct from 'block' which might override CSS rules
                cardWrapper.style.display = ''; 
            } else {
                cardWrapper.style.display = 'none';
            }
        }
    });
}

// Add Event Listeners when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const cityFilter = document.getElementById('cityFilter');
    const pointsFilter = document.getElementById('pointsFilter');
    const difficultyFilter = document.getElementById('difficultyFilter');

    if(searchInput) searchInput.addEventListener('input', filterChallenges);
    if(categoryFilter) categoryFilter.addEventListener('change', filterChallenges);
    if(cityFilter) cityFilter.addEventListener('change', filterChallenges);
    if(pointsFilter) pointsFilter.addEventListener('change', filterChallenges);
    if(difficultyFilter) difficultyFilter.addEventListener('change', filterChallenges);
});