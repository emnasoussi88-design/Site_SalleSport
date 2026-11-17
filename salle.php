<?php
session_start();
$serveur     = "localhost";
$utilisateur = "root";
$motdepasse  = "";
$base        = "salle_sport";

$conn = mysql_connect($serveur, $utilisateur, $motdepasse);
if (!$conn) {
    die("Échec de la connexion : " . mysql_error());
}
if (!mysql_select_db($base, $conn)) {
    die("Échec de la sélection de la base : " . mysql_error());
}
mysql_set_charset('utf8', $conn);

$action = isset($_POST['action']) ? $_POST['action'] : '';

// ════════════════════════════════════════════════════════
//  ACTION : connexion
// ════════════════════════════════════════════════════════
if ($action === 'connexion') {
    $email  = trim($_POST['email']);
    $mdp    = $_POST['mdp'];

    $erreurs = array();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Email invalide.";
    }
    if (empty($mdp)) {
        $erreurs[] = "Veuillez entrer votre mot de passe.";
    }
    if (!empty($erreurs)) {
        afficherErreurs($erreurs, 'connexion.html');
        mysql_close($conn); exit;
    }

    $email_s = mysql_real_escape_string($email, $conn);
    $req = "SELECT id, pseudo, mot_de_passe FROM membres WHERE email = '$email_s'";
    $res = mysql_query($req, $conn);

    if (!$res || mysql_num_rows($res) === 0) {
        afficherErreurs(array("Email ou mot de passe incorrect."), 'connexion.html');
        mysql_close($conn); exit;
    }
    $membre = mysql_fetch_assoc($res);
    if (md5($mdp) !== $membre['mot_de_passe']) {
        afficherErreurs(array("Email ou mot de passe incorrect."), 'connexion.html');
        mysql_close($conn); exit;
    }

    $_SESSION['membre_id']     = $membre['id'];
    $_SESSION['membre_pseudo'] = $membre['pseudo'];

    afficherSucces(
        "Bienvenue <strong>{$membre['pseudo']}</strong> ! Vous êtes connecté(e).<br><br>
         <a href='espace_compte.html' style='color:#0E7F5E;font-weight:700;'>
           &rarr; Accéder à mon espace compte
         </a>",
        'connexion.html'
    );
    mysql_close($conn); exit;
}

// ════════════════════════════════════════════════════════
//  ACTION : inscription
// ════════════════════════════════════════════════════════
if ($action === 'inscription') {
    $pseudo      = trim($_POST['pseudo']);
    $email       = trim($_POST['email']);
    $mdp         = $_POST['mdp'];
    $mdp_confirm = $_POST['mdp_confirm'];
    $nom         = isset($_POST['nom'])    ? trim($_POST['nom'])    : '';
    $prenom      = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';

    $erreurs = array();
    if (strlen($pseudo) < 5 || strlen($pseudo) > 10) {
        $erreurs[] = "Pseudo invalide (5 à 10 caractères).";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreurs[] = "Email invalide.";
    }
    if (!preg_match('/[A-Z]/', $mdp) ||
        !preg_match('/[a-z]/', $mdp) ||
        !preg_match('/[0-9]/', $mdp)) {
        $erreurs[] = "Mot de passe invalide (majuscule + minuscule + chiffre requis).";
    }
    if ($mdp !== $mdp_confirm) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }
    if (!empty($erreurs)) {
        afficherErreurs($erreurs, 'inscription.html');
        mysql_close($conn); exit;
    }

    $email_s = mysql_real_escape_string($email, $conn);
    $req = "SELECT id FROM membres WHERE email = '$email_s'";
    $res = mysql_query($req, $conn);
    if (mysql_num_rows($res) > 0) {
        afficherErreurs(array("Cet email est déjà utilisé."), 'inscription.html');
        mysql_close($conn); exit;
    }

    $mdp_hash = md5($mdp);
    $pseudo_s = mysql_real_escape_string($pseudo, $conn);
    $nom_s    = mysql_real_escape_string($nom, $conn);
    $prenom_s = mysql_real_escape_string($prenom, $conn);

    $req = "INSERT INTO membres (pseudo, nom, prenom, email, mot_de_passe)
            VALUES ('$pseudo_s', '$nom_s', '$prenom_s', '$email_s', '$mdp_hash')";

    if (mysql_query($req, $conn)) {
        $_SESSION['membre_id']     = mysql_insert_id($conn);
        $_SESSION['membre_pseudo'] = $pseudo;

        afficherSuccesAvecBouton(
            "Inscription réussie ! Bienvenue <strong>$pseudo</strong> &#127881;",
            "Votre compte a été créé avec succès. Passez maintenant à l'abonnement.",
            'abonnement.html',
            '&#128170; Choisir mon abonnement',
            'inscription.html'
        );
    } else {
        afficherErreurs(
            array("Erreur lors de l'inscription : " . mysql_error($conn)),
            'inscription.html'
        );
    }
    mysql_close($conn); exit;
}

