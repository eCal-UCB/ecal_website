document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('pubSearch');
    const sortButton = document.getElementById('sortButton');
    let isAscending = true;

    // Function to get publication date (returns numerical value for sorting)
    function getPublicationDate(pub) {
        const journalSpan = pub.querySelector('.pubJournal');
        if (!journalSpan) return 999999; // Default for items without date
        const text = journalSpan.textContent;
        
        // Match month and year
        const months = {
            'january': 1, 'jan': 1,
            'february': 2, 'feb': 2,
            'march': 3, 'mar': 3,
            'april': 4, 'apr': 4,
            'may': 5,
            'june': 6, 'jun': 6,
            'july': 7, 'jul': 7,
            'august': 8, 'aug': 8,
            'september': 9, 'sep': 9, 'sept': 9,
            'october': 10, 'oct': 10,
            'november': 11, 'nov': 11,
            'december': 12, 'dec': 12
        };
        
        const yearMatch = text.match(/\b(19|20)\d{2}\b/);
        const year = yearMatch ? parseInt(yearMatch[0]) : 9999;
        
        // Extract month
        const monthMatch = text.match(new RegExp(Object.keys(months).join('|'), 'i'));
        const month = monthMatch ? months[monthMatch[0].toLowerCase()] : 12;
        
        // Return date as YYYYMM format for easy numerical comparison
        return year * 100 + month;
    }

    // Function to sort publications
    function sortPublications() {
        const sections = ['jPubs', 'cPubs'];
        
        sections.forEach(sectionClass => {
            const list = document.querySelector(`ol.${sectionClass}`);
            if (!list) return;

            const items = Array.from(list.children);
            items.sort((a, b) => {
                const dateA = getPublicationDate(a);
                const dateB = getPublicationDate(b);
                return isAscending ? dateA - dateB : dateB - dateA;
            });

            // Clear and re-append items
            items.forEach(item => list.appendChild(item));
        });

        // Update button text
        sortButton.innerHTML = isAscending ? 'Sort ↓' : 'Sort ↑';
        isAscending = !isAscending;
    }

    // Function to filter publications
    function filterPublications() {
        const searchTerm = searchInput.value.toLowerCase();
        const sections = document.querySelectorAll('#journals, #conf');
        
        sections.forEach(section => {
            let hasVisiblePubs = false;
            const pubs = section.nextElementSibling.nextElementSibling.querySelectorAll('.pubContainer');
            
            pubs.forEach(pub => {
                const title = pub.querySelector('.pubTitle').textContent.toLowerCase();
                const authors = pub.querySelector('.pubAuthors').textContent.toLowerCase();
                const isMatch = title.includes(searchTerm) || authors.includes(searchTerm);
                
                pub.classList.toggle('pub-hidden', !isMatch);
                if (isMatch) hasVisiblePubs = true;
            });
            
            // Hide section title if no visible publications
            section.nextElementSibling.classList.toggle('section-hidden', !hasVisiblePubs);
        });
    }

    // Event listeners
    searchInput.addEventListener('input', filterPublications);
    sortButton.addEventListener('click', sortPublications);
}); 