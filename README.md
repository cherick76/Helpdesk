# HelpDesk Plugin

Modulárny WordPress plugin pre správu číselníkov pracovníkov, projektov a chýb v helpdesk prostredí.

## Funkcie

### Modul Pracovníci
- CRUD operácie pre pracovníkov
- Správa klapiek, mobilných čísel a poznámok
- Priradenie k projektom (M:N vzťah)
- Validácia vstupov
- i18n podpora

### Modul Projekty
- CRUD operácie pre projekty
- Správa zákazníckeho čísla, nazvu projektu a ďalších údajov
- Priradenie pracovníkov (M:N vzťah)
- Hlavný pracovník projektu
- PM manažér
- Validácia formátov polí
- i18n podpora

### Modul Chyby
- CRUD operácie pre chyby (placeholder)
- Stavy: nový, rozpracovaný, vyriešený, zatvorený
- Prepojenie s projektami a pracovníkmi
- Prípraveni pre budúce rozšírenia
- i18n podpora

## Požiadavky

- WordPress 5.0+
- PHP 7.2+
- MySQL 5.7+

## Inštalácia

1. Skopírujte zložku `helpdesk` do `/wp-content/plugins/`
2. Aktivujte plugin z WordPress administrácie
3. Prejdite na HelpDesk → Nastavenia na konfigurácii modulov

## Architektúra

### Štruktúra Pluginu

```
helpdesk/
├── helpdesk.php                 # Hlavný súbor pluginu
├── includes/
│   ├── class-helpdesk.php       # Hlavná trieda pluginu
│   ├── admin/
│   │   └── class-admin.php      # Admin rozhraní
│   ├── models/
│   │   ├── class-base-model.php # Základná Model trieda
│   │   ├── class-employee.php   # Employee model
│   │   ├── class-project.php    # Project model
│   │   └── class-bug.php        # Bug model
│   ├── modules/
│   │   ├── class-base-module.php        # Základná Module trieda
│   │   ├── employees/
│   │   │   └── class-employees-module.php
│   │   ├── projects/
│   │   │   └── class-projects-module.php
│   │   └── bugs/
│   │       └── class-bugs-module.php
│   ├── utils/
│   │   ├── class-database.php   # Databázové tabuľky a query builder
│   │   └── class-validator.php  # Validácia a sanitizácia
│   └── views/
│       ├── admin-dashboard.php
│       ├── admin-employees.php
│       ├── admin-projects.php
│       ├── admin-bugs.php
│       └── admin-settings.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
└── languages/
    └── helpdesk.pot
```

### Databázové Tabuľky

#### hd_pracovnici
Tabuľka pracovníkov s polami:
- id (PRIMARY KEY)
- meno_priezvisko (VARCHAR 255)
- klapka (CHAR 4, UNIQUE)
- mobil (VARCHAR 20)
- poznamka (LONGTEXT)
- created_at, updated_at

#### hd_projekty
Tabuľka projektov s polami:
- id (PRIMARY KEY)
- zakaznicke_cislo (CHAR 4)
- servisna_sluzba (VARCHAR 255)
- nazov (VARCHAR 255)
- podnazov (VARCHAR 255)
- projektove_cislo (VARCHAR 50, UNIQUE)
- sla (VARCHAR 50)
- servisny_kontrakt (VARCHAR 255)
- zakaznik (VARCHAR 255)
- pm_manazer_id (BIGINT UNSIGNED, FOREIGN KEY)
- created_at, updated_at

#### hd_projekt_pracovnik
M:N väzbová tabuľka s polami:
- id (PRIMARY KEY)
- projekt_id (BIGINT UNSIGNED, FOREIGN KEY)
- pracovnik_id (BIGINT UNSIGNED, FOREIGN KEY)
- is_hlavny (BOOLEAN)
- created_at

#### hd_riesenia
Tabuľka chýb s polami:
- id (PRIMARY KEY)
- nazov (VARCHAR 255)
- popis (LONGTEXT)
- stav (VARCHAR 50) - 'novy', 'rozpracovany', 'vyrieseny', 'zavrety'
- projekt_id (BIGINT UNSIGNED)
- pracovnik_id (BIGINT UNSIGNED)
- datum_zaznamu (DATETIME)
- created_at, updated_at

## Používanie

### Administrácia

1. **Pracovníci**: Spravujte zoznam pracovníkov s klapkami, mobilnými číslami a poznámkami
2. **Projekty**: Vytvárajte projekty, priradujte pracovníkov a nastavujte PM manažéra
3. **Chyby**: Evidujte a sledujte chyby spojené s projektami
4. **Nastavenia**: Aktivujte/Deaktivujte jednotlivé moduly

### AJAX Akcie

- `helpdesk_save_employee` - Uloženie/Aktualizácia pracovníka
- `helpdesk_delete_employee` - Zmazanie pracovníka
- `helpdesk_get_employee` - Načítanie pracovníka
- `helpdesk_get_employees` - Načítanie zoznamu pracovníkov
- `helpdesk_save_project` - Uloženie/Aktualizácia projektu
- `helpdesk_delete_project` - Zmazanie projektu
- `helpdesk_get_project` - Načítanie projektu
- `helpdesk_get_projects` - Načítanie zoznamu projektov
- `helpdesk_add_employee_to_project` - Priradenie pracovníka do projektu
- `helpdesk_remove_employee_from_project` - Odobranie pracovníka z projektu
- `helpdesk_save_bug` - Uloženie/Aktualizácia chyby
- `helpdesk_delete_bug` - Zmazanie chyby
- `helpdesk_get_bug` - Načítanie chyby
- `helpdesk_get_bugs` - Načítanie zoznamu chýb

## Bezpečnosť

- Všetky AJAX požiadavky sú chránené nonce
- Kontrola oprávnení pomocou `manage_helpdesk` capability
- Sanitizácia a validácia všetkých vstupov
- Použitie prepared statements pri databázových dopytoch

## Modulárna Architektúra

Plugin je navrhnutý na byť modulárny:

1. Každý modul (Pracovníci, Projekty, Chyby) je možné nezávisle aktivovať/deaktivovať
2. Moduly sa načítavajú iba ak sú aktívne
3. Každý modul má svoju vlastnú triedu zdediťujúcu BaseModule
4. AJAX akcie sú špecifické pre každý modul

## Budúce Rozšírenia

- REST API endpointy
- Frontend read-only výpis
- Import/Export dát
- Incidenty/Tickety
- Reporty a analytika
- Históriu zmien
- Upozornenia a notifikácie

## Licencia

GPL v2 nebo later

## Podpora

V prípade problémov, prosím kontaktujte vývojára.