// ════════════════════════════════════════════════════════
//  ACTION : abonnement
// ════════════════════════════════════════════════════════
if ($action === 'abonnement') {
    $activite    = trim($_POST['activite']);
    $age         = strtolower(trim($_POST['age']));
    $duree       = intval($_POST['duree']);
    $net_a_payer = trim($_POST['net_a_payer']);

    $erreurs = array();
    $activites_valides = array('Gymnastique','karaté','judo','Natation');
    if (!in_array($activite, $activites_valides)) $erreurs[] = "Activité invalide.";
    if (!in_array($age, array('enfant','adulte')))  $erreurs[] = "Veuillez sélectionner votre tranche d'âge.";
    if (!in_array($duree, array(1, 3)))             $erreurs[] = "Veuillez sélectionner la durée d'abonnement.";
    if (empty($net_a_payer))                        $erreurs[] = "Veuillez d'abord cliquer sur Calculer.";

    if (!empty($erreurs)) {
        afficherErreurs($erreurs, 'abonnement.html');
        mysql_close($conn); exit;
    }

    $tarifs = array('Gymnastique'=>50,'karaté'=>60,'judo'=>60,'Natation'=>70);
    $prix   = $tarifs[$activite] * $duree;
    if ($age === 'enfant') $prix = $prix * 0.80;

    $activite_s = mysql_real_escape_string($activite, $conn);
    $req = "INSERT INTO abonnements (activite, age, duree_mois, prix, date_abonnement)
            VALUES ('$activite_s', '$age', $duree, $prix, NOW())";

    if (mysql_query($req, $conn)) {
        afficherSucces(
            "&#9989; Paiement confirmé !<br><br>
             Activité : <strong>$activite</strong><br>
             Durée : <strong>$duree mois</strong><br>
             <span style='font-size:22px;font-weight:900;color:#0E7F5E'>
               Montant payé : " . number_format($prix, 2) . " DT
             </span>",
            'abonnement.html'
        );
    } else {
        afficherErreurs(array("Erreur : " . mysql_error($conn)), 'abonnement.html');
    }
    mysql_close($conn); exit;
}

// ════════════════════════════════════════════════════════
//  ACTION : imc
// ════════════════════════════════════════════════════════
if ($action === 'imc') {
    $sexe               = trim($_POST['sexe']);
    $taille             = floatval($_POST['taille']);
    $poids              = floatval($_POST['poids']);
    $masse_graisse      = trim($_POST['masse_graisse']);
    $masse_sans_graisse = trim($_POST['masse_sans_graisse']);
    $masse_musculaire   = trim($_POST['masse_musculaire']);
    $masse_eau          = trim($_POST['masse_eau']);
    $resultat_imc       = trim($_POST['resultat_imc']);

    $erreurs = array();
    if (!in_array($sexe, array('homme','femme'))) $erreurs[] = "Veuillez sélectionner votre sexe.";
    if ($taille <= 0 || $taille > 250)            $erreurs[] = "Taille invalide.";
    if ($poids  <= 0 || $poids  > 300)            $erreurs[] = "Poids invalide.";
    if (empty($resultat_imc))                     $erreurs[] = "Veuillez d'abord cliquer sur Calculer.";

    if (!empty($erreurs)) {
        afficherErreurs($erreurs, 'calcul.html');
        mysql_close($conn); exit;
    }

    $tailleM = $taille / 100;
    $imc     = round($poids / ($tailleM * $tailleM), 2);
    $mg      = floatval($masse_graisse);
    $msg     = floatval($masse_sans_graisse);
    $mm      = floatval($masse_musculaire);
    $me      = floatval($masse_eau);

    $sexe_s = mysql_real_escape_string($sexe, $conn);
    $req = "INSERT INTO imc
                (sexe, taille, poids, imc, masse_graisse,
                 masse_sans_graisse, masse_musculaire, masse_eau, date_calcul)
            VALUES
                ('$sexe_s', $taille, $poids, $imc,
                 $mg, $msg, $mm, $me, NOW())";

    if (mysql_query($req, $conn)) {
        afficherSucces(
            "Résultats enregistrés !<br>
             Taille : <strong>$taille cm</strong> &nbsp;|&nbsp;
             Poids : <strong>$poids kg</strong><br>
             IMC : <strong>$imc</strong><br>
             $resultat_imc",
            'calcul.html'
        );
    } else {
        afficherErreurs(array("Erreur : " . mysql_error($conn)), 'calcul.html');
    }
    mysql_close($conn); exit;
}

