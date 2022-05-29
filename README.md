#Domišljijski slovarček: backend

Server-side koda za domišljijski slovarček:

* stran: https://domišljijski-slovarček.tamius.net/
* github: https://github.com/tamius-han/domisljijski-slovar

# Predstavitev

Uporablja se:

* PHP
* SQL (mariaDB)

Kar je problem, ker:

* Tamius ni lih velik privrženec PHPja

Tako da pričakujte špaget. Bi imel rajši node+typescript, ampak domenca folga samo PHP.

# Postavitev za razvoj in testiranje

* Daš kodo na en strežnik, ki zmore PHP
* Uvoziš podatke iz `.sql` datoteke v `sql/` mapi projekta.

Podatki niso komplet, ampak so samo manjši subset, ker sem SQL bazo izvozil _preden_ sem prenesel vse podatke iz stare. 

* Ko imaš podatke uvožene, se dodaš v tabelo `users`. Rabiš **gmail naslov**, nickname ni obvezen, `canManageTranslations` in `canManageUsers` se nastavita na 1.
* Kopiraš `src/conf/php-vars.example.php` v `src/conf/php-vars.php` 
* Kopiraš `src/conf/db-config.example.php` v `src/conf/db-config.php`
* Dobiš potrebne stvari za google OAUTH in jih dodaš v `src/conf/php-vars.php`
* Nastaviš spremenljivke v `src/conf/db-config.php`

# Dodajanje prevoda

Idealno se najprej preveri, če prevod za besedo v slovarčku že obstaja. Če prevoda ni (oz. če je nezadosten), potem se lahko stvari začne urejat.

## Angleška beseda ne obstaja
0.  Na frontendu nakucaš angleško besedo, slovensko besedo, ter pomena za vsako besedo. Pomen angleške besede naj bo v angleščini, slovenske v slovenščini.
1.  Kliče se POST `/words` za obe besedi, vsaka posebej. Nazaj se dobi `word` objekt, ki vsebuje ID.
2.  Kliče se POST `/meaning`, Angleškemu pomenu se zraven vtakne ID angleške besede, slovenskemu pomenu se pritakne ID slovenske besede. V arrayu (`wordIds`). (V obe zahtevi se doda tudi IDje kategorij, v arrayu, torej `categoryIds`). Nazaj se dobi ID pomena.
3.  Kliče se POST `/translation`, ki vsebuje `meaning_en` in `meaning_sl`

## Angleška (ali slovenska) beseda obstaja, ampak nima želenega pomena

0. Na frontendu nakucaš 2x pomen (angleški, slovenski), ter besedo (če ta še ne obstaja)
1. Če manjka beseda se kliče POST `/words`, da se doda beseda. Dobiš nazaj ID.
1. Kliče se POST `/meaning`. Zraven pomena v angleščini se pritakne ID angleške besede, zraven pomena v slovenščini se pritakne ID slovenske besede. Kot prej, v arrayu. Kot prej se tudi zdaj pritakne categoryIds. Nazaj se dobi ID pomena.

## Prevod obstaja, ampak imam alternativno besedo

0. Dobiš obstoječi prevod. Iz njega pobereš ID od pomenov.
1. POST `/words` z novo besedo
2. UPDATE `/meaning`, kjer se na seznam wordId-jev obstoječega pomena doda ID nove besede
