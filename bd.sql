--------------------------------------------------
-- ESPECE
--------------------------------------------------
CREATE TABLE ESPECE (
    id_espece NUMBER PRIMARY KEY,
    nom_latin VARCHAR2(100) UNIQUE NOT NULL,
    nom_usuel VARCHAR2(100) NOT NULL,
    menacee NUMBER(1) NOT NULL,
    CONSTRAINT ck_espece_menacee CHECK (menacee IN (0,1))
);

--------------------------------------------------
-- PERSONNEL
--------------------------------------------------
CREATE TABLE PERSONNEL (
    id_personne NUMBER PRIMARY KEY,
    prenom VARCHAR2(50) NOT NULL,
    nom VARCHAR2(50) NOT NULL,
    motdepasse VARCHAR2(255) NOT NULL,
    date_entreefonction DATE NOT NULL,
    date_sortie DATE,
    salaire NUMBER(10,2) NOT NULL,
    type_poste VARCHAR2(30) NOT NULL,
    id_chef_soigneur NUMBER,
    id_remplacant NUMBER,
    CONSTRAINT ck_personnel_salaire CHECK (salaire >= 0),
    CONSTRAINT ck_dates_personnel CHECK (
        date_sortie IS NULL OR date_sortie >= date_entreefonction
    ),
    CONSTRAINT ck_personnel_type_poste CHECK (
        type_poste IN (
           'soigneur',
            'chef_soigneur',
            'veterinaire',
            'entretien',
            'employe_boutique',
            'responsable_boutique',
            'technique',
            'comptable',
            'directeur', 
            'rh'
        )
    ),
    CONSTRAINT fk_personnel_chef
        FOREIGN KEY (id_chef_soigneur) REFERENCES PERSONNEL(id_personne),
    CONSTRAINT fk_personnel_remplacant
        FOREIGN KEY (id_remplacant) REFERENCES PERSONNEL(id_personne)
);

--------------------------------------------------
-- HISTORIQUE_EMPLOI
--------------------------------------------------
CREATE TABLE HISTORIQUE_EMPLOI (
    id_historique NUMBER PRIMARY KEY,
    type_poste VARCHAR2(30) NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE,
    id_personne NUMBER NOT NULL,
    CONSTRAINT ck_hist_dates CHECK (
        date_fin IS NULL OR date_fin >= date_debut
    ),
    CONSTRAINT ck_hist_type_poste CHECK (
        type_poste IN (
            'soigneur',
            'chef_soigneur',
            'veterinaire',
            'entretien',
            'employe_boutique',
            'responsable_boutique',
            'technique',
            'comptable',
            'directeur',   
            'rh'
        )
    ),
    CONSTRAINT fk_hist_personnel
        FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne)
);
--------------------------------------------------
-- ZONE
--------------------------------------------------
CREATE TABLE ZONE (
    id_zone NUMBER PRIMARY KEY,
    nom_zone VARCHAR2(100) UNIQUE NOT NULL,
    id_personnel_chef NUMBER,
    CONSTRAINT fk_zone_chef
        FOREIGN KEY (id_personnel_chef) REFERENCES PERSONNEL(id_personne)
);

--------------------------------------------------
-- ENCLOS
--------------------------------------------------
CREATE TABLE ENCLOS (
    id_enclos NUMBER PRIMARY KEY,
    latitude NUMBER(8,5) NOT NULL,
    longitude NUMBER(8,5) NOT NULL,
    surface NUMBER(10,2) NOT NULL,
    particularite VARCHAR2(150),
    id_zone NUMBER NOT NULL,
    CONSTRAINT fk_enclos_zone
        FOREIGN KEY (id_zone) REFERENCES ZONE(id_zone),
    CONSTRAINT ck_enclos_surface CHECK (surface > 0)
);

--------------------------------------------------
-- BOUTIQUE
--------------------------------------------------
CREATE TABLE BOUTIQUE (
    id_boutique NUMBER PRIMARY KEY,
    nom_boutique VARCHAR2(100) UNIQUE NOT NULL,
    type_boutique VARCHAR2(30) NOT NULL,
    id_zone NUMBER NOT NULL,
    id_responsable NUMBER NOT NULL,
    CONSTRAINT fk_boutique_zone
        FOREIGN KEY (id_zone) REFERENCES ZONE(id_zone),
    CONSTRAINT fk_boutique_responsable
        FOREIGN KEY (id_responsable) REFERENCES PERSONNEL(id_personne),
    CONSTRAINT ck_type_boutique CHECK (type_boutique IN ('souvenirs','snack'))
);

