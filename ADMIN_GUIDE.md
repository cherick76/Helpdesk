# HelpDesk WordPress Plugin - Admin Guide

## Rýchly Náhľad

HelpDesk je modulárny WordPress plugin pre správu pracovníkov, projektov a chýb. Plugin je navrhnutý pre profesionálne helpdesk a servisné prostredie.

## Inštalácia

### Požiadavky
- WordPress 5.0 alebo vyššie
- PHP 7.2 alebo vyššie
- MySQL 5.7 alebo vyššie
- Aktívny WordPress admin účet

### Kroky Inštalácie

1. **Stiahnutie**
   - Stiahnutie zložky `helpdesk` z repozitára

2. **Umiestňovanie**
   - Skopírujte zložku do `/wp-content/plugins/`

3. **Aktivácia**
   - Prejdite na Plugins → Inštalované pluginy
   - Vyhľadajte "HelpDesk" 
   - Kliknite na "Aktivovať"

4. **Overenie**
   - V admin menu by ste mali vidieť nový "HelpDesk" prvok
   - Kliknite naň a mali by ste vidieť dashboard

## Admin Rozhranie

### Dashboard
- Prehliad všetkých modulov
- Krátke informácie o plugine
- Linky na jednotlivé moduly

### Menu Položky

#### HelpDesk
- Dashboard - Prehliad pluginu
- Pracovníci - Správa pracovníkov
- Projekty - Správa projektov
- Chyby - Správa chýb (ak aktívny)
- Nastavenia - Konfigurácia

## Práca s Modulmi

### Pracovníci (Employees)

#### Zobrazenie Zoznamu
- Prejdite na HelpDesk → Pracovníci
- Zoznam všetkých pracovníkov s údajmi:
  - ID
  - Meno a Priezvisko
  - Klapka
  - Mobil
  - Poznámka (skratka v tabuľke)

#### Pridanie Nového
1. Kliknite na "+ Pridať pracovníka"
2. Vyplňte Formulár:
   - **Meno a Priezvisko*** - Meno a priezvisko pracovníka
   - **Klapka*** - 4-miestne číslo (musí byť unikátne)
   - **Mobil** - Telefónne číslo (voliteľné)
   - **Poznámka** - Dodatočné informácie (voliteľné)
3. Kliknite "Uložiť"

#### Úprava
1. V tabuľke kliknite na tlačidlo "Upraviť"
2. Zmeňte požadované údaje
3. Kliknite "Uložiť"

#### Zmazanie
1. V tabuľke kliknite na tlačidlo "Zmazať"
2. Potvrďte mazanie
3. ⚠️ Pozor: Zmazanie je trvalé!

### Projekty (Projects)

#### Zobrazenie Zoznamu
- Prejdite na HelpDesk → Projekty
- Zoznam všetkých projektov s údajmi:
  - ID
  - Názov
  - Projektové Číslo
  - Zákazník

#### Pridanie Nového
1. Kliknite na "+ Pridať projekt"
2. Vyplňte Základné Informácie:
   - **Zákaznícke Číslo*** - 4-miestne číslo
   - **Projektové Číslo*** - Unikátny identifikátor (musí byť unikátne)
   - **Názov Projektu*** - Názov projektu
   - **Podnázov** - Dodatočný názov (voliteľné)
3. Vyplňte Detailné Informácie:
   - **Zákazník** - Názov zákazníka
   - **Servisná Služba** - Typ služby
   - **SLA** - Service Level Agreement
   - **Servisný Kontrakt** - Identifikátor kontraktu
4. Vyberte PM Manažéra (voliteľné)
5. Priraďte Pracovníkov:
   - Zaškrtnite checkbox vedľa pracovníka
   - Vyberte jedného ako "Hlavného"
6. Kliknite "Uložiť"

#### Úprava Projektu
1. Kliknite na "Upraviť"
2. Zmeňte požadované údaje
3. Aktualizujte zoznam pracovníkov ak treba
4. Kliknite "Uložiť"

#### Priradenie Pracovníkov
- Pri úprave projektu v sekcii "Pracovníci":
  - Zaškrtnite pracovníkov, ktorých chcete priradiť
  - Vyberte jedného ako "Hlavného" (voliteľné)
  - Jeden projekt môže mať jedného hlavného pracovníka

