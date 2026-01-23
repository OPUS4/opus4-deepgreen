# DeepGreen Client for OPUS 4

Der Client erlaubt das Abholen von Dokumenten von DeepGreen für eine
konfigurierte Institution, dem Betreiber der OPUS 4 Instanz.

Die Library kann integriert in OPUS 4 (ab 4.9) oder auch unabhängig 
verwendet werden. 

## Konfiguration

Die Verbindungsdaten zum DeepGreen-Server müssen in `deepgreen.ini` abgelegt werden.

    deepgreen.serviceUrl =
    deepgreen.repositoryId =
    deepgreen.apiKey = 

Für die Verwendung mit einer OPUS 4 Instanz, liegt die Konfigurationsdatei in `application/configs`.

    deepgreen.configFile = APPLICATION_PATH'/application/configs/deepgreen.ini'

Für Tests oder die Standalone-Nutzung wird der Pfad zur Konfigurationsdatei in `test/test.ini` festgelegt.

    deepgreen.configFile = APPLICATION_PATH'/deepgreen.ini'

Die Konfiguration kann mit Hilfe des `config`-Kommandos erzeugt und getestet werden.

    $ bin/deepgreen config

## Import

Beim Import werden die ID der Notification und ihr `created_date` in Enrichments gespeichert.

    deepgreen.notificationId
    deepgreen.timestamp

Notifications, die bereits importiert wurden, werden bei einem wiederholten Import ignoriert.

Notifications, die älter sind als ein Dokument mit derselben DOI, werden ignoriert.

Notifications, bei denen ein Dokument mit derselben DOI existiert, werden momentan nicht importiert. 

Die Dokumente werden im FilesAndJats-Format von DeepGreen heruntergeladen.

> [!WARNING]
> FilesAndJats-Pakete mit Dateien, deren MIME-Type in OPUS 4, nicht erlaubt ist (Konfiguration), werden nicht importiert. 

### Umgang mit Updates

> [!WARNING]
> Momentan werden Notifications mit DOI Konflikten vom DeepGreen Client einfach nicht importiert. Über die SWORD 
> Schnittstelle werden aktuell doppelte Dokumente in OPUS 4 angelegt, die dann manuell verglichen und bereinigt werden 
> müssen. Für den Übergang, bis neue Tools verfügbar sind, muss sich der Client evtl. genauso verhalten.

In Zukunft sollten solche Dokumente in eine "Inbox" verschoben werden, um dann mit geeigneten Tools die relevanten
Änderungen als Update für das existierende Dokument übernehmen zu können. Wenn Regeln für automatische Updates 
definierbar sind, kann auch das umgesetzt werden. Momentan existieren beide Möglichkeiten noch nicht.

## Kommandos

Die verfügbaren Kommandos und Optionen werden angezeigt, wenn das DeepGreen-Tool ohne Kommando aufgerufen wird.

    $ bin/deepgreen

### Config

Abfrage der Verbindungsdaten und Erzeugung der Konfigurationsdatei.

### Check

Download von DeepGreen Notifications.

### Download

Download von Dokumenten, in den verfügbaren Formaten, z.B. FilesAndJats.

### Import

Das Import-Kommando ist nur im Kontext der OPUS 4 Application verfügbar. Mit `--since` kann das Startdatum für den 
Import angegeben werden. Ohne die Option werden die letzten dreißig Tage importiert.

    $ bin/opus4 deepgreen:import
    $ bin/opus4 deepgreen:import --since 2025-12-01
