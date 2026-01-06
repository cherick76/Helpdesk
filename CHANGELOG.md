# CHANGELOG

## [1.1.0] - 2025-12-29

### Pridané
- **Nový import CSV pre pracovníkov s intelektným spracovaním:**
  - Automatické hľadanie existujúcich pracovníkov podľa mena a priezviska
  - Doplnenie klapky (telefónu) a mobilu existujúcim pracovníkom
  - Detekcia konfliktov - upozornenie ak sa existujúca klapka nezmutuje
  - Preskakovanie konfliktov bez overenia (bezpečnosť)
  - Podrobný súhrn zmien a doplnení na konci importu
  - Flexibilný parser - akceptuje oba formáty CSV:
    - Nový: "Pracovník", "Telefón", "Mobil"
    - Starý: "Meno a priezvisko", "Klapka", "Mobil"
  - Detailný report v novom okne s kategorizáciou (konflikty, zmeny, chyby)

### Vylepšené
- JavaScript interface pre import zobrazuje teraz detailný report
- CSV parser automaticky deteguje stĺpce (flexibilita formátov)

## [1.0.0] - 2025-12-17

### Pridané
- Kompletnú modulárnu architektúru pluginu
- Modul Pracovníci s CRUD operáciami
  - Spravovanie pracovníkov s klapkami
  - Validácia vstupov
  - Priradenie k projektom (M:N vzťah)
- Modul Projekty s CRUD operáciami
  - Spravovanie projektov
  - Priradenie pracovníkov
  - PM manažér
  - Hlavný pracovník projektu
  - Validácia formátov
- Modul Chyby ako placeholder
  - Základná CRUD funkčnosť
  - Stavy: nový, rozpracovaný, vyriešený, zatvorený
  - Príprava na budúce rozšírenia
- Admin Panel s intuitívnym rozhraním
  - Dashboard s prehliadom
  - Modálne formuláre
  - Tabuľkový zoznam záznamov
  - Akcie: Pridať, Upraviť, Zmazať
- Databázové tabuľky
  - hd_pracovnici
  - hd_projekty
  - hd_projekt_pracovnik (M:N vzťah)
  - hd_chyby
- AJAX implementácia
  - Asynchronné CRUD operácie
  - Nonce bezpečnosť
  - JSON odpovede
- Bezpečnosť
  - Capability checks
  - Nonce verification
  - Input sanitization a validation
  - Prepared statements
- i18n podpora
  - .pot súbor s prekladmi
  - Slovenčina as predvolený jazyk
  - Text domain: helpdesk
- Nastavenia pluginu
  - Modulárna aktivácia/deaktivizácia
  - Modulárne menu

### Technológie
- WordPress 5.0+ compatible
- PHP 7.2+ compatible
- MySQL 5.7+ compatible
- AJAX s jQuery
- WP REST ready (príprava)

### Súbory
- 30+ PHP súborov
- CSS styly pre admin
- JavaScript pre admin interakciu
- Prekladový .pot súbor
- Dokumentácia a README

## Budúce Verzie

### Plánované na v1.1
- REST API endpointy
- Frontend read-only výpis
- Export do CSV/Excel
- Historiku zmien
- Upozornenia a notifikácie

### Plánované na v1.2
- Import z CSV
- Automatizácia
- Reporty a analytika
- Propojenie s incidentami

### Plánované na v2.0
- Ticketing systém
- SLA tracking
- Time tracking
- Integrácia s externým systemom
