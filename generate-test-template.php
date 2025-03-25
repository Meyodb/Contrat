<?php
// Ce script génère un fichier Word de test contenant toutes les variables disponibles
require __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\JcTable;

// Définir le chemin de stockage
$storagePath = __DIR__ . '/storage/app/templates/modele_test_complet.docx';

// Créer un nouveau document Word
$phpWord = new PhpWord();

// Définir les styles
$titleStyle = ['bold' => true, 'size' => 16, 'color' => '000080'];
$sectionTitleStyle = ['bold' => true, 'size' => 14, 'color' => '000080'];
$subsectionTitleStyle = ['bold' => true, 'size' => 12, 'color' => '000080'];
$normalStyle = ['size' => 11];
$boldStyle = ['bold' => true, 'size' => 11];
$variableStyle = ['italic' => true, 'size' => 11, 'color' => '0000FF'];

// Ajouter une section
$section = $phpWord->addSection();

// Titre du document
$section->addText('MODÈLE DE CONTRAT DE TRAVAIL À DURÉE INDÉTERMINÉE (CDI)', $titleStyle, ['alignment' => 'center']);
$section->addTextBreak(2);

// Introduction
$section->addText('Le présent modèle contient toutes les variables disponibles dans le système.', $normalStyle);
$section->addText('Les variables sont encadrées par des accolades doubles: {{VARIABLE}}.', $normalStyle);
$section->addTextBreak();

// Section Identification des parties
$section->addText('1. IDENTIFICATION DES PARTIES', $sectionTitleStyle);
$section->addTextBreak();

// Créer un tableau pour les parties
$table = $section->addTable(['borderSize' => 6, 'borderColor' => '000000', 'width' => 100 * 50]);
$table->addRow();

// Colonne employeur
$cell1 = $table->addCell(5000);
$cell1->addText('EMPLOYEUR', $boldStyle, ['alignment' => 'center']);
$cell1->addText('Raison sociale: {{COMPANY_NAME}}', $normalStyle);
$cell1->addText('Siège social: {{COMPANY_ADDRESS}}', $normalStyle);
$cell1->addText('SIRET: {{COMPANY_SIRET}}', $normalStyle);
$cell1->addText('Représentée par: {{COMPANY_REPRESENTATIVE}}', $normalStyle);
$cell1->addText('En qualité de: {{COMPANY_REPRESENTATIVE_TITLE}}', $normalStyle);

// Colonne employé
$cell2 = $table->addCell(5000);
$cell2->addText('EMPLOYÉ(E)', $boldStyle, ['alignment' => 'center']);
$cell2->addText('Nom: {{USER_NAME}}', $normalStyle);
$cell2->addText('Email: {{USER_EMAIL}}', $normalStyle);
$cell2->addText('Adresse: {{USER_ADDRESS}}', $normalStyle);
$cell2->addText('N° Sécurité Sociale: {{USER_SSN}}', $normalStyle);
$cell2->addText('Date de naissance: {{USER_BIRTHDATE}}', $normalStyle);

$section->addTextBreak();

// Préambule
$section->addText('2. PRÉAMBULE', $sectionTitleStyle);
$section->addText('Le présent contrat de travail est conclu entre les parties ci-dessus désignées, qui se sont rapprochées et ont convenu ce qui suit.', $normalStyle);
$section->addTextBreak();

// Conditions d'emploi
$section->addText('3. CONDITIONS D\'EMPLOI', $sectionTitleStyle);

$section->addText('3.1. Fonction', $subsectionTitleStyle);
$section->addText('{{USER_NAME}} est embauché(e) en qualité de {{JOB_TITLE}} au coefficient {{JOB_COEFFICIENT}} de la Convention Collective {{COLLECTIVE_AGREEMENT}}.', $normalStyle);

$section->addText('3.2. Date de début', $subsectionTitleStyle);
$section->addText('Le présent contrat prend effet à compter du {{CONTRACT_START_DATE}}.', $normalStyle);

$section->addText('3.3. Période d\'essai', $subsectionTitleStyle);
$section->addText('Le présent contrat est conclu avec une période d\'essai de {{TRIAL_PERIOD_MONTHS}} mois.', $normalStyle);

$section->addText('3.4. Lieu de travail', $subsectionTitleStyle);
$section->addText('{{USER_NAME}} exercera ses fonctions à {{WORK_LOCATION}}.', $normalStyle);

$section->addTextBreak();

// Durée du travail
$section->addText('4. DURÉE DU TRAVAIL', $sectionTitleStyle);
$section->addText('La durée du travail est fixée à {{WORK_HOURS}} heures par semaine.', $normalStyle);
$section->addText('Les horaires pourront être modifiés selon les nécessités du service.', $normalStyle);
$section->addTextBreak();

// Rémunération
$section->addText('5. RÉMUNÉRATION', $sectionTitleStyle);
$section->addText('En contrepartie de son travail, {{USER_NAME}} percevra une rémunération mensuelle brute de {{MONTHLY_SALARY}} euros pour un horaire hebdomadaire de {{WORK_HOURS}} heures.', $normalStyle);
$section->addText('Le taux horaire brut est fixé à {{HOURLY_RATE}} euros.', $normalStyle);
$section->addTextBreak();

// Congés
$section->addText('6. CONGÉS PAYÉS', $sectionTitleStyle);
$section->addText('{{USER_NAME}} bénéficiera des congés payés conformément aux dispositions légales et conventionnelles en vigueur.', $normalStyle);
$section->addTextBreak();