// ════════════════════════════════════════════════════════
//  ACTION : paiement → PAYÉ ou NON PAYÉ
// ════════════════════════════════════════════════════════
if ($action === 'paiement') {
    $email = trim($_POST['email']);
    $mdp   = $_POST['mdp'];

    $erreurs = array();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "Email invalide.";
    if (empty($mdp))                                $erreurs[] = "Veuillez entrer votre mot de passe.";

    if (!empty($erreurs)) {
        afficherErreurs($erreurs, 'payer.html');
        mysql_close($conn); exit;
    }

    $email_s  = mysql_real_escape_string($email, $conn);
    $req      = "SELECT id, pseudo, mot_de_passe FROM membres WHERE email = '$email_s'";
    $resultat = mysql_query($req, $conn);

    if (!$resultat || mysql_num_rows($resultat) === 0) {
        afficherErreurs(array("Email ou mot de passe incorrect."), 'payer.html');
        mysql_close($conn); exit;
    }
    $membre = mysql_fetch_assoc($resultat);
    if (md5($mdp) !== $membre['mot_de_passe']) {
        afficherErreurs(array("Email ou mot de passe incorrect."), 'payer.html');
        mysql_close($conn); exit;
    }

    $mois_actuel = date('Y-m');
    $req_abo = "SELECT activite, age, duree_mois, prix, date_abonnement
                FROM abonnements
                WHERE SUBSTRING(date_abonnement, 1, 7) = '$mois_actuel'
                ORDER BY date_abonnement DESC
                LIMIT 1";
    $res_abo = mysql_query($req_abo, $conn);

    $mois_fr = array(
        '01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril',
        '05'=>'Mai','06'=>'Juin','07'=>'Juillet','08'=>'Août',
        '09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'
    );
    $mois_nom = $mois_fr[date('m')] . ' ' . date('Y');

    if ($res_abo && mysql_num_rows($res_abo) > 0) {
        $abo = mysql_fetch_assoc($res_abo);
        afficherStatutPaiement(true,  $membre['pseudo'], $mois_nom, $abo);
    } else {
        afficherStatutPaiement(false, $membre['pseudo'], $mois_nom, null);
    }
    mysql_close($conn); exit;
}

afficherErreurs(array("Action inconnue."), 'index.html');

// ════════════════════════════════════════════════════════
//  FONCTIONS UTILITAIRES
// ════════════════════════════════════════════════════════

