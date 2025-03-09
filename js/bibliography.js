async function loadBibliography() {
    try {
        // Create a container for the publications
        const publicationsContainer = document.createElement('div');
        publicationsContainer.id = 'publications-container';
        
        // Add a loading message
        publicationsContainer.innerHTML = '<p>Loading publications...</p>';
        
        // Add the container after the title
        const titleLine = document.querySelector('#search-container');
        titleLine.parentNode.insertBefore(publicationsContainer, titleLine.nextSibling);

        // Fetch the BibTeX file
        const response = await fetch('pubs/moura-biblio.bib');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const bibText = await response.text();
        
        // Parse BibTeX entries
        let entries = parseBibTeX(bibText);
        
        // Create the publications list
        const publicationsList = document.createElement('ol');
        publicationsList.className = 'publications';
        
        // Add the publications list to the container
        publicationsContainer.innerHTML = '';
        publicationsContainer.appendChild(publicationsList);
        
        // Function to sort entries
        let isAscending = true;
        function sortEntries() {
            const items = Array.from(publicationsList.children);
            items.sort((a, b) => {
                const dateA = getPublicationDate(a);
                const dateB = getPublicationDate(b);
                return isAscending ? dateA - dateB : dateB - dateA;
            });
            
            // Clear and re-append items
            publicationsList.innerHTML = '';
            items.forEach(item => publicationsList.appendChild(item));
            
            // Update button text and direction
            const sortButton = document.getElementById('sortButton');
            sortButton.innerHTML = isAscending ? 'Sort &darr;' : 'Sort &uarr;';
            isAscending = !isAscending;
        }
        
        // Function to get publication date value
        function getPublicationDate(pub) {
            const journalSpan = pub.querySelector('.pubJournal');
            if (!journalSpan) return 999999;
            const text = journalSpan.textContent;
            
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
            
            const monthMatch = text.match(new RegExp(Object.keys(months).join('|'), 'i'));
            const month = monthMatch ? months[monthMatch[0].toLowerCase()] : 12;
            
            return year * 100 + month;
        }
        
        // Create and append publication entries
        entries.forEach(entry => {
            const li = document.createElement('li');
            li.className = 'pubContainer';
            
            // Format authors
            const authors = entry.author.split(' and ')
                .map(author => author.trim())
                .join(', ');
            
            // Format journal info
            let journalInfo = entry.journal;
            // if (entry.volume) journalInfo += `, ${entry.volume}`;
            // if (entry.number) journalInfo += `(${entry.number})`;
            // if (entry.pages) journalInfo += `, ${entry.pages}`;
            
            // Format date
            const monthName = entry.month ? capitalizeFirstLetter(entry.month) : '';
            const year = entry.year || '';
            const date = [monthName, year].filter(Boolean).join(' ');
            if (date) journalInfo += `, ${date}`;
            
            li.innerHTML = `
                <span class="pubTitle">
                    ${entry.doi ? 
                      `<a href="https://doi.org/${entry.doi}">${entry.title}</a>` :
                      entry.title}
                </span>
                <span class="pubAuthors">${authors}</span>
                <span class="pubJournal">${journalInfo}</span>
                ${entry.doi ? `
                <span class="pubInfo">
                    DOI: <a href="https://doi.org/${entry.doi}">${entry.doi}</a>
                </span>` : ''}
            `;
            
            publicationsList.appendChild(li);
        });
        
        // Add event listeners
        const sortButton = document.getElementById('sortButton');
        sortButton.addEventListener('click', sortEntries);
        
        const searchInput = document.getElementById('pubSearch');
        searchInput.addEventListener('input', () => {
            const searchTerm = searchInput.value.toLowerCase();
            const publications = document.querySelectorAll('.pubContainer');
            
            publications.forEach(pub => {
                const title = pub.querySelector('.pubTitle').textContent.toLowerCase();
                const authors = pub.querySelector('.pubAuthors').textContent.toLowerCase();
                const journal = pub.querySelector('.pubJournal').textContent.toLowerCase();
                const isMatch = title.includes(searchTerm) || 
                              authors.includes(searchTerm) || 
                              journal.includes(searchTerm);
                
                pub.classList.toggle('pub-hidden', !isMatch);
            });
        });
        
        // Initial sort (newest first)
        sortEntries();
        
    } catch (error) {
        console.error('Error loading bibliography:', error);
        const publicationsContainer = document.getElementById('publications-container');
        if (publicationsContainer) {
            publicationsContainer.innerHTML = '<p style="color: red;">Error loading publications. Please try again later.</p>';
        }
    }
}

function parseBibTeX(text) {
    const entries = [];
    const entryRegex = /@(\w+)\s*{\s*([^,]*),\s*((?:[^@]*[^@\s])?)\s*}/g;
    
    let match;
    while ((match = entryRegex.exec(text)) !== null) {
        const [_, type, key, content] = match;
        const entry = { type, key };
        
        // More precise field matching that handles multi-line values
        const fieldMatches = content.matchAll(/(\w+)\s*=\s*["{]([^}"]+)[}"],?/g);
        for (const fieldMatch of fieldMatches) {
            const [_, field, value] = fieldMatch;
            // Clean up the value: remove extra whitespace and line breaks
            entry[field.toLowerCase()] = value
                .replace(/\s+/g, ' ')
                .replace(/\n/g, ' ')
                .trim();
        }
        
        entries.push(entry);
    }
    
    return entries;
}

function capitalizeFirstLetter(string) {
    if (!string) return '';
    // Handle abbreviated months (e.g., "jan" -> "Jan")
    if (string.length <= 3) {
        return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
    }
    // Handle full month names (e.g., "january" -> "January")
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

// Load bibliography when DOM is ready
document.addEventListener('DOMContentLoaded', loadBibliography); 