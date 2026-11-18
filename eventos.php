
<!DOCTYPE html>
<html lang="pt-BR">
    <link rel="icon" href="imgs/gremio favicon.png">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Projetos e Eventos | Grêmio Estudantil</title>

    <link rel="stylesheet" href="css/eventos.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Anton&display=swap" rel="stylesheet">
</head>

<body>
    

    <main class="page-container">

        <header class="page-header">

            <div>
                <span class="page-kicker">COMUNIDADE ETEC</span>

                <h1>Projetos <span>&</span> Eventos</h1>

                <p>
                    Acompanhe as atividades, iniciativas e acontecimentos
                    da nossa comunidade escolar.
                </p>
            </div>

            <a href="index.php" class="back-button">
                ← Voltar ao início
            </a>

        </header>

        <section class="calendar-section">

            <div class="section-heading">

                <div>
                    <h2>Calendário da comunidade</h2>
                    <p>Confira o que está acontecendo durante o ano.</p>
                </div>

                <div class="year-controls">

                    <button id="prevYear" type="button">←</button>

                    <span id="currentYear">2026</span>

                    <button id="nextYear" type="button">→</button>

                </div>

            </div>

            <div class="calendar-legend">

                <span>
                    <i class="legend-dot event-dot"></i>
                    Eventos
                </span>

                <span>
                    <i class="legend-dot project-dot"></i>
                    Projetos
                </span>

                <span>
                    <i class="legend-dot today-dot"></i>
                    Hoje
                </span>

            </div>

            <div id="calendarContainer" class="calendar-container"></div>

        </section>

        <section class="activities-section">

            <div class="activities-heading">

                <div>
                    <span class="page-kicker">ACOMPANHAMENTO</span>

                    <h2 id="activitiesTitle">Atividades</h2>

                    <p id="activitiesSubtitle">
                        Selecione uma data no calendário.
                    </p>
                </div>

                <div class="activity-filters">

                    <button class="filter-button active" data-filter="todos">
                        Todos
                    </button>

                    <button class="filter-button" data-filter="evento">
                        Eventos
                    </button>

                    <button class="filter-button" data-filter="projeto">
                        Projetos
                    </button>

                </div>

            </div>

            <div id="activitiesList" class="activities-list"></div>

        </section>

    </main>

    <script src="js/eventos.js"></script>

</body>
</html>