function afficherStatutPaiement($paye, $pseudo, $mois, $abo) {
    if ($paye) {
        $icon    = '&#9989;';
        $titre   = 'MOIS PAYÉ';
        $couleur = '#0E7F5E';
        $bg      = '#E8F8F2';
        $detail  = "Activité : <strong>{$abo['activite']}</strong><br>
                    Tranche : <strong>{$abo['age']}</strong><br>
                    Durée : <strong>{$abo['duree_mois']} mois</strong><br>
                    Montant : <strong>" . number_format($abo['prix'], 2) . " DT</strong><br>
                    Date : <strong>{$abo['date_abonnement']}</strong>";
    } else {
        $icon    = '&#10060;';
        $titre   = 'MOIS NON PAYÉ';
        $couleur = '#C0392B';
        $bg      = '#FDF2F2';
        $detail  = "Aucun abonnement enregistré pour ce mois.<br>
                    <a href='abonnement.html' style='color:{$couleur};font-weight:700;'>
                      &rarr; Souscrire un abonnement
                    </a>";
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Statut paiement — ERS</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #0A0F1E 0%, #1E3A5F 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    /* ── Carte principale ── */
    .card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      max-width: 460px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(201,168,76,0.2);
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from { transform: translateY(30px); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }

    /* ── En-tête ── */
    .card-top {
      background: linear-gradient(135deg, #0A0F1E, #1E3A5F);
      padding: 22px 30px;
      border-bottom: 2px solid rgba(201,168,76,0.35);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card-top .brand-dot {
      width: 10px; height: 10px;
      background: #C9A84C;
      border-radius: 50%;
      box-shadow: 0 0 8px rgba(201,168,76,0.7);
    }

    .card-top h1 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 20px;
      letter-spacing: 4px;
      color: #C9A84C;
    }

    /* ── Corps ── */
    .card-body {
      padding: 36px 30px 30px;
      text-align: center;
    }

    /* Icône animée */
    .status-icon {
      font-size: 72px;
      margin-bottom: 10px;
      display: block;
      animation: popIn 0.5s 0.2s both;
    }

    @keyframes popIn {
      from { transform: scale(0.5); opacity: 0; }
      to   { transform: scale(1);   opacity: 1; }
    }

    /* Badge mois */
    .mois-badge {
      display: inline-block;
      background: rgba(201,168,76,0.1);
      color: #8A6820;
      padding: 5px 16px;
      border-radius: 30px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.8px;
      margin-bottom: 14px;
      border: 1px solid rgba(201,168,76,0.25);
    }

    /* Titre statut */
    .status-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 32px;
      letter-spacing: 3px;
      color: <?php echo $couleur; ?>;
      margin-bottom: 6px;
    }

    /* Bonjour */
    .welcome {
      font-size: 14.5px;
      color: #4A5568;
      margin-bottom: 20px;
    }

    .welcome strong { color: #0A0F1E; }

    /* Boîte détail */
    .detail-box {
      background: <?php echo $bg; ?>;
      border-left: 4px solid <?php echo $couleur; ?>;
      border-radius: 12px;
      padding: 18px 22px;
      text-align: left;
      font-size: 13.5px;
      line-height: 2;
      color: #333;
      margin-bottom: 24px;
    }

    /* Bouton retour */
    .btn {
      display: block;
      padding: 14px;
      background: linear-gradient(135deg, #0A0F1E, #1E3A5F);
      color: #fff;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      font-size: 13.5px;
      letter-spacing: 0.5px;
      transition: opacity 0.2s, transform 0.2s;
    }

    .btn:hover {
      opacity: 0.88;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-top">
      <div class="brand-dot"></div>
      <h1>ERS &mdash; STATUT PAIEMENT</h1>
    </div>
    <div class="card-body">
      <span class="status-icon"><?php echo $icon; ?></span>
      <div class="mois-badge">&#128197; <?php echo htmlspecialchars($mois); ?></div>
      <div class="status-title"><?php echo $titre; ?></div>
      <div class="welcome">Bonjour <strong><?php echo htmlspecialchars($pseudo); ?></strong></div>
      <div class="detail-box"><?php echo $detail; ?></div>
      <a href="payer.html" class="btn">&larr; Retour</a>
    </div>
  </div>
</body>
</html>
<?php
}

// ── Afficher les erreurs ──────────────────────────────────────────────────
function afficherErreurs($erreurs, $retour) {
    $liste = implode('<br>', array_map(create_function('$e', 'return "&#9888; " . $e;'), $erreurs));
    afficherPage("Erreur", $liste, $retour, '#C0392B', '#FDF2F2', '&#10060;');
}

// ── Afficher un succès simple ────────────────────────────────────────────
function afficherSucces($message, $retour) {
    afficherPage("Succès", $message, $retour, '#0E7F5E', '#E8F8F2', '&#9989;');
}

// ── Succès avec bouton d'action (après inscription) ───────────────────────
function afficherSuccesAvecBouton($titre_msg, $sous_msg, $lien_action, $texte_bouton, $retour) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscription réussie — ERS</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #0A0F1E 0%, #1E3A5F 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      max-width: 460px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(201,168,76,0.2);
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from { transform: translateY(30px); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }

    .card-top {
      background: linear-gradient(135deg, #0A0F1E, #1E3A5F);
      padding: 22px 30px;
      border-bottom: 2px solid rgba(201,168,76,0.35);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card-top .brand-dot {
      width: 10px; height: 10px;
      background: #C9A84C;
      border-radius: 50%;
      box-shadow: 0 0 8px rgba(201,168,76,0.7);
    }

    .card-top h1 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 20px;
      letter-spacing: 4px;
      color: #C9A84C;
    }

    .card-body {
      padding: 36px 30px 30px;
      text-align: center;
    }

    .icon {
      font-size: 64px;
      margin-bottom: 16px;
      display: block;
      animation: popIn 0.5s 0.2s both;
    }

    @keyframes popIn {
      from { transform: scale(0.5); opacity: 0; }
      to   { transform: scale(1);   opacity: 1; }
    }

    .msg {
      font-size: 17px;
      font-weight: 700;
      color: #0A0F1E;
      margin-bottom: 8px;
    }

    .sub {
      font-size: 13.5px;
      color: #8A95A3;
      margin-bottom: 26px;
      line-height: 1.6;
    }

    /* Ligne décorative */
    .divider {
      height: 1px;
      background: linear-gradient(to right, transparent, rgba(201,168,76,0.4), transparent);
      margin-bottom: 24px;
    }

    .btn-main {
      display: block;
      padding: 15px;
      background: linear-gradient(135deg, #C9A84C, #E8C96A);
      color: #0A0F1E;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 800;
      font-size: 14px;
      letter-spacing: 0.5px;
      margin-bottom: 12px;
      transition: filter 0.2s, transform 0.2s;
      box-shadow: 0 4px 16px rgba(201,168,76,0.35);
    }

    .btn-main:hover {
      filter: brightness(1.06);
      transform: translateY(-1px);
    }

    .btn-back {
      display: block;
      padding: 12px;
      background: transparent;
      color: #8A95A3;
      border: 1.5px solid #D1D9E6;
      border-radius: 10px;
      text-decoration: none;
      font-size: 13px;
      transition: all 0.2s;
    }

    .btn-back:hover {
      color: #0A0F1E;
      border-color: #0A0F1E;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-top">
      <div class="brand-dot"></div>
      <h1>ERS &mdash; INSCRIPTION</h1>
    </div>
    <div class="card-body">
      <span class="icon">&#127881;</span>
      <div class="msg"><?php echo $titre_msg; ?></div>
      <div class="sub"><?php echo $sous_msg; ?></div>
      <div class="divider"></div>
      <a href="<?php echo htmlspecialchars($lien_action); ?>" class="btn-main"><?php echo $texte_bouton; ?></a>
      <a href="<?php echo htmlspecialchars($retour); ?>" class="btn-back">&larr; Retour à l'inscription</a>
    </div>
  </div>
</body>
</html>
<?php
}

// ── Page générique (succès / erreur) ─────────────────────────────────────
function afficherPage($titre, $message, $retour, $couleur, $bg, $icon) {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($titre); ?> — ERS</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: 'Outfit', sans-serif;
      background: linear-gradient(135deg, #0A0F1E 0%, #1E3A5F 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      max-width: 460px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 0 1px rgba(201,168,76,0.2);
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from { transform: translateY(30px); opacity: 0; }
      to   { transform: translateY(0);    opacity: 1; }
    }

    /* ── En-tête ── */
    .card-top {
      background: linear-gradient(135deg, #0A0F1E, #1E3A5F);
      padding: 22px 30px;
      border-bottom: 2px solid rgba(201,168,76,0.35);
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .card-top .brand-dot {
      width: 10px; height: 10px;
      background: #C9A84C;
      border-radius: 50%;
      box-shadow: 0 0 8px rgba(201,168,76,0.7);
    }

    .card-top h1 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 20px;
      letter-spacing: 4px;
      color: #C9A84C;
    }

    /* ── Corps ── */
    .card-body {
      padding: 36px 30px 30px;
      text-align: center;
    }

    .icon {
      font-size: 64px;
      margin-bottom: 14px;
      display: block;
      animation: popIn 0.5s 0.2s both;
    }

    @keyframes popIn {
      from { transform: scale(0.5); opacity: 0; }
      to   { transform: scale(1);   opacity: 1; }
    }

    h2 {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 28px;
      letter-spacing: 3px;
      color: <?php echo $couleur; ?>;
      margin-bottom: 18px;
    }

    /* Boîte message */
    .msg-box {
      background: <?php echo $bg; ?>;
      border-left: 4px solid <?php echo $couleur; ?>;
      border-radius: 12px;
      padding: 18px 22px;
      text-align: left;
      font-size: 14px;
      line-height: 1.9;
      color: #333;
      margin-bottom: 26px;
    }

    /* Bouton retour */
    a.btn {
      display: block;
      padding: 14px;
      background: linear-gradient(135deg, #0A0F1E, #1E3A5F);
      color: #fff;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      font-size: 13.5px;
      letter-spacing: 0.5px;
      transition: opacity 0.2s, transform 0.2s;
      box-shadow: 0 4px 16px rgba(10,15,30,0.3);
    }

    a.btn:hover {
      opacity: 0.88;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="card-top">
      <div class="brand-dot"></div>
      <h1>ERS &mdash; SALLE DE SPORT</h1>
    </div>
    <div class="card-body">
      <span class="icon"><?php echo $icon; ?></span>
      <h2><?php echo htmlspecialchars($titre); ?></h2>
      <div class="msg-box"><?php echo $message; ?></div>
      <a href="<?php echo htmlspecialchars($retour); ?>" class="btn">&larr; Retour</a>
    </div>
  </div>
</body>
</html>
<?php
}
?>
