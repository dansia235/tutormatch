<?php
/**
 * Endpoint pour servir la documentation Swagger UI
 */
require_once '../includes/init.php';

// Headers pour servir du HTML
header('Content-Type: text/html; charset=utf-8');

$swaggerYamlUrl = '/tutoring/swagger.yaml';
$baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TutorMatch API Documentation</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui.css" />
    <style>
        html {
            box-sizing: border-box;
            overflow: -moz-scrollbars-vertical;
            overflow-y: scroll;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body {
            margin:0;
            background: #fafafa;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        .swagger-ui .topbar {
            background-color: #2c3e50;
        }
        .swagger-ui .topbar .download-url-wrapper .download-url-button {
            background-color: #3498db;
        }
        #swagger-ui {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            margin-bottom: 2rem;
        }
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 300;
        }
        .header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        .quick-links {
            background: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .quick-links h3 {
            margin-top: 0;
            color: #2c3e50;
        }
        .quick-links a {
            display: inline-block;
            margin: 0.25rem 0.5rem 0.25rem 0;
            padding: 0.5rem 1rem;
            background: #ecf0f1;
            color: #2c3e50;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        .quick-links a:hover {
            background: #3498db;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(52, 152, 219, 0.3);
        }
        
        .quick-links a:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(52, 152, 219, 0.3);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TutorMatch API</h1>
        <p>Documentation interactive de l'API de gestion de tutorat</p>
    </div>
    
    <div class="quick-links">
        <h3>🚀 Liens rapides</h3>
        <a href="#" onclick="handleTagClick(this, 'Authentication'); return false;">Authentification</a>
        <a href="#" onclick="handleTagClick(this, 'Students'); return false;">Étudiants</a>
        <a href="#" onclick="handleTagClick(this, 'Teachers'); return false;">Enseignants</a>
        <a href="#" onclick="handleTagClick(this, 'Internships'); return false;">Stages</a>
        <a href="#" onclick="handleTagClick(this, 'Assignments'); return false;">Affectations</a>
        <a href="#" onclick="handleTagClick(this, 'Companies'); return false;">Entreprises</a>
        <a href="#" onclick="handleTagClick(this, 'Documents'); return false;">Documents</a>
        <a href="#" onclick="handleTagClick(this, 'Messages'); return false;">Messages</a>
        <a href="#" onclick="handleTagClick(this, 'Meetings'); return false;">Réunions</a>
        <a href="#" onclick="handleTagClick(this, 'Evaluations'); return false;">Évaluations</a>
        <a href="#" onclick="handleTagClick(this, 'Dashboard'); return false;">Tableau de bord</a>
        <a href="#" onclick="handleTagClick(this, 'Monitoring'); return false;">Monitoring</a>
    </div>

    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@4.15.5/swagger-ui-standalone-preset.js"></script>
    <script>
    window.onload = function() {
        // Configuration Swagger UI
        const ui = SwaggerUIBundle({
            url: '<?php echo $baseUrl . $swaggerYamlUrl; ?>',
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl
            ],
            layout: "StandaloneLayout",
            tryItOutEnabled: true,
            requestInterceptor: function(request) {
                // Ajouter automatiquement le token JWT si disponible
                const token = localStorage.getItem('jwt_token') || sessionStorage.getItem('jwt_token');
                if (token && !request.headers.Authorization) {
                    request.headers.Authorization = 'Bearer ' + token;
                }
                return request;
            },
            responseInterceptor: function(response) {
                // Gérer les réponses d'authentification
                if (response.status === 401) {
                    console.warn('Token expired or invalid. Please re-authenticate.');
                    localStorage.removeItem('jwt_token');
                    sessionStorage.removeItem('jwt_token');
                }
                return response;
            },
            onComplete: function() {
                // Personnalisation après chargement
                console.log('TutorMatch API Documentation loaded successfully');
                
                // Ajouter un bouton pour définir le token JWT
                const topbar = document.querySelector('.swagger-ui .topbar');
                if (topbar) {
                    const authButton = document.createElement('button');
                    authButton.innerHTML = '🔑 Set JWT Token';
                    authButton.style.cssText = `
                        background: #3498db;
                        color: white;
                        border: none;
                        padding: 8px 16px;
                        border-radius: 4px;
                        cursor: pointer;
                        margin-left: 10px;
                        font-size: 14px;
                    `;
                    
                    authButton.onclick = function() {
                        const token = prompt('Entrez votre token JWT:');
                        if (token) {
                            localStorage.setItem('jwt_token', token);
                            alert('Token JWT défini avec succès!');
                            location.reload();
                        }
                    };
                    
                    topbar.appendChild(authButton);
                }
            },
            // Configuration de l'interface
            docExpansion: 'list',
            operationsSorter: 'alpha',
            tagsSorter: 'alpha',
            filter: true,
            syntaxHighlight: {
                activate: true,
                theme: 'arta'
            }
        });

        // Fonction pour tester rapidement les endpoints
        window.testAPI = function(endpoint, method = 'GET', data = null) {
            const baseUrl = '<?php echo $baseUrl; ?>/tutoring/api';
            const token = localStorage.getItem('jwt_token');
            
            const options = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                }
            };
            
            if (token) {
                options.headers.Authorization = 'Bearer ' + token;
            }
            
            if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
                options.body = JSON.stringify(data);
            }
            
            return fetch(baseUrl + endpoint, options)
                .then(response => response.json())
                .then(data => {
                    console.log('API Response:', data);
                    return data;
                })
                .catch(error => {
                    console.error('API Error:', error);
                    throw error;
                });
        };
        
        console.log('Utilisation: testAPI("/users", "GET") pour tester les endpoints');
        
        // Fonction pour naviguer vers un tag spécifique
        window.scrollToTag = function(tagName) {
            console.log('Searching for tag:', tagName);
            
            // Fonction de recherche avec plusieurs tentatives
            function findAndScrollToTag(attempt = 0) {
                if (attempt > 10) {
                    console.warn('Tag not found after multiple attempts:', tagName);
                    return;
                }
                
                // Différents sélecteurs possibles pour les tags Swagger UI
                const selectors = [
                    `[data-tag="${tagName}"]`,
                    `#operations-tag-${tagName}`,
                    `h4[id*="operations-tag-${tagName}"]`,
                    `.opblock-tag-section h4:contains("${tagName}")`,
                    `.opblock-tag span:contains("${tagName}")`,
                    `[id*="${tagName.toLowerCase()}"]`
                ];
                
                let tagElement = null;
                
                for (let selector of selectors) {
                    tagElement = document.querySelector(selector);
                    if (tagElement) break;
                }
                
                // Fallback: recherche par texte
                if (!tagElement) {
                    const allElements = document.querySelectorAll('.swagger-ui h4, .swagger-ui .opblock-tag, .swagger-ui span');
                    for (let element of allElements) {
                        if (element.textContent.toLowerCase().includes(tagName.toLowerCase())) {
                            tagElement = element;
                            break;
                        }
                    }
                }
                
                if (tagElement) {
                    console.log('Found tag element:', tagElement);
                    
                    // Scroll vers l'élément
                    tagElement.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start',
                        inline: 'nearest'
                    });
                    
                    // Highlight temporairement la section
                    const parentSection = tagElement.closest('.opblock-tag-section') || tagElement.parentElement;
                    if (parentSection) {
                        parentSection.style.transition = 'all 0.5s ease';
                        parentSection.style.backgroundColor = 'rgba(52, 152, 219, 0.1)';
                        parentSection.style.borderLeft = '4px solid #3498db';
                        parentSection.style.paddingLeft = '1rem';
                        
                        setTimeout(() => {
                            parentSection.style.backgroundColor = '';
                            parentSection.style.borderLeft = '';
                            parentSection.style.paddingLeft = '';
                        }, 3000);
                    }
                    
                    // Essayer d'expand la section si elle est collapsée
                    const expandButton = tagElement.querySelector('button') || 
                                       tagElement.closest('.opblock-tag')?.querySelector('button');
                    if (expandButton && expandButton.getAttribute('aria-expanded') === 'false') {
                        setTimeout(() => expandButton.click(), 500);
                    }
                    
                } else {
                    // Réessayer après un délai
                    setTimeout(() => findAndScrollToTag(attempt + 1), 500);
                }
            }
            
            // Commencer la recherche après un petit délai
            setTimeout(() => findAndScrollToTag(), 100);
        };
        
        // Alternative: utiliser l'API Swagger UI pour l'expansion des tags
        window.expandTag = function(tagName) {
            // Cette fonction peut être utilisée pour étendre automatiquement un tag
            setTimeout(() => {
                const tagButton = document.querySelector(`[data-tag="${tagName}"] button`) ||
                                document.querySelector(`h4[id*="${tagName}"] ~ .no-margin button`);
                if (tagButton && !tagButton.classList.contains('expanded')) {
                    tagButton.click();
                }
            }, 1500);
        };
        
        // Fonction pour gérer le clic sur un lien avec feedback visuel
        window.handleTagClick = function(linkElement, tagName) {
            // Feedback visuel immédiat
            linkElement.style.backgroundColor = '#2980b9';
            linkElement.style.transform = 'scale(0.95)';
            
            // Remettre le style original après un court délai
            setTimeout(() => {
                linkElement.style.backgroundColor = '';
                linkElement.style.transform = '';
            }, 200);
            
            // Naviguer vers le tag
            scrollToTag(tagName);
        };
        
        // Améliorer la fonction scrollToTag pour aussi étendre le tag
        window.scrollAndExpandTag = function(tagName) {
            scrollToTag(tagName);
            expandTag(tagName);
        };
    };
    </script>
</body>
</html>