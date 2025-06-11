<?php
/**
 * Example of form components usage
 */

// We'll simulate a student form for this example
$student = [
    'id' => 1,
    'first_name' => 'Marie',
    'last_name' => 'Dupont',
    'email' => 'marie.dupont@example.com',
    'phone' => '0123456789',
    'birth_date' => '1998-05-15',
    'address' => '123 Rue de Paris',
    'postal_code' => '75001',
    'city' => 'Paris',
    'country' => 'France',
    'bio' => 'Étudiante en informatique passionnée par la programmation web et mobile.',
    'preferences' => ['web', 'mobile'],
    'receive_notifications' => true,
    'internship_type' => 'remote'
];

// Prepare data for dropdowns
$countries = [
    'france' => 'France',
    'belgium' => 'Belgique',
    'switzerland' => 'Suisse',
    'canada' => 'Canada',
    'other' => 'Autre'
];

$internshipTypes = [
    'onsite' => 'Sur site',
    'remote' => 'À distance',
    'hybrid' => 'Hybride'
];

// Form action
$formAction = '/tutoring/api/students/update.php';
?>

<!-- Card component containing a form -->
<?php 
$cardContent = '
<div class="mb-6">
    <h2 class="text-xl font-semibold text-secondary-700">Informations personnelles</h2>
    <p class="text-gray-500 text-sm">Mettez à jour vos informations personnelles</p>
</div>

<form id="student-form" action="' . htmlspecialchars($formAction) . '" method="POST" data-controller="form" data-action="submit->form#submitForm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- First name input -->
        ' . include_with_vars(__DIR__ . '/../forms/input.php', [
            'name' => 'first_name',
            'label' => 'Prénom',
            'value' => $student['first_name'],
            'required' => true,
            'placeholder' => 'Votre prénom'
        ]) . '
        
        <!-- Last name input -->
        ' . include_with_vars(__DIR__ . '/../forms/input.php', [
            'name' => 'last_name',
            'label' => 'Nom',
            'value' => $student['last_name'],
            'required' => true,
            'placeholder' => 'Votre nom'
        ]) . '
        
        <!-- Email input -->
        ' . include_with_vars(__DIR__ . '/../forms/input.php', [
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
            'value' => $student['email'],
            'required' => true,
            'placeholder' => 'votre.email@example.com'
        ]) . '
        
        <!-- Phone input -->
        ' . include_with_vars(__DIR__ . '/../forms/input.php', [
            'name' => 'phone',
            'label' => 'Téléphone',
            'type' => 'tel',
            'value' => $student['phone'],
            'placeholder' => '0123456789'
        ]) . '
        
        <!-- Birth date input -->
        ' . include_with_vars(__DIR__ . '/../forms/input.php', [
            'name' => 'birth_date',
            'label' => 'Date de naissance',
            'type' => 'date',
            'value' => $student['birth_date'],
            'required' => true
        ]) . '
        
        <!-- Internship type select -->
        ' . include_with_vars(__DIR__ . '/../forms/select.php', [
            'name' => 'internship_type',
            'label' => 'Type de stage',
            'options' => $internshipTypes,
            'selected' => $student['internship_type'],
            'required' => true
        ]) . '
    </div>
    
    <div class="mt-6">
        <h3 class="text-lg font-semibold text-secondary-700 mb-4">Adresse</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Address input -->
            ' . include_with_vars(__DIR__ . '/../forms/input.php', [
                'name' => 'address',
                'label' => 'Adresse',
                'value' => $student['address'],
                'placeholder' => 'Numéro et nom de rue'
            ]) . '
            
            <!-- Postal code input -->
            ' . include_with_vars(__DIR__ . '/../forms/input.php', [
                'name' => 'postal_code',
                'label' => 'Code postal',
                'value' => $student['postal_code'],
                'placeholder' => '75000'
            ]) . '
            
            <!-- City input -->
            ' . include_with_vars(__DIR__ . '/../forms/input.php', [
                'name' => 'city',
                'label' => 'Ville',
                'value' => $student['city'],
                'placeholder' => 'Ville'
            ]) . '
            
            <!-- Country select -->
            ' . include_with_vars(__DIR__ . '/../forms/select.php', [
                'name' => 'country',
                'label' => 'Pays',
                'options' => $countries,
                'selected' => strtolower($student['country']),
                'placeholder' => 'Sélectionnez votre pays'
            ]) . '
        </div>
    </div>
    
    <div class="mt-6">
        <!-- Bio textarea -->
        ' . include_with_vars(__DIR__ . '/../forms/textarea.php', [
            'name' => 'bio',
            'label' => 'Biographie',
            'value' => $student['bio'],
            'placeholder' => 'Parlez de vous, votre parcours, vos intérêts...',
            'rows' => 4
        ]) . '
        
        <!-- Notification checkbox -->
        ' . include_with_vars(__DIR__ . '/../forms/checkbox.php', [
            'name' => 'receive_notifications',
            'label' => 'Recevoir des notifications par email',
            'checked' => $student['receive_notifications']
        ]) . '
    </div>
    
    <!-- Submit button -->
    ' . include_with_vars(__DIR__ . '/../forms/submit-button.php', [
        'text' => 'Enregistrer les modifications',
        'variant' => 'primary',
        'id' => 'submit-student-form'
    ]) . '
</form>
';

$cardFooter = '
<div class="text-gray-500 text-sm">
    Les informations personnelles seront traitées conformément à notre politique de confidentialité.
</div>
';

// Render the card with the form
include_with_vars(__DIR__ . '/../cards/card.php', [
    'title' => 'Profil étudiant',
    'content' => $cardContent,
    'footer' => $cardFooter,
    'id' => 'student-profile-card',
    'class' => 'mb-6'
]);

/**
 * Helper function to include a file with variables
 * 
 * @param string $file File to include
 * @param array $vars Variables to extract
 * @return string Output of the included file
 */
function include_with_vars($file, array $vars = []) {
    if (file_exists($file)) {
        // Extract variables into the current scope
        extract($vars);
        
        // Start output buffering
        ob_start();
        
        // Include the file
        include $file;
        
        // Return the output
        return ob_get_clean();
    }
    
    return '';
}
?>