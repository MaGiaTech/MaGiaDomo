# MaGiaDomo

[![Licenza MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![GitHub stars](https://img.shields.io/github/stars/MaGiaTech/MaGiaDomo)](https://github.com/MaGiaTech/MaGiaDomo/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/MaGiaTech/MaGiaDomo)](https://github.com/MaGiaTech/MaGiaDomo/issues)

Dashboard per il controllo dei dispositivi Shelly e Daikin

---

## 📋 Descrizione

Questo progetto fornisce un'interfaccia web per visualizzare lo stato e controllare i dispositivi Shelly e Daikin presenti sulla tua rete.

## ✨ Funzionalità

- Lettura dello stato di tutti i dispositivi Shelly
- Dashboard interattiva con aggiornamenti in tempo reale
- Controllo on/off dei dispositivi
- Visualizzazione di consumi e metriche
- Interfaccia responsive (funziona su PC, tablet e smartphone)
- Comando condizionatori Diakin
- Regole automatiche per regolare i condizionatori Daikin

## 🛠️ Tecnologie utilizzate

- **Apache** - Web server (version 2.4)
- **PHP** - Backend (versione 7.4)
- **JavaScript** - Frontend interattivo
- **HTML/CSS** - Interfaccia utente

## 📦 Versioni disponibili

| Versione | Stato | Descrizione |
|----------|-------|-------------|
| [v1.1.0](v1.1.0/) | ✅ Stabile | Ultima versione stabile |
| [v1.0.0](v1.0.0/) | ✅ Stabile | Prima versione funzionante |
| [dev/](dev/) | ⚠️ Sperimentale | Sviluppo in corso (potrebbe non funzionare) |

## 🚀 Installazione

### Prerequisiti

- Apache 2.4+
- PHP 7.4+
- Accesso alla rete locale con dispositivi Shelly e Daikin

### Passaggi

**1. Clona il repository:**
`git clone https://github.com/MaGiaTech/MaGiaDomo.git`
`cd MaGiaDomo`

**2. Scegli la versione da usare (es. versione stabile):**
`cd v1.1.0/`

**3. Copia i file nella directory di Apache:**
`sudo cp -r MaGiaDomo /var/www/html/`

**4. Configura Apache:**
- Assicurati che il modulo PHP sia abilitato
- Imposta i permessi corretti per le cartelle

**5. Avvia/riavvia Apache:**
`sudo systemctl restart apache2`

**6. Apri il browser su:**
`http://localhost/MaGiaDomo`

## ⚙️ Configurazione

Il progetto è preconfigurato per funzionare senza file di configurazione. Tutti i dispositivi Shelly vengono rilevati automaticamente tramite broadcast sulla rete locale. Per i condizionatori Daikin bisogna inserire lo IP della sua interfaccia.

### Porte utilizzate
- **Apache**: 80 (default)

## 🐛 Segnalazione bug

Se trovi un bug, apri un [Issue](https://github.com/MaGiaTech/MaGiaDomo/issues) con:
- Descrizione del problema
- Passi per riprodurlo
- Comportamento atteso vs reale
- Screenshot (se utile)

## 🤝 Contributi

I contributi sono benvenuti! Leggi il [CONTRIBUTING.md](CONTRIBUTING.md) per le linee guida.

## 📝 Licenza

Questo progetto è distribuito sotto licenza **MIT con obbligo di attribuzione**.
Vedi il file [LICENSE](LICENSE) per i dettagli.

## ⚠️ Avviso di non responsabilità

Questo software è fornito "così com'è" senza garanzie.
Leggi il [DISCLAIMER.md](DISCLAIMER.md) per maggiori informazioni.

## 👤 Autore

**MaGiaTech - Massimo GIAMMATTEI**
- GitHub: [https://github.com/MaGiaTech](https://github.com/MaGiaTech)

---

## ⭐ Supporto

Se questo progetto ti è stato utile, metti una stella su GitHub!
Se hai domande o suggerimenti, apri un Issue o contattami.

---

*Ultimo aggiornamento: 2026*