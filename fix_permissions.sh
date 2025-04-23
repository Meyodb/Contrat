#!/bin/bash

# Script de correction des permissions pour les signatures
# À exécuter avec sudo: sudo bash fix_permissions.sh

# Vérification des droits sudo
if [ "$EUID" -ne 0 ]; then
  echo "Ce script doit être exécuté en tant que root (utilisez sudo)."
  exit 1
fi

# Chemins de l'application
APP_PATH=$(pwd)
STORAGE_PATH="$APP_PATH/storage/app/public"
PUBLIC_PATH="$APP_PATH/public"

echo "==========================================================="
echo "  CORRECTION DES PERMISSIONS POUR LES SIGNATURES"
echo "  $(date)"
echo "==========================================================="

# 1. Corriger les permissions du dossier storage
echo ""
echo "1. Correction des permissions du dossier storage"

mkdir -p "$STORAGE_PATH/signatures/admin"
mkdir -p "$STORAGE_PATH/signatures/employees"
chmod -R 777 "$STORAGE_PATH"
chown -R www-data:www-data "$STORAGE_PATH"

echo "   - Permissions 777 appliquées à $STORAGE_PATH"
echo "   - Propriétaire changé pour www-data:www-data"

# 2. Corriger les permissions du dossier public/signatures
echo ""
echo "2. Correction des permissions du dossier public/signatures"

mkdir -p "$PUBLIC_PATH/signatures"
chmod -R 777 "$PUBLIC_PATH/signatures"
chown -R www-data:www-data "$PUBLIC_PATH/signatures"

echo "   - Permissions 777 appliquées à $PUBLIC_PATH/signatures"
echo "   - Propriétaire changé pour www-data:www-data"

# 3. Créer le lien symbolique si nécessaire
echo ""
echo "3. Vérification du lien symbolique storage"

if [ ! -e "$PUBLIC_PATH/storage" ]; then
  ln -sf "$STORAGE_PATH" "$PUBLIC_PATH/storage"
  chmod 777 "$PUBLIC_PATH/storage"
  echo "   - Lien symbolique créé: $PUBLIC_PATH/storage"
else
  echo "   - Le lien symbolique existe déjà"
fi

# 4. Copier la signature admin si elle existe
echo ""
echo "4. Vérification de la signature admin"

ADMIN_SIG="$STORAGE_PATH/signatures/admin/admin_signature.png"
PUBLIC_ADMIN_SIG="$PUBLIC_PATH/signatures/admin_signature.png"

if [ -e "$ADMIN_SIG" ] && [ ! -e "$PUBLIC_ADMIN_SIG" ]; then
  cp "$ADMIN_SIG" "$PUBLIC_ADMIN_SIG"
  chmod 777 "$PUBLIC_ADMIN_SIG"
  chown www-data:www-data "$PUBLIC_ADMIN_SIG"
  echo "   - Signature admin copiée dans le dossier public"
fi

if [ -e "$ADMIN_SIG" ]; then
  chmod 777 "$ADMIN_SIG"
  chown www-data:www-data "$ADMIN_SIG"
  echo "   - Permissions de la signature admin corrigées"
fi

echo ""
echo "==========================================================="
echo "  CORRECTION TERMINÉE"
echo "==========================================================="
echo ""
echo "Exécutez maintenant: php fix_signatures.php" 