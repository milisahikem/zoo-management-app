<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoo'land - Vivez l'expérience sauvage</title>
    <link rel="stylesheet" href="style_visiteur.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Playfair+Display:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
        <div class="logo">🌿 ZOO'LAND</div>
        <ul class="nav-links">
            <li><a href="#decouvrir">Découvrir le Zoo</a></li>
            <li><a href="#actu">L'Actualité</a></li>
            <li><a href="parrainage_visiteur.php" style="color: #E6A117; font-weight: bold;">❤️ Parrainer</a></li>
            <li><a href="login.php" class="btn-login">Espace Personnel</a></li>
        </ul>
    </nav>

    <header class="hero" style="background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.6)), url('images/accueil.avif');">
        <div class="hero-content">
            <h1>Une île de biodiversité</h1>
            <p>De Simba le Lion à Kong le Gorille, venez rencontrer nos animaux exceptionnels.</p>
            <a href="#decouvrir" class="btn-primary">Commencer le voyage</a>
        </div>
    </header>

    <section id="actu" class="section">
        <h2 class="section-title">L'actu de nos pensionnaires</h2>
        <div class="cards-container">

            <a href="zone.php?id=401" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <img src="images/bebelion.avif" alt="Lionceaux">
                    <div class="card-content">
                        <span class="tag">Zone Félins</span>
                        <h3>Carnet rose chez les lions</h3>
                        <p>Venez observer nos majestueux petits lions jouer dans leur enclos.</p>
                    </div>
                </div>
            </a>

            <a href="zone.php?id=402" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <img src="images/elephant.avif" alt="Éléphants">
                    <div class="card-content">
                        <span class="tag">Zone Savane</span>
                        <h3>La baignade de Dumbo</h3>
                        <p>Venez assister au bain de nos éléphants dans leur grand bassin d'eau douce.</p>
                    </div>
                </div>
            </a>

            <a href="login.php" style="text-decoration: none; color: inherit;">
                <div class="card">
                    <img src="images/soigneur.avif" alt="Métier de soigneur">
                    <div class="card-content">
                        <span class="tag">Rencontre</span>
                        <h3>Le métier de soigneur</h3>
                        <p>Découvrez le quotidien de nos équipes en charge des animaux et des oiseaux.</p>
                    </div>
                </div>
            </a>

        </div>
    </section>

    <section id="decouvrir" class="section dark-section">
        <h2 class="section-title light">Explorez nos 4 Mondes</h2>
        <p style="text-align: center; color: #aaa; margin-bottom: 40px; font-size: 1.1rem;">
            Cliquez sur une zone pour découvrir les animaux qui y habitent.
        </p>

        <div class="zones-grid">

            <a href="zone.php?id=403" style="text-decoration: none;">
                <div class="zone-item" style="background-image: url('images/aigle.avif');">
                    <div class="zone-overlay">
                        <h3>Terre des Rapaces</h3>
                    </div>
                </div>
            </a>

            <a href="zone.php?id=401" style="text-decoration: none;">
                <div class="zone-item" style="background-image: url('images/felins.avif');">
                    <div class="zone-overlay">
                        <h3>Le Royaume des Félins</h3>
                    </div>
                </div>
            </a>

            <a href="zone.php?id=404" style="text-decoration: none;">
                <div class="zone-item" style="background-image: url('images/gorille.avif');">
                    <div class="zone-overlay">
                        <h3>Forêt des Primates</h3>
                    </div>
                </div>
            </a>

            <a href="zone.php?id=402" style="text-decoration: none;">
                <div class="zone-item" style="background-image: url('images/savane.avif');">
                    <div class="zone-overlay">
                        <h3>La Grande Savane</h3>
                    </div>
                </div>
            </a>

        </div>
    </section>

    <script>
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
