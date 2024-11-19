# OS2Forms Fasit

Modulet OS2Forms Fasit giver muligheden for at videresende et genereret
indsendelsesbilag til en borger i fagsystemet Fasit Schultz.

## Krav

For at kunne snakke sammen med Fasit skal kommunen og Fasit først indbyrdes
aftale hvilke certifikater der anvendes. Disse certifikater skal være OCES-3,
f.eks. FOCES-3, og skal bruges i pem- eller cer-format.

Dernæst oplyses det anvendte certifikats thumbprint eller public-key til Fasit,
som derefter aktiverer snitfladen. Se evt. 
[README#certificate](../README.md#certificate)
for hvordan et certifikats thumbprint kan findes gennem kommandolinjen.

## Konfiguration

Integrationen konfigureres under
**Indstillinger** > **OS2Forms Fasit** (/admin/os2forms_fasit/settings).
Her skal følgende sættes op:

* Fasit API base url
  * Basis url’en til Fasit. Denne specificeres af Fasit.
  * Eksempel: https://webservices.fasit.dk/
* Fasit API tenant
  * Fasit tenant. Denne specificeres af Fasit.
  * Eksempel: aarhus
* Fasit API version
  * Hvilken version af af API’et der skal bruges. Her er mulighederne ’v1’ eller ’v2’. Der bør altid bruges ’v2’.
  * Eksempel: v2
* Certificate
  * Her kan angives detaljer til et azure key vault hvori certifikatet ligges (Azure key vault) eller en sti direkte til certifikatet (Filsystem)
* Passphrase
  * Passphrase til certifikatet, hvis sådan et eksisterer.


Se evt. Fasit Scultz dokumentationen for flere detaljer på opbygningen af endpoint url’er.

Det er desuden muligt at teste om os2forms kan få fat i certifikatet på samme konfigurations-side. 

## Handler

For at videresende noget til Fasit skal der på formular niveau opsættes en ’Fasit’-handler.
Dette gøres på en formular under Indstillinger > Emails/Handlers > Add handler.
På denne konfigureres følgende:

* Document title
  * Dokumentets titel i Fasit
* Document description
  * Dokumentets beskrivelse i Fasit
* CPR element
  * Elementet der indeholdender det CPR-nummer der skal videresendes til i Fasit.
  * Her kan benyttes enten ’textfield’, ’os2forms_nemid_cpr’ eller ’os2forms_person_lookup’.
* Attachment element
  * Elementet der står for at oprette et OS2Forms attachment, altså et ’os2forms_attachment’-element.

Alle felter er obligatoriske.

Når der indsendes en formular bliver et ‘job’ sat i en kø.
Videresendelsen til Fasit sker først når dette job køres.
Se [README#queue](../README.md#queue) for flere detaljer.
