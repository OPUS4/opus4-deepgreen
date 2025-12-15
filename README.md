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

Das Import-Kommando ist nur im Kontext der OPUS 4 Application verfügbar.