// Autres variables spécifiques
$section->addText('7. AUTRES VARIABLES DISPONIBLES', $sectionTitleStyle);
$section->addText('Date du contrat: {{CONTRACT_DATE}}', $normalStyle);
$section->addText('Date de signature: {{CONTRACT_SIGNING_DATE}}', $normalStyle);
$section->addText('Heures supplémentaires à 20%: {{OVERTIME_HOURS_20}}', $normalStyle);
$section->addTextBreak();

// Signatures
$section->addText('8. SIGNATURES', $sectionTitleStyle);
$section->addTextBreak();

// Tableau pour les signatures
$sigTable = $section->addTable(['borderSize' => 0, 'width' => 100 * 50]);
$sigTable->addRow();

// Colonne employeur
$sigCell1 = $sigTable->addCell(5000);
$sigCell1->addText('Fait à _____________, le {{CONTRACT_DATE}}', $normalStyle, ['alignment' => 'center']);
$sigCell1->addTextBreak();
$sigCell1->addText('Pour l\'employeur:', $boldStyle, ['alignment' => 'center']);
$sigCell1->addText('{{COMPANY_NAME}}', $normalStyle, ['alignment' => 'center']);
$sigCell1->addTextBreak(2);
$sigCell1->addText('{{ADMIN_SIGNATURE}}', $variableStyle, ['alignment' => 'center']);

// Colonne employé
$sigCell2 = $sigTable->addCell(5000);
$sigCell2->addText('Fait à _____________, le {{CONTRACT_DATE}}', $normalStyle, ['alignment' => 'center']);
$sigCell2->addTextBreak();
$sigCell2->addText('L\'employé(e):', $boldStyle, ['alignment' => 'center']);
$sigCell2->addText('{{USER_NAME}}', $normalStyle, ['alignment' => 'center']);
$sigCell2->addTextBreak(2);
$sigCell2->addText('{{EMPLOYEE_SIGNATURE}}', $variableStyle, ['alignment' => 'center']);

// Liste complète des variables
$section->addTextBreak(2);
$section->addText('LISTE COMPLÈTE DES VARIABLES DISPONIBLES', $sectionTitleStyle, ['alignment' => 'center']);
$section->addTextBreak();

$varTable = $section->addTable(['borderSize' => 1, 'borderColor' => '000000', 'width' => 100 * 50]);

// En-tête du tableau
$varTable->addRow();
$varTable->addCell(5000, ['bgColor' => 'D3D3D3'])->addText('Variable', $boldStyle, ['alignment' => 'center']);
$varTable->addCell(5000, ['bgColor' => 'D3D3D3'])->addText('Description', $boldStyle, ['alignment' => 'center']);

// Ajouter toutes les variables
$variables = [
    // Informations utilisateur
    ['{{USER_NAME}}', 'Nom complet de l\'employé'],
    ['{{USER_EMAIL}}', 'Adresse email de l\'employé'],
    ['{{USER_ADDRESS}}', 'Adresse postale de l\'employé'],
    ['{{USER_SSN}}', 'Numéro de sécurité sociale'],
    ['{{USER_BIRTHDATE}}', 'Date de naissance'],
    
    // Informations entreprise
    ['{{COMPANY_NAME}}', 'Nom de l\'entreprise'],
    ['{{COMPANY_ADDRESS}}', 'Adresse de l\'entreprise'],
    ['{{COMPANY_SIRET}}', 'Numéro SIRET'],
    ['{{COMPANY_REPRESENTATIVE}}', 'Nom du représentant de l\'entreprise'],
    ['{{COMPANY_REPRESENTATIVE_TITLE}}', 'Titre du représentant'],
    
    // Informations contrat
    ['{{CONTRACT_DATE}}', 'Date du jour'],
    ['{{CONTRACT_START_DATE}}', 'Date de début du contrat'],
    ['{{CONTRACT_SIGNING_DATE}}', 'Date de signature du contrat'],
    ['{{TRIAL_PERIOD_MONTHS}}', 'Durée de la période d\'essai en mois'],
    
    // Informations emploi
    ['{{JOB_TITLE}}', 'Intitulé du poste'],
    ['{{JOB_COEFFICIENT}}', 'Coefficient selon la convention collective'],
    ['{{COLLECTIVE_AGREEMENT}}', 'Convention collective applicable'],
    ['{{WORK_LOCATION}}', 'Lieu de travail'],
    ['{{WORK_HOURS}}', 'Heures de travail hebdomadaires'],
    ['{{MONTHLY_SALARY}}', 'Salaire mensuel brut'],
    ['{{HOURLY_RATE}}', 'Taux horaire brut'],
    ['{{OVERTIME_HOURS_20}}', 'Heures supplémentaires à 20%'],
    
    // Signatures
    ['{{ADMIN_SIGNATURE}}', 'Signature de l\'employeur (administrateur)'],
    ['{{EMPLOYEE_SIGNATURE}}', 'Signature de l\'employé'],
];

foreach ($variables as $var) {
    $varTable->addRow();
    $varTable->addCell(5000)->addText($var[0], $variableStyle);
    $varTable->addCell(5000)->addText($var[1], $normalStyle);
}

// Créer le répertoire de stockage s'il n'existe pas
$storageDir = dirname($storagePath);
if (!is_dir($storageDir)) {
    mkdir($storageDir, 0755, true);
}

// Enregistrer le document
$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
$objWriter->save($storagePath);

echo "Le fichier modèle a été créé avec succès : " . $storagePath . "\n"; 