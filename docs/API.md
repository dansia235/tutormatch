# Documentation de l'API TutorMatch

Cette documentation décrit les endpoints disponibles dans l'API REST de TutorMatch.

## Authentification

L'API utilise l'authentification JWT (JSON Web Token). Pour accéder aux endpoints protégés, vous devez inclure un token valide dans l'en-tête `Authorization`.

### Obtenir un token

```
POST /api/auth/login.php
```

**Paramètres de requête :**
```json
{
  "username": "string",
  "password": "string"
}
```

**Réponse :**
```json
{
  "success": true,
  "token": "string",
  "refresh_token": "string",
  "user": {
    "id": "integer",
    "username": "string",
    "role": "string",
    "first_name": "string",
    "last_name": "string"
  }
}
```

### Rafraîchir un token

```
POST /api/auth/refresh.php
```

**En-têtes :**
```
Authorization: Bearer {refresh_token}
```

**Réponse :**
```json
{
  "success": true,
  "token": "string",
  "refresh_token": "string"
}
```

### Déconnexion

```
POST /api/auth/logout.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Déconnexion réussie"
}
```

## Utilisateurs

### Récupérer tous les utilisateurs

```
GET /api/users/index.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Paramètres de requête optionnels :**
```
role=admin|coordinator|teacher|student
page=1
limit=20
```

**Réponse :**
```json
{
  "success": true,
  "users": [
    {
      "id": "integer",
      "username": "string",
      "email": "string",
      "first_name": "string",
      "last_name": "string",
      "role": "string",
      "department": "string",
      "active": "boolean",
      "created_at": "datetime"
    }
  ],
  "total": "integer",
  "page": "integer",
  "limit": "integer"
}
```

### Récupérer un utilisateur spécifique

```
GET /api/users/show.php?id={user_id}
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "user": {
    "id": "integer",
    "username": "string",
    "email": "string",
    "first_name": "string",
    "last_name": "string",
    "role": "string",
    "department": "string",
    "active": "boolean",
    "created_at": "datetime"
  }
}
```

## Stages (Internships)

### Récupérer tous les stages

```
GET /api/internships/index.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Paramètres de requête optionnels :**
```
status=available|assigned|completed
domain=string
company_id=integer
page=1
limit=20
```

**Réponse :**
```json
{
  "success": true,
  "internships": [
    {
      "id": "integer",
      "title": "string",
      "description": "string",
      "company_id": "integer",
      "company_name": "string",
      "domain": "string",
      "location": "string",
      "remote_type": "string",
      "start_date": "date",
      "end_date": "date",
      "required_skills": "string",
      "status": "string",
      "created_at": "datetime"
    }
  ],
  "total": "integer",
  "page": "integer",
  "limit": "integer"
}
```

### Récupérer un stage spécifique

```
GET /api/internships/show.php?id={internship_id}
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "internship": {
    "id": "integer",
    "title": "string",
    "description": "string",
    "company_id": "integer",
    "company_name": "string",
    "company_logo": "string",
    "domain": "string",
    "location": "string",
    "remote_type": "string",
    "start_date": "date",
    "end_date": "date",
    "required_skills": "string",
    "status": "string",
    "created_at": "datetime",
    "contact_name": "string",
    "contact_email": "string",
    "contact_phone": "string"
  }
}
```

### Rechercher des stages

```
GET /api/internships/search.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Paramètres de requête :**
```
q=string               // Terme de recherche
domain=string          // Filtrer par domaine
location=string        // Filtrer par lieu
remote_type=string     // Filtrer par type de remote
status=string          // Filtrer par statut
skills=string          // Filtrer par compétences requises
page=1
limit=20
```

**Réponse :**
```json
{
  "success": true,
  "internships": [
    {
      "id": "integer",
      "title": "string",
      "description": "string",
      "company_id": "integer",
      "company_name": "string",
      "domain": "string",
      "location": "string",
      "remote_type": "string",
      "start_date": "date",
      "end_date": "date",
      "required_skills": "string",
      "status": "string",
      "created_at": "datetime"
    }
  ],
  "total": "integer",
  "page": "integer",
  "limit": "integer"
}
```

## Affectations (Assignments)

### Récupérer toutes les affectations

```
GET /api/assignments/index.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Paramètres de requête optionnels :**
```
status=pending|confirmed|rejected|completed
student_id=integer
teacher_id=integer
page=1
limit=20
```

**Réponse :**
```json
{
  "success": true,
  "assignments": [
    {
      "id": "integer",
      "student_id": "integer",
      "student_first_name": "string",
      "student_last_name": "string",
      "teacher_id": "integer",
      "teacher_first_name": "string",
      "teacher_last_name": "string",
      "internship_id": "integer",
      "internship_title": "string",
      "company_name": "string",
      "status": "string",
      "satisfaction_score": "float",
      "compatibility_score": "float",
      "assignment_date": "datetime",
      "confirmation_date": "datetime"
    }
  ],
  "total": "integer",
  "page": "integer",
  "limit": "integer"
}
```

### Récupérer une affectation spécifique

