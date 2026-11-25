<?php
// POST JSON : {"mode_paiement":"especes","taux_taxe":20,"lignes":[{"variante_id":1,"quantite":2}]}
// Vente + déduction de stock = une seule transaction SQL atomique.
require __DIR__.'/../includes/bootstrap.php';
$uid = require_perm('sales.create');
$in = json_decode(file_get_contents('php://input'), true);
if (empty($in['lignes']) || !in_array($in['mode_paiement'] ?? '', ['especes','carte','mobile','autre'], true))
  json_out(['erreur'=>'Données invalides'], 422);
$taux = max(0, (float)($in['taux_taxe'] ?? 0)); $pdo = db();
try {
  $pdo->beginTransaction(); $ht = 0; $lignes = [];
  foreach ($in['lignes'] as $l) {
    $q = (int)($l['quantite'] ?? 0); if ($q <= 0) throw new RuntimeException('Quantité invalide');
    $st = $pdo->prepare('SELECT v.id, v.quantite, p.nom, p.prix_vente, p.prix_achat FROM variantes v
        JOIN produits p ON p.id=v.produit_id WHERE v.id=? AND v.actif=1 AND p.actif=1 FOR UPDATE');
    $st->execute([(int)$l['variante_id']]); $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r) throw new RuntimeException('Produit introuvable ou désactivé');
    if ($r['quantite'] < $q) throw new RuntimeException("Stock insuffisant pour {$r['nom']}");
    $ht += $q * $r['prix_vente']; $lignes[] = [$r, $q];
  }
  $taxe = round($ht * $taux / 100, 2);
  $pdo->prepare('INSERT INTO ventes(vendeur_id,mode_paiement,total_ht,total_taxe,total_ttc) VALUES (?,?,?,?,?)')
      ->execute([$uid, $in['mode_paiement'], $ht, $taxe, $ht + $taxe]);
  $vid = (int)$pdo->lastInsertId();
  foreach ($lignes as [$r, $q]) {
    $pdo->prepare('INSERT INTO lignes_vente(vente_id,variante_id,quantite,prix_unitaire,cout_unitaire,taux_taxe) VALUES (?,?,?,?,?,?)')
        ->execute([$vid, $r['id'], $q, $r['prix_vente'], $r['prix_achat'], $taux]);
    $pdo->prepare('UPDATE variantes SET quantite=quantite-? WHERE id=?')->execute([$q, $r['id']]);
    $pdo->prepare('INSERT INTO mouvements_stock(variante_id,type,quantite,motif,ref_type,ref_id,utilisateur_id) VALUES (?,?,?,?,?,?,?)')
        ->execute([$r['id'], 'sortie', $q, 'Vente', 'vente', $vid, $uid]);
  }
  $pdo->commit(); json_out(['vente_id'=>$vid, 'total_ttc'=>$ht + $taxe], 201);
} catch (RuntimeException $e) { $pdo->rollBack(); json_out(['erreur'=>$e->getMessage()], 409);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); json_out(['erreur'=>'Erreur serveur'], 500); }