--------------------------------------------------
-- ANIMAL
--------------------------------------------------
CREATE TABLE ANIMAL (
    id_animal NUMBER PRIMARY KEY,
    date_de_naissance DATE,
    nom VARCHAR2(100) NOT NULL,
    poids NUMBER(10,2) NOT NULL,
    regime_alimentaire VARCHAR2(20) NOT NULL,
    id_espece NUMBER NOT NULL,
    id_enclos NUMBER NOT NULL,
    id_soigneur_attitre NUMBER NOT NULL,
    CONSTRAINT fk_animal_espece
        FOREIGN KEY (id_espece) REFERENCES ESPECE(id_espece),
    CONSTRAINT fk_animal_enclos
        FOREIGN KEY (id_enclos) REFERENCES ENCLOS(id_enclos),
    CONSTRAINT fk_animal_soigneur
        FOREIGN KEY (id_soigneur_attitre) REFERENCES PERSONNEL(id_personne),
    CONSTRAINT ck_animal_poids CHECK (poids > 0),
    CONSTRAINT ck_regime CHECK (regime_alimentaire IN ('carnivore','herbivore','omnivore'))
);

--------------------------------------------------
-- VISITEUR
--------------------------------------------------
CREATE TABLE VISITEUR (
    id_visiteur NUMBER PRIMARY KEY,
    nom VARCHAR2(50) NOT NULL,
    prenom VARCHAR2(50) NOT NULL
);

--------------------------------------------------
-- NIVEAU
--------------------------------------------------
CREATE TABLE NIVEAU (
    id_niveau NUMBER PRIMARY KEY,
    libelle VARCHAR2(20) UNIQUE NOT NULL,
    CONSTRAINT ck_niveau CHECK (libelle IN ('bronze','argent','or'))
);

--------------------------------------------------
-- PRESTATION
--------------------------------------------------
CREATE TABLE PRESTATION (
    id_prestation NUMBER PRIMARY KEY,
    type VARCHAR2(100) UNIQUE NOT NULL
);

--------------------------------------------------
-- PRESTATAIRE
--------------------------------------------------
CREATE TABLE PRESTATAIRE (
    id_prestataire NUMBER PRIMARY KEY,
    prix NUMBER(10,2) NOT NULL,
    nom VARCHAR2(100) NOT NULL,
    nature VARCHAR2(100) NOT NULL,
    CONSTRAINT ck_prestataire_prix CHECK (prix >= 0)
);

--------------------------------------------------
-- SOINS
--------------------------------------------------
CREATE TABLE SOINS (
    id_soin NUMBER PRIMARY KEY,
    type_soin VARCHAR2(20) NOT NULL,
    dosejournaliereSoin NUMBER(8,2) NOT NULL,
    date_soin DATE NOT NULL,
    id_animal NUMBER NOT NULL,
    id_personne NUMBER NOT NULL,
    CONSTRAINT fk_soins_animal
        FOREIGN KEY (id_animal) REFERENCES ANIMAL(id_animal),
    CONSTRAINT fk_soins_personnel
        FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne),
    CONSTRAINT ck_dose_soin CHECK (dosejournaliereSoin > 0),
    CONSTRAINT ck_type_soin CHECK (type_soin IN ('simple','complexe'))
);

--------------------------------------------------
-- REPARATION
--------------------------------------------------
CREATE TABLE REPARATION (
    id_reparation NUMBER PRIMARY KEY,
    nature VARCHAR2(20) NOT NULL,
    date_reparation DATE NOT NULL,
    id_enclos NUMBER NOT NULL,
    id_personne NUMBER,
    id_prestataire NUMBER,
    CONSTRAINT fk_rep_enclos
        FOREIGN KEY (id_enclos) REFERENCES ENCLOS(id_enclos),
    CONSTRAINT fk_rep_personnel
        FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne),
    CONSTRAINT fk_rep_prestataire
        FOREIGN KEY (id_prestataire) REFERENCES PRESTATAIRE(id_prestataire),
    CONSTRAINT ck_nature_rep CHECK (nature IN ('simple','complexe')),
    CONSTRAINT ck_rep_intervenant CHECK (
        (id_personne IS NOT NULL AND id_prestataire IS NULL)
        OR
        (id_personne IS NULL AND id_prestataire IS NOT NULL)
    )
);

