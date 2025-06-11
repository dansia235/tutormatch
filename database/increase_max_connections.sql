-- Script pour augmenter le nombre maximum de connexions MySQL
-- À exécuter en tant qu'administrateur MySQL

-- Vérifier la valeur actuelle
SHOW VARIABLES LIKE 'max_connections';

-- Augmenter la limite de connexions (ajustez selon vos besoins et capacités du serveur)
SET GLOBAL max_connections = 200;

-- Vérifier la nouvelle valeur
SHOW VARIABLES LIKE 'max_connections';

-- Notes importantes:
-- 1. Cette modification est temporaire et sera réinitialisée au redémarrage du serveur MySQL
-- 2. Pour une modification permanente, modifiez le fichier my.cnf ou my.ini:
--    Ajoutez ou modifiez la ligne: max_connections=200
--    Puis redémarrez le serveur MySQL

-- Variables liées à la gestion des connexions qu'il peut être utile d'ajuster:
SHOW VARIABLES LIKE 'wait_timeout'; -- Temps d'inactivité avant fermeture d'une connexion (secondes)
SHOW VARIABLES LIKE 'interactive_timeout'; -- Temps d'attente pour les connexions interactives

-- Pour réduire les timeouts (recommandé avec un nombre élevé de connexions):
-- SET GLOBAL wait_timeout = 60; -- 60 secondes au lieu de la valeur par défaut (généralement 28800)
-- SET GLOBAL interactive_timeout = 60;