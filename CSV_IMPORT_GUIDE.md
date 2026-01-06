# Import CSV Pracovníkov - Dokumentácia

## Popis Funkcie

Nový import CSV pre pracovníkov umožňuje:

1. **Automatické doplnenie údajov** - Ak pracovník v systéme už existuje, import doplní iba klapku (telefón) a mobil
2. **Detekcia konfliktov** - Ak sa existujúca klapka nezmutuje s CSV, import upozorní na konflikt a nebude zmenu aplikovať
3. **Priame vkladanie nových** - Nové pracovníkov sa vytvoria podľa údajov z CSV
4. **Detailný report** - Na konci importu sa zobrazí podrobný súhrn všetkých zmien a doplnení

## CSV Format

Import akceptuje **dva formáty CSV súborov**:

### Nový formát (odporúčaný)
```
Pracovník;Telefón;Mobil
"Ján Novo";"1234";"0911123456"
"Mária Oldová";"5678";"0912234567"
```

### Starý formát (podporovaný)
```
Meno a priezvisko;Klapka;Mobil
"Peter Existujúci";"9999";"0914456789"
"Jana Stara";"8888";"0915567890"
```

### Požiadavky na CSV:
- **Oddeľovač**: Bodkočiarka (`;`)
- **Citácia textu**: Úvodzovky (`"`)
- **Povinné stĺpce**: Pracovník/Meno a priezvisko, Telefón/Klapka
- **Voliteľné stĺpce**: Mobil
- **Kódovanie**: UTF-8 (najlepšie)

## Logika Importu

### Scénár 1: Nový pracovník
```
CSV: "Peter Test", "1234", "0914456789"
Výsledok: ✅ Vytvorený nový pracovník s klapkou 1234 a mobilom 0914456789
```

### Scénár 2: Existujúci pracovník - doplnenie údajov
```
Systém: "Peter Existujúci", "", ""
CSV: "Peter Existujúci", "5678", "0915567890"
Výsledok: ✅ Doplnená klapka na 5678, mobil na 0915567890
```

### Scénár 3: Existujúci pracovník - konflikt klapky
```
Systém: "Peter Existujúci", "1111", ""
CSV: "Peter Existujúci", "2222", "0915567890"
Výsledok: ⚠️ KONFLIKT - Systém má klapku 1111, CSV má 2222
         Záznam sa NEIMPORTUJE - bezpečnosť na prvom mieste
         Riešenie: Upravte CSV na správnu klapku a skúste znova
```

### Scénár 4: Existujúci pracovník - bez zmien
```
Systém: "Peter Existujúci", "1234", "0914456789"
CSV: "Peter Existujúci", "1234", "0914456789"
Výsledok: ℹ️ Bez zmien - údaje sú už aktuálne
```

## Ako Importovať

1. Otvorte stránku **Pracovníci** v admin paneli
2. Kliknite tlačidlo **"Importovať z CSV"**
3. Vyberte CSV súbor s pracovníkmi
4. Kliknite **OK** v dialógu výberu súboru
5. Import sa spustí a zobrazí sa report s výsledkami

## Výsledný Report

Po importe sa otvorí nové okno s detailným reportom:

### Struktura Reportu:

**📊 Zhrnutie**
- Počet nových pracovníkov
- Počet aktualizovaných pracovníkov
- Počet konfliktov (ak sú)

**✅ Súhrn zmien a doplnení**
- Detailný zoznam všetkých akcií (vytvorenie, doplnenie)
- Konkrétne zmeny u každého pracovníka

**⚠️ Konflikty (ak sú)**
- Zoznam pracovníkov s rozdielnou klapkou
- Existujúca hodnota
- Hodnota v CSV
- Riešenie: Upravte CSV a skúste znova

**❌ Chyby (ak sú)**
- Validačné chyby (prázdne povinné polia, duplicitné klapky, atď.)
- Chyby pri spracovaní

## Príklady Použitia

### Príklad 1: Import nových pracovníkov
Ak máte CSV s novými pracovníkmi a klapkami:
```
Pracovník;Telefón;Mobil
"Ján Varga";"1001";"0911123456"
"Mária Nováková";"1002";"0912234567"
```
✅ Import vytvorí obidva pracovníkov

### Príklad 2: Doplnenie mobilov existujúcim pracovníkom
```
Systém: "Ján Varga", "1001", "" (bez mobilu)
CSV: "Ján Varga", "1001", "0911123456"
```
✅ Import doplní mobil bez konfliktu

### Príklad 3: Chyba - konflikt klapky
```
Systém: "Ján Varga", "1001", ""
CSV: "Ján Varga", "2001", "0911123456" (INÁ KLAPKA!)
```
⚠️ Import preskočí tento záznam - konflikt!

## Bezpečnostné Opatrenia

1. **Detekcia konfliktov** - Preskripty zmeny v klapke bez potvrdenia
2. **Validácia** - Skontroluje povinné polia a formáty
3. **Deduplikácia** - Zabráni duplicitným klapkám
4. **Nonce bezpečnosť** - Protizáštita proti CSRF útokom
5. **Prepare statements** - Ochrana proti SQL injections

## Príklady CSV Súborov

V pracovnej zložke sú k dispozícii test súbory:
- `test_employees_new_format.csv` - Príklad v novom formáte
- `test_employees_old_format.csv` - Príklad v starom formáte

## Odhad Času Importu

- 10 pracovníkov: < 1 sekunda
- 100 pracovníkov: < 5 sekúnd
- 1000 pracovníkov: < 30 sekúnd

## Riešenie Problémov

### Chyba: "CSV súbor musí obsahovať stĺpec..."
- Skontrolujte názvy stĺpcov
- Musia byť presne "Pracovník" a "Telefón" (alebo "Meno a priezvisko" a "Klapka")

### Chyba: "Klapka X už existuje"
- Klapka je už priradená inému pracovníkovi
- Zmeňte klapku v CSV na jedinečnú

### Problém: Import nenačíta CSV
- Skontrolujte kódovanie súboru (UTF-8)
- Skontrolujte oddeľovač (bodkočiarka `;`)
- Otestujte s príkladným súborom

---

**Verzia**: 1.1.0  
**Dátum**: 2025-12-29