--------------------------------------------------
-- TABLES ASSOCIATIVES
--------------------------------------------------
CREATE TABLE DONNE_ACCES (
    id_niveau NUMBER,
    id_prestation NUMBER,
    PRIMARY KEY (id_niveau, id_prestation),
    FOREIGN KEY (id_niveau) REFERENCES NIVEAU(id_niveau),
    FOREIGN KEY (id_prestation) REFERENCES PRESTATION(id_prestation)
);

CREATE TABLE PARRAINER (
    id_visiteur NUMBER,
    id_animal NUMBER,
    id_niveau NUMBER,
    date_parrainage DATE,
    montant NUMBER(10,2),
    PRIMARY KEY (id_visiteur, id_animal, date_parrainage),
    FOREIGN KEY (id_visiteur) REFERENCES VISITEUR(id_visiteur),
    FOREIGN KEY (id_animal) REFERENCES ANIMAL(id_animal),
    FOREIGN KEY (id_niveau) REFERENCES NIVEAU(id_niveau),
    CONSTRAINT ck_parrainage_montant CHECK (montant >= 0)
);

CREATE TABLE SPECIALISE (
    id_personne NUMBER,
    id_espece NUMBER,
    PRIMARY KEY (id_personne, id_espece),
    FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne),
    FOREIGN KEY (id_espece) REFERENCES ESPECE(id_espece)
);

CREATE TABLE COHABITER (
    id_espece1 NUMBER,
    id_espece2 NUMBER,
    PRIMARY KEY (id_espece1, id_espece2),
    FOREIGN KEY (id_espece1) REFERENCES ESPECE(id_espece),
    FOREIGN KEY (id_espece2) REFERENCES ESPECE(id_espece),
    CONSTRAINT ck_cohabiter_diff CHECK (id_espece1 <> id_espece2)
);

CREATE TABLE EST_PARENT (
    id_animal_parent NUMBER,
    id_animal_enfant NUMBER,
    PRIMARY KEY (id_animal_parent, id_animal_enfant),
    FOREIGN KEY (id_animal_parent) REFERENCES ANIMAL(id_animal),
    FOREIGN KEY (id_animal_enfant) REFERENCES ANIMAL(id_animal),
    CONSTRAINT ck_est_parent_diff CHECK (id_animal_parent <> id_animal_enfant)
);

CREATE TABLE GENERE_CA (
    id_boutique NUMBER,
    date_ca DATE,
    montantCA NUMBER(10,2),
    PRIMARY KEY (id_boutique, date_ca),
    FOREIGN KEY (id_boutique) REFERENCES BOUTIQUE(id_boutique),
    CONSTRAINT ck_genere_ca_montant CHECK (montantCA >= 0)
);

CREATE TABLE NOURRIT (
    id_personne NUMBER,
    id_animal NUMBER,
    date_nourriture DATE,
    dose_journaliere NUMBER(8,2),
    PRIMARY KEY (id_personne, id_animal, date_nourriture),
    FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne),
    FOREIGN KEY (id_animal) REFERENCES ANIMAL(id_animal),
    CONSTRAINT ck_nourrit_dose CHECK (dose_journaliere > 0)
);

CREATE TABLE TRAVAILLER (
    id_personne NUMBER,
    id_boutique NUMBER,
    PRIMARY KEY (id_personne, id_boutique),
    FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne),
    FOREIGN KEY (id_boutique) REFERENCES BOUTIQUE(id_boutique)
    );

CREATE TABLE AFFECTER (
    id_personne NUMBER,
    id_zone NUMBER,
    PRIMARY KEY (id_personne, id_zone),
    FOREIGN KEY (id_personne) REFERENCES PERSONNEL(id_personne),
    FOREIGN KEY (id_zone) REFERENCES ZONE(id_zone)
);


--------------------------------------------------
-- INSERTIONS COMPLETES - PROJET ZOO
--------------------------------------------------

--------------------------------------------------
-- ESPECE
--------------------------------------------------
INSERT INTO ESPECE VALUES (101,'Panthera leo','Lion',1);
INSERT INTO ESPECE VALUES (102,'Panthera tigris','Tigre',1);
INSERT INTO ESPECE VALUES (103,'Giraffa camelopardalis','Girafe',0);
INSERT INTO ESPECE VALUES (104,'Ursus arctos','Ours brun',0);
INSERT INTO ESPECE VALUES (105,'Aquila chrysaetos','Aigle royal',1);
INSERT INTO ESPECE VALUES (106,'Loxodonta africana','Elephant',1);
INSERT INTO ESPECE VALUES (107,'Hippopotamus amphibius','Hippopotame',0);
INSERT INTO ESPECE VALUES (108,'Gorilla gorilla','Gorille',1);
INSERT INTO ESPECE VALUES (109,'Crocodylus niloticus','Crocodile',0);
INSERT INTO ESPECE VALUES (110,'Suricata suricatta','Suricate',0);

