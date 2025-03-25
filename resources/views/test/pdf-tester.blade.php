<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Testeur de PDF de contrat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Testeur de PDF de contrat</h1>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Créer des données de test</h5>
                    </div>
                    <div class="card-body">
                        <p>Cliquez sur le bouton ci-dessous pour créer un employé et un contrat de test avec des données complètes :</p>
                        <button id="create-test-data" class="btn btn-success">
                            Créer des données de test
                        </button>
                        <div id="test-data-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Test rapide</h5>
                    </div>
                    <div class="card-body">
                        <p>Cliquez sur le bouton ci-dessous pour tester le PDF avec le premier contrat disponible :</p>
                        <a href="{{ route('test.contract.pdf') }}" class="btn btn-primary" target="_blank">
                            Générer PDF du premier contrat
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Liste des contrats disponibles</h5>
                    </div>
                    <div class="card-body">
                        <p>Chargement des contrats...</p>
                        <div id="contracts-list" class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Titre</th>
                                        <th>Utilisateur</th>
                                        <th>Statut</th>
                                        <th>Créé le</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="contracts-table-body">
                                    <!-- Les contrats seront affichés ici -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Charger la liste des contrats au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadContracts();
            
            // Ajouter un gestionnaire d'événements pour le bouton de création de données de test
            document.getElementById('create-test-data').addEventListener('click', function() {
                createTestData();
            });
        });
        
        // Fonction pour créer des données de test
        function createTestData() {
            const resultDiv = document.getElementById('test-data-result');
            resultDiv.innerHTML = '<div class="alert alert-info">Création des données de test en cours...</div>';
            
            fetch('{{ route("test.create.data") }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="alert alert-success">
                                <h5>Données de test créées avec succès !</h5>
                                <p>Un contrat de test a été créé pour l'employé "${data.user.name}".</p>
                                <p>Vous pouvez maintenant <a href="${data.test_pdf_url}" target="_blank" class="alert-link">générer le PDF</a> pour ce contrat.</p>
                            </div>
                        `;
                        // Recharger la liste des contrats
                        loadContracts();
                    } else {
                        resultDiv.innerHTML = `<div class="alert alert-danger">Erreur : ${data.message || 'Une erreur est survenue'}</div>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la création des données de test:', error);
                    resultDiv.innerHTML = '<div class="alert alert-danger">Erreur lors de la création des données de test</div>';
                });
        }
        
        // Fonction pour charger les contrats
        function loadContracts() {
            fetch('{{ route("test.contracts.list") }}')
                .then(response => response.json())
                .then(data => {
                    const tableBody = document.getElementById('contracts-table-body');
                    tableBody.innerHTML = '';
                    
                    if (data.length === 0) {
                        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Aucun contrat trouvé</td></tr>';
                        return;
                    }
                    
                    data.forEach(contract => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                            <td>${contract.id}</td>
                            <td>${contract.title}</td>
                            <td>${contract.user}</td>
                            <td><span class="badge bg-${getStatusBadgeColor(contract.status)}">${getStatusLabel(contract.status)}</span></td>
                            <td>${contract.created_at}</td>
                            <td>
                                <a href="${contract.test_url}" class="btn btn-sm btn-primary" target="_blank">Générer PDF</a>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement des contrats:', error);
                    document.getElementById('contracts-table-body').innerHTML = 
                        '<tr><td colspan="6" class="text-center text-danger">Erreur lors du chargement des contrats</td></tr>';
                });
        }
        
        // Fonction pour obtenir la couleur du badge selon le statut
        function getStatusBadgeColor(status) {
            switch (status) {
                case 'draft': return 'secondary';
                case 'submitted': return 'primary';
                case 'in_review': return 'warning';
                case 'admin_signed': return 'info';
                case 'employee_signed': return 'success';
                case 'completed': return 'success';
                case 'rejected': return 'danger';
                default: return 'secondary';
            }
        }
        
        // Fonction pour obtenir le libellé du statut
        function getStatusLabel(status) {
            switch (status) {
                case 'draft': return 'Brouillon';
                case 'submitted': return 'Soumis';
                case 'in_review': return 'En révision';
                case 'admin_signed': return 'Signé admin';
                case 'employee_signed': return 'Signé employé';
                case 'completed': return 'Complété';
                case 'rejected': return 'Rejeté';
                default: return status;
            }
        }
    </script>
</body>
</html> 