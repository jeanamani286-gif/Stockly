<?php
// POST JSON : {"commande_id":3,"receptions":[{"ligne_id":7,"qte_recue":4}]}
// Seules les quantités réellement reçues augmentent le stock.
require __DIR__.'/../includes/bootstrap.php';
$uid = require_perm('purchases.manage');
$in = json_decode(file_get_contents('php://input'), true); $pdo = db(); $cid = (int)$in['commande_id'];
try {
  $pdo->beginTransaction();
  $c = $pdo->prepare('SELECT statut FROM commandes_fournisseur WHERE id=? FOR UPDATE'); $c->execute([$cid]); $cmd = $c->fetch(PDO::FETCH_ASSOC);
  if (!$cmd || !in_array($cmd['statut'], ['commandee','partiellement_recue'], true)) throw new RuntimeException('Commande non réceptionnable');
  foreach ($in['receptions'] as $r) {
    $q = (int)$r['qte_recue']; if ($q <= 0) continue;
    $s = $pdo->prepare('SELECT variante_id FROM lignes_commande WHERE id=? AND commande_id=?'); $s->execute([(int)$r['ligne_id'], $cid]);
    $l = $s->fetch(PDO::FETCH_ASSOC); if (!$l) throw new RuntimeException('Ligne invalide');
    $pdo->prepare('UPDATE lignes_commande SET qte_recue=qte_recue+? WHERE id=?')->execute([$q, (int)$r['ligne_id']]);
    $pdo->prepare('INSERT INTO receptions(commande_id,ligne_id,qte_recue,utilisateur_id) VALUES (?,?,?,?)')->execute([$cid, (int)$r['ligne_id'], $q, $uid]);
    $pdo->prepare('UPDATE variantes SET quantite=quantite+? WHERE id=?')->execute([$q, $l['variante_id']]);
    $pdo->prepare('INSERT INTO mouvements_stock(variante_id,type,quantite,motif,ref_type,ref_id,utilisateur_id) VALUES (?,?,?,?,?,?,?)')
        ->execute([$l['variante_id'], 'entree', $q, 'Réception fournisseur', 'commande', $cid, $uid]);
  }
  $t = $pdo->prepare('SELECT SUM(qte_recue>=qte_commandee)=COUNT(*) FROM lignes_commande WHERE commande_id=?'); $t->execute([$cid]);
  $statut = $t->fetchColumn() ? 'receptionnee' : 'partiellement_recue';
  $pdo->prepare('UPDATE commandes_fournisseur SET statut=? WHERE id=?')->execute([$statut, $cid]);
  $pdo->commit(); json_out(['statut'=>$statut]);
} catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); json_out(['erreur'=>$e->getMessage()], 409); }