--------------------------------------------------
-- PERSONNEL
-- ordre :
-- id_personne, prenom, nom, motdepasse,
-- date_entreefonction, date_sortie, salaire,
-- type_poste, id_chef_soigneur, id_remplacant
--------------------------------------------------
--------------------------------------------------
-- PERSONNEL
-- Tous les mots de passe sont hachés en Bcrypt
-- Mot de passe par défaut pour tous : zoo123
--------------------------------------------------
--------------------------------------------------
-- PERSONNEL (Hashs corrigés à 60 caractères)
--------------------------------------------------
INSERT INTO PERSONNEL VALUES (201,'Alice','Durand','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2018-03-15','YYYY-MM-DD'),NULL,3200,'chef_soigneur',NULL,NULL);
INSERT INTO PERSONNEL VALUES (202,'Karim','Legrand','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2020-06-10','YYYY-MM-DD'),NULL,2200,'soigneur',201,NULL);
INSERT INTO PERSONNEL VALUES (203,'Sophie','Morel','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2021-01-08','YYYY-MM-DD'),NULL,2600,'veterinaire',201,NULL);
INSERT INTO PERSONNEL VALUES (204,'Nina','Petit','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2019-09-01','YYYY-MM-DD'),NULL,2100,'entretien',NULL,NULL);
INSERT INTO PERSONNEL VALUES (205,'Marc','Robin','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2017-04-20','YYYY-MM-DD'),NULL,2500,'responsable_boutique',NULL,NULL);
INSERT INTO PERSONNEL VALUES (206,'Lea','Fournier','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2023-02-14','YYYY-MM-DD'),NULL,1900,'employe_boutique',205,NULL);
INSERT INTO PERSONNEL VALUES (207,'Lucas','Martin','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2022-05-10','YYYY-MM-DD'),NULL,2100,'soigneur',201,NULL);
INSERT INTO PERSONNEL VALUES (208,'Emma','Bernard','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2021-03-12','YYYY-MM-DD'),NULL,2200,'soigneur',201,NULL);
INSERT INTO PERSONNEL VALUES (209,'Noah','Dubois','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2020-11-04','YYYY-MM-DD'),NULL,2400,'veterinaire',NULL,NULL);
INSERT INTO PERSONNEL VALUES (210,'Lina','Moreau','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2019-08-01','YYYY-MM-DD'),NULL,2000,'technique',NULL,NULL);
INSERT INTO PERSONNEL VALUES (211,'Tom','Roux','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2023-02-01','YYYY-MM-DD'),NULL,1800,'employe_boutique',205,NULL);
INSERT INTO PERSONNEL VALUES (212,'Paul','Mercier','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2015-02-01','YYYY-MM-DD'),NULL,4500,'directeur',NULL,NULL);
INSERT INTO PERSONNEL VALUES (213,'Clara','Rousseau','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2024-01-15','YYYY-MM-DD'),NULL,2800,'rh',NULL,NULL);
INSERT INTO PERSONNEL VALUES (214,'Hugo','Blanc','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2023-09-01','YYYY-MM-DD'),NULL,2100,'soigneur',201,NULL);
INSERT INTO PERSONNEL VALUES (215,'Inès','Garnier','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2022-11-10','YYYY-MM-DD'),NULL,2500,'veterinaire',NULL,NULL);
INSERT INTO PERSONNEL VALUES (216,'Jules','Leroy','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2020-05-20','YYYY-MM-DD'),NULL,2200,'technique',NULL,NULL);
INSERT INTO PERSONNEL VALUES (217,'Victor','Simon','$2y$12$jqpvENDg7WADfNIv/zt7duyN96pFGHIh4N3O5lgDWuzr/aCLUAS02',TO_DATE('2025-01-05','YYYY-MM-DD'),NULL,2400,'comptable',NULL,NULL);
-- HISTORIQUE_EMPLOI
--------------------------------------------------
INSERT INTO HISTORIQUE_EMPLOI VALUES (301,'chef_soigneur',TO_DATE('2018-03-15','YYYY-MM-DD'),NULL,201);
INSERT INTO HISTORIQUE_EMPLOI VALUES (302,'soigneur',TO_DATE('2020-06-10','YYYY-MM-DD'),NULL,202);
INSERT INTO HISTORIQUE_EMPLOI VALUES (303,'veterinaire',TO_DATE('2021-01-08','YYYY-MM-DD'),NULL,203);
INSERT INTO HISTORIQUE_EMPLOI VALUES (304,'entretien',TO_DATE('2019-09-01','YYYY-MM-DD'),NULL,204);
INSERT INTO HISTORIQUE_EMPLOI VALUES (305,'responsable_boutique',TO_DATE('2017-04-20','YYYY-MM-DD'),NULL,205);
INSERT INTO HISTORIQUE_EMPLOI VALUES (306,'employe_boutique',TO_DATE('2023-02-14','YYYY-MM-DD'),NULL,206);
INSERT INTO HISTORIQUE_EMPLOI VALUES (307,'soigneur',TO_DATE('2022-05-10','YYYY-MM-DD'),NULL,207);
INSERT INTO HISTORIQUE_EMPLOI VALUES (308,'soigneur',TO_DATE('2021-03-12','YYYY-MM-DD'),NULL,208);
INSERT INTO HISTORIQUE_EMPLOI VALUES (309,'veterinaire',TO_DATE('2020-11-04','YYYY-MM-DD'),NULL,209);
INSERT INTO HISTORIQUE_EMPLOI VALUES (310,'technique',TO_DATE('2019-08-01','YYYY-MM-DD'),NULL,210);
INSERT INTO HISTORIQUE_EMPLOI VALUES (311,'employe_boutique',TO_DATE('2023-02-01','YYYY-MM-DD'),NULL,211);
INSERT INTO HISTORIQUE_EMPLOI VALUES (312,'directeur',TO_DATE('2015-02-01','YYYY-MM-DD'),NULL,212);
INSERT INTO HISTORIQUE_EMPLOI VALUES (313,'rh',TO_DATE('2024-01-15','YYYY-MM-DD'),NULL,213);
INSERT INTO HISTORIQUE_EMPLOI VALUES (314,'soigneur',TO_DATE('2023-09-01','YYYY-MM-DD'),NULL,214);
INSERT INTO HISTORIQUE_EMPLOI VALUES (315,'veterinaire',TO_DATE('2022-11-10','YYYY-MM-DD'),NULL,215);
INSERT INTO HISTORIQUE_EMPLOI VALUES (316,'technique',TO_DATE('2020-05-20','YYYY-MM-DD'),NULL,216);
INSERT INTO HISTORIQUE_EMPLOI VALUES (317,'comptable',TO_DATE('2025-01-05','YYYY-MM-DD'),NULL,217);
------------------------------------------------
-- ZONE
--------------------------------------------------
INSERT INTO ZONE VALUES (401,'Felins',201);
INSERT INTO ZONE VALUES (402,'Savane',201);
INSERT INTO ZONE VALUES (403,'Rapaces',201);
INSERT INTO ZONE VALUES (404,'Primates',201);
INSERT INTO ZONE VALUES (405,'Reptiles',201);

--------------------------------------------------
-- ENCLOS
--------------------------------------------------
INSERT INTO ENCLOS VALUES (501,49.89417,2.29575,500,'Enclos des lions',401);
INSERT INTO ENCLOS VALUES (502,49.89450,2.29610,550,'Enclos des tigres',401);
INSERT INTO ENCLOS VALUES (503,49.89490,2.29660,900,'Plaine des girafes',402);
INSERT INTO ENCLOS VALUES (504,49.89520,2.29710,1200,'Zone elephants et hippopotames',402);
INSERT INTO ENCLOS VALUES (505,49.89550,2.29740,300,'Voliere des rapaces',403);
INSERT INTO ENCLOS VALUES (506,49.89580,2.29780,450,'Maison des gorilles',404);
INSERT INTO ENCLOS VALUES (507,49.89610,2.29810,250,'Terrarium des crocodiles',405);
INSERT INTO ENCLOS VALUES (508,49.89640,2.29840,180,'Petit enclos des suricates',402);

--------------------------------------------------
-- VISITEUR
--------------------------------------------------
INSERT INTO VISITEUR VALUES (801,'Martin','Julie');
INSERT INTO VISITEUR VALUES (802,'Bernard','Lucas');
INSERT INTO VISITEUR VALUES (803,'Petit','Emma');
INSERT INTO VISITEUR VALUES (804,'Dubois','Lina');
INSERT INTO VISITEUR VALUES (805,'Roux','Tom');
INSERT INTO VISITEUR VALUES (806,'Garcia','Noah');
INSERT INTO VISITEUR VALUES (807,'Lambert','Sarah');
INSERT INTO VISITEUR VALUES (808,'Moreau','Yanis');