```
GET /api/assignments/show.php?id={assignment_id}
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "assignment": {
    "id": "integer",
    "student_id": "integer",
    "student_first_name": "string",
    "student_last_name": "string",
    "student_number": "string",
    "teacher_id": "integer",
    "teacher_first_name": "string",
    "teacher_last_name": "string",
    "internship_id": "integer",
    "internship_title": "string",
    "internship_description": "string",
    "company_id": "integer",
    "company_name": "string",
    "company_logo": "string",
    "status": "string",
    "satisfaction_score": "float",
    "compatibility_score": "float",
    "assignment_date": "datetime",
    "confirmation_date": "datetime",
    "notes": "string"
  }
}
```

### Créer une affectation

```
POST /api/assignments/create.php
```

**En-têtes :**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "student_id": "integer",
  "teacher_id": "integer",
  "internship_id": "integer",
  "status": "pending",
  "notes": "string"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Affectation créée avec succès",
  "assignment_id": "integer"
}
```

### Mettre à jour le statut d'une affectation

```
PUT /api/assignments/status.php
```

**En-têtes :**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "id": "integer",
  "status": "pending|confirmed|rejected|completed",
  "notes": "string"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Statut mis à jour avec succès"
}
```

## Étudiants (Students)

### Récupérer les préférences d'un étudiant

```
GET /api/students/preferences.php?student_id={student_id}
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "preferences": [
    {
      "internship_id": "integer",
      "internship_title": "string",
      "company_name": "string",
      "preference_order": "integer",
      "notes": "string"
    }
  ]
}
```

### Ajouter une préférence pour un étudiant

```
POST /api/students/add-preference.php
```

**En-têtes :**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "student_id": "integer",
  "internship_id": "integer",
  "preference_order": "integer",
  "notes": "string"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Préférence ajoutée avec succès"
}
```

## Tableaux de bord (Dashboard)

### Récupérer les statistiques

```
GET /api/dashboard/stats.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "stats": {
    "total_students": "integer",
    "total_teachers": "integer",
    "total_internships": "integer",
    "total_assignments": "integer",
    "pending_assignments": "integer",
    "confirmed_assignments": "integer",
    "completed_assignments": "integer",
    "satisfaction_score_avg": "float",
    "compatibility_score_avg": "float",
    "internships_by_domain": {
      "domain1": "integer",
      "domain2": "integer"
    },
    "assignments_by_department": {
      "department1": "integer",
      "department2": "integer"
    }
  }
}
```

### Récupérer les données de graphique

```
GET /api/dashboard/charts.php?type={chart_type}
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Paramètres de requête :**
```
type=assignments_status|internships_domain|teacher_workload|student_satisfaction
```

**Réponse :**
```json
{
  "success": true,
  "chart_data": {
    "type": "pie|bar|line",
    "data": {
      "labels": ["string"],
      "datasets": [
        {
          "label": "string",
          "data": [0],
          "backgroundColor": ["string"]
        }
      ]
    },
    "options": {}
  }
}
```

## Messages

### Récupérer les conversations

```
GET /api/messages/conversations.php
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "conversations": [
    {
      "id": "integer",
      "participant_id": "integer",
      "participant_name": "string",
      "participant_role": "string",
      "last_message": "string",
      "last_message_date": "datetime",
      "unread_count": "integer"
    }
  ]
}
```

### Récupérer une conversation

```
GET /api/messages/conversation.php?participant_id={user_id}
```

**En-têtes :**
```
Authorization: Bearer {token}
```

**Réponse :**
```json
{
  "success": true,
  "messages": [
    {
      "id": "integer",
      "sender_id": "integer",
      "sender_name": "string",
      "receiver_id": "integer",
      "receiver_name": "string",
      "content": "string",
      "sent_at": "datetime",
      "read_at": "datetime",
      "is_read": "boolean"
    }
  ]
}
```

### Envoyer un message

```
POST /api/messages/send.php
```

**En-têtes :**
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Corps de la requête :**
```json
{
  "receiver_id": "integer",
  "content": "string"
}
```

**Réponse :**
```json
{
  "success": true,
  "message": "Message envoyé avec succès",
  "message_id": "integer"
}
```

## Codes d'erreur

L'API retourne les codes d'erreur HTTP standard :

- `200 OK` - La requête a réussi
- `201 Created` - La ressource a été créée avec succès
- `400 Bad Request` - La requête est mal formée
- `401 Unauthorized` - L'authentification a échoué
- `403 Forbidden` - L'accès à la ressource est interdit
- `404 Not Found` - La ressource n'existe pas
- `500 Internal Server Error` - Erreur serveur interne

Pour les réponses d'erreur, le format est le suivant :

```json
{
  "success": false,
  "error": "Description de l'erreur",
  "code": "integer"
}
```

## Pagination

Pour les endpoints qui retournent des listes, la pagination est disponible avec les paramètres suivants :

- `page` - Numéro de page (commence à 1)
- `limit` - Nombre d'éléments par page (par défaut 20, maximum 100)

La réponse inclut les informations de pagination :

```json
{
  "success": true,
  "data": [...],
  "pagination": {
    "total": "integer",
    "per_page": "integer",
    "current_page": "integer",
    "last_page": "integer",
    "from": "integer",
    "to": "integer"
  }
}
```