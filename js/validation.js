/**
 * Script de validation pour le formulaire "Devenir membre"
 * Vérifie les champs du formulaire avant soumission
 */

document.addEventListener('DOMContentLoaded', function() {
    // Récupération du formulaire d'inscription
    const formulaire = document.getElementById('formulaire-membre');
    
    // Si le formulaire existe sur la page
    if (formulaire) {
        // Champs du formulaire
        const prenom = document.getElementById('prenom');
        const nom = document.getElementById('nom');
        const email = document.getElementById('email');
        const telephone = document.getElementById('telephone');
        const classe = document.getElementById('classe');
        const serie = document.getElementById('serie');
        
        // Ajout d'un écouteur d'événement pour la soumission du formulaire
        formulaire.addEventListener('submit', function(e) {
            // Empêcher la soumission par défaut
            e.preventDefault();
            
            // Flag pour suivre la validité du formulaire
            let formulaireValide = true;
            
            // Réinitialiser les messages d'erreur précédents
            const erreurs = document.querySelectorAll('.invalid-feedback');
            erreurs.forEach(function(erreur) {
                erreur.textContent = '';
            });
            
            // Supprimer la classe 'is-invalid' de tous les champs
            const champs = formulaire.querySelectorAll('input, select');
            champs.forEach(function(champ) {
                champ.classList.remove('is-invalid');
            });
            
            // Validation du prénom (obligatoire, lettres uniquement)
            if (prenom.value.trim() === '') {
                afficherErreur(prenom, 'Le prénom est obligatoire');
                formulaireValide = false;
            } else if (!/^[a-zA-ZÀ-ÿ\-\s]+$/.test(prenom.value.trim())) {
                afficherErreur(prenom, 'Le prénom ne doit contenir que des lettres');
                formulaireValide = false;
            }
            
            // Validation du nom (obligatoire, lettres uniquement)
            if (nom.value.trim() === '') {
                afficherErreur(nom, 'Le nom est obligatoire');
                formulaireValide = false;
            } else if (!/^[a-zA-ZÀ-ÿ\-\s]+$/.test(nom.value.trim())) {
                afficherErreur(nom, 'Le nom ne doit contenir que des lettres');
                formulaireValide = false;
            }
            
            // Validation de l'email (facultatif, format valide si renseigné)
            if (email.value.trim() !== '' && !estEmailValide(email.value.trim())) {
                afficherErreur(email, 'Format d\'email invalide');
                formulaireValide = false;
            }
            
            // Validation du téléphone (obligatoire, format sénégalais local)
            if (telephone.value.trim() === '') {
                afficherErreur(telephone, 'Le numéro de téléphone est obligatoire');
                formulaireValide = false;
            } else if (!estTelephoneValide(telephone.value.trim())) {
                afficherErreur(telephone, 'Format de téléphone invalide (ex: 77 123 45 67)');
                formulaireValide = false;
            }
            
            // Validation de la classe (obligatoire)
            if (classe.value === '') {
                afficherErreur(classe, 'Veuillez sélectionner une classe');
                formulaireValide = false;
            }
            
            // Validation de la série (obligatoire)
            if (serie.value === '') {
                afficherErreur(serie, 'Veuillez sélectionner une série');
                formulaireValide = false;
            }
            
            // Si la classe est "Seconde", vérifier que la série est "S"
            if (classe.value === 'Seconde' && serie.value !== 'S') {
                afficherErreur(serie, 'Pour la classe de Seconde, la série doit être S');
                formulaireValide = false;
            }
            
            // Si tous les champs sont valides, soumettre le formulaire
            if (formulaireValide) {
                formulaire.submit();
            }
        });
        
        // Mise à jour dynamique des options de série en fonction de la classe
        classe.addEventListener('change', function() {
            // Réinitialiser la série
            serie.value = '';
            
            // Désactiver toutes les options sauf la première (option vide)
            Array.from(serie.options).forEach(function(option) {
                if (option.value === '') {
                    option.disabled = false;
                } else {
                    option.disabled = true;
                }
            });

            // Activer les options selon la classe
            if (classe.value === 'Seconde') {
                // Seconde : uniquement S et Autre
                serie.querySelector('option[value="S"]').disabled = false;
                serie.querySelector('option[value="Autre"]').disabled = false;
            } else if (classe.value === 'Première' || classe.value === 'Terminal') {
                // Première et Terminale : S1, S2 et Autre
                serie.querySelector('option[value="S1"]').disabled = false;
                serie.querySelector('option[value="S2"]').disabled = false;
                serie.querySelector('option[value="Autre"]').disabled = false;
            } else if (classe.value === 'Supérieur') {
                // Supérieur : uniquement Autre
                serie.querySelector('option[value="Autre"]').disabled = false;
                serie.value = 'Autre';
            }
        });
    }
    
    /**
     * Fonction pour afficher un message d'erreur sous un champ
     * 
     * @param {HTMLElement} champ - Le champ concerné par l'erreur
     * @param {string} message - Le message d'erreur à afficher
     */
    function afficherErreur(champ, message) {
        // Ajouter la classe d'erreur Bootstrap au champ
        champ.classList.add('is-invalid');
        
        // Trouver l'élément de feedback d'erreur associé au champ
        const conteneurErreur = champ.nextElementSibling;
        
        if (conteneurErreur && conteneurErreur.className === 'invalid-feedback') {
            conteneurErreur.textContent = message;
        }
    }
    
    /**
     * Vérifie si une adresse email est valide
     * 
     * @param {string} email - L'adresse email à vérifier
     * @return {boolean} - True si le format est valide, sinon false
     */
    function estEmailValide(email) {
        const regexEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regexEmail.test(email);
    }
    
    /**
     * Vérifie si un numéro de téléphone est au format français
     * 
     * @param {string} telephone - Le numéro à vérifier
     * @return {boolean} - True si le format est valide, sinon false
     */
    function estTelephoneValide(telephone) {
        // Supprimer les espaces et le +221 éventuel
        telephone = telephone.replace(/\s/g, '').replace(/^\+221/, '');
        // Format local : commence par 77, 78, 76, 70, 75 et 9 chiffres
        return /^(77|78|76|70|75)[0-9]{7}$/.test(telephone);
    }
});