--------------------------------------------------
-- NIVEAU
--------------------------------------------------
INSERT INTO NIVEAU VALUES (901,'bronze');
INSERT INTO NIVEAU VALUES (902,'argent');
INSERT INTO NIVEAU VALUES (903,'or');

--------------------------------------------------
-- PRESTATION
--------------------------------------------------
INSERT INTO PRESTATION VALUES (1001,'Photo de l''animal');
INSERT INTO PRESTATION VALUES (1002,'Fond d''ecran');
INSERT INTO PRESTATION VALUES (1003,'Visite gratuite');
INSERT INTO PRESTATION VALUES (1004,'Rencontre avec un soigneur');
INSERT INTO PRESTATION VALUES (1005,'Newsletter du zoo');

--------------------------------------------------
-- PRESTATAIRE
--------------------------------------------------
INSERT INTO PRESTATAIRE VALUES (1101,1500,'ReparZoo','Maintenance');
INSERT INTO PRESTATAIRE VALUES (1102,800,'CloturePro','Clotures');
INSERT INTO PRESTATAIRE VALUES (1103,1200,'ElectroParc','Electricite');
INSERT INTO PRESTATAIRE VALUES (1104,950,'AquaClean','Nettoyage');

--------------------------------------------------
-- BOUTIQUE
--------------------------------------------------
INSERT INTO BOUTIQUE VALUES (601,'Savane Shop','souvenirs',402,205);
INSERT INTO BOUTIQUE VALUES (602,'Snack Ailes','snack',403,206);
INSERT INTO BOUTIQUE VALUES (603,'Jungle Store','souvenirs',404,211);

--------------------------------------------------
-- ANIMAL
--------------------------------------------------
INSERT INTO ANIMAL VALUES (701,TO_DATE('2014-05-10','YYYY-MM-DD'),'Simba',190,'carnivore',101,501,202);
INSERT INTO ANIMAL VALUES (702,TO_DATE('2020-04-20','YYYY-MM-DD'),'Nala',180,'carnivore',101,501,202);
INSERT INTO ANIMAL VALUES (703,TO_DATE('2017-08-12','YYYY-MM-DD'),'Shere Khan',210,'carnivore',102,502,207);
INSERT INTO ANIMAL VALUES (704,TO_DATE('2016-07-01','YYYY-MM-DD'),'Melman',800,'herbivore',103,503,208);
INSERT INTO ANIMAL VALUES (705,TO_DATE('2019-02-11','YYYY-MM-DD'),'Baloo',350,'omnivore',104,503,207);
INSERT INTO ANIMAL VALUES (706,TO_DATE('2021-03-03','YYYY-MM-DD'),'Aquila',6,'carnivore',105,505,202);
INSERT INTO ANIMAL VALUES (707,TO_DATE('2015-09-14','YYYY-MM-DD'),'Dumbo',1200,'herbivore',106,504,208);
INSERT INTO ANIMAL VALUES (708,TO_DATE('2016-06-22','YYYY-MM-DD'),'Gloria',1500,'herbivore',107,504,207);
INSERT INTO ANIMAL VALUES (709,TO_DATE('2014-12-11','YYYY-MM-DD'),'Kong',180,'omnivore',108,506,207);
INSERT INTO ANIMAL VALUES (710,TO_DATE('2019-03-30','YYYY-MM-DD'),'Snappy',400,'carnivore',109,507,202);
INSERT INTO ANIMAL VALUES (711,TO_DATE('2021-07-10','YYYY-MM-DD'),'Timon',1.5,'omnivore',110,508,208);
INSERT INTO ANIMAL VALUES (712,TO_DATE('2021-07-11','YYYY-MM-DD'),'Pumbaa',90,'omnivore',104,503,207);

