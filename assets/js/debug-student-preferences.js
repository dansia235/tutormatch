/**
 * Debug script for student preferences search functionality
 * This will be loaded in the student preferences page to help diagnose search issues
 */
console.log("Debug script loaded for student preferences");

document.addEventListener('DOMContentLoaded', function() {
    // Add more detailed logging to the search input
    const searchInput = document.querySelector('[data-student-preferences-target="internshipSearch"]');
    if (searchInput) {
        console.log("Search input found:", searchInput);
        
        // Add a more visible placeholder
        searchInput.setAttribute('placeholder', 'Saisissez une lettre pour rechercher un stage...');
        
        // Monitor input events
        searchInput.addEventListener('input', function(event) {
            console.log("Search input event:", event.target.value);
        });
        
        // Add a button to test search directly
        const searchButton = document.createElement('button');
        searchButton.className = 'btn btn-primary mt-2';
        searchButton.innerText = 'Test Search (Debug)';
        searchButton.addEventListener('click', function() {
            const term = searchInput.value.trim() || 'test';
            console.log("Testing search with term:", term);
            
            // Make a direct fetch request to the API
            fetch(`/tutoring/api/internships/search.php?term=${encodeURIComponent(term)}`)
                .then(response => {
                    console.log("Search response status:", response.status);
                    return response.json();
                })
                .then(data => {
                    console.log("Search response data:", data);
                    
                    // Display results for debugging
                    const debugDiv = document.getElementById('search-debug-results') || document.createElement('div');
                    debugDiv.id = 'search-debug-results';
                    debugDiv.className = 'mt-3 p-3 border bg-light';
                    
                    if (data.success) {
                        debugDiv.innerHTML = `
                            <h5>Search Results (${data.count})</h5>
                            <pre>${JSON.stringify(data.data, null, 2)}</pre>
                        `;
                    } else {
                        debugDiv.innerHTML = `
                            <h5 class="text-danger">Error</h5>
                            <p>${data.message || 'Unknown error'}</p>
                        `;
                    }
                    
                    // Add to page if not already there
                    if (!document.getElementById('search-debug-results')) {
                        const cardBody = searchInput.closest('.card-body');
                        if (cardBody) {
                            cardBody.appendChild(debugDiv);
                        }
                    }
                })
                .catch(error => {
                    console.error("Error testing search:", error);
                    
                    // Display error for debugging
                    const debugDiv = document.getElementById('search-debug-results') || document.createElement('div');
                    debugDiv.id = 'search-debug-results';
                    debugDiv.className = 'mt-3 p-3 border bg-light';
                    debugDiv.innerHTML = `
                        <h5 class="text-danger">Error</h5>
                        <p>${error.message}</p>
                    `;
                    
                    // Add to page if not already there
                    if (!document.getElementById('search-debug-results')) {
                        const cardBody = searchInput.closest('.card-body');
                        if (cardBody) {
                            cardBody.appendChild(debugDiv);
                        }
                    }
                });
        });
        
        // Add the button to the page
        const cardBody = searchInput.closest('.card-body');
        if (cardBody) {
            const debugContainer = document.createElement('div');
            debugContainer.className = 'debug-container mt-2 mb-3';
            debugContainer.appendChild(searchButton);
            
            // Add a note about the debug mode
            const debugNote = document.createElement('div');
            debugNote.className = 'mt-2 text-muted small';
            debugNote.innerHTML = '<i class="bi bi-info-circle"></i> Mode debug activé pour tester la recherche';
            debugContainer.appendChild(debugNote);
            
            cardBody.insertBefore(debugContainer, cardBody.querySelector('[data-student-preferences-target="searchResults"]'));
        }
    }
    
    // Add XHR request monitoring
    const originalXhrOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        this.addEventListener('load', function() {
            console.log('XHR completed:', {
                url: this._url,
                method: this._method,
                status: this.status,
                response: this.responseText
            });
        });
        
        this._url = arguments[1];
        this._method = arguments[0];
        originalXhrOpen.apply(this, arguments);
    };
    
    // Add fetch monitoring
    const originalFetch = window.fetch;
    window.fetch = function() {
        const url = arguments[0];
        const options = arguments[1] || {};
        
        console.log('Fetch request:', {
            url: url,
            method: options.method || 'GET',
            body: options.body
        });
        
        return originalFetch.apply(this, arguments)
            .then(response => {
                console.log('Fetch response:', {
                    url: url,
                    status: response.status,
                    statusText: response.statusText
                });
                
                // Clone the response to allow it to be used elsewhere
                const clonedResponse = response.clone();
                
                // Try to get the JSON body if possible
                clonedResponse.json()
                    .then(data => {
                        console.log('Fetch response data:', data);
                    })
                    .catch(() => {
                        // Not JSON, ignore
                    });
                
                return response;
            });
    };
});