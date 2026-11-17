/* ============================================================
   controle.js — Validation des formulaires
   ERS Salle de Sport — Projet PHP 2026
   ============================================================ */

/* ── CONNEXION ───────────────────────────────────────────────── */
function verifConnexion() {
    var email = document.getElementById("cEmail").value;
    var mdp   = document.getElementById("cMdp").value;
    var pseudo = document.getElementById("cPseudo") ? document.getElementById("cPseudo").value : "";
    if (email.indexOf("@") === -1 || email.indexOf(".") === -1) {
        alert("Email invalide");
        return false;
    }
    if (mdp.trim() === "") {
        alert("Veuillez entrer votre mot de passe");
        return false;
    }
    return true;
}

/* ── INSCRIPTION ─────────────────────────────────────────────── */
function verifInscription() {
    var pseudo = document.getElementById("x1") ? document.getElementById("x1").value : "";
    if (pseudo.length < 5 || pseudo.length > 10) {
        alert("Pseudo invalide (5 à 10 caractères)");
        return false;
    }
    var email = document.getElementById("x2").value;
    if (email.indexOf("@") === -1 || email.indexOf(".") === -1) {
        alert("Email invalide");
        return false;
    }
    var mdp = document.getElementById("x22").value;
    var nbMaj = 0, nbMin = 0, nbChiffre = 0;
    for (var i = 0; i < mdp.length; i++) {
        if (mdp.charAt(i) >= "A" && mdp.charAt(i) <= "Z") nbMaj++;
        if (mdp.charAt(i) >= "a" && mdp.charAt(i) <= "z") nbMin++;
        if (mdp.charAt(i) >= "0" && mdp.charAt(i) <= "9") nbChiffre++;
    }
    if (nbMaj === 0 || nbMin === 0 || nbChiffre === 0) {
        alert("Mot de passe invalide : il doit contenir au moins une majuscule, une minuscule et un chiffre");
        return false;
    }
    var confirm = document.getElementById("x222").value;
    if (confirm !== mdp) {
        alert("Veuillez confirmer votre mot de passe");
        return false;
    }
    return true;
}

/* ── ABONNEMENT ──────────────────────────────────────────────── */
function calculAbonnement() {
    var tarifs = { "Gymnastique": 50, "karaté": 60, "judo": 60, "Natation": 70 };
    var selectActivite = document.getElementById("x4");
    var activite = selectActivite.options[selectActivite.selectedIndex].value;
    var prixBase = tarifs[activite] || 50;

    var age = document.getElementById("rAge1").checked ? "enfant"
            : document.getElementById("rAge2").checked ? "adulte"
            : null;
    if (!age) { alert("Veuillez sélectionner votre tranche d'âge"); return; }

    var duree = document.getElementById("rDuree1").checked ? 1
              : document.getElementById("rDuree2").checked ? 3
              : null;
    if (!duree) { alert("Veuillez sélectionner la durée d'abonnement"); return; }

    var prix = prixBase * duree;
    if (age === "enfant") prix = prix * 0.80;

    document.getElementById("netAPayer").value = prix.toFixed(2) + " DT";
}

function soumettreAbonnement() {
    var net = document.getElementById("netAPayer").value;
    if (!net || net.trim() === "") {
        alert("Veuillez d'abord cliquer sur Calculer avant de confirmer.");
        return false;
    }
    return true;
}

/* ── CALCUL IMC ──────────────────────────────────────────────── */
function calculIMC() {
    var sexe = document.getElementById("rHomme").checked ? "homme"
             : document.getElementById("rFemme").checked ? "femme"
             : null;
    if (!sexe) { alert("Veuillez sélectionner votre sexe"); return false; }

    var taille = parseFloat(document.getElementById("x6").value);
    var poids  = parseFloat(document.getElementById("x7").value);

    if (isNaN(taille) || taille <= 0) { alert("Taille invalide (en cm)"); return false; }
    if (isNaN(poids)  || poids  <= 0) { alert("Poids invalide (en kg)");  return false; }

    var tailleM = taille / 100;
    var imc = poids / (tailleM * tailleM);

    var pctMG = sexe === "homme"
        ? 1.2 * imc + 0.23 * 25 - 16.2
        : 1.2 * imc + 0.23 * 25 - 5.4;
    if (pctMG < 0) pctMG = 0;

    var masseGraisse     = parseFloat(((pctMG / 100) * poids).toFixed(2));
    var masseSansGraisse = parseFloat((poids - masseGraisse).toFixed(2));
    var masseMusculaire  = parseFloat((masseSansGraisse * 0.75).toFixed(2));
    var masseEau = sexe === "homme"
        ? parseFloat((-2.097 + 0.1069 * taille + 0.2466 * poids).toFixed(2))
        : parseFloat((-2.097 + 0.1069 * taille + 0.1835 * poids).toFixed(2));

    document.getElementById("x8").value  = masseGraisse     + " kg";
    document.getElementById("x9").value  = masseSansGraisse + " kg";
    document.getElementById("x11").value = masseMusculaire  + " kg";
    document.getElementById("x12").value = masseEau         + " kg";

    var imcArrondi = parseFloat(imc.toFixed(2));
    var resultat = imcArrondi < 18.5 ? imcArrondi + " - Insuffisance pondérale"
                 : imcArrondi < 25   ? imcArrondi + " - Poids normal"
                 : imcArrondi < 30   ? imcArrondi + " - Surpoids"
                 :                     imcArrondi + " - Obésité";

    document.getElementById("x10").value = resultat;
    return false;
}

function soumettreIMC() {
    var res = document.getElementById("x10").value;
    if (!res || res.trim() === "") {
        alert("Veuillez d'abord cliquer sur Calculer avant d'enregistrer.");
        return false;
    }
    return true;
}

/* ── PAIEMENT ────────────────────────────────────────────────── */
function verifPaiement() {
    var email = document.getElementById("x2").value;
    if (email.indexOf("@") === -1 || email.indexOf(".") === -1) {
        alert("Email invalide");
        return false;
    }
    var mdp = document.getElementById("x222").value;
    if (mdp.trim() === "") {
        alert("Veuillez entrer votre mot de passe");
        return false;
    }
    var mois = ["Janvier","Février","Mars","Avril","Mai","Juin",
                "Juillet","Août","Septembre","Octobre","Novembre","Décembre"];
    var maintenant = new Date();
    var xz = document.getElementById("xz");
    if (xz) xz.value = mois[maintenant.getMonth()] + " " + maintenant.getFullYear();
    return true;
}