#### Zmazanie
1. Kliknite na "Zmazať"
2. Potvrďte mazanie
3. ⚠️ Projekt a všetky jeho väzby budú zmazané!

### Chyby (Bugs)

#### Zobrazenie Zoznamu
- Prejdite na HelpDesk → Chyby
- Zoznam chýb s údajmi:
  - ID
  - Názov
  - Projekt
  - Stav (farebný badge)
  - Dátum

#### Stavy Chýb
- **Nový** - Novo vytvorená chyba (modré)
- **Rozpracovaný** - V procese riešenia (žlté)
- **Vyriešený** - Chyba bola opravená (zelené)
- **Zatvorený** - Chyba uzavretá (sivé)

#### Pridanie Novej Chyby
1. Kliknite na "+ Pridať chybu"
2. Vyplňte Informácie:
   - **Názov Chyby*** - Krátky popis
   - **Popis** - Detailný popis problému
   - **Projekt** - Vyberte projekt (voliteľné)
   - **Pracovník** - Priraďte pracovníka (voliteľné)
   - **Stav** - Vyberte stav (default: Nový)
3. Kliknite "Uložiť"

#### Zmena Stavu
1. Kliknite na "Upraviť"
2. Zmeňte stav v dropdown menu
3. Kliknite "Uložiť"

#### Zmazanie
1. Kliknite na "Zmazať"
2. Potvrďte mazanie

## Nastavenia

### Modulárna Aktivácia

Prejdite na HelpDesk → Nastavenia

Zde môžete:
- **Aktivovať/Deaktivovať** jednotlivé moduly:
  - ☑ Pracovníci
  - ☑ Projekty
  - ☑ Chyby
- Po zmene kliknite "Uložiť zmeny"
- Menu sa automaticky aktualizuje

### Informácie
- Aktuálna verzia pluginu
- Popis pluginu

## Tipy a Triky

### Hľadanie a Filter
- V tabuľkách je dostupné hľadanie
- Stránkovanie pre väčšie zoznamy

### Validácia
- Povinné polia sú označené *
- Chyby sa zobrazujú pod poľom

### Bezpečnosť
- Všetky operácie vyžadujú potvrdenie
- Zmazanie vyžaduje dodatočné potvrdenie

### Keyboard Shortcuts
- **ESC** - Zatvorí modálne okno
- **Tab** - Navigácia medzi poľami

## Časté Problémy

### Problem 1: Plugin sa neobjaví v admin menu
**Riešenie**: 
- Overite, že ste admin
- Skúste deaktivovať a znova aktivovať plugin

### Problem 2: Chyba pri uložení
**Riešenie**:
- Overite, že všetky povinné polia sú vyplnené
- Pozrite si error zprávy pod poľami

### Problem 3: Pracovníci sa nepridávajú k projektom
**Riešenie**:
- Overite, že ste vybrali pracovníkov
- Skúste odfresh stránku

### Problem 4: Databázové tabuľky nie sú vytvorené
**Riešenie**:
- Deaktivujte a znova aktivujte plugin
- Overite, že máte MySQL prístup

## Zálohovanie

Odporúčame:
1. Pravidelne si zazálohujte databázu
2. Exportujte dáta pomocou štandardných nástrojov
3. Spravujte verzie pluginu

## Aktualizácia

Keď je dostupná nová verzia:
1. Stiahnutie novej verzie
2. Deaktivovanie starej verzie
3. Odstránenie starej verzie z `/wp-content/plugins/`
4. Inštalácia novej verzie
5. Aktivovanie novej verzie
6. Testovanie funkčnosti

## Oddinštalácia

1. Prejdite na Plugins → Inštalované pluginy
2. Vyhľadajte "HelpDesk"
3. Kliknite na "Deaktivovať"
4. Kliknite na "Zmazať"
5. ⚠️ Všetky dáta budú zmazané!

## Podpora

V prípade problémov:
- Skontaktujte vývojára
- Skúste resetovať plugin
- Overite kompatibilitu WordPress verzie

## Licencia

HelpDesk je distribuovaný pod GPL v2 licenciou.
