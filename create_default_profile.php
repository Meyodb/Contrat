<?php
// Script pour créer une image de profil par défaut

// Créer le répertoire images s'il n'existe pas déjà
$dir = __DIR__ . '/public/images';
if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
}

// Créer une image 200x200 pixels
$img = imagecreatetruecolor(200, 200);

// Définir les couleurs
$background = imagecolorallocate($img, 240, 240, 240); // Gris clair
$textcolor = imagecolorallocate($img, 100, 100, 100);  // Gris foncé

// Remplir le fond
imagefilledrectangle($img, 0, 0, 200, 200, $background);

// Dessiner un cercle pour l'avatar
$circle_color = imagecolorallocate($img, 200, 200, 200); // Gris moyen
imagefilledellipse($img, 100, 100, 150, 150, $circle_color);

// Dessiner une silhouette simple
$icon_color = imagecolorallocate($img, 255, 255, 255); // Blanc
imagefilledellipse($img, 100, 80, 60, 60, $icon_color); // Tête
imagefilledrectangle($img, 70, 110, 130, 160, $icon_color); // Corps

// Sauvegarder l'image
$filepath = $dir . '/default-profile.png';
imagepng($img, $filepath);
imagedestroy($img);

echo "Image de profil par défaut créée avec succès: $filepath\n";
?> 