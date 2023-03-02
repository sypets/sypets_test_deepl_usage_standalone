Script for reproducing problem with Translator::getUsage() POST
request with deeplcom/deepl PHP Composer package.

The script fails on some servers.

The script does not fail if either
1. Method is changed to GET (second command line argument)
2. or the commented out lines for setting Content-Type and CURLOPT_POSTFIELDS are uncommented

2023-03-02 Sybille Peters