--------------------------------------------------
-- SOINS
--------------------------------------------------
INSERT INTO SOINS VALUES (1201,'simple',5,TO_DATE('2025-03-01','YYYY-MM-DD'),701,202);
INSERT INTO SOINS VALUES (1202,'complexe',2,TO_DATE('2025-03-02','YYYY-MM-DD'),701,203);
INSERT INTO SOINS VALUES (1203,'simple',4,TO_DATE('2025-03-03','YYYY-MM-DD'),702,202);
INSERT INTO SOINS VALUES (1204,'simple',3,TO_DATE('2025-03-03','YYYY-MM-DD'),704,208);
INSERT INTO SOINS VALUES (1205,'complexe',2,TO_DATE('2025-03-04','YYYY-MM-DD'),705,209);
INSERT INTO SOINS VALUES (1206,'simple',1,TO_DATE('2025-03-04','YYYY-MM-DD'),706,202);
INSERT INTO SOINS VALUES (1207,'complexe',2,TO_DATE('2025-03-05','YYYY-MM-DD'),707,203);
INSERT INTO SOINS VALUES (1208,'simple',3,TO_DATE('2025-03-05','YYYY-MM-DD'),708,207);
INSERT INTO SOINS VALUES (1209,'complexe',1.5,TO_DATE('2025-03-06','YYYY-MM-DD'),709,209);
INSERT INTO SOINS VALUES (1210,'simple',2,TO_DATE('2025-03-06','YYYY-MM-DD'),710,202);
INSERT INTO SOINS VALUES (1211,'simple',0.5,TO_DATE('2025-03-07','YYYY-MM-DD'),711,208);
INSERT INTO SOINS VALUES (1212,'simple',1,TO_DATE('2025-03-07','YYYY-MM-DD'),712,207);

--------------------------------------------------
-- REPARATION
--------------------------------------------------
INSERT INTO REPARATION VALUES (1301,'simple',TO_DATE('2025-02-15','YYYY-MM-DD'),501,210,NULL);
INSERT INTO REPARATION VALUES (1302,'complexe',TO_DATE('2025-02-20','YYYY-MM-DD'),502,NULL,1101);
INSERT INTO REPARATION VALUES (1303,'simple',TO_DATE('2025-02-22','YYYY-MM-DD'),505,210,NULL);
INSERT INTO REPARATION VALUES (1304,'complexe',TO_DATE('2025-02-25','YYYY-MM-DD'),507,NULL,1103);
INSERT INTO REPARATION VALUES (1305,'simple',TO_DATE('2025-03-01','YYYY-MM-DD'),508,210,NULL);
INSERT INTO REPARATION VALUES (1306,'complexe',TO_DATE('2025-03-03','YYYY-MM-DD'),504,NULL,1102);

--------------------------------------------------
-- DONNE_ACCES
--------------------------------------------------
INSERT INTO DONNE_ACCES VALUES (901,1001);
INSERT INTO DONNE_ACCES VALUES (901,1005);

INSERT INTO DONNE_ACCES VALUES (902,1001);
INSERT INTO DONNE_ACCES VALUES (902,1002);
INSERT INTO DONNE_ACCES VALUES (902,1005);

INSERT INTO DONNE_ACCES VALUES (903,1001);
INSERT INTO DONNE_ACCES VALUES (903,1002);
INSERT INTO DONNE_ACCES VALUES (903,1003);
INSERT INTO DONNE_ACCES VALUES (903,1004);
INSERT INTO DONNE_ACCES VALUES (903,1005);

--------------------------------------------------
-- PARRAINER
--------------------------------------------------
INSERT INTO PARRAINER VALUES (801,701,902,TO_DATE('2025-01-01','YYYY-MM-DD'),200);
INSERT INTO PARRAINER VALUES (802,704,901,TO_DATE('2025-01-02','YYYY-MM-DD'),100);
INSERT INTO PARRAINER VALUES (803,706,903,TO_DATE('2025-01-03','YYYY-MM-DD'),300);
INSERT INTO PARRAINER VALUES (804,707,902,TO_DATE('2025-01-04','YYYY-MM-DD'),220);
INSERT INTO PARRAINER VALUES (805,709,903,TO_DATE('2025-01-05','YYYY-MM-DD'),280);
INSERT INTO PARRAINER VALUES (806,710,901,TO_DATE('2025-01-06','YYYY-MM-DD'),120);
INSERT INTO PARRAINER VALUES (807,711,901,TO_DATE('2025-01-07','YYYY-MM-DD'),90);
INSERT INTO PARRAINER VALUES (808,703,903,TO_DATE('2025-01-08','YYYY-MM-DD'),350);

--------------------------------------------------
-- SPECIALISE
--------------------------------------------------
INSERT INTO SPECIALISE VALUES (202,101);
INSERT INTO SPECIALISE VALUES (202,105);
INSERT INTO SPECIALISE VALUES (202,109);
INSERT INTO SPECIALISE VALUES (203,101);
INSERT INTO SPECIALISE VALUES (203,106);
INSERT INTO SPECIALISE VALUES (207,102);
INSERT INTO SPECIALISE VALUES (207,104);
INSERT INTO SPECIALISE VALUES (207,107);
INSERT INTO SPECIALISE VALUES (208,103);
INSERT INTO SPECIALISE VALUES (208,106);
INSERT INTO SPECIALISE VALUES (208,110);
INSERT INTO SPECIALISE VALUES (207,108);
INSERT INTO SPECIALISE VALUES (214, 109); 
INSERT INTO SPECIALISE VALUES (214, 110); 
--------------------------------------------------
-- COHABITER
--------------------------------------------------
INSERT INTO COHABITER VALUES (103,110);
INSERT INTO COHABITER VALUES (104,107);
INSERT INTO COHABITER VALUES (106,103);
INSERT INTO COHABITER VALUES (104,103);

