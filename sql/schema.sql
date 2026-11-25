CREATE DATABASE IF NOT EXISTS boutique CHARACTER SET utf8mb4;
USE boutique;
CREATE TABLE roles (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(30) UNIQUE NOT NULL);
CREATE TABLE permissions (id INT AUTO_INCREMENT PRIMARY KEY, code VARCHAR(50) UNIQUE NOT NULL);
CREATE TABLE role_permissions (role_id INT, permission_id INT, PRIMARY KEY(role_id,permission_id),
  FOREIGN KEY(role_id) REFERENCES roles(id), FOREIGN KEY(permission_id) REFERENCES permissions(id));
CREATE TABLE utilisateurs (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(80) NOT NULL,
  email VARCHAR(120) UNIQUE NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role_id INT NOT NULL,
  actif TINYINT(1) DEFAULT 1, FOREIGN KEY(role_id) REFERENCES roles(id));
CREATE TABLE categories (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(80) UNIQUE NOT NULL);
CREATE TABLE produits (id INT AUTO_INCREMENT PRIMARY KEY, reference VARCHAR(50) UNIQUE NOT NULL,
  code_barres VARCHAR(50) NULL, nom VARCHAR(120) NOT NULL, categorie_id INT,
  prix_achat DECIMAL(12,2) NOT NULL CHECK (prix_achat>=0), prix_vente DECIMAL(12,2) NOT NULL CHECK (prix_vente>=0),
  seuil_min INT DEFAULT 0, seuil_max INT NULL, delai_livraison_j INT DEFAULT 7, actif TINYINT(1) DEFAULT 1,
  FOREIGN KEY(categorie_id) REFERENCES categories(id));
CREATE TABLE variantes (id INT AUTO_INCREMENT PRIMARY KEY, produit_id INT NOT NULL, libelle VARCHAR(80) NOT NULL,
  quantite INT NOT NULL DEFAULT 0 CHECK (quantite>=0), actif TINYINT(1) DEFAULT 1,
  FOREIGN KEY(produit_id) REFERENCES produits(id)); -- une variante "Standard" par produit sans variantes
CREATE TABLE mouvements_stock (id INT AUTO_INCREMENT PRIMARY KEY, variante_id INT NOT NULL,
  type ENUM('entree','sortie','ajustement') NOT NULL, quantite INT NOT NULL, motif VARCHAR(150) NOT NULL,
  ref_type VARCHAR(20) NULL, ref_id INT NULL, utilisateur_id INT, date_mvt DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY(variante_id) REFERENCES variantes(id));
CREATE TABLE ventes (id INT AUTO_INCREMENT PRIMARY KEY, vendeur_id INT NOT NULL, date_vente DATETIME DEFAULT CURRENT_TIMESTAMP,
  mode_paiement ENUM('especes','carte','mobile','autre') NOT NULL, total_ht DECIMAL(12,2), total_taxe DECIMAL(12,2), total_ttc DECIMAL(12,2),
  FOREIGN KEY(vendeur_id) REFERENCES utilisateurs(id));
CREATE TABLE lignes_vente (id INT AUTO_INCREMENT PRIMARY KEY, vente_id INT NOT NULL, variante_id INT NOT NULL,
  quantite INT NOT NULL CHECK (quantite>0), prix_unitaire DECIMAL(12,2) NOT NULL, cout_unitaire DECIMAL(12,2) NOT NULL, taux_taxe DECIMAL(5,2) DEFAULT 0,
  FOREIGN KEY(vente_id) REFERENCES ventes(id), FOREIGN KEY(variante_id) REFERENCES variantes(id));
CREATE TABLE fournisseurs (id INT AUTO_INCREMENT PRIMARY KEY, nom VARCHAR(120) NOT NULL, telephone VARCHAR(30), email VARCHAR(120));
CREATE TABLE commandes_fournisseur (id INT AUTO_INCREMENT PRIMARY KEY, fournisseur_id INT NOT NULL, date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
  statut ENUM('preparation','commandee','partiellement_recue','receptionnee','annulee') DEFAULT 'preparation', date_commande DATE NULL,
  FOREIGN KEY(fournisseur_id) REFERENCES fournisseurs(id));
CREATE TABLE lignes_commande (id INT AUTO_INCREMENT PRIMARY KEY, commande_id INT NOT NULL, variante_id INT NOT NULL,
  qte_commandee INT NOT NULL, qte_recue INT NOT NULL DEFAULT 0, cout_unitaire DECIMAL(12,2) NOT NULL,
  FOREIGN KEY(commande_id) REFERENCES commandes_fournisseur(id), FOREIGN KEY(variante_id) REFERENCES variantes(id));
CREATE TABLE receptions (id INT AUTO_INCREMENT PRIMARY KEY, commande_id INT NOT NULL, ligne_id INT NOT NULL, qte_recue INT NOT NULL,
  date_reception DATETIME DEFAULT CURRENT_TIMESTAMP, utilisateur_id INT, FOREIGN KEY(commande_id) REFERENCES commandes_fournisseur(id));
CREATE TABLE previsions (id INT AUTO_INCREMENT PRIMARY KEY, variante_id INT, horizon_jours INT, quantite_prevue DECIMAL(10,2),
  methode VARCHAR(40), date_calcul DATETIME DEFAULT CURRENT_TIMESTAMP);
CREATE TABLE recommandations (id INT AUTO_INCREMENT PRIMARY KEY, variante_id INT, type VARCHAR(30), quantite_suggeree INT, message VARCHAR(255),
  statut ENUM('proposee','validee','rejetee') DEFAULT 'proposee', date_calcul DATETIME DEFAULT CURRENT_TIMESTAMP);
INSERT INTO roles(nom) VALUES ('administrateur'),('responsable'),('vendeur');
INSERT INTO permissions(code) VALUES ('users.manage'),('products.manage'),('stock.manage'),('sales.create'),('sales.view'),('purchases.manage'),('stats.view'),('ai.use');
INSERT INTO role_permissions SELECT 1,id FROM permissions;
INSERT INTO role_permissions SELECT 2,id FROM permissions WHERE code<>'users.manage';
INSERT INTO role_permissions SELECT 3,id FROM permissions WHERE code IN ('sales.create','sales.view');
-- Admin initial : php -r "echo password_hash('ChangeMe!',PASSWORD_DEFAULT);" puis INSERT dans utilisateurs (role_id=1)
