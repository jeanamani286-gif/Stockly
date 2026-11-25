"""API d'analyse locale. pip install flask pandas mysql-connector-python ; python app.py
Appelée uniquement par le backend PHP (127.0.0.1), qui contrôle les droits d'accès."""
import re
import pandas as pd
import mysql.connector
from flask import Flask, jsonify, request

app = Flask(__name__)
def conn():
    return mysql.connector.connect(host="localhost", user="root", password="", database="boutique")

def prevoir(vid, horizon=30):
    df = pd.read_sql("""SELECT DATE(v.date_vente) jour, SUM(l.quantite) qte FROM lignes_vente l
        JOIN ventes v ON v.id=l.vente_id WHERE l.variante_id=%s GROUP BY jour""", conn(), params=(vid,))
    if df.empty: return {"estimation": None, "limite": "Aucun historique de ventes."}
    s = df.set_index(pd.to_datetime(df.jour))["qte"].resample("D").sum().fillna(0)
    res = {"estimation": round(float(s.tail(28).mean() * horizon), 1), "methode": "moyenne mobile 28 j", "type": "PRÉVISION (non observé)"}
    if len(s) < 28: res["limite"] = f"Seulement {len(s)} jours d'historique : estimation peu fiable."
    return res

@app.get("/forecast/<int:vid>")
def forecast(vid): return jsonify(prevoir(vid, int(request.args.get("horizon", 30))))

@app.get("/restock")
def restock():
    """Demande sur (délai + 7 j) - stock - commandes en cours. Aucun achat automatique."""
    st = pd.read_sql("""SELECT v.id, p.nom, v.quantite, p.delai_livraison_j,
        COALESCE((SELECT SUM(lc.qte_commandee-lc.qte_recue) FROM lignes_commande lc JOIN commandes_fournisseur c ON c.id=lc.commande_id
          WHERE lc.variante_id=v.id AND c.statut IN ('commandee','partiellement_recue')),0) en_cours
        FROM variantes v JOIN produits p ON p.id=v.produit_id WHERE v.actif=1""", conn())
    out = []
    for r in st.itertuples():
        f = prevoir(r.id, int(r.delai_livraison_j) + 7).get("estimation") or 0
        q = int(max(0, round(f - r.quantite - r.en_cours)))
        if q > 0: out.append({"variante_id": r.id, "produit": r.nom, "quantite_suggeree": q, "statut": "proposée (validation du responsable requise)"})
    return jsonify(out)

@app.get("/slow-movers")
def slow():
    df = pd.read_sql("""SELECT v.id, p.nom, v.quantite, COALESCE(SUM(CASE WHEN s.date_vente>=NOW()-INTERVAL 60 DAY THEN l.quantite END),0) vendu_60j
        FROM variantes v JOIN produits p ON p.id=v.produit_id LEFT JOIN lignes_vente l ON l.variante_id=v.id
        LEFT JOIN ventes s ON s.id=l.vente_id WHERE v.quantite>0 GROUP BY v.id, p.nom, v.quantite HAVING vendu_60j<=2""", conn())
    df["action"] = "Envisager une promotion ou un déstockage"
    return jsonify(df.to_dict("records"))

# Assistant : intentions par mots-clés + requêtes SQL prédéfinies (sans LLM)
INTENTS = [
 (r"chiffre d'affaires.*(jour|aujourd)", "SELECT COALESCE(SUM(total_ttc),0) FROM ventes WHERE DATE(date_vente)=CURDATE()", "Chiffre d'affaires du jour : {v} (TTC)."),
 (r"chiffre d'affaires.*mois", "SELECT COALESCE(SUM(total_ttc),0) FROM ventes WHERE YEAR(date_vente)=YEAR(NOW()) AND MONTH(date_vente)=MONTH(NOW())", "Chiffre d'affaires du mois : {v} (TTC)."),
 (r"rupture", "SELECT GROUP_CONCAT(p.nom SEPARATOR ', ') FROM variantes va JOIN produits p ON p.id=va.produit_id WHERE va.quantite=0 AND va.actif=1", "Produits en rupture : {v}."),
 (r"stock faible|seuil", "SELECT GROUP_CONCAT(p.nom SEPARATOR ', ') FROM variantes va JOIN produits p ON p.id=va.produit_id WHERE va.quantite<=p.seuil_min AND va.actif=1", "Stock faible : {v}."),
 (r"plus vendu", "SELECT GROUP_CONCAT(nom SEPARATOR ', ') FROM (SELECT p.nom FROM lignes_vente l JOIN variantes va ON va.id=l.variante_id JOIN produits p ON p.id=va.produit_id GROUP BY p.id ORDER BY SUM(l.quantite) DESC LIMIT 5) t", "Produits les plus vendus : {v}."),
 (r"marge", "SELECT ROUND(COALESCE(SUM((prix_unitaire-cout_unitaire)*quantite),0),2) FROM lignes_vente", "Marge commerciale estimée (≠ bénéfice net) : {v}."),
]
@app.post("/assistant")
def assistant():
    q = (request.json or {}).get("question", "").lower()
    for pat, sql, tpl in INTENTS:
        if re.search(pat, q):
            cur = conn().cursor(); cur.execute(sql); v = cur.fetchone()[0]
            return jsonify({"reponse": tpl.format(v=v if v not in (None, "") else "aucun")})
    return jsonify({"reponse": "Je ne peux pas répondre avec les données disponibles. Essayez : chiffre d'affaires du jour, ruptures, stock faible, produits les plus vendus, marge."})

if __name__ == "__main__":
    app.run(host="127.0.0.1", port=5000)