--------------------------------------------------
-- EST_PARENT
--------------------------------------------------
INSERT INTO EST_PARENT VALUES (701,702);

--------------------------------------------------
-- GENERE_CA
--------------------------------------------------
INSERT INTO GENERE_CA VALUES (601,TO_DATE('2025-03-01','YYYY-MM-DD'),1200);
INSERT INTO GENERE_CA VALUES (601,TO_DATE('2025-03-02','YYYY-MM-DD'),980);
INSERT INTO GENERE_CA VALUES (601,TO_DATE('2025-03-03','YYYY-MM-DD'),1340);

INSERT INTO GENERE_CA VALUES (602,TO_DATE('2025-03-01','YYYY-MM-DD'),800);
INSERT INTO GENERE_CA VALUES (602,TO_DATE('2025-03-02','YYYY-MM-DD'),920);
INSERT INTO GENERE_CA VALUES (602,TO_DATE('2025-03-03','YYYY-MM-DD'),760);

INSERT INTO GENERE_CA VALUES (603,TO_DATE('2025-03-01','YYYY-MM-DD'),640);
INSERT INTO GENERE_CA VALUES (603,TO_DATE('2025-03-02','YYYY-MM-DD'),710);
INSERT INTO GENERE_CA VALUES (603,TO_DATE('2025-03-03','YYYY-MM-DD'),690);

--------------------------------------------------
-- NOURRIT
--------------------------------------------------
INSERT INTO NOURRIT VALUES (202,701,TO_DATE('2025-03-01','YYYY-MM-DD'),5);
INSERT INTO NOURRIT VALUES (202,702,TO_DATE('2025-03-01','YYYY-MM-DD'),5);
INSERT INTO NOURRIT VALUES (207,703,TO_DATE('2025-03-01','YYYY-MM-DD'),6);
INSERT INTO NOURRIT VALUES (208,704,TO_DATE('2025-03-01','YYYY-MM-DD'),10);
INSERT INTO NOURRIT VALUES (207,705,TO_DATE('2025-03-01','YYYY-MM-DD'),8);
INSERT INTO NOURRIT VALUES (202,706,TO_DATE('2025-03-01','YYYY-MM-DD'),1);
INSERT INTO NOURRIT VALUES (208,707,TO_DATE('2025-03-01','YYYY-MM-DD'),25);
INSERT INTO NOURRIT VALUES (207,708,TO_DATE('2025-03-01','YYYY-MM-DD'),30);
INSERT INTO NOURRIT VALUES (207,709,TO_DATE('2025-03-01','YYYY-MM-DD'),10);
INSERT INTO NOURRIT VALUES (202,710,TO_DATE('2025-03-01','YYYY-MM-DD'),4);
INSERT INTO NOURRIT VALUES (208,711,TO_DATE('2025-03-01','YYYY-MM-DD'),0.3);
INSERT INTO NOURRIT VALUES (207,712,TO_DATE('2025-03-01','YYYY-MM-DD'),5);

INSERT INTO NOURRIT VALUES (202,701,TO_DATE('2025-03-02','YYYY-MM-DD'),5.5);
INSERT INTO NOURRIT VALUES (207,703,TO_DATE('2025-03-02','YYYY-MM-DD'),6.2);
INSERT INTO NOURRIT VALUES (208,707,TO_DATE('2025-03-02','YYYY-MM-DD'),26);
INSERT INTO NOURRIT VALUES (207,708,TO_DATE('2025-03-02','YYYY-MM-DD'),29.5);

--------------------------------------------------
-- TRAVAILLER
--------------------------------------------------
INSERT INTO TRAVAILLER VALUES (205,601);
INSERT INTO TRAVAILLER VALUES (206,602);
INSERT INTO TRAVAILLER VALUES (211,603);
INSERT INTO TRAVAILLER VALUES (206,601);

--------------------------------------------------
-- AFFECTER
--------------------------------------------------
INSERT INTO AFFECTER VALUES (204,401);
INSERT INTO AFFECTER VALUES (204,402);

COMMIT;

