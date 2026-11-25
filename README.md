# Boutique intelligente — socle V1
1. XAMPP : importer `sql/schema.sql` dans MySQL, créer un compte admin (hash via password_hash).
2. Copier `php/` dans `htdocs/boutique/`.
3. `cd python && python app.py` (API locale 127.0.0.1:5000).
Fait : schéma complet, auth + permissions serveur, vente atomique, réception partielle, prévisions, réappro, faible rotation, assistant.
À faire : interface Bootstrap, CRUD (produits, fournisseurs, utilisateurs), tableau de bord, passerelle PHP -> Flask, tests.
